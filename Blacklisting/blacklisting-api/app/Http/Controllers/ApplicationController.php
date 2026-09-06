<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CaseApplication;
use App\Models\Profile;
use App\Models\BlacklistCase;
use App\Models\ChequeDetail;
use App\Services\WorkflowEngine;

class ApplicationController extends Controller
{
    protected $engine;

    public function __construct(WorkflowEngine $engine)
    {
        $this->engine = $engine;
    }

    // =========================================================
    // LODGE CASE — Create application with basic payee profile
    // =========================================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'applicant_name'        => 'required|string',
            'applicant_email'       => 'required|email',
            'applicant_contact'     => 'required|string',
            'entity_type'           => 'sometimes|in:individual,institutional',
        ]);

        try {
            DB::beginTransaction();
            $tenantId = auth()->user()->tenant_id ?? 'default_tenant';

            $applicant = Profile::create([
                'tenant_id'   => $tenantId,
                'name_english'=> $validated['applicant_name'],
                'type'        => 'applicant',
                'entity_type' => $validated['entity_type'] ?? 'individual',
                'contact_details' => [
                    'email' => $validated['applicant_email'],
                    'phone' => $validated['applicant_contact'],
                ],
            ]);

            $appNumber = 'APP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $application = CaseApplication::create([
                'application_number' => $appNumber,
                'tenant_id'          => $tenantId,
                'applicant_id'       => $applicant->id,
                'status'             => 'draft',
            ]);

            DB::commit();
            return response()->json(['success' => true, 'data' => $application], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // =========================================================
    // GET ALL APPLICATIONS
    // =========================================================
    public function index()
    {
        $applications = CaseApplication::with(['applicant', 'cases.target', 'cases.chequeDetails'])
            ->orderBy('id', 'desc')
            ->get();
        return response()->json(['success' => true, 'data' => $applications]);
    }

    // =========================================================
    // GET SINGLE APPLICATION (with full relations)
    // =========================================================
    public function show($id)
    {
        $application = CaseApplication::with([
            'applicant',
            'cases.target',
            'cases.chequeDetails.drawer',
        ])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $application]);
    }

    // =========================================================
    // UPDATE APPLICANT PROFILE (Full profile after basic lodge)
    // =========================================================
    public function updateProfile(Request $request, $id)
    {
        $application = CaseApplication::with('applicant')->findOrFail($id);
        $profile = $application->applicant;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $data = $request->all();
        unset($data['_token']);

        // Base validation
        $validated = $request->validate([
            'entity_type'  => 'required|in:individual,institutional',
            'name_english' => 'required|string',
        ]);

        try {
            $contactDetails = $profile->contact_details ?? [];

            // Extract phone & email
            if ($request->filled('contact_phone')) $contactDetails['phone'] = $request->input('contact_phone');
            if ($request->filled('contact_email')) $contactDetails['email'] = $request->input('contact_email');

            // Store extra fields (NID, reissue, family, authorized person, website, etc.) in metadata
            $extraFields = [
                'nid_number', 'reissue', 'reissue_date', 'pratilipi_type', 'reissue_reason',
                'grandfather_name_en', 'grandmother_name_en', 'father_name_en', 'mother_name_en',
                'spouse_name_en', 'daughter_name_en', 'son_name_en', 'father_in_law_en', 'mother_in_law_en',
                'registration_authority', 'pan_issue_date', 'pan_authority', 'pan_district', 'website',
                'perm_town_en', 'perm_street_en', 'temp_town_en', 'temp_street_en',
                'reg_town_en', 'reg_street_en', 'mail_town_en', 'mail_street_en',
                'authorized_person'
            ];

            foreach ($extraFields as $field) {
                if ($request->has($field)) {
                    $contactDetails[$field] = $request->input($field);
                }
            }

            $fillableKeys = (new Profile())->getFillable();
            $updateData = [];
            foreach ($data as $key => $value) {
                if (in_array($key, $fillableKeys)) {
                    $updateData[$key] = $value;
                }
            }
            $updateData['contact_details'] = $contactDetails;

            $profile->update($updateData);
            return response()->json(['success' => true, 'data' => $profile->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // =========================================================
    // ADD TARGET (Cheque = one Target per case)
    // =========================================================
    public function addTarget(Request $request, $id)
    {
        $application = CaseApplication::findOrFail($id);

        $request->validate([
            'drawer.entity_type'     => 'required|in:individual,institutional',
            'drawer.name_english'    => 'required|string',
            'cheque.account_number'  => 'required|string',
            'cheque.cheque_number'   => 'required|string',
            'cheque.cheque_date'     => 'required|date',
            'cheque.amount'          => 'required|numeric|min:1',
            'cheque.amount_in_words' => 'required|string',
            'cheque.return_reason'   => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $drawerInput = $request->input('drawer', []);
            $contactDetails = [];
            if (isset($drawerInput['contact_phone'])) {
                $contactDetails['phone'] = $drawerInput['contact_phone'];
                unset($drawerInput['contact_phone']);
            }
            if (isset($drawerInput['contact_email'])) {
                $contactDetails['email'] = $drawerInput['contact_email'];
                unset($drawerInput['contact_email']);
            }

            // Save extra drawer metadata
            foreach ($drawerInput as $k => $v) {
                if (!in_array($k, (new Profile())->getFillable())) {
                    $contactDetails[$k] = $v;
                }
            }

            $fillableKeys = (new Profile())->getFillable();
            $drawerData = [];
            foreach ($drawerInput as $k => $v) {
                if (in_array($k, $fillableKeys)) {
                    $drawerData[$k] = $v;
                }
            }

            $drawerData['contact_details'] = $contactDetails;
            $drawerData['tenant_id'] = $application->tenant_id;
            $drawerData['type'] = 'drawer';

            $drawer = Profile::create($drawerData);

            // The Target profile = the applicant (payee) of the application
            $target = $application->applicant;

            $caseNumber = 'CASE-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

            $case = BlacklistCase::create([
                'application_id'  => $application->id,
                'case_number'     => $caseNumber,
                'tenant_id'       => $application->tenant_id,
                'applicant_id'    => $application->applicant_id,
                'target_id'       => $target->id,
                'category'        => 'blacklisting',
                'total_liability' => $validated['cheque']['amount'],
                'status'          => 'draft',
                'notice_expires_at' => now()->addDays(45),
            ]);

            $chequeData = $validated['cheque'];
            ChequeDetail::create([
                'case_id'           => $case->id,
                'drawer_id'         => $drawer->id,
                'cheque_date'       => $chequeData['cheque_date'],
                'cheque_date_bs'    => $chequeData['cheque_date_bs'] ?? null,
                'dishonour_date_ad' => $chequeData['dishonour_date_ad'] ?? null,
                'dishonour_date_bs' => $chequeData['dishonour_date_bs'] ?? null,
                'amount'            => $chequeData['amount'],
                'amount_in_words'   => $chequeData['amount_in_words'],
                'cheque_number'     => $chequeData['cheque_number'],
                'payee_name'        => $target->name_english,
                'payer_name'        => $drawer->name_english,
                'account_number'    => $chequeData['account_number'],
                'presentment_dates' => $chequeData['presentment_dates'] ?? [],
                'return_reason'     => $chequeData['return_reason'],
                'other_reason'      => $chequeData['other_reason'] ?? null,
            ]);

            $this->engine->recordEvent($case, 'created', [], 'Target added to application. 45-day notice period initiated.');

            DB::commit();
            return response()->json(['success' => true, 'data' => $case->load(['target', 'chequeDetails.drawer'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}

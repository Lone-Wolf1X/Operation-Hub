<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlacklistCase;
use App\Services\WorkflowEngine;

class CaseController extends Controller
{
    protected $engine;

    public function __construct(WorkflowEngine $engine)
    {
        $this->engine = $engine;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'category' => 'required|string',
            'total_liability' => 'numeric',
            
            // Applicant Details
            'applicant_name_english' => 'required|string',
            'applicant_name_nepali' => 'nullable|string',
            'applicant_contact' => 'nullable|array',
            
            // Target Details
            'target_name_english' => 'required|string',
            'target_name_nepali' => 'nullable|string',
            'target_citizenship' => 'nullable|string',
            'target_contact' => 'nullable|array',
        ]);

        try {
            // Create or fetch applicant
            $applicant = \App\Models\Profile::firstOrCreate(
                [
                    'tenant_id' => $validated['tenant_id'],
                    'name_english' => $validated['applicant_name_english'],
                    'type' => 'applicant'
                ],
                [
                    'name_nepali' => $validated['applicant_name_nepali'] ?? null,
                    'contact_details' => $validated['applicant_contact'] ?? [],
                ]
            );

            // Create or fetch target
            $target = \App\Models\Profile::firstOrCreate(
                [
                    'tenant_id' => $validated['tenant_id'],
                    'citizenship_number' => $validated['target_citizenship'],
                    'type' => 'target'
                ],
                [
                    'name_english' => $validated['target_name_english'],
                    'name_nepali' => $validated['target_name_nepali'] ?? null,
                    'contact_details' => $validated['target_contact'] ?? [],
                ]
            );

            // Append foreign keys for WorkflowEngine
            $validated['applicant_id'] = $applicant->id;
            $validated['target_id'] = $target->id;

            $case = $this->engine->initializeCase($validated['tenant_id'], $validated);
            return response()->json(['success' => true, 'data' => $case], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function transition(Request $request, $id)
    {
        $validated = $request->validate([
            'target_stage' => 'required|string',
            'user_id' => 'required|integer',
            'payload' => 'nullable|array'
        ]);

        $case = BlacklistCase::findOrFail($id);

        try {
            $case = $this->engine->transition($case, $validated['target_stage'], $validated['user_id'], $validated['payload'] ?? []);
            return response()->json(['success' => true, 'data' => $case]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    
    public function events($id)
    {
        $case = BlacklistCase::with('events')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $case->events]);
    }

    public function generateNoticeDocument(Request $request, $id, \App\Services\DocumentService $docService)
    {
        $case = BlacklistCase::findOrFail($id);
        
        $data = $request->validate([
            'date' => 'nullable|string',
            'time' => 'nullable|string',
            'ref_number' => 'nullable|string',
            'customer_address' => 'required|string',
            'branch_name' => 'required|string',
            'payee_name' => 'required|string',
            'amount' => 'required|numeric',
            'amount_words' => 'required|string',
            'cheque_number' => 'required|string',
            'presentation_date_1' => 'required|string',
            'presentation_date_2' => 'required|string',
            'application_date' => 'required|string',
        ]);
        
        // Use LaravelNepaliDate to auto-stamp if not provided
        if (empty($data['date'])) {
            try {
                $nepaliDate = \Anuzpandey\LaravelNepaliDate\LaravelNepaliDate::from(now())->toNepaliDate();
                // Convert to Nepali digits and replace hyphens with slashes
                $eng = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $nep = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
                $data['date'] = str_replace($eng, $nep, str_replace('-', '/', $nepaliDate));
                $data['eng_date'] = date('Y/m/d');
            } catch (\Exception $e) {
                $data['date'] = date('Y/m/d');
                $data['eng_date'] = date('Y/m/d');
            }
        } else {
            $data['eng_date'] = $request->input('eng_date', '');
        }
        
        if (empty($data['time'])) {
            $eng = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $nep = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
            $data['time'] = str_replace($eng, $nep, now()->format('h:i A'));
        }
        
        try {
            $filePath = $docService->generateNotice($case, $data);
            
            // Record this action in the immutable timeline
            $this->engine->recordEvent($case, 'document_generated', [
                'document_type' => '45_days_notice',
                'parameters_used' => $data
            ], '45 Days Notice document generated and downloaded.');

            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function generateDishonourDocument(Request $request, $id, \App\Services\DocumentService $docService)
    {
        $case = BlacklistCase::findOrFail($id);
        
        $data = $request->validate([
            'date' => 'nullable|string',
            'time' => 'nullable|string',
            'ref_number' => 'nullable|string',
            'payee_name' => 'required|string',
            'payee_address' => 'required|string',
            'branch_name' => 'required|string',
            'application_date' => 'required|string',
            'presentation_date_1' => 'required|string',
            'presentation_date_2' => 'required|string',
            'dishonour_reason' => 'nullable|string',
            'notice_date' => 'required|string',
            'cheque_number' => 'required|string',
            'amount' => 'required|string',
            'cheque_issue_date' => 'required|string',
        ]);
        
        // Use LaravelNepaliDate to auto-stamp if not provided
        if (empty($data['date'])) {
            try {
                $nepaliDate = \Anuzpandey\LaravelNepaliDate\LaravelNepaliDate::from(now())->toNepaliDate();
                $eng = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $nep = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
                $data['date'] = str_replace($eng, $nep, str_replace('-', '/', $nepaliDate));
                $data['eng_date'] = date('Y/m/d');
            } catch (\Exception $e) {
                $data['date'] = date('Y/m/d');
                $data['eng_date'] = date('Y/m/d');
            }
        } else {
            $data['eng_date'] = $request->input('eng_date', '');
        }
        
        if (empty($data['time'])) {
            $eng = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $nep = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
            $data['time'] = str_replace($eng, $nep, now()->format('h:i A'));
        }
        
        try {
            $filePath = $docService->generateDishonourCertificate($case, $data);
            
            $this->engine->recordEvent($case, 'document_generated', [
                'document_type' => 'dishonour_certificate',
                'parameters_used' => $data
            ], 'Cheque Dishonour Certificate generated and downloaded.');

            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function generateCoverNoteDocument(Request $request, $id, \App\Services\DocumentService $docService)
    {
        $case = BlacklistCase::findOrFail($id);
        
        $data = $request->validate([
            'ref_number' => 'nullable|string',
            'province_name' => 'required|string',
            'branch_name' => 'required|string',
            'payee_name' => 'required|string',
            'payee_application_date' => 'required|string',
            'cheque_number' => 'required|string',
            'amount' => 'required|numeric',
            'cheque_issue_date' => 'required|string',
            'cheque_return_date' => 'required|string',
            'dishonour_reason' => 'required|string',
            'dishonour_reason_extra' => 'nullable|string',
            'notice_date' => 'required|string',
            'postal_receipt_date' => 'nullable|string',
            'application_date' => 'required|string',
            'dishonour_certificate_date' => 'required|string',
            'cheque_stop_date' => 'nullable|string',
            'citizenship_number' => 'nullable|string',
            'citizenship_issue_details' => 'nullable|string',
            'fathers_name' => 'nullable|string',
            'grandfathers_name' => 'nullable|string',
            'company_citizenship_number' => 'nullable|string',
            'company_citizenship_details' => 'nullable|string',
            'company_fathers_name' => 'nullable|string',
            'company_grandfathers_name' => 'nullable|string',
            'pan_details' => 'nullable|string',
            'registration_details' => 'nullable|string',
            'transaction_id_date' => 'nullable|string',
            'charge_amount' => 'nullable|string',
            'charge_remarks' => 'nullable|string',
            'final_remarks' => 'nullable|string',
            'eng_date' => 'nullable|string',
        ]);
        
        if (empty($data['eng_date'])) {
            $data['eng_date'] = date('Y/m/d');
        }
        
        try {
            $filePath = $docService->generateCoverNote($case, $data);
            
            $this->engine->recordEvent($case, 'document_generated', [
                'document_type' => 'cover_note',
                'parameters_used' => $data
            ], 'Cover Note generated and downloaded.');

            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

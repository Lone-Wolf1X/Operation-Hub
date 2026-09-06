<?php

namespace App\Http\Controllers;

use App\Models\CibBlacklist;
use App\Models\ScreeningLog;
use Illuminate\Http\Request;

class ScreeningController extends Controller
{
    public function screen(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|in:individual,institutional',
        ]);

        $type = $request->entity_type;
        $query = CibBlacklist::query()->where('entity_type', $type);

        $hasCriteria = false;
        $searchTermLog = '';

        if ($type === 'individual') {
            if ($request->filled('citizenship_number')) {
                $query->where('citizenship_number', $request->citizenship_number);
                $hasCriteria = true;
                $searchTermLog .= 'CTZ: ' . $request->citizenship_number . ' ';
            }
            if ($request->filled('father_name')) {
                $query->where('father_name', 'LIKE', '%' . $request->father_name . '%');
                $hasCriteria = true;
                $searchTermLog .= 'Father: ' . $request->father_name . ' ';
            }
            if ($request->filled('date_of_birth')) {
                $query->whereDate('date_of_birth', $request->date_of_birth);
                $hasCriteria = true;
                $searchTermLog .= 'DOB: ' . $request->date_of_birth . ' ';
            }
            if ($request->filled('name')) {
                $query->where('name', 'LIKE', '%' . $request->name . '%');
                $hasCriteria = true;
                $searchTermLog .= 'Name: ' . $request->name . ' ';
            }
        } else {
            if ($request->filled('pan')) {
                $query->where('pan', $request->pan);
                $hasCriteria = true;
                $searchTermLog .= 'PAN: ' . $request->pan . ' ';
            }
            if ($request->filled('pan_issue_date')) {
                $query->whereDate('pan_issue_date', $request->pan_issue_date);
                $hasCriteria = true;
                $searchTermLog .= 'PAN Issue Date: ' . $request->pan_issue_date . ' ';
            }
            if ($request->filled('reg_date')) {
                $query->whereDate('reg_date', $request->reg_date);
                $hasCriteria = true;
                $searchTermLog .= 'Unit Reg Date: ' . $request->reg_date . ' ';
            }
            if ($request->filled('name')) {
                $query->where('name', 'LIKE', '%' . $request->name . '%');
                $hasCriteria = true;
                $searchTermLog .= 'Name: ' . $request->name . ' ';
            }
        }

        if (!$hasCriteria) {
            return response()->json([
                'is_blacklisted' => false,
                'matches' => []
            ]);
        }

        $matches = $query->get();
        $isMatchFound = $matches->isNotEmpty();

        ScreeningLog::create([
            'user_id' => $request->user()->id ?? 1,
            'search_type' => 'advanced_' . $type,
            'search_term' => trim($searchTermLog),
            'is_match_found' => $isMatchFound,
            'matched_profile_ids' => $isMatchFound ? $matches->pluck('id')->toArray() : null,
        ]);

        return response()->json([
            'is_blacklisted' => $isMatchFound,
            'matches' => $matches
        ]);
    }

    public function logs()
    {
        $logs = ScreeningLog::with('user')->latest()->paginate(20);
        return response()->json($logs);
    }
}

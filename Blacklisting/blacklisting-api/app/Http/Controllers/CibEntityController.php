<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\CibEntityImport;
use App\Models\CibBlacklist;
use App\Models\CibEntity;
use Maatwebsite\Excel\Facades\Excel;

class CibEntityController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:10240',
        ]);

        Excel::import(new CibEntityImport, $request->file('file'));

        return response()->json(['message' => 'CIB Data uploaded successfully']);
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        if (!$query) {
            return response()->json([]);
        }

        $results = CibEntity::where('name', 'LIKE', '%' . $query . '%')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return response()->json($results);
    }
    
    public function stats()
    {
        $totalIndividuals = CibBlacklist::where('entity_type', 'individual')->count();
        $totalUnits = CibBlacklist::where('entity_type', 'institutional')->count();
        // All records in cib_blacklists are currently blacklisted
        $blacklisted = CibBlacklist::count();
        // Released = records that no longer appear in the latest batch (future logic)
        $released = 0;

        return response()->json([
            'individuals' => $totalIndividuals,
            'units' => $totalUnits,
            'blacklisted' => $blacklisted,
            'released' => $released
        ]);
    }
}

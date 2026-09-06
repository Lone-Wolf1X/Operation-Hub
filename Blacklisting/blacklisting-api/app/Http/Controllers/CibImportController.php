<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CibBlacklistImport;
use App\Models\CibBlacklist;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CibImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'entity_type' => 'required|in:individual,institutional'
        ]);

        $batchId = Str::uuid()->toString();

        try {
            Excel::import(new CibBlacklistImport($request->entity_type, $batchId), $request->file('file'));

            // Release logic: Any entity of the same type NOT in this batch gets released (deleted or marked)
            // As per user: "if koi hamare bank se blacklist hoka jata hai toh uska proper data hamare pass rahega... CIB se release ho gaya toh hum release kar denge"
            $releasedCount = CibBlacklist::where('entity_type', $request->entity_type)
                ->where('upload_batch_id', '!=', $batchId)
                ->delete();

            return response()->json([
                'message' => 'Upload successful',
                'batch_id' => $batchId,
                'released_records' => $releasedCount,
                'upload_date' => Carbon::now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function latestUploadInfo()
    {
        $latest = CibBlacklist::latest('updated_at')->first();
        if ($latest) {
            return response()->json([
                'last_upload_date' => $latest->updated_at->format('Y-m-d H:i:s')
            ]);
        }
        
        return response()->json([
            'last_upload_date' => null
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEntryImport;
use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class ImportController extends Controller
{
    //
    public function importEntries(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.*' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data format.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->input('data');

        $response = ProcessEntryImport::dispatchSync($data);

        return response()->json([
            'response' => $response,
            'success' => true,
            'message' => 'Import started. You will be notified upon completion.',
        ]);
    }
}

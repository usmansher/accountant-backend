<?php

namespace App\Http\Controllers;

use App\Models\EntryType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EntryTypeController extends Controller
{
    // Fetch all entry types
    public function index()
    {
        return EntryType::all();
    }

    // Store a new entry type
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|unique:entrytypes,label',
            'name' => 'required|string|unique:entrytypes,name',
            'description' => 'required|string',
            'base_type' => 'integer',
            'numbering' => 'integer',
            'prefix' => 'string',
            'suffix' => 'string',
            'zero_padding' => 'integer',
            'restriction_bankcash' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $entryType = EntryType::create($request->all());

        return response()->json($entryType, 201);
    }

    // Show a specific entry type
    public function show($id)
    {
        return EntryType::findOrFail($id);
    }

    // Update a specific entry type
    public function update(Request $request, $id)
    {
        $entryType = EntryType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'label' => 'required|string|unique:entrytypes,label,' . $id,
            'name' => 'required|string|unique:entrytypes,name,' . $id,
            'description' => 'required|string',
            'base_type' => 'integer',
            'numbering' => 'integer',
            'prefix' => 'nullable|string',
            'suffix' => 'nullable|string',
            'zero_padding' => 'integer',
            'restriction_bankcash' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $entryType->update($request->all());

        return response()->json($entryType, 200);
    }

    // Delete a specific entry type
    public function destroy($id)
    {
        $entryType = EntryType::findOrFail($id);
        $entryType->delete();

        return response()->json(null, 204);
    }
}

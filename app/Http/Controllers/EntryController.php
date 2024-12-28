<?php

namespace App\Http\Controllers;

use App\Repositories\EntryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EntryController extends Controller
{
    protected $entryRepository;

    public function __construct(EntryRepository $entryRepository)
    {
        $this->entryRepository = $entryRepository;
    }

    /**
     * Display a listing of the entries.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Assuming you have a view for listing entries
        $entries = $this->entryRepository->getAllEntries();
        return response()->json($entries, 200);
    }

    public function show($id)
    {
        $entry = $this->entryRepository->show($id);

        if ($entry === false) {
            return response()->json(['message' => 'Entry not found'], 404);
        }

        return response()->json($entry, 200);
    }

    /**
     * Store a newly created entry in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $entry = $this->entryRepository->createEntry($request->all());

        if ($entry === false) {
            return response()->json(['message' => 'Failed to create entry'], 500);
        }

        return response()->json($entry, 201);
    }

    /**
     * Update the specified entry in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $entry = $this->entryRepository->updateEntry($id, $request->all());

        if ($entry === false) {
            return response()->json(['message' => 'Failed to update entry'], 500);
        }

        return response()->json($entry, 200);
    }

    /**
     * Remove the specified entry from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleted = $this->entryRepository->deleteEntry($id);

        if ($deleted === false) {
            return response()->json(['message' => 'Failed to delete entry'], 500);
        }

        return response()->json(['message' => 'Entry deleted successfully'], 200);
    }
}

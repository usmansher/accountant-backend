<?php

namespace App\Repositories;

use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\EntryType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EntryRepository
{


    public function getAllEntries()
    {
        return Entry::with('items')->get();
    }

    /**
     * Create a new entry along with its related entry items.
     *
     * @param array $data
     * @return Entry|bool
     */
    public function createEntry(array $data)
    {
        $validator = Validator::make($data, [
            'entrytype_id' => 'nullable|exists:entrytypes,id',
            'date' => 'required|date',
            'narration' => 'nullable|string',
            'tag_id' => 'nullable|exists:tags,id',
            'number' => 'nullable',
            'items.*.dc' => 'required|in:D,C',
            'items.*.ledger_id' => 'required|exists:ledgers,id',
            'items.*.amount' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }


        if (!isset($data['entrytype_id'])) {
            $data['entrytype_id'] = EntryType::firstOrFail()->id;
        }

        // do the cr_total and dr_tototal and make sure they are equal
        $entries = $data['items'];
        $dr_total = array_reduce($entries, function ($carry, $item) {
            if ($item['dc'] == 'D') {
                return $carry + $item['amount'];
            }
            return $carry;
        }, 0);

        $cr_total = array_reduce($entries, function ($carry, $item) {
            if ($item['dc'] == 'C') {
                return $carry + $item['amount'];
            }
            return $carry;
        }, 0);



        DB::beginTransaction();

        try {
            $entry = Entry::create([
                'entrytype_id' => $data['entrytype_id'],
                'date' => $data['date'],
                'narration' => $data['narration'] ?? null,
                'dr_total' => $dr_total,
                'number' => $data['number'],
                'tag_id' => $data['tag_id'],
                'cr_total' => $cr_total,
            ]);

            foreach ($data['items'] as $item) {
                EntryItem::create([
                    'entry_id' => $entry->id,
                    'dc' => $item['dc'],
                    'ledger_id' => $item['ledger_id'],
                    'narration' => $item['narration'] ?? null,
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            return $entry;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            Log::error('Failed to create entry: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing entry.
     *
     * @param int $id
     * @param array $data
     * @return Entry|bool
     */
    public function updateEntry($id, array $data)
    {
        $validator = Validator::make($data, [
            'entrytype_id' => 'nullable|exists:entrytypes,id',
            'date' => 'required|date',
            'narration' => 'nullable|string',
            'tag_id' => 'nullable|exists:tags,id',
            'number' => 'nullable',
            'items.*.dc' => 'required|in:D,C',
            'items.*.ledger_id' => 'required|exists:ledgers,id',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        DB::beginTransaction();

        if (!isset($data['entrytype_id'])) {
            $data['entrytype_id'] = EntryType::firstOrFail()->id;
        }

        // do the cr_total and dr_tototal and make sure they are equal
        $entries = $data['items'];
        $dr_total = array_reduce($entries, function ($carry, $item) {
            if ($item['dc'] == 'D') {
                return $carry + $item['amount'];
            }
            return $carry;
        }, 0);

        $cr_total = array_reduce($entries, function ($carry, $item) {
            if ($item['dc'] == 'C') {
                return $carry + $item['amount'];
            }
            return $carry;
        }, 0);


        try {
            $entry = Entry::findOrFail($id);
            $entry->update([
                'entrytype_id' => $data['entrytype_id'],
                'date' => $data['date'],
                'narration' => $data['narration'] ?? null,
                'dr_total' => $dr_total,
                'number' => $data['number'],
                'tag_id' => $data['tag_id'],
                'cr_total' => $cr_total,
            ]);

            // Delete old entry items and recreate
            EntryItem::where('entry_id', $entry->id)->delete();

            foreach ($data['items'] as $item) {
                EntryItem::create([
                    'entry_id' => $entry->id,
                    'dc' => $item['dc'],
                    'ledger_id' => $item['ledger_id'],
                    'narration' => $item['narration'] ?? null,
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            return $entry;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update entry: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an entry and its related items.
     *
     * @param int $id
     * @return bool
     */
    public function deleteEntry($id)
    {
        DB::beginTransaction();

        try {
            $entry = Entry::findOrFail($id);
            EntryItem::where('entry_id', $entry->id)->delete();
            $entry->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete entry: ' . $e->getMessage());
            return false;
        }
    }


    /* Show a specific entry */
    public function show($id)
    {
        return Entry::with('items.ledger', 'tag', 'entryType')->findOrFail($id);
    }
}

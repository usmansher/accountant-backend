<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Entry;
use App\Models\EntryItem;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;

class ProcessEntryImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entriesData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($entriesData)
    {
        $this->entriesData = $entriesData;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();

        try {
            foreach ($this->entriesData as $entryRow) {

                $entryType = \App\Models\EntryType::where('label', $entryRow['entry_type_id'])->first();
                if (!$entryType) {
                    throw new \Exception("Entry Type not found for label: {$entryRow['entry_type_id']}");
                }

                $tag = \App\Models\Tag::where('title', $entryRow['tag_id'])->first();

                // For each entry, gather fields from your new JSON structure:
                $entryData = [
                    'id' => Uuid::uuid4()->toString(),
                    'tag_id' =>  $tag ? $tag->id : null,
                    'entrytype_id' => $entryType->id,
                    'number' => $entryRow['entry_number'] ?? null,
                    'date' => $entryRow['entry_date'], // e.g. "2025-01-05"
                    'narration' => $entryRow['entry_narration'] ?? null,
                ];
                // Create the entry (no dr_total, cr_total in new JSON)
                $entry = Entry::create($entryData);

                $dr_total = 0;
                $cr_total = 0;
                // Now create the related items
                if (!empty($entryRow['items'])) {
                    foreach ($entryRow['items'] as $itemRow) {
                        $ledger = \App\Models\Ledger::where('code', $itemRow['ledger_code'])->first();
                        if (!$ledger) {
                            // Optionally skip or throw an exception if ledger not found
                            throw new \Exception("Ledger not found for code: {$itemRow['ledger_code']}");
                        }

                        $entryItemData = [
                            'id' => Uuid::uuid4()->toString(),
                            'entry_id' => $entry->id,
                            'ledger_id' => $ledger->id,
                            'amount' => $itemRow['amount'],
                            'narration' => $itemRow['item_narration'] ?? null,
                            'dc' => $itemRow['dc'],
                            'reconciliation_date' => $itemRow['item_reconciliation_date'] ?? null,
                        ];

                        $entryItemData['ledger_id'] = $ledger->id;
                        EntryItem::create($entryItemData);

                        if ($itemRow['dc'] == 'D') {
                            $dr_total += $itemRow['amount'];
                        } else {
                            $cr_total += $itemRow['amount'];
                        }
                    }
                }

                $entry->update([
                    'dr_total' => $dr_total,
                    'cr_total' => $cr_total,
                ]);
            }

            DB::commit();
            Log::info('Entries and Entry Items imported successfully via queue.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Queue Import Error: ' . $e->getMessage());
        }
    }
}

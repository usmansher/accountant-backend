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
                // Prepare entry data
                $entryData = [
                    'id' => $entryRow['id'] ?? Uuid::uuid4()->toString(),
                    'tag_id' => $entryRow['tag_id'] ?? null,
                    'entrytype_id' => $entryRow['entrytype_id'],
                    'number' => $entryRow['number'] ?? null,
                    'date' => $entryRow['date'],
                    'dr_total' => $entryRow['dr_total'] ?? 0.00,
                    'cr_total' => $entryRow['cr_total'] ?? 0.00,
                    'narration' => $entryRow['narration'] ?? null,
                    'created_at' => $entryRow['created_at'] ?? now(),
                    'updated_at' => $entryRow['updated_at'] ?? now(),
                ];

                // Create the entry
                $entry = Entry::create($entryData);

                // Handle entry_items
                foreach ($entryRow['entry_items'] as $itemRow) {
                    $entryItemData = [
                        'id' => $itemRow['id'] ?? Uuid::uuid4()->toString(),
                        'entry_id' => $entry->id,
                        'ledger_id' => $itemRow['ledger_id'],
                        'amount' => $itemRow['amount'],
                        'narration' => $itemRow['narration'] ?? null,
                        'dc' => $itemRow['dc'],
                        'reconciliation_date' => $itemRow['reconciliation_date'] ?? null,
                        'created_at' => $itemRow['created_at'] ?? now(),
                        'updated_at' => $itemRow['updated_at'] ?? now(),
                    ];

                    // Create the entry item
                    EntryItem::create($entryItemData);
                }
            }

            DB::commit();

            Log::info('Entries and Entry Items imported successfully via queue.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Queue Import Error: ' . $e->getMessage());
        }
    }
}

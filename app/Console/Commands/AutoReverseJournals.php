<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Sequence; // Utilizing your existing Sequence model
use App\Models\ActivityLog;
use Carbon\Carbon;

class AutoReverseJournals extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accounting:auto-reverse';

    /**
     * The console command description.
     */
    protected $description = 'Process journal entries scheduled for automatic reversal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        // 1. Find eligible entries
        // Must be: Posted, Not yet reversed, Auto-reverse date is today or in past
        $entriesToReverse = JournalEntry::where('status', 'posted')
            ->where('is_reversed', false)
            ->whereNotNull('auto_reverse_date')
            ->whereDate('auto_reverse_date', '<=', $today)
            ->with('lines') // Eager load lines to copy them
            ->get();

        $count = $entriesToReverse->count();
        if ($count === 0) {
            $this->info("No entries due for reversal found.");
            return;
        }

        $this->info("Found {$count} entries to reverse. Processing...");

        foreach ($entriesToReverse as $originalEntry) {
            DB::beginTransaction();

            try {
                // 2. Generate Reference for the Reversal
                // Assuming Sequence::getNextSequence takes (tenant_id, prefix)
                $newReference = Sequence::getNextSequence($originalEntry->tenant_id, 'JV');

                // 3. Create the Reversal Header
                $reversalEntry = new JournalEntry();
                $reversalEntry->tenant_id = $originalEntry->tenant_id;
                $reversalEntry->date = $originalEntry->auto_reverse_date; // The reversal happens ON the scheduled date
                $reversalEntry->reference = $newReference;
                $reversalEntry->description = "Auto-Reversal of " . $originalEntry->reference;
                $reversalEntry->status = 'posted'; // Auto-reversals are usually immediate
                $reversalEntry->tax_type = $originalEntry->tax_type;
                $reversalEntry->locked = true;
                $reversalEntry->created_by = null; // System generated
                $reversalEntry->save();

                // 4. Create Reversal Lines (Swap Debit/Credit)
                foreach ($originalEntry->lines as $line) {
                    $newLine = new JournalEntryLine();
                    $newLine->tenant_id = $line->tenant_id;
                    $newLine->journal_entry_id = $reversalEntry->id;
                    $newLine->account_id = $line->account_id;
                    
                    // SWAP LOGIC: Original Debit becomes Credit, Original Credit becomes Debit
                    $newLine->debit = $line->credit;
                    $newLine->credit = $line->debit;
                    
                    $newLine->description = "Reversal: " . ($line->description ?? 'System Entry');
                    $newLine->tax_code_id = $line->tax_code_id;
                    $newLine->tax_amount = $line->tax_amount; // Keeps tax association for reporting
                    $newLine->save();
                }

                // 5. Mark Original as Reversed
                $originalEntry->update(['is_reversed' => true]);

                // 6. Log Activity
                ActivityLog::create([
                    'tenant_id' => $originalEntry->tenant_id,
                    'user_id' => null, // System
                    'action' => 'auto-reversed',
                    'description' => "System generated reversal: {$newReference}",
                    'subject_type' => JournalEntry::class,
                    'subject_id' => $originalEntry->id,
                    'ip_address' => '127.0.0.1'
                ]);

                DB::commit();
                $this->info("Reversed {$originalEntry->reference} -> Created {$newReference}");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to reverse {$originalEntry->reference}: " . $e->getMessage());
                // Continue to next entry, don't stop the whole batch
            }
        }

        $this->info("Batch complete.");
    }
}
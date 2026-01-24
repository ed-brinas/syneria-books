<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring invoices that are due for creation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        
        $this->info("Starting recurring invoice processing for {$today->toDateString()}...");

        // 1. Query for invoices due for recurrence
        $invoices = Invoice::where('is_recurring', true)
            ->where('status', '<>', 'voided') // Don't generate from voided templates
            ->whereDate('next_recurrence_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('recurrence_end_date')
                      ->orWhereDate('recurrence_end_date', '>=', $today);
            })
            ->with('items') // Eager load items to clone
            ->get();

        $count = $invoices->count();
        $this->info("Found {$count} invoices due for recurrence.");

        foreach ($invoices as $parentInvoice) {
            try {
                DB::beginTransaction();

                $this->processInvoice($parentInvoice, $today);

                DB::commit();
                $this->info("Generated recurring invoice for Parent ID: {$parentInvoice->id}");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to process Parent ID {$parentInvoice->id}: " . $e->getMessage());
                // Continue to next invoice even if one fails
            }
        }

        $this->info('Recurring invoice processing completed.');
    }

    private function processInvoice(Invoice $parent, Carbon $processDate)
    {
        // A. Calculate Dates
        $newDate = $parent->next_recurrence_date; // Should be today or past
        $newDueDate = $this->calculateDueDate($newDate, $parent->payment_terms);

        // B. Replicate Parent Invoice
        // We exclude specific fields that shouldn't be copied
        $newInvoice = $parent->replicate([
            'id', 
            'number', 
            'status', 
            'created_at', 
            'updated_at', 
            'is_recurring', 
            'recurrence_interval', 
            'recurrence_type', 
            'recurrence_end_date', 
            'last_recurrence_date', 
            'next_recurrence_date'
        ]);

        // C. Set New Attributes
        $newInvoice->date = $newDate;
        $newInvoice->due_date = $newDueDate;
        $newInvoice->status = 'draft'; // Always create as draft for review
        $newInvoice->reference = $parent->reference ? "{$parent->reference} (Recurring)" : null;
        $newInvoice->is_recurring = false; // The child is not recurring
        
        // Save to generate ID (handled by model boot)
        $newInvoice->save();

        // D. Replicate Items
        foreach ($parent->items as $item) {
            $newItem = $item->replicate(['id', 'invoice_id', 'created_at', 'updated_at']);
            $newItem->invoice_id = $newInvoice->id;
            $newItem->save();
        }

        // E. Update Parent Schedule
        $parent->last_recurrence_date = $newDate;
        
        // Calculate next date
        $nextDate = Carbon::parse($newDate);
        if ($parent->recurrence_type === 'weeks') {
            $nextDate->addWeeks($parent->recurrence_interval);
        } else {
            $nextDate->addMonths($parent->recurrence_interval);
        }

        // If next date is after end date, stop (set null)
        if ($parent->recurrence_end_date && $nextDate->gt($parent->recurrence_end_date)) {
            $parent->next_recurrence_date = null;
            $parent->is_recurring = false; // Optional: Auto-disable
        } else {
            $parent->next_recurrence_date = $nextDate;
        }

        $parent->save();
    }

    private function calculateDueDate($date, $terms)
    {
        $baseDate = Carbon::parse($date);

        return match ($terms) {
            'Net 30' => $baseDate->addDays(30),
            'Net 60' => $baseDate->addDays(60),
            'Net 90' => $baseDate->addDays(90),
            default => $baseDate, // 'Due on Receipt' or unknown
        };
    }
}
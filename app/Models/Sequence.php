<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Generate the next sequence number for a specific type and branch.
     * Must be called within a Transaction to ensure atomicity.
     *
     * @param string $tenantId
     * @param string $branchCode (e.g., '000', '001')
     * @param string $type (e.g., 'JV', 'INV')
     * @return string
     */
    public static function getNextSequence(string $tenantId, string $branchCode, string $type): string
    {
        // Strategy: We use a composite type key (e.g., 'JV-000') to maintain 
        // separate counters for each branch within the existing 'sequences' table structure.
        // This ensures Head Office (000) and Cebu (001) have their own independent series 
        // (JV-000-2025-00001 vs JV-001-2025-00001).
        
        $compositeType = "{$type}-{$branchCode}";

        // 1. Lock the sequence row for update to prevent race conditions
        $sequence = static::where('tenant_id', $tenantId)
            ->where('type', $compositeType)
            ->lockForUpdate()
            ->first();

        // 2. Initialize if doesn't exist
        if (!$sequence) {
            $sequence = static::create([
                'tenant_id' => $tenantId,
                'type' => $compositeType,
                'current_value' => 0,
            ]);
            
            // Re-fetch with lock to ensure strict consistency in high concurrency
            $sequence = static::where('id', $sequence->id)->lockForUpdate()->first();
        }

        // 3. Increment
        $sequence->current_value++;
        $sequence->save();

        // 4. Format: {Type}-{BranchCode}-{Year}-{Seq}
        // Example: JV-000-2024-000001
        $year = date('Y');
        $padded = str_pad($sequence->current_value, 6, '0', STR_PAD_LEFT);
        
        return "{$type}-{$branchCode}-{$year}-{$padded}";
    }
}
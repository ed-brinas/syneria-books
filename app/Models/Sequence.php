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
     * Generate the next sequence number for a specific type.
     * Must be called within a Transaction to ensure atomicity.
     *
     * @param string $tenantId
     * @param string $type (e.g., 'JV', 'INV')
     * @return string
     */
    public static function getNextSequence(string $tenantId, string $type): string
    {
        // 1. Lock the sequence row for update to prevent race conditions
        $sequence = static::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->lockForUpdate()
            ->first();

        // 2. Initialize if doesn't exist
        if (!$sequence) {
            $sequence = static::create([
                'tenant_id' => $tenantId,
                'type' => $type,
                'current_value' => 0,
            ]);
            
            // Re-fetch with lock to ensure strict consistency in high concurrency
            $sequence = static::where('id', $sequence->id)->lockForUpdate()->first();
        }

        // 3. Increment
        $sequence->current_value++;
        $sequence->save();

        // 4. Format: JV-{Year}-{000001}
        $year = date('Y');
        $padded = str_pad($sequence->current_value, 6, '0', STR_PAD_LEFT);
        
        return "{$type}-{$year}-{$padded}";
    }
}
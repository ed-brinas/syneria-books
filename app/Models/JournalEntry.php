<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'date',
        'auto_reverse_date',
        'tax_type',        
        'reference',
        'description',
        'status',
        'locked',
        'is_reversed',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'auto_reverse_date' => 'date',
        'locked' => 'boolean',
        'is_reversed' => 'boolean',
    ];

    /**
     * The lines associated with the journal entry.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * The user who created the entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate total debits.
     */
    public function getTotalDebitAttribute()
    {
        return $this->lines()->sum('debit');
    }

    /**
     * Calculate total credits.
     */
    public function getTotalCreditAttribute()
    {
        return $this->lines()->sum('credit');
    }

    /**
     * Check if entry is balanced.
     */
    public function isBalanced(): bool
    {
        // Using epsilon comparison for floating point math safety
        return abs($this->total_debit - $this->total_credit) < 0.0001;
    }
}
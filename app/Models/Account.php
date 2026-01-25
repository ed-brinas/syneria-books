<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Account extends Model
{
    use BelongsToTenant, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'type',
        'subtype',
        'description',
        'is_active',
        'is_system'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get the journal entry lines associated with this account.
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }
    
    /**
     * Get the bank account associated with this account (if any).
     */
    public function bankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class);
    }

    /**
     * Scope to get only active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
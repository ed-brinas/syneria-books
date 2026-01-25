<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'currency',
        'branch_code',
        'swift_code',
        'tenant_id',
        'address',
        'account_id',
        'is_active'
    ];

    /**
     * Encrypt sensitive financial data at rest.
     */
    protected $casts = [
        'account_name' => 'encrypted',
        'account_number' => 'encrypted',
        'branch_code' => 'encrypted',
        'swift_code' => 'encrypted',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the linked Chart of Accounts entry.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope a query to only include active bank accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
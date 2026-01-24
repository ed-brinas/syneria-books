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
        'address'
    ];

    /**
     * Encrypt sensitive financial data at rest.
     */
    protected $casts = [
        'account_name' => 'encrypted',
        'account_number' => 'encrypted',
        'branch_code' => 'encrypted',
        'swift_code' => 'encrypted',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
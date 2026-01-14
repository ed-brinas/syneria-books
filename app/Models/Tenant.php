<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_name',
        'trade_name',
        'company_reg_number',
        'tax_identification_number',
        'business_address',
        'city',
        'postal_code',
        'business_type',
        'country',
        'domain',
        'subscription_plan',
        'subscription_expires_at',
    ];

    protected $casts = [
        'subscription_expires_at' => 'date',
        'company_name' => 'encrypted',
        'trade_name' => 'encrypted',
        'company_reg_number' => 'encrypted',
        'tax_identification_number' => 'encrypted',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
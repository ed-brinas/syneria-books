<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'logo_path',
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

    /**
     * Mutator: Ensure empty strings are always stored as NULL.
     * This prevents 'isset()' checks from passing on empty values and fixes S3 key errors.
     */
    public function setLogoPathAttribute(?string $value): void
    {
        $this->attributes['logo_path'] = empty($value) ? null : $value;
    }

    /**
     * Accessor: Returns S3 URL if logo exists, otherwise generates UI Avatar.
     */
    public function getLogoUrlAttribute(): string
    {
        // 1. If we have a stored path (and it's not empty), return the Storage URL
        if (!empty($this->logo_path)) {
            return Storage::disk('s3')->url($this->logo_path);
        }

        // 2. Fallback: Generate UI Avatar
        // We use the company name (decrypted via cast) or a default string
        $name = $this->company_name ?? 'Organization';
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    // UUID Configuration
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 
        'type', 
        'name', 
        'company_name', 
        'email', 
        'tax_number', 
        'address'
    ];

    /**
     * ENCRYPTION SETTINGS
     * Laravel automatically encrypts these on save and decrypts on access.
     */
    protected $casts = [
        'name' => 'encrypted',
        'company_name' => 'encrypted',
        'email' => 'encrypted',
        'tax_number' => 'encrypted',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate UUID and assign Tenant ID
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Global Tenant Scope
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }
}
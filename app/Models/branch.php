<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Support\Facades\DB;

class Branch extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasUuids;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'tin',
        'rdo_code', // Revenue District Office (BIR Requirement)
        'address',
        'city',
        'province',
        'zip_code',
        'phone',    // Contact Info
        'email',    // Contact Info
        'is_default',
        'is_active',
    ];

    /**
     * Encrypt sensitive PII and Tax Data.
     * Note: Encrypted fields cannot be searched directly in SQL (WHERE clauses).
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'name' => 'encrypted',
        'tin' => 'encrypted',
        'code' => 'encrypted',
        'rdo_code' => 'encrypted', // Encrypted: Sensitive Tax Info
        'phone' => 'encrypted',    // Encrypted: PII
        'email' => 'encrypted',    // Encrypted: PII
    ];

    /**
     * Set this branch as default and unset others for the same tenant.
     */
    public function markAsDefault()
    {
        DB::transaction(function () {
            Branch::where('tenant_id', $this->tenant_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            $this->update(['is_default' => true]);
        });
    }
}
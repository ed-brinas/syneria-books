<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Support\Facades\DB;

class Branch extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'tin',
        'address',
        'city',
        'province',
        'zip_code',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'name' => 'encrypted',
        'tin' => 'encrypted',
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
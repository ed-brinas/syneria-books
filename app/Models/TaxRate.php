<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxRate extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'rate',
        'type',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'float',
        'is_active' => 'boolean',
    ];

    // Helper to get rate as percentage for display (e.g., "10%")
    public function getDisplayRateAttribute()
    {
        return ($this->rate * 100) . '%';
    }

    // Scope to only show active rates
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
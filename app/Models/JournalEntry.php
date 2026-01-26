<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ScopedBy([TenantScope::class])]
class JournalEntry extends Model
{
    use BelongsToTenant, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'branch_id', // Multi-Branch Support
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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

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
     * The branch this entry belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
        // Using epsilon for floating point comparison
        return abs($this->total_debit - $this->total_credit) < 0.001;
    }
}
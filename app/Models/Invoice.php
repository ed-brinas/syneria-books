<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 
        'contact_id', 
        'type', 
        'subtype',      // Added
        'tax_type',     // Added
        'number', 
        'reference',
        'payment_terms', // Added
        'date', 
        'due_date', 
        'status', 
        'subtotal', 
        'tax_total', 
        'grand_total', 
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
            if (empty($model->status)) {
                $model->status = 'draft';
            }
            if (empty($model->subtype)) {
                $model->subtype = 'standard';
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
    // Helper to get friendly name for subtypes
    public function getSubtypeLabelAttribute()
    {
        return match($this->subtype) {
            'sales_invoice' => 'Sales Invoice (Goods)',
            'service_invoice' => 'Billing Invoice (Services)',
            'standard' => 'Standard Invoice',
            default => ucfirst(str_replace('_', ' ', $this->subtype)),
        };
    }
}
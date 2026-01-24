<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'subtype',
        'tax_type',
        'number',
        'reference',
        'payment_terms',
        'currency_code',
        'date',
        'due_date',
        'is_recurring',
        'recurrence_interval',
        'recurrence_type',
        'recurrence_end_date',
        'last_recurrence_date',
        'next_recurrence_date',
        'status',
        'subtotal',
        'tax_total',
        'withholding_tax_rate',
        'withholding_tax_amount',
        'grand_total',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'recurrence_end_date' => 'date',
        'last_recurrence_date' => 'date',
        'next_recurrence_date' => 'date',
        'is_recurring' => 'boolean',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'withholding_tax_rate' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
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

    public function attachments()
    {
        return $this->hasMany(InvoiceAttachment::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class); 
    }

    public function getIsLockedAttribute()
    {
        return in_array($this->status, ['review', 'reviewed', 'posted', 'paid', 'voided']);
    }
}
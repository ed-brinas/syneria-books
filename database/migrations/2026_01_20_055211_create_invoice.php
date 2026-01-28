<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('contact_id')->constrained(); 
            $table->foreignUuid('branch_id')->nullable()->constrained('branches');
            $table->enum('type', ['invoice', 'bill']); 
            $table->enum('subtype', ['sales_invoice', 'service_invoice', 'standard'])->default('standard');
            $table->enum('tax_type', ['vat', 'non_vat', 'vat_exempt', 'zero_rated'])->default('vat');
            $table->string('number')->nullable();
            $table->string('reference')->nullable(); 
            $table->string('payment_terms')->nullable();           
            $table->string('currency_code', 3)->default('PHP'); 
            $table->date('date');
            $table->date('due_date');
            $table->boolean('is_recurring')->default(false);
            $table->integer('recurrence_interval')->nullable();
            $table->enum('recurrence_type', ['weeks', 'months'])->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->date('last_recurrence_date')->nullable();
            $table->date('next_recurrence_date')->nullable();
            $table->enum('status', ['draft', 'review', 'reviewed', 'posted', 'paid', 'voided'])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('withholding_tax_rate', 5, 2)->default(0);
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);            
            $table->text('notes')->nullable();    
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'type', 'number']); 
        });

        // 2. Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_id')->constrained(); 
            $table->foreignUuid('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_rate', 5, 2)->default(0); 
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            
            $table->timestamps();
        });

        // 3. Attachments
        Schema::create('invoice_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoice_attachments');
        Schema::dropIfExists('invoices');
    }
};
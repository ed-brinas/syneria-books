<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Contacts (Customers, Suppliers, Employees)
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['customer', 'supplier', 'employee']);
            $table->text('name'); 
            $table->text('company_name')->nullable();
            $table->text('email')->nullable();
            $table->text('tax_number')->nullable();
            
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Invoices (Used for both AR Invoices and AP Bills)
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('contact_id')->constrained(); 
            $table->enum('type', ['invoice', 'bill']); 
            $table->enum('subtype', ['sales_invoice', 'service_invoice', 'standard'])->default('standard');
            $table->enum('tax_type', ['vat', 'non_vat', 'vat_exempt', 'zero_rated'])->default('vat');
            $table->string('number')->nullable();
            $table->string('reference')->nullable(); 
            $table->string('payment_terms')->nullable();           
            $table->date('date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'posted', 'paid', 'voided'])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();    
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'type', 'number']); 
        });

        // 3. Invoice Items (Lines)
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_id')->constrained(); 
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('contacts');
    }
};
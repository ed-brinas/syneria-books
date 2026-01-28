<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Head table for the Journal Entry
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('branches');
            $table->date('date');
            $table->date('auto_reverse_date')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('tax_type')->default('no_tax'); 
            $table->string('status')->default('draft');
            $table->boolean('locked')->default(false);
            $table->boolean('is_reversed')->default(false);
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'date']);
        });

        // Line items for the Journal Entry
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_id')->constrained();
            $table->foreignUuid('tax_code_id')->nullable()->constrained('tax_rates'); 
            $table->decimal('tax_amount', 20, 4)->default(0); 
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};
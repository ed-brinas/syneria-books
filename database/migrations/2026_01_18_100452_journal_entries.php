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
            
            $table->date('date');
            $table->string('reference')->nullable(); // External ref / check #
            $table->text('description')->nullable();
            
            // Status: draft, posted, voided
            $table->string('status')->default('draft');
            
            // Locking mechanism for audit compliance
            $table->boolean('locked')->default(false);
            
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['tenant_id', 'date']);
        });

        // Line items for the Journal Entry
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Redundant tenant_id strictly for rapid filtering/indexing isolation without joining header
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('account_id')->constrained();
            
            // Precise decimal storage for financial data (20 digits total, 4 decimal places)
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            
            $table->text('description')->nullable(); // Line item specific description
            
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
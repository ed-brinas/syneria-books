<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('type'); // e.g., 'JV', 'INV', 'BILL'
            $table->unsignedBigInteger('current_value')->default(0);
            $table->string('prefix')->nullable(); // e.g., 'JV-'
            $table->timestamps();

            // Ensure one sequence type per tenant
            $table->unique(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
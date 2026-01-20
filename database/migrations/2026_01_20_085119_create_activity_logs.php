<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');           
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete(); 
            $table->string('action'); // e.g., 'created', 'updated', 'voided', 'deleted'
            $table->text('description'); // e.g., "Voided Invoice SI-0001"
            $table->nullableUuidMorphs('subject');             
            $table->string('ip_address')->nullable();
            $table->text('properties')->nullable(); // JSON for extra details
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('company_name');
            $table->text('trade_name')->nullable();
            $table->text('company_reg_number')->nullable();
            $table->text('tax_identification_number')->nullable();
            $table->text('business_address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('business_type')->nullable();
            $table->string('country')->default('PH');
            $table->string('domain')->nullable()->unique();
            $table->string('subscription_plan')->default('free');
            $table->date('subscription_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

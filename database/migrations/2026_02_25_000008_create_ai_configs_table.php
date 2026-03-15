<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->text('system_prompt')->nullable();
            $table->text('business_description')->nullable();
            $table->json('product_catalog')->nullable();
            $table->json('pricing_info')->nullable();
            $table->json('faq')->nullable();
            $table->string('tone')->default('friendly'); // professional, friendly, casual, formal
            $table->string('language')->default('en');
            $table->unsignedInteger('response_delay_min_seconds')->default(30);
            $table->unsignedInteger('response_delay_max_seconds')->default(180);
            $table->json('working_hours')->nullable();
            $table->string('timezone')->default('UTC');
            $table->json('escalation_rules')->nullable();
            $table->json('sales_methodology')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configs');
    }
};

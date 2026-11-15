<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_config_id')->constrained('ai_configs')->cascadeOnDelete();
            $table->string('product_key', 36)->index();
            $table->string('kind', 10)->default('image');
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedInteger('size_bytes');
            $table->string('variant_label')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('allow_ai_autosend')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['ai_config_id', 'product_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};

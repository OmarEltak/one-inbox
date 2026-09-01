<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 32)->default('media');
            $table->string('path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 128);
            $table->unsignedInteger('size_bytes');
            $table->enum('kind', ['image', 'audio', 'video', 'document']);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->char('checksum_sha256', 64);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'checksum_sha256']);
            $table->index(['team_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};

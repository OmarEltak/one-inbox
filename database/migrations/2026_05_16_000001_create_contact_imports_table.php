<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_name');
            $table->string('tag');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
            $table->index(['team_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_imports');
    }
};

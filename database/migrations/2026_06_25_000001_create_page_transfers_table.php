<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('superseded_page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('superseded_by_page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->unsignedBigInteger('from_team_id')->nullable();
            $table->unsignedBigInteger('to_team_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('reason'); // 'reconnect', 'cleanup', 'manual'
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['superseded_page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_transfers');
    }
};

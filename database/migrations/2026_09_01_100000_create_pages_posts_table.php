<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pages_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('platform_post_id');
            $table->timestamp('created_at_platform');
            $table->timestamp('first_seen_at')->useCurrent();
            $table->unique(['page_id', 'platform_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages_posts');
    }
};

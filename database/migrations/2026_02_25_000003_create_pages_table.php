<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connected_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // facebook, instagram, whatsapp, telegram
            $table->string('platform_page_id');
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->text('page_access_token')->nullable(); // encrypted
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'platform', 'platform_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};

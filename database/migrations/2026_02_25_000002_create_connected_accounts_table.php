<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // facebook, instagram, whatsapp, telegram
            $table->string('platform_user_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('avatar')->nullable();
            $table->text('access_token')->nullable(); // encrypted
            $table->text('refresh_token')->nullable(); // encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'platform', 'platform_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_accounts');
    }
};

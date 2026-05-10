<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('platform_user_id')->index();
            $table->string('source')->default('meta'); // meta | manual | api
            $table->string('confirmation_code', 64)->unique();
            $table->string('status')->default('pending'); // pending | completed | failed
            $table->json('matched_records')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_deletion_requests');
    }
};

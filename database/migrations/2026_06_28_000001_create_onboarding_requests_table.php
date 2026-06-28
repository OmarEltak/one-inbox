<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('platform'); // facebook | instagram (+room for others later)
            $table->string('business_name')->nullable();
            $table->string('page_url')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending | in_progress | completed | rejected
            $table->foreignId('assigned_admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resulting_page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_requests');
    }
};

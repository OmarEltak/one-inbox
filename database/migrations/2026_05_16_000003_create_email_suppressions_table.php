<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('reason'); // unsubscribed, bounced, complaint, manual
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'email']);
            $table->index(['team_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};

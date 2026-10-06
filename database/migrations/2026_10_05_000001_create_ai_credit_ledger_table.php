<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A — AI Credit Economy ledger.
 *
 * Append-only ledger table. Balance for a team = SUM(delta) partitioned by
 * balance_type ('monthly' | 'wallet'). Hot path (every AI dispatch) reads a
 * 60s Redis cache in App\Services\Billing\AiCredits::balance(), not MySQL.
 *
 * See docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md §3.4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            // Positive = grant / refund / monthly_reset. Negative = charge.
            $table->integer('delta');
            // SQLite doesn't support ENUM; application-level whitelist in
            // App\Services\Billing\AiCredits::BALANCE_TYPES.
            $table->string('balance_type', 16);
            $table->string('reason', 64);
            // Morph target: Message, DeepAnalysis, LemonSqueezyOrder, etc.
            $table->string('cost_source_type', 64)->nullable();
            $table->unsignedBigInteger('cost_source_id')->nullable();
            // Null for system-initiated writes (monthly reset, backfill).
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['team_id', 'created_at']);
            $table->index(['team_id', 'reason']);
            $table->index(['team_id', 'balance_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_ledger');
    }
};

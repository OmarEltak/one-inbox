<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase D — Deep Analysis storage.
 *
 * One row per operator-triggered bulk-analysis run (spec §5). The result_json
 * is injected into AiChat's prompt on subsequent turns so the operator can
 * ask follow-up questions about the same cohort without paying again.
 *
 * Charge happens at dispatch time, so cohort_size + credits_charged capture
 * exactly what the team was billed. Failed jobs trigger an outage refund.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deep_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode');                   // 'customer_themes', 'agent_audit' (Phase H)
            $table->json('cohort_filter');            // {page_id, contact_ids, days, limit}
            $table->unsignedInteger('cohort_size');   // contacts actually processed
            $table->unsignedInteger('credits_charged');
            $table->string('status');                 // queued, running, completed, failed
            $table->json('result_json')->nullable();
            $table->json('token_usage')->nullable();  // {prompt, completion, total}
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deep_analyses');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A — opt-in auto-deduct for expensive actions (>5 credits).
 *
 * Default false means first-time expensive actions trigger a confirmation
 * exception that the UI surfaces as a modal (see Phase D). User can tick
 * "always auto-deduct" in that modal to flip this to true.
 *
 * See docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md §3.6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('auto_deduct_expensive_actions')->default(false)->after('ai_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('auto_deduct_expensive_actions');
        });
    }
};

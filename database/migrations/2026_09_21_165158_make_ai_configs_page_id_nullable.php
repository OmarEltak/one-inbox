<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A — allow a page-less AiConfig row.
 *
 * The "Meet Your AI" onboarding creates the team's first AiConfig BEFORE any
 * page is connected (that's the whole point of the flow — meet your AI before
 * asking for OAuth). So page_id must be nullable and the unique index on it
 * has to be dropped and re-added as a partial/composite that tolerates nulls.
 *
 * MySQL and SQLite both allow multiple NULLs in a UNIQUE column, so simply
 * dropping-NOT-NULL + keeping the unique constraint works for both drivers.
 * (Postgres would need a `WHERE page_id IS NOT NULL` partial index — not our
 * target driver.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Order matters on MySQL: drop FK first, then modify column, then
        // recreate FK. SQLite ignores the FK drop calls (no-op).
        Schema::table('ai_configs', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                // Drop the FK by convention name if it exists.
                try {
                    $table->dropForeign(['page_id']);
                } catch (\Throwable $e) {
                    // FK didn't exist under conventional name — safe to continue.
                }
            }
        });

        Schema::table('ai_configs', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable()->change();
        });

        Schema::table('ai_configs', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Best-effort rollback. We cannot make page_id NOT NULL again if there
        // are page-less rows in the wild, so delete them first.
        DB::table('ai_configs')->whereNull('page_id')->delete();

        Schema::table('ai_configs', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                try {
                    $table->dropForeign(['page_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        });

        Schema::table('ai_configs', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable(false)->change();
        });

        Schema::table('ai_configs', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();
            }
        });
    }
};

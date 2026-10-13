<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen messages.media_url to accommodate Meta's signed CDN URLs. Facebook/
 * Instagram return URLs for inbound media attachments that commonly hit
 * 500-1200 chars (lookaside.fbsbx.com with long signature params). The old
 * varchar(255) truncation was silently killing 80%+ of the backfill job's
 * message inserts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->text('media_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('media_url', 255)->nullable()->change();
        });
    }
};

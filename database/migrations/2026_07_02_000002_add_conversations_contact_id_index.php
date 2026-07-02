<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard's contact whereHas('conversations') was doing an EXISTS subquery
 * per contact against 23k+ conversations with no index on contact_id — hitting
 * 30s PHP timeout on older accounts and returning 504 through the tunnel.
 *
 * Adding a covering (contact_id, page_id) index turns each subquery into an
 * index range scan.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['contact_id', 'page_id'], 'conversations_contact_page_idx');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_contact_page_idx');
        });
    }
};

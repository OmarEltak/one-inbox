<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            // { sheet_url, webhook_url, events: [lead_captured, deal_closed], last_delivery: {...} }
            $table->json('sales_connectors')->nullable()->after('comment_settings');
        });
    }

    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn('sales_connectors');
        });
    }
};

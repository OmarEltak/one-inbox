<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->ulid('media_asset_id')->nullable()->after('media_type');
            $table->foreign('media_asset_id')->references('id')->on('media_assets')->nullOnDelete();
            $table->index('media_asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['media_asset_id']);
            $table->dropIndex(['media_asset_id']);
            $table->dropColumn('media_asset_id');
        });
    }
};

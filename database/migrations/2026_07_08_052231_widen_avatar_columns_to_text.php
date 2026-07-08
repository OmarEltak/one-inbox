<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Facebook/Instagram CDN avatar URLs include signing query params that
        // easily exceed VARCHAR(255). Widen to TEXT on all three tables.
        Schema::table('pages', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });

        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
        });

        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
        });
    }
};

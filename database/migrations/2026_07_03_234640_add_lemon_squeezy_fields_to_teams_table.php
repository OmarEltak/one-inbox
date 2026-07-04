<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('lemon_squeezy_id')->nullable()->after('subscription_status');
            $table->string('lemon_squeezy_customer_id')->nullable()->after('lemon_squeezy_id');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['lemon_squeezy_id', 'lemon_squeezy_customer_id']);
        });
    }
};

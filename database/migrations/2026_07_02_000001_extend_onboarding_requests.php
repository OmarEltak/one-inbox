<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('onboarding_requests', function (Blueprint $table) {
            $table->string('contact_email', 190)->nullable()->after('contact_phone');
            $table->timestamp('customer_dismissed_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_requests', function (Blueprint $table) {
            $table->dropColumn(['contact_email', 'customer_dismissed_at']);
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // active | escalated | completed | spam — gates SendAiResponse dispatch.
            $table->string('sales_stage')->default('active')->after('ai_paused');
            // JSON map { email: "x@y.com", phone: "+201...", ... } filled by extraction.
            $table->json('captured_data')->nullable()->after('sales_stage');

            $table->index(['sales_stage', 'page_id']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            // Per-contact daily AI reply counter. Lazy-reset on next dispatch when
            // ai_replies_reset_at < now().
            $table->unsignedInteger('ai_replies_today')->default(0)->after('lead_status');
            $table->timestamp('ai_replies_reset_at')->nullable()->after('ai_replies_today');
        });

        Schema::table('ai_configs', function (Blueprint $table) {
            // info_only | capture_data | booking | ecommerce | custom
            $table->string('sales_goal_preset')->default('info_only')->after('faq');
            // Array of field descriptors: [{ key, label, type }] where type is
            // email | phone | text | address | select.
            $table->json('required_capture_fields')->nullable()->after('sales_goal_preset');
            // Flat array of keywords (any language). Case-insensitive match.
            $table->json('escalation_keywords')->nullable()->after('required_capture_fields');
            // Per-contact daily AI reply cap. Customer-editable but hard-capped
            // at 50 in the Livewire component (anti-abuse ceiling).
            $table->unsignedInteger('contact_ai_reply_cap')->default(20)->after('escalation_keywords');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['sales_stage', 'page_id']);
            $table->dropColumn(['sales_stage', 'captured_data']);
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['ai_replies_today', 'ai_replies_reset_at']);
        });
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn([
                'sales_goal_preset',
                'required_capture_fields',
                'escalation_keywords',
                'contact_ai_reply_cap',
            ]);
        });
    }
};

<?php

namespace Database\Seeders;

use App\Models\ConnectedAccount;
use App\Models\Contact;
use App\Models\ContactPlatform;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TikTokDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Target the first team in the system (your production team)
        $team = Team::first();

        if (! $team) {
            $this->command->error('No team found. Please create a team first.');
            return;
        }

        $this->command->info("Seeding TikTok demo data for team: {$team->name}");

        // Clean up any previous demo data
        $this->cleanup($team);

        // 1. Create ConnectedAccount (the TikTok business account)
        $connectedAccount = ConnectedAccount::create([
            'team_id'          => $team->id,
            'platform'         => 'tiktok',
            'platform_user_id' => 'demo_7380291046753280001',
            'name'             => 'One Inbox Business',
            'avatar'           => 'https://p16-sign.tiktokcdn-us.com/tos-useast5-avt-0068-tx/demo_avatar.jpeg',
            'access_token'     => 'demo_access_token_' . Str::random(32),
            'refresh_token'    => 'demo_refresh_token_' . Str::random(32),
            'token_expires_at' => Carbon::now()->addDays(60),
            'scopes'           => ['message.list.read', 'message.list.send', 'message.list.manage'],
            'is_active'        => true,
            'connected_at'     => Carbon::now(),
        ]);

        // 2. Create Page
        $page = Page::create([
            'connected_account_id' => $connectedAccount->id,
            'team_id'              => $team->id,
            'platform'             => 'tiktok',
            'platform_page_id'     => 'demo_7380291046753280001',
            'name'                 => 'One Inbox Business',
            'avatar'               => 'https://p16-sign.tiktokcdn-us.com/tos-useast5-avt-0068-tx/demo_avatar.jpeg',
            'page_access_token'    => 'demo_page_token_' . Str::random(32),
            'category'             => 'tiktok_business',
            'is_active'            => true,
            'metadata'             => ['follower_count' => 1240, 'verified' => false],
        ]);

        // 3. Create demo conversations
        $this->createConversation($team, $page, [
            'platform_contact_id' => 'demo_user_7291034857362940001',
            'name'                => 'Ahmed Hassan',
            'avatar'              => 'https://p16-sign.tiktokcdn-us.com/tos-useast5-avt-0068-tx/demo1.jpeg',
            'messages'            => [
                ['direction' => 'inbound', 'content' => 'Hello! I saw your video about the product. Is it available in Egypt? 🇪🇬', 'minutes_ago' => 35],
                ['direction' => 'outbound', 'content' => 'Hi Ahmed! Yes, we ship to Egypt! 🎉 Which product are you interested in?', 'minutes_ago' => 32, 'sender_type' => 'user'],
                ['direction' => 'inbound', 'content' => 'The premium package. How much does it cost with shipping?', 'minutes_ago' => 28],
                ['direction' => 'outbound', 'content' => 'The premium package is $79/month. We also have a free trial — want me to set one up for you?', 'minutes_ago' => 25, 'sender_type' => 'ai'],
                ['direction' => 'inbound', 'content' => 'Yes please! That sounds great 👍', 'minutes_ago' => 20],
            ],
        ]);

        $this->createConversation($team, $page, [
            'platform_contact_id' => 'demo_user_7291034857362940002',
            'name'                => 'Sara Mohamed',
            'avatar'              => 'https://p16-sign.tiktokcdn-us.com/tos-useast5-avt-0068-tx/demo2.jpeg',
            'messages'            => [
                ['direction' => 'inbound', 'content' => 'Hi, I have a question about your service 🙋‍♀️', 'minutes_ago' => 120],
                ['direction' => 'outbound', 'content' => 'Hi Sara! Of course, how can I help you?', 'minutes_ago' => 118, 'sender_type' => 'user'],
                ['direction' => 'inbound', 'content' => 'Can I connect multiple social media accounts in one inbox?', 'minutes_ago' => 115],
                ['direction' => 'outbound', 'content' => 'Absolutely! One Inbox supports Facebook, Instagram, WhatsApp, TikTok, Telegram and more — all in a single dashboard.', 'minutes_ago' => 113, 'sender_type' => 'ai'],
                ['direction' => 'inbound', 'content' => 'Wow that\'s exactly what I need! Do you have a demo?', 'minutes_ago' => 110],
                ['direction' => 'outbound', 'content' => 'Yes! Visit ot1-pro.com to start a free trial, or I can walk you through it here 😊', 'minutes_ago' => 108, 'sender_type' => 'user'],
                ['direction' => 'inbound', 'content' => 'Perfect, signing up now! Thank you 🙏', 'minutes_ago' => 105],
            ],
        ]);

        $this->createConversation($team, $page, [
            'platform_contact_id' => 'demo_user_7291034857362940003',
            'name'                => 'Khaled Ali',
            'avatar'              => 'https://p16-sign.tiktokcdn-us.com/tos-useast5-avt-0068-tx/demo3.jpeg',
            'messages'            => [
                ['direction' => 'inbound', 'content' => 'Hey, does your platform support AI auto-reply?', 'minutes_ago' => 240],
                ['direction' => 'outbound', 'content' => 'Yes! Our AI can automatically respond to common questions in any language 🤖', 'minutes_ago' => 238, 'sender_type' => 'ai'],
                ['direction' => 'inbound', 'content' => 'In Arabic too?', 'minutes_ago' => 235],
                ['direction' => 'outbound', 'content' => 'نعم بالطبع! يدعم النظام العربية بشكل كامل 🌟', 'minutes_ago' => 233, 'sender_type' => 'ai'],
                ['direction' => 'inbound', 'content' => 'ممتاز! كيف أبدأ؟', 'minutes_ago' => 230],
                ['direction' => 'outbound', 'content' => 'ابدأ بالتسجيل المجاني على ot1-pro.com وربط حساباتك خلال دقائق!', 'minutes_ago' => 228, 'sender_type' => 'user'],
            ],
        ]);

        $this->command->info('✅ TikTok demo data seeded successfully!');
        $this->command->info('   - 1 connected TikTok account');
        $this->command->info('   - 3 demo conversations with realistic messages');
        $this->command->info('');
        $this->command->info('To remove demo data, run: php artisan db:seed --class=TikTokDemoSeeder --clean');
    }

    private function createConversation(Team $team, Page $page, array $data): void
    {
        $now = Carbon::now();
        $lastMsg = collect($data['messages'])->sortBy('minutes_ago')->last();
        $lastMsgAt = $now->copy()->subMinutes($lastMsg['minutes_ago']);

        // Create contact
        $contact = Contact::create([
            'team_id'             => $team->id,
            'name'                => $data['name'],
            'avatar'              => $data['avatar'],
            'lead_score'          => rand(30, 80),
            'lead_status'         => 'warm',
            'first_seen_at'       => $now->copy()->subMinutes(max(array_column($data['messages'], 'minutes_ago'))),
            'last_interaction_at' => $lastMsgAt,
        ]);

        // Link contact to TikTok platform
        ContactPlatform::create([
            'contact_id'          => $contact->id,
            'platform'            => 'tiktok',
            'platform_contact_id' => $data['platform_contact_id'],
            'platform_name'       => $data['name'],
            'platform_avatar'     => $data['avatar'],
        ]);

        // Create conversation
        $inboundMessages = collect($data['messages'])->where('direction', 'inbound');
        $lastInbound = $inboundMessages->sortBy('minutes_ago')->last();

        $conversation = Conversation::create([
            'page_id'                    => $page->id,
            'team_id'                    => $team->id,
            'platform'                   => 'tiktok',
            'platform_conversation_id'   => 'demo_conv_' . Str::random(16),
            'contact_id'                 => $contact->id,
            'status'                     => 'open',
            'ai_paused'                  => false,
            'last_message_at'            => $lastMsgAt,
            'last_message_preview'       => $lastMsg['content'],
            'unread_count'               => $lastMsg['direction'] === 'inbound' ? 1 : 0,
        ]);

        // Create messages
        foreach ($data['messages'] as $msg) {
            Message::create([
                'conversation_id'    => $conversation->id,
                'platform_message_id'=> 'demo_msg_' . Str::random(20),
                'direction'          => $msg['direction'],
                'sender_type'        => $msg['direction'] === 'inbound' ? 'contact' : ($msg['sender_type'] ?? 'user'),
                'sender_id'          => null,
                'content_type'       => 'text',
                'content'            => $msg['content'],
                'platform_sent_at'   => $now->copy()->subMinutes($msg['minutes_ago']),
                'created_at'         => $now->copy()->subMinutes($msg['minutes_ago']),
                'updated_at'         => $now->copy()->subMinutes($msg['minutes_ago']),
            ]);
        }
    }

    private function cleanup(Team $team): void
    {
        // Remove previously seeded demo accounts
        $demoAccounts = ConnectedAccount::where('team_id', $team->id)
            ->where('platform', 'tiktok')
            ->where('platform_user_id', 'like', 'demo_%')
            ->get();

        foreach ($demoAccounts as $account) {
            $pages = Page::where('connected_account_id', $account->id)->get();
            foreach ($pages as $page) {
                $conversations = Conversation::where('page_id', $page->id)->get();
                foreach ($conversations as $conv) {
                    Message::where('conversation_id', $conv->id)
                        ->where('platform_message_id', 'like', 'demo_%')
                        ->delete();
                    $conv->delete();
                }
                $page->delete();
            }
            $account->delete();
        }

        // Clean up demo contacts
        $demoContactPlatforms = ContactPlatform::where('platform', 'tiktok')
            ->where('platform_contact_id', 'like', 'demo_%')
            ->get();

        foreach ($demoContactPlatforms as $cp) {
            Contact::find($cp->contact_id)?->delete();
            $cp->delete();
        }
    }
}

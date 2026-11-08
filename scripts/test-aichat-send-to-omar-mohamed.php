<?php
/**
 * 2026-11-09 — E2E test: ask the AI chat assistant to send Omar Mohamed a
 * message and verify it actually hit the Meta Graph API (not a fake green
 * checkmark).
 *
 * Simulates the full Livewire flow by calling the component methods
 * directly as Omar (user 3). Runs in prod tinker.
 *
 * Usage on prod:
 *   cd /var/www/ot1-pro.com
 *   sudo -u deploy HOME=/home/deploy XDG_CONFIG_HOME=/home/deploy/.config \
 *     php artisan tinker --execute="require 'scripts/test-aichat-send-to-omar-mohamed.php';"
 */

use App\Livewire\AiChat;
use App\Models\AiCommand;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// 1. Boot as Omar (user 3)
$omar = User::find(3);
if (! $omar) { echo "user 3 not found — abort\n"; exit(1); }
Auth::login($omar);
$team = $omar->currentTeam;
if (! $team) { echo "no current team for user 3 — abort\n"; exit(1); }
echo "Acting as: {$omar->name} / team {$team->name} (id={$team->id})\n\n";

// 2. Locate Omar Mohamed's conversation
$contact = Contact::where('team_id', $team->id)
    ->where('name', 'like', '%Omar Mohamed%')
    ->orderBy('id')
    ->first();
if (! $contact) { echo "no 'Omar Mohamed' contact on team {$team->id} — abort\n"; exit(1); }
echo "Target contact: id={$contact->id}  name='{$contact->name}'  platform={$contact->platform}\n";

$conv = Conversation::where('team_id', $team->id)
    ->where('contact_id', $contact->id)
    ->orderByDesc('last_message_at')
    ->first();
echo "Target conversation: id={$conv?->id}  sales_stage={$conv?->sales_stage}  last_msg_at={$conv?->last_message_at?->toIso8601String()}\n\n";

// 3. Record baseline — count outbound messages on this conversation before our test
$baselineOutbound = Message::where('conversation_id', $conv->id)
    ->where('direction', 'outbound')
    ->count();
echo "Baseline outbound messages on conv {$conv->id}: {$baselineOutbound}\n\n";

// 4. Boot AiChat and simulate the operator typing an instruction
$component = new AiChat();
$component->mount();
$instruction = "send Omar Mohamed a quick message saying: 'Hi — this is a live test from the OT1-Pro AI pilot team. Please ignore 🙏'";
$component->message = $instruction;

echo "═══ STEP 1: AiChat::sendMessage() ═══\n";
echo "Operator instruction: {$instruction}\n\n";
try {
    $component->sendMessage();
} catch (\Throwable $e) {
    echo "sendMessage() threw: " . $e->getMessage() . "\n";
    echo "trace: " . substr($e->getTraceAsString(), 0, 1200) . "\n";
    exit(1);
}
echo "sendMessage() returned without throwing.\n";

// 5. The AiCommand row is now 'pending'. Run ProcessAiChatTurn synchronously
// to resolve it (would normally be the queue worker).
$latestCmd = AiCommand::where('team_id', $team->id)
    ->where('user_id', $omar->id)
    ->latest('id')
    ->first();
echo "AiCommand id={$latestCmd?->id} status={$latestCmd?->status}\n";

if ($latestCmd?->status === 'pending') {
    echo "\n═══ STEP 2: ProcessAiChatTurn::handle() (sync) ═══\n";
    $historyForJob = []; // minimal — the job re-reads from DB
    $job = new \App\Jobs\ProcessAiChatTurn($latestCmd->id, $historyForJob);
    app()->call([$job, 'handle']);
    $latestCmd->refresh();
    echo "After job: status={$latestCmd->status}\n";
    echo "AI response:\n" . mb_substr((string) $latestCmd->response, 0, 800) . "\n\n";
    $actionTaken = (array) ($latestCmd->action_taken ?? []);
    echo "action_taken: " . json_encode($actionTaken, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
}

// 6. Check if the component has a pendingAction waiting for confirmation.
// The component instance we have is stale (sendMessage persisted the cmd
// then the job ran separately) — re-mount to get fresh server state.
echo "═══ STEP 3: Re-mount AiChat to inspect pendingAction ═══\n";
$component2 = new AiChat();
$component2->mount();
if ($component2->pendingAction) {
    echo "pendingAction detected:\n" . json_encode($component2->pendingAction, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
    echo "pendingActionSummary: {$component2->pendingActionSummary}\n\n";
    echo "═══ STEP 4: AiChat::confirmAction() ═══\n";
    try {
        $component2->confirmAction();
        echo "confirmAction() returned without throwing.\n";
    } catch (\Throwable $e) {
        echo "confirmAction() threw: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "No pendingAction on re-mount — the AI did NOT propose a send_message action.\n";
    echo "This likely means the AI responded conversationally instead of recognizing a send-command intent.\n";
    echo "Check cmd->response above to see what it said.\n\n";
}

// 7. Check the outbound message count — did a new one land?
$postOutbound = Message::where('conversation_id', $conv->id)
    ->where('direction', 'outbound')
    ->count();
$newMessages = Message::where('conversation_id', $conv->id)
    ->where('direction', 'outbound')
    ->orderByDesc('id')
    ->take($postOutbound - $baselineOutbound)
    ->get();

echo "═══ STEP 5: Verify Message landed and dispatched ═══\n";
echo "Outbound messages before: {$baselineOutbound}\n";
echo "Outbound messages after:  {$postOutbound}\n";

if ($postOutbound > $baselineOutbound) {
    foreach ($newMessages->reverse() as $m) {
        echo "\n  NEW MESSAGE id={$m->id}:\n";
        echo "    sender_type:         {$m->sender_type}\n";
        echo "    content:             " . mb_substr((string) $m->content, 0, 300) . "\n";
        echo "    platform_message_id: " . ($m->platform_message_id ?? '(null)') . "\n";
        echo "    platform_sent_at:    " . ($m->platform_sent_at?->toIso8601String() ?? '(null)') . "\n";
        echo "    send_status:         " . ($m->metadata['send_status'] ?? '(not stamped)') . "\n";
        echo "    send_error:          " . ($m->metadata['send_error'] ?? '(none)') . "\n";
    }
    echo "\n✅ A new outbound message was created.\n";
    $last = $newMessages->first();
    if ($last->platform_message_id && !isset($last->metadata['send_status'])) {
        echo "✅ VERIFIED: platform_message_id set + no error — Meta accepted the send.\n";
    } elseif (isset($last->metadata['send_status']) && $last->metadata['send_status'] !== 'ok') {
        echo "❌ Message was stamped as FAILED — Meta rejected the send.\n";
        echo "   Error: " . ($last->metadata['send_error_raw'] ?? '?') . "\n";
    } else {
        echo "⚠️  Platform_message_id is null but no send_status error — check queue worker logs.\n";
    }
} else {
    echo "\n❌ No new outbound message. The AI chat never executed the send.\n";
}

echo "\n(Test complete — message, if it sent, went to the REAL Omar Mohamed on Facebook.)\n";

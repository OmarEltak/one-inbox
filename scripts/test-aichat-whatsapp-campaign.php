<?php

declare(strict_types=1);

/**
 * 2026-11-09 — E2E: can the AI chat assistant actually launch a WhatsApp
 * bulk campaign when asked, or does it break the UI / do nothing?
 *
 * SAFETY: asks the AI to SCHEDULE a campaign for tomorrow (not dispatch now),
 * so no real customers get a message. Verifies the Campaign row is created
 * with status='scheduled', then deletes it.
 *
 * Simulates the full Livewire flow: sendMessage → ProcessAiChatTurn →
 * pendingAction → confirmAction → actionSendBulkMessage (scheduling branch).
 */

use App\Livewire\AiChat;
use App\Models\AiCommand;
use App\Models\Campaign;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$omar = User::find(3);
if (! $omar) {
    echo "user 3 not found — abort\n";
    exit(1);
}
Auth::login($omar);
$team = $omar->currentTeam;
if (! $team) {
    echo "no current team — abort\n";
    exit(1);
}
echo "Acting as: {$omar->name} / team {$team->name} (id={$team->id})\n\n";

// Pick a WhatsApp page for the campaign. If none, fall back to any active page.
$waPage = Page::where('team_id', $team->id)
    ->where('is_active', true)
    ->where('platform', 'whatsapp')
    ->first();
if (! $waPage) {
    $waPage = Page::where('team_id', $team->id)->where('is_active', true)->first();
}
if (! $waPage) {
    echo "no active page on team {$team->id} — abort\n";
    exit(1);
}
echo "Target page: id={$waPage->id}  platform={$waPage->platform}  name='{$waPage->name}'\n\n";

$tomorrow = now()->addDay()->setTime(10, 0, 0);
$instruction = "launch a WhatsApp campaign scheduled for {$tomorrow->toIso8601String()} from page id {$waPage->id} to all contacts with lead status 'hot' with this message: 'Omar from OT1-Pro — quick reminder: your 2-day free trial is still open. Want me to set it up?'";

echo "═══ STEP 1: AiChat::sendMessage() ═══\n";
echo "Instruction (truncated): " . mb_substr($instruction, 0, 250) . "...\n\n";

$component = new AiChat();
$component->mount();
$component->message = $instruction;
try {
    $component->sendMessage();
} catch (\Throwable $e) {
    echo "sendMessage() threw: {$e->getMessage()}\n";
    exit(1);
}

$latestCmd = AiCommand::where('team_id', $team->id)
    ->where('user_id', $omar->id)
    ->latest('id')
    ->first();
echo "AiCommand id={$latestCmd?->id} status={$latestCmd?->status}\n";

echo "\n═══ STEP 2: ProcessAiChatTurn::handle() (sync) ═══\n";
if ($latestCmd?->status === 'pending') {
    $job = new \App\Jobs\ProcessAiChatTurn($latestCmd->id, []);
    app()->call([$job, 'handle']);
    $latestCmd->refresh();
    echo "After job: status={$latestCmd->status}\n";
    echo "AI response (first 600 chars):\n" . mb_substr((string) $latestCmd->response, 0, 600) . "\n\n";
    echo 'action_taken: ' . json_encode((array) ($latestCmd->action_taken ?? []), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
}

echo "═══ STEP 3: Re-mount AiChat and inspect pendingAction ═══\n";
$baselineCampaigns = Campaign::where('team_id', $team->id)->count();
echo "Baseline campaigns on team: {$baselineCampaigns}\n\n";

$component2 = new AiChat();
$component2->mount();
if ($component2->pendingAction) {
    echo "pendingAction detected:\n" . json_encode($component2->pendingAction, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
    echo "pendingActionSummary: {$component2->pendingActionSummary}\n\n";

    $actionType = $component2->pendingAction['action'] ?? null;
    echo "Action type detected: {$actionType}\n";

    if ($actionType !== 'send_bulk_message') {
        echo "⚠️  Expected 'send_bulk_message' but got '{$actionType}'. The AI misclassified the intent.\n\n";
    }

    echo "═══ STEP 4: AiChat::confirmAction() ═══\n";
    try {
        $component2->confirmAction();
        echo "confirmAction() returned without throwing.\n\n";
    } catch (\Throwable $e) {
        echo "confirmAction() THREW: {$e->getMessage()}\n";
        echo substr($e->getTraceAsString(), 0, 1200) . "\n";
    }
} else {
    echo "No pendingAction on re-mount.\n";
    echo "This means the AI responded conversationally instead of proposing a bulk-send action.\n\n";
}

echo "═══ STEP 5: Verify Campaign row was created ═══\n";
$postCampaigns = Campaign::where('team_id', $team->id)->count();
echo "Campaigns before: {$baselineCampaigns}\n";
echo "Campaigns after:  {$postCampaigns}\n\n";

if ($postCampaigns > $baselineCampaigns) {
    $newCampaign = Campaign::where('team_id', $team->id)
        ->orderByDesc('id')
        ->first();
    echo "✅ New Campaign created:\n";
    echo "  id:              {$newCampaign->id}\n";
    echo "  name:            {$newCampaign->name}\n";
    echo "  status:          {$newCampaign->status}\n";
    echo "  platform:        {$newCampaign->platform}\n";
    echo "  scheduled_at:    " . ($newCampaign->scheduled_at?->toIso8601String() ?? '(null)') . "\n";
    echo '  target_criteria: ' . json_encode($newCampaign->target_criteria, JSON_UNESCAPED_UNICODE) . "\n";
    echo '  message_template: ' . mb_substr((string) $newCampaign->message_template, 0, 200) . "\n\n";

    if ($newCampaign->status === 'scheduled' && $newCampaign->scheduled_at && $newCampaign->scheduled_at->gt(now())) {
        echo "✅ VERIFIED: Campaign is scheduled for the future — nothing sent to real customers.\n";
    } else {
        echo "⚠️  Campaign status / timing is NOT scheduled-future. Review manually before trusting.\n";
    }

    // CLEANUP — remove the test campaign so it doesn't fire tomorrow
    $deleted = $newCampaign->delete();
    echo "Cleanup: deleted test Campaign #{$newCampaign->id} (deleted={$deleted})\n";
} else {
    echo "❌ NO Campaign row was created. The AI chat did not execute the bulk-send.\n";
    echo "This is the 'break the UI and nothing happens' bug the user reported.\n";
}

echo "\n(End of test.)\n";

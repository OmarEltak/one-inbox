<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Livewire\AiChat;
use App\Models\DeepAnalysis;
use App\Models\Team;

$team = Team::find(2);
$chat = new class extends AiChat {
    public function callParse(Team $t, string $text): ?array { return $this->parseDeepAnalysisRequest($t, $text); }
};

$cases = [
    // User's exact 2026-10-10 prompt — should fall through to inline chat (return NULL)
    ['prompt' => 'read and analyze the last 100 messages for mishkah i want u to give me each name for the last 100 chats and what action our moderator did with it and what would u do instead or how would u have handled it as a proffesional',
     'expect' => 'NULL (per-contact review → inline chat path)',
     'route' => null],

    // Explicit audit of team should still route to agent_audit
    ['prompt' => 'audit how our moderators handled last week',
     'expect' => 'agent_audit',
     'route' => DeepAnalysis::MODE_AGENT_AUDIT],
    ['prompt' => 'evaluate our team performance',
     'expect' => 'agent_audit',
     'route' => DeepAnalysis::MODE_AGENT_AUDIT],

    // Theme summaries should route to customer_themes
    ['prompt' => 'analyze the last 500 contacts and find patterns',
     'expect' => 'customer_themes',
     'route' => DeepAnalysis::MODE_CUSTOMER_THEMES],

    // Explicit per-contact phrasings fall through to inline
    ['prompt' => 'give me each name and what the agent did',
     'expect' => 'NULL (per-contact)',
     'route' => null],
    ['prompt' => 'list each contact one by one',
     'expect' => 'NULL (per-contact)',
     'route' => null],
    ['prompt' => 'what would you do instead for each conversation',
     'expect' => 'NULL (per-contact)',
     'route' => null],
];

$pass = 0; $fail = 0;
echo "=== parser routing — null means inline-chat fallthrough ===\n";
foreach ($cases as $c) {
    $result = $chat->callParse($team, $c['prompt']);
    $gotRoute = $result === null ? null : ($result['mode'] ?? 'UNKNOWN');
    $ok = $gotRoute === $c['route'];
    $icon = $ok ? "PASS" : "FAIL";
    $gotLabel = $result === null ? 'NULL (inline)' : $gotRoute;
    echo "  $icon — expected={$c['expect']}  got=$gotLabel\n";
    echo "    prompt: " . substr($c['prompt'], 0, 70) . "...\n";
    $ok ? $pass++ : $fail++;
}
echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);

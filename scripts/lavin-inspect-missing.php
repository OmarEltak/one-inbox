<?php
/** Inspect the 19 missing-order candidates to extract clean data for push. */

use App\Models\Conversation;

$ids = [8937, 8939, 9210, 9241, 9254, 9314, 9325, 9355, 9357, 9381, 9387, 9424, 9433, 9436, 9460, 9500, 8944, 8946, 8959];

foreach ($ids as $id) {
    $c = Conversation::find($id);
    if (! $c) continue;
    echo "\n### conv#{$id} {$c->platform} contact={$c->contact?->name} stage={$c->sales_stage} ai_paused=" . ($c->ai_paused?'Y':'n') . "\n";
    echo "  captured: " . json_encode($c->captured_data, JSON_UNESCAPED_UNICODE) . "\n";
    foreach ($c->messages()->orderBy('id')->get() as $m) {
        echo "  " . $m->created_at->format('m-d H:i') . ' ' . substr($m->direction, 0, 3) . ' ' . $m->sender_type . ' "' . substr(preg_replace('/\s+/u', ' ', $m->content ?? ''), 0, 120) . "\"\n";
    }
}

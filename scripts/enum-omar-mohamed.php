<?php
echo PHP_EOL . 'All Omar Mohamed contacts on team 2:' . PHP_EOL;
foreach (App\Models\Contact::where('team_id', 2)->where('name', 'like', '%Omar Mohamed%')->get() as $c) {
    echo '  contact_id=' . $c->id . '  name="' . $c->name . '"  platform=' . $c->platform . '  platform_user_id=' . $c->platform_user_id . PHP_EOL;
    foreach (App\Models\Conversation::where('contact_id', $c->id)->get() as $cv) {
        $last = $cv->last_message_at ? $cv->last_message_at->toIso8601String() : 'null';
        $hoursAgo = $cv->last_message_at ? round(now()->diffInHours($cv->last_message_at, false) * -1, 1) : null;
        echo '    → conv_id=' . $cv->id . '  page_id=' . $cv->page_id . '  last_msg_at=' . $last . ' (' . $hoursAgo . 'h ago)  stage=' . $cv->sales_stage . PHP_EOL;
    }
}

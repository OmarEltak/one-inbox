<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Last 10 messages ===" . PHP_EOL;
$msgs = DB::table('messages')->orderBy('id','desc')->limit(10)->get(['id','direction','content','platform_message_id','conversation_id','created_at']);
foreach($msgs as $m) {
    echo $m->id . ' | ' . $m->direction . ' | conv=' . $m->conversation_id . ' | ' . substr($m->content,0,60) . ' | pid=' . $m->platform_message_id . ' | ' . $m->created_at . PHP_EOL;
}

echo PHP_EOL . "=== Failed jobs (last 5) ===" . PHP_EOL;
$jobs = DB::table('failed_jobs')->orderBy('id','desc')->limit(5)->get(['id','payload','exception','failed_at']);
foreach($jobs as $j) {
    $p = json_decode($j->payload, true);
    echo $j->id . ' | ' . ($p['displayName'] ?? 'unknown') . ' | ' . $j->failed_at . PHP_EOL;
    echo '  EX: ' . substr($j->exception, 0, 300) . PHP_EOL;
}

echo PHP_EOL . "=== Pending jobs ===" . PHP_EOL;
$pending = DB::table('jobs')->orderBy('id','desc')->limit(5)->get(['id','queue','payload','attempts','created_at']);
foreach($pending as $j) {
    $p = json_decode($j->payload, true);
    echo $j->id . ' | attempts=' . $j->attempts . ' | ' . ($p['displayName'] ?? 'unknown') . PHP_EOL;
}

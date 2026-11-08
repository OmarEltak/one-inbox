<?php
/**
 * 2026-11-09 — Full-field snapshot of OT1-Pro's AiConfig rows (8 + 10) for
 * complete rollback. The initial update scripts' inline backup only covered
 * 7 fields; this snapshot covers all mass-assignable fields so a precise
 * rollback is always possible.
 *
 * Writes to: /tmp/ot1_aiconfig_full_snapshot_<ts>.json
 *
 * Run on prod (idempotent, safe to re-run):
 *   cd /var/www/ot1-pro.com
 *   sudo -u deploy HOME=/home/deploy XDG_CONFIG_HOME=/home/deploy/.config \
 *     php artisan tinker --execute="require 'scripts/snapshot-ot1-aiconfig.php';"
 */

use App\Models\AiConfig;

$snap = [];
foreach ([8, 10] as $id) {
    $cfg = AiConfig::find($id);
    if ($cfg) {
        $snap[$id] = $cfg->toArray();
    }
}
$path = '/tmp/ot1_aiconfig_full_snapshot_' . date('Ymd_His') . '.json';
file_put_contents($path, json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "wrote full snapshot: {$path}\n";
echo "snapshot size: " . filesize($path) . " bytes\n";
echo "config ids captured: " . implode(', ', array_keys($snap)) . "\n";

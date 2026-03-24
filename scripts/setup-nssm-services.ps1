# setup-nssm-services.ps1
# Run this ONCE on Server A as Administrator to install all background services.
# On Server B/C: run this but skip the Reverb and Scheduler installs (queue only).
#
# Usage: Right-click PowerShell -> Run as Administrator -> paste this path and run

$ErrorActionPreference = "Stop"

# ── Config ──────────────────────────────────────────────────────────────────
$php = "php"   # uses PHP from PATH (Herd adds it automatically)
$app = "C:\Users\$env:USERNAME\Herd\one-inbox"

# Verify NSSM is available
if (-not (Get-Command nssm -ErrorAction SilentlyContinue)) {
    Write-Error "NSSM not found in PATH. Download from https://nssm.cc/download, put nssm.exe in C:\tools\ and add C:\tools\ to your PATH, then re-run this script."
    exit 1
}

# Verify app folder exists
if (-not (Test-Path $app)) {
    Write-Error "App folder not found at $app — make sure the app is cloned there first."
    exit 1
}

Write-Host "Installing OT1 Pro Windows services..." -ForegroundColor Cyan

# ── Queue Worker ─────────────────────────────────────────────────────────────
Write-Host "  Installing OneInboxQueue..."
nssm install OneInboxQueue $php "$app\artisan queue:work --sleep=3 --tries=3 --max-time=3600"
nssm set OneInboxQueue AppDirectory $app
nssm set OneInboxQueue AppStdout "$app\storage\logs\queue.log"
nssm set OneInboxQueue AppStderr "$app\storage\logs\queue-error.log"
nssm set OneInboxQueue Start SERVICE_AUTO_START
nssm start OneInboxQueue
Write-Host "  OneInboxQueue started." -ForegroundColor Green

# ── Reverb WebSocket Server (Server A only) ──────────────────────────────────
$isServerA = Read-Host "Is this Server A (the primary server with MySQL/Redis)? [y/n]"
if ($isServerA -eq 'y') {

    Write-Host "  Installing OneInboxReverb..."
    nssm install OneInboxReverb $php "$app\artisan reverb:start"
    nssm set OneInboxReverb AppDirectory $app
    nssm set OneInboxReverb AppStdout "$app\storage\logs\reverb.log"
    nssm set OneInboxReverb AppStderr "$app\storage\logs\reverb-error.log"
    nssm set OneInboxReverb Start SERVICE_AUTO_START
    nssm start OneInboxReverb
    Write-Host "  OneInboxReverb started." -ForegroundColor Green

    Write-Host "  Installing OneInboxScheduler..."
    nssm install OneInboxScheduler $php "$app\artisan schedule:work"
    nssm set OneInboxScheduler AppDirectory $app
    nssm set OneInboxScheduler AppStdout "$app\storage\logs\scheduler.log"
    nssm set OneInboxScheduler AppStderr "$app\storage\logs\scheduler-error.log"
    nssm set OneInboxScheduler Start SERVICE_AUTO_START
    nssm start OneInboxScheduler
    Write-Host "  OneInboxScheduler started." -ForegroundColor Green
}

# ── Summary ──────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "Done! Verifying services:" -ForegroundColor Cyan
nssm status OneInboxQueue
if ($isServerA -eq 'y') {
    nssm status OneInboxReverb
    nssm status OneInboxScheduler
}
Write-Host ""
Write-Host "All services will auto-start on reboot. No terminals needed." -ForegroundColor Green
Write-Host ""
Write-Host "To manage services later:"
Write-Host "  nssm status OneInboxQueue"
Write-Host "  nssm restart OneInboxQueue"
Write-Host "  nssm stop OneInboxQueue"

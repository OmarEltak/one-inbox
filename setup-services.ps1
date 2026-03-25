# setup-services.ps1
# Run ONCE on Server A as Administrator to install all background services.
# On Server B/C: run this but answer 'n' when asked if this is Server A.
#
# Usage: Right-click PowerShell -> Run as Administrator
#        & "C:\Users\NanoChip\Herd\one-inbox\setup-services.ps1"

$ErrorActionPreference = "Stop"

# ── Config ───────────────────────────────────────────────────────────────────
$php  = "C:\Users\NanoChip\.config\herd\bin\php84\php.exe"
$app  = "C:\Users\NanoChip\Herd\one-inbox"
$nssm = "C:\Windows\System32\nssm.exe"
$sc   = "C:\Windows\System32\sc.exe"

# ── Helpers ──────────────────────────────────────────────────────────────────
function Remove-ServiceFully($name) {
    & $sc stop $name 2>$null | Out-Null
    Start-Sleep -Milliseconds 500
    & $nssm remove $name confirm 2>$null | Out-Null
    # Force-remove from registry if stuck "marked for deletion"
    $regPath = "HKLM:\SYSTEM\CurrentControlSet\Services\$name"
    if (Test-Path $regPath) {
        Remove-Item -Path $regPath -Recurse -Force -ErrorAction SilentlyContinue
        Write-Host "    Force-removed $name from registry." -ForegroundColor Yellow
    }
    Start-Sleep -Milliseconds 500
}

function Install-Service($name, $args, $log) {
    Remove-ServiceFully $name
    & $nssm install $name $php "$app\artisan $args"
    & $nssm set $name AppDirectory $app
    & $nssm set $name AppStdout "$app\storage\logs\$log.log"
    & $nssm set $name AppStderr "$app\storage\logs\$log-error.log"
    & $nssm set $name Start SERVICE_AUTO_START
    & $nssm start $name
    Write-Host "  $name started." -ForegroundColor Green
}

# ── Verify ───────────────────────────────────────────────────────────────────
if (-not (Test-Path $php))  { Write-Error "PHP not found at $php"; exit 1 }
if (-not (Test-Path $app))  { Write-Error "App folder not found at $app"; exit 1 }
if (-not (Test-Path $nssm)) { Write-Error "NSSM not found at $nssm"; exit 1 }

Write-Host "Installing One Inbox Windows services..." -ForegroundColor Cyan

# ── Queue Worker (all servers) ───────────────────────────────────────────────
Write-Host "  Installing OneInboxQueue..."
Install-Service "OneInboxQueue" "queue:work --sleep=3 --tries=3 --max-time=3600" "queue"

# ── Reverb + Scheduler (Server A only) ───────────────────────────────────────
$isServerA = Read-Host "Is this Server A (primary server)? [y/n]"
if ($isServerA -eq 'y') {
    Write-Host "  Installing OneInboxReverb..."
    Install-Service "OneInboxReverb" "reverb:start --port=8080" "reverb"

    Write-Host "  Installing OneInboxScheduler..."
    Install-Service "OneInboxScheduler" "schedule:work" "scheduler"
}

# ── Summary ──────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "Status:" -ForegroundColor Cyan
& $nssm status OneInboxQueue
if ($isServerA -eq 'y') {
    & $nssm status OneInboxReverb
    & $nssm status OneInboxScheduler
}

Write-Host ""
Write-Host "All services will auto-start on every reboot. No terminals needed." -ForegroundColor Green
Write-Host ""
Write-Host "To manage services:"
Write-Host "  $nssm status  OneInboxQueue"
Write-Host "  $nssm restart OneInboxQueue"
Write-Host "  $nssm stop    OneInboxQueue"

Read-Host "Press Enter to exit"

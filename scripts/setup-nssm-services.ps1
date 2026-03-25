# setup-nssm-services.ps1
# Run ONCE on this machine as Administrator to install all background services.
# Installs services for BOTH dev (one-inbox) and prod (one-inbox-prod).
#
# Usage: Right-click PowerShell -> Run as Administrator
#        & "C:\Users\NanoChip\Herd\one-inbox\scripts\setup-nssm-services.ps1"

$ErrorActionPreference = "Continue"   # don't abort on nssm errors

# ── Config ──────────────────────────────────────────────────────────────────
$php     = "php"   # uses PHP from PATH (Herd adds it automatically)
$appDev  = "C:\Users\$env:USERNAME\Herd\one-inbox"
$appProd = "C:\Users\$env:USERNAME\Herd\one-inbox-prod"
$nssm    = "C:\Windows\System32\nssm.exe"
$sc      = "C:\Windows\System32\sc.exe"

# Verify NSSM is available
if (-not (Get-Command nssm -ErrorAction SilentlyContinue)) {
    Write-Error "NSSM not found in PATH. Download from https://nssm.cc/download, put nssm.exe in C:\Windows\System32\, then re-run."
    exit 1
}

if (-not (Test-Path $appDev))  { Write-Error "Dev app not found at $appDev";  exit 1 }
if (-not (Test-Path $appProd)) { Write-Error "Prod app not found at $appProd"; exit 1 }

# ── Helpers ──────────────────────────────────────────────────────────────────
function Remove-ServiceFully($name) {
    $svc = Get-Service -Name $name -ErrorAction SilentlyContinue
    if (-not $svc) { return }

    & $sc stop $name 2>&1 | Out-Null
    Start-Sleep -Milliseconds 800

    & $sc delete $name 2>&1 | Out-Null
    Start-Sleep -Milliseconds 800

    $regPath = "HKLM:\SYSTEM\CurrentControlSet\Services\$name"
    if (Test-Path $regPath) {
        Remove-Item -Path $regPath -Recurse -Force -ErrorAction SilentlyContinue
        Write-Host "    Force-removed $name from registry." -ForegroundColor Yellow
        Start-Sleep -Milliseconds 1500
    }
}

function Install-Service($name, $app, $cmd, $log) {
    Write-Host "  Installing $name..." -NoNewline
    Remove-ServiceFully $name

    # Retry up to 3 times (SCM can be slow to release "marked for deletion")
    $tries = 0
    do {
        $out = & $nssm install $name $php "$app\artisan $cmd" 2>&1
        $tries++
        if ($LASTEXITCODE -ne 0 -and $tries -lt 3) { Start-Sleep -Seconds 2 }
    } while ($LASTEXITCODE -ne 0 -and $tries -lt 3)

    if ($LASTEXITCODE -ne 0) {
        Write-Host " FAILED (reboot and re-run if this persists)" -ForegroundColor Red
        return
    }

    & $nssm set $name AppDirectory $app                             2>&1 | Out-Null
    & $nssm set $name AppStdout "$app\storage\logs\$log.log"        2>&1 | Out-Null
    & $nssm set $name AppStderr "$app\storage\logs\$log-error.log"  2>&1 | Out-Null
    & $nssm set $name Start SERVICE_AUTO_START                      2>&1 | Out-Null
    & $nssm start $name                                             2>&1 | Out-Null
    Write-Host " OK" -ForegroundColor Green
}

# ── Install services ─────────────────────────────────────────────────────────
Write-Host "Installing One Inbox Windows services (dev + prod)..." -ForegroundColor Cyan

Write-Host ""
Write-Host "[DEV - one-inbox]" -ForegroundColor Yellow
Install-Service "OneInboxQueue"     $appDev  "queue:work --sleep=3 --tries=3 --max-time=3600" "queue"
Install-Service "OneInboxReverb"    $appDev  "reverb:start --port=8080"                        "reverb"
Install-Service "OneInboxScheduler" $appDev  "schedule:work"                                   "scheduler"

Write-Host ""
Write-Host "[PROD - one-inbox-prod]" -ForegroundColor Yellow
Install-Service "OneInboxQueueProd"     $appProd "queue:work --sleep=3 --tries=3 --max-time=3600" "queue"
Install-Service "OneInboxSchedulerProd" $appProd "schedule:work"                                  "scheduler"

# ── Summary ──────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "Status:" -ForegroundColor Cyan
Write-Host "  DEV:"
& $nssm status OneInboxQueue
& $nssm status OneInboxReverb
& $nssm status OneInboxScheduler
Write-Host "  PROD:"
& $nssm status OneInboxQueueProd
& $nssm status OneInboxSchedulerProd

Write-Host ""
Write-Host "All services auto-start on reboot. No terminals needed." -ForegroundColor Green
Write-Host ""
Write-Host "To manage services:"
Write-Host "  nssm status  OneInboxQueueProd"
Write-Host "  nssm restart OneInboxQueueProd"
Write-Host "  nssm stop    OneInboxQueueProd"

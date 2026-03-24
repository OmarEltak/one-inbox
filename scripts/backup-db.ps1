# backup-db.ps1
# Backs up the SQLite database daily to OneDrive.
# Schedule this via Windows Task Scheduler to run daily at 3am.
#
# Setup: Task Scheduler -> Create Basic Task
#   Program:   powershell.exe
#   Arguments: -NonInteractive -File "C:\Users\NanoChip\Herd\one-inbox\scripts\backup-db.ps1"

$src     = "C:\Users\NanoChip\Herd\one-inbox\database\database.sqlite"
$backups = "C:\Users\NanoChip\OneDrive\Backups\one-inbox"
$logFile = "C:\Users\NanoChip\OneDrive\Backups\one-inbox\backup.log"
$date    = Get-Date -Format 'yyyy-MM-dd'
$dst     = "$backups\db-$date.sqlite"

# Create backup folder if it doesn't exist
if (-not (Test-Path $backups)) {
    New-Item -ItemType Directory -Path $backups | Out-Null
}

try {
    # Copy the database file
    Copy-Item $src $dst -Force
    $msg = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm')] OK — backed up to $dst"
    Write-Host $msg
    Add-Content $logFile $msg
} catch {
    $msg = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm')] ERROR — $_"
    Write-Host $msg -ForegroundColor Red
    Add-Content $logFile $msg
    exit 1
}

# Keep only the last 30 backups
$old = Get-ChildItem "$backups\db-*.sqlite" | Sort-Object LastWriteTime | Select-Object -SkipLast 30
if ($old) {
    $old | Remove-Item -Force
    $msg = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm')] Cleaned up $($old.Count) old backup(s)"
    Add-Content $logFile $msg
}

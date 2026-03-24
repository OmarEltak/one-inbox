$ErrorActionPreference = "Stop"
Set-Location "C:\Users\NanoChip\Herd\one-inbox-prod"

Write-Host "==> Pulling latest code..."
git pull origin main

Write-Host "==> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

Write-Host "==> Installing Node dependencies..."
npm ci

Write-Host "==> Building assets..."
npm run build

Write-Host "==> Running migrations..."
php artisan migrate --force

Write-Host "==> Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host "==> Restarting services..."
nssm restart OneInboxQueue
nssm restart OneInboxReverb

Write-Host "==> Done."

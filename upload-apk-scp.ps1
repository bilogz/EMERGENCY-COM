# Upload APK to Server using SCP
# Run this script from Windows PowerShell (not from SSH session)

$localFile = "C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk"
$serverUser = "root"  # Change this to your SSH username if different
$serverHost = "alertaraqc.com"  # Or use IP address if you have it
$serverPath = "/var/www/html/emergency_communication_alertaraqc/USERS/downloads/emergency-comms-app.apk"

Write-Host "=== Uploading APK to Server ===" -ForegroundColor Cyan
Write-Host "Local file: $localFile" -ForegroundColor Yellow
Write-Host "Server: $serverUser@$serverHost" -ForegroundColor Yellow
Write-Host "Destination: $serverPath" -ForegroundColor Yellow
Write-Host ""

# Check if file exists
if (-not (Test-Path $localFile)) {
    Write-Host "ERROR: File not found at $localFile" -ForegroundColor Red
    exit 1
}

Write-Host "File size: $([math]::Round((Get-Item $localFile).Length / 1MB, 2)) MB" -ForegroundColor Green
Write-Host ""

# Upload using SCP
Write-Host "Uploading... (you will be prompted for SSH password)" -ForegroundColor Yellow
scp $localFile "${serverUser}@${serverHost}:${serverPath}"

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✓ Upload successful!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. SSH into server and verify:" -ForegroundColor White
    Write-Host "   ssh $serverUser@$serverHost" -ForegroundColor Gray
    Write-Host "   ls -lh $serverPath" -ForegroundColor Gray
    Write-Host ""
    Write-Host "2. Set permissions:" -ForegroundColor White
    Write-Host "   chmod 644 $serverPath" -ForegroundColor Gray
    Write-Host ""
    Write-Host "3. Test download URL:" -ForegroundColor White
    Write-Host "   https://emergency-comm.alertaraqc.com/USERS/downloads/emergency-comms-app.apk" -ForegroundColor Gray
} else {
    Write-Host ""
    Write-Host "✗ Upload failed. Error code: $LASTEXITCODE" -ForegroundColor Red
    Write-Host ""
    Write-Host "Troubleshooting:" -ForegroundColor Yellow
    Write-Host "- Make sure SSH/SCP is enabled on your server" -ForegroundColor White
    Write-Host "- Check your SSH username (currently set to: $serverUser)" -ForegroundColor White
    Write-Host "- Verify server hostname/IP is correct" -ForegroundColor White
    Write-Host "- Check if port 22 is open in your firewall" -ForegroundColor White
}



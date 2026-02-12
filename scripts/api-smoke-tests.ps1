param(
    [string]$BaseUrl = "https://emergency-comm.alertaraqc.com",
    [string]$Email = "",
    [string]$Password = "",
    [string]$DeviceId = "apk-smoke-device-001",
    [string]$FcmToken = "apk-smoke-fcm-token"
)

function Invoke-ApiPost {
    param([string]$Url, [hashtable]$Payload)
    try {
        $json = $Payload | ConvertTo-Json -Depth 10
        $resp = Invoke-RestMethod -Method Post -Uri $Url -ContentType "application/json" -Body $json
        return @{ ok = $true; data = $resp }
    } catch {
        $message = $_.Exception.Message
        if ($_.Exception.Response) {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $message = $reader.ReadToEnd()
            $reader.Close()
        }
        return @{ ok = $false; data = $message }
    }
}

Write-Output "=== API Smoke Test ==="
Write-Output "Base URL: $BaseUrl"

$googleProbe = Invoke-ApiPost -Url "$BaseUrl/PHP/api/login.php" -Payload @{
    email = "probe@example.com"
    google_token = "invalid_probe_token"
}
Write-Output "Google login probe reached endpoint: $($googleProbe.ok -or ($googleProbe.data -match 'Google|Invalid|configured|token'))"
Write-Output ($googleProbe.data | ConvertTo-Json -Depth 5)

if (-not [string]::IsNullOrWhiteSpace($Email) -and -not [string]::IsNullOrWhiteSpace($Password)) {
    $login = Invoke-ApiPost -Url "$BaseUrl/PHP/api/login.php" -Payload @{
        email = $Email
        password = $Password
        device_id = $DeviceId
        device_type = "android"
        device_name = "apk-smoke"
        fcm_token = $FcmToken
    }
    Write-Output "Password login success: $($login.ok)"
    Write-Output ($login.data | ConvertTo-Json -Depth 6)
} else {
    Write-Output "Password login test skipped (pass -Email and -Password to enable)."
}

$tokenUpdate = Invoke-ApiPost -Url "$BaseUrl/PHP/api/production%20api/update_fcm_token.php" -Payload @{
    user_id = 1
    device_id = $DeviceId
    fcm_token = $FcmToken
}
Write-Output "FCM token update endpoint success: $($tokenUpdate.ok)"
Write-Output ($tokenUpdate.data | ConvertTo-Json -Depth 5)

Write-Output "=== Done ==="

<?php
/**
 * Device and IP Tracking Helper Functions
 */

/**
 * Get client IP address
 * Handles proxies and load balancers
 */
function getClientIP() {
    $ipKeys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Parse user agent to get device information
 */
function getDeviceInfo($userAgent = null) {
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    if (empty($userAgent)) {
        return [
            'device_type' => 'Unknown',
            'browser' => 'Unknown',
            'os' => 'Unknown',
            'user_agent' => ''
        ];
    }
    
    $deviceInfo = [
        'device_type' => 'Desktop',
        'browser' => 'Unknown',
        'os' => 'Unknown',
        'user_agent' => $userAgent
    ];
    
    // Detect device type
    if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
        $deviceInfo['device_type'] = 'Mobile';
    } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
        $deviceInfo['device_type'] = 'Tablet';
    }
    
    // Detect browser
    if (preg_match('/chrome/i', $userAgent) && !preg_match('/edg/i', $userAgent)) {
        $deviceInfo['browser'] = 'Chrome';
    } elseif (preg_match('/edg/i', $userAgent)) {
        $deviceInfo['browser'] = 'Edge';
    } elseif (preg_match('/firefox/i', $userAgent)) {
        $deviceInfo['browser'] = 'Firefox';
    } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
        $deviceInfo['browser'] = 'Safari';
    } elseif (preg_match('/opera|opr/i', $userAgent)) {
        $deviceInfo['browser'] = 'Opera';
    } elseif (preg_match('/msie|trident/i', $userAgent)) {
        $deviceInfo['browser'] = 'Internet Explorer';
    }
    
    // Detect OS
    if (preg_match('/windows/i', $userAgent)) {
        $deviceInfo['os'] = 'Windows';
        if (preg_match('/windows nt 10/i', $userAgent)) {
            $deviceInfo['os'] = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6.3/i', $userAgent)) {
            $deviceInfo['os'] = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6.2/i', $userAgent)) {
            $deviceInfo['os'] = 'Windows 8';
        } elseif (preg_match('/windows nt 6.1/i', $userAgent)) {
            $deviceInfo['os'] = 'Windows 7';
        }
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $deviceInfo['os'] = 'macOS';
    } elseif (preg_match('/linux/i', $userAgent)) {
        $deviceInfo['os'] = 'Linux';
    } elseif (preg_match('/android/i', $userAgent)) {
        $deviceInfo['os'] = 'Android';
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        $deviceInfo['os'] = 'iOS';
    }
    
    return $deviceInfo;
}

/**
 * Format device info as JSON string for database storage
 */
function formatDeviceInfoForDB($userAgent = null) {
    $deviceInfo = getDeviceInfo($userAgent);
    return json_encode([
        'device_type' => $deviceInfo['device_type'],
        'browser' => $deviceInfo['browser'],
        'os' => $deviceInfo['os']
    ], JSON_UNESCAPED_UNICODE);
}


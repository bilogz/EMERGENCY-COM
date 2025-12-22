<?php
/**
 * Guest Monitoring Notice
 * Displays a persistent notice for guest users about monitoring
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is a guest
$isGuest = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'guest';
$monitoringEnabled = isset($_SESSION['guest_monitoring']) && $_SESSION['guest_monitoring'] === true;

if ($isGuest && $monitoringEnabled):
?>
<div class="guest-monitoring-banner" id="guestMonitoringBanner">
    <div class="monitoring-content">
        <div class="monitoring-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="monitoring-text">
            <strong><i class="fas fa-exclamation-triangle"></i> Anonymous Guest Session - Monitoring Active</strong>
            <span>All actions including emergency calls are monitored and logged for security. Your IP address and device information are being tracked.</span>
        </div>
        <button type="button" class="monitoring-close" onclick="document.getElementById('guestMonitoringBanner').style.display='none'; localStorage.setItem('guest_banner_dismissed', 'true');">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<style>
.guest-monitoring-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 0.75rem 1rem;
    z-index: 1500;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.monitoring-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.monitoring-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.monitoring-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.monitoring-text strong {
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.monitoring-text span {
    font-size: 0.85rem;
    opacity: 0.9;
}

.monitoring-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
    flex-shrink: 0;
}

.monitoring-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

@media (max-width: 768px) {
    .guest-monitoring-banner {
        padding: 0.5rem;
    }
    
    .monitoring-content {
        gap: 0.5rem;
    }
    
    .monitoring-icon {
        font-size: 1.2rem;
    }
    
    .monitoring-text strong {
        font-size: 0.85rem;
    }
    
    .monitoring-text span {
        font-size: 0.75rem;
    }
    
    .monitoring-close {
        width: 28px;
        height: 28px;
    }
}
</style>

<script>
// Check if banner was dismissed
if (localStorage.getItem('guest_banner_dismissed') === 'true') {
    document.getElementById('guestMonitoringBanner').style.display = 'none';
}
</script>
<?php
endif;
?>


<?php
/**
 * Admin compatibility wrapper for emergency alert polling.
 *
 * The global emergency alert widget runs on ADMIN pages but reads alerts
 * from the USERS API endpoint.
 */

require_once __DIR__ . '/../../USERS/api/get-alerts.php';

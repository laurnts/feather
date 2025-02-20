<!-- Use this index.php within the public root when placing the project within a subdirectory -->

<?php
// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/development/var/debug.log');

// Environment Configuration
define('DEBUG_MODE', true);

if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("[App] Starting application");
    error_log("[App] Request URI: " . $_SERVER['REQUEST_URI']);
}

// Check if request is for development
if (strpos($_SERVER['REQUEST_URI'], '/development/') === 0) {
    // Forward to development index.php
    require __DIR__ . '/development/index.php';
    exit;
}

// If not development, show the main site
require __DIR__ . '/index.html';
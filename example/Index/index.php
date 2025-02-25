<?php
// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/var/debug.log');

// Set debug mode (set to false in production)
define('DEBUG_MODE', false);

if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("[App] Starting application");
}

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Laurnts\Feather\Router\Router;

// Initialize router with project root
$router = new Router(__DIR__);

// Add 404 handler
$router->setNotFound(function() use ($router) {
    $router->setPageConfig([
        'page_title' => '404 - Not Found',
        'meta_description' => 'The requested page could not be found'
    ]);
    require_once __DIR__ . '/pages/404.php';
});

// Dispatch the route
$router->dispatch();
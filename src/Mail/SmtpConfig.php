<?php

namespace Laurnts\Feather\Mail;

use Laurnts\Feather\Router\Router;

class SmtpConfig {
    private static $config = null;
    private static $router;
    
    public static function setRouter(Router $router) {
        self::$router = $router;
    }
    
    public static function get(): array {
        if (self::$config === null) {
            if (!self::$router) {
                throw new \Exception('Router not set. Call SmtpConfig::setRouter() first.');
            }
            
            $envFile = self::$router->getProjectRoot() . '/env.php';
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[SmtpConfig] Looking for config in: " . $envFile);
            }
            
            if (!file_exists($envFile)) {
                error_log("[SmtpConfig] ERROR: env.php not found in project root");
                throw new \Exception('env.php not found in project root');
            }
            
            $env = include $envFile;
            
            if (!isset($env['smtp'])) {
                error_log("[SmtpConfig] ERROR: SMTP configuration not found in env.php");
                throw new \Exception('SMTP configuration not found in env.php');
            }
            
            $config = $env['smtp'];
            
            // Override password with environment variable if set
            if (getenv('SMTP_PASSWORD')) {
                $config['password'] = getenv('SMTP_PASSWORD');
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("[SmtpConfig] Using password from environment variable");
                }
            }
            
            // Validate required fields
            $required = ['to_email', 'host', 'username', 'password', 'secure', 'port'];
            foreach ($required as $field) {
                if (empty($config[$field])) {
                    error_log("[SmtpConfig] ERROR: Missing required field: " . $field);
                    throw new \Exception("SMTP configuration missing required field: {$field}");
                }
            }
            
            self::$config = $config;
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[SmtpConfig] Configuration loaded successfully");
            }
        }
        return self::$config;
    }
    
    public static function set(array $config): void {
        self::$config = array_merge(self::get(), $config);
    }
} 
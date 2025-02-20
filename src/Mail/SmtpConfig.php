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
                $projectRoot = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
            } else {
                $projectRoot = self::$router->getProjectRoot();
            }
            
            $envFile = $projectRoot . '/env.php';
            if (!file_exists($envFile)) {
                throw new \Exception('env.php not found in project root');
            }
            
            $env = include $envFile;
            
            if (!isset($env['smtp'])) {
                throw new \Exception('SMTP configuration not found in env.php');
            }
            
            $config = $env['smtp'];
            
            // Override password with environment variable if set
            if (getenv('SMTP_PASSWORD')) {
                $config['password'] = getenv('SMTP_PASSWORD');
            }
            
            // Validate required fields
            $required = ['to_email', 'host', 'username', 'password', 'secure', 'port'];
            foreach ($required as $field) {
                if (empty($config[$field])) {
                    throw new \Exception("SMTP configuration missing required field: {$field}");
                }
            }
            
            self::$config = $config;
        }
        return self::$config;
    }
    
    public static function set(array $config): void {
        self::$config = array_merge(self::get(), $config);
    }
} 
<?php

namespace Laurnts\Feather\Mail;

class SmtpConfig {
    private static $config = null;
    
    public static function get(): array {
        if (self::$config === null) {
            $env = include __DIR__ . '/../../../../env.php';
            
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
<?php

namespace Laurnts\Feather\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Laurnts\Feather\Router\Router;

class Smtp {
    private $mailer;
    private $config;
    private static $router;
    
    public function __construct() {
        $this->config = SmtpConfig::get();
        $this->initializeMailer();
    }
    
    public static function setRouter(Router $router) {
        self::$router = $router;
    }
    
    private function initializeMailer(): void {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth = true;
        $this->mailer->Host = $this->config['host'];
        $this->mailer->Username = $this->config['username'];
        $this->mailer->Password = $this->config['password'];
        $this->mailer->SMTPSecure = $this->config['secure'];
        $this->mailer->Port = $this->config['port'];
        $this->mailer->CharSet = 'UTF-8';
    }
    
    private function validateRateLimit(): void {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $current_time = time();
        $session_time = $_SESSION['last_email_time'] ?? 0;
        $time_diff = $current_time - $session_time;
        
        if ($time_diff < 60) {
            throw new \Exception('Please wait ' . (60 - $time_diff) . ' seconds before sending another message.');
        }
    }
    
    private function validateInput(string $name, string $email, string $message): void {
        if (strlen($name) < 2 || strlen($name) > 50) {
            throw new \Exception('Name must be between 2 and 50 characters');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            throw new \Exception('Please enter a valid email address');
        }
        
        if (strlen($message) < 10 || strlen($message) > 2000) {
            throw new \Exception('Message must be between 10 and 2000 characters');
        }
        
        // Check for spam
        $spam_keywords = ['viagra', 'cialis', 'casino', 'porn', 'sex', 'http://', 'https://', '[url]', '[link]'];
        foreach ($spam_keywords as $keyword) {
            if (stripos($message, $keyword) !== false || stripos($name, $keyword) !== false) {
                throw new \Exception('Message contains inappropriate content');
            }
        }
    }
    
    private function sanitizeInput(string $input): string {
        $input = trim(filter_var($input, FILTER_SANITIZE_STRING));
        $input = strip_tags($input);
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    public function send(string $name, string $email, string $message): bool {
        try {
            // Check rate limit
            $this->validateRateLimit();
            
            // Sanitize inputs
            $name = $this->sanitizeInput($name);
            $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
            $message = $this->sanitizeInput($message);
            
            // Validate inputs
            $this->validateInput($name, $email, $message);
            
            // Set email parameters
            $this->mailer->setFrom($this->config['username'], $name);
            $this->mailer->addReplyTo($email, $name);
            $this->mailer->addAddress($this->config['to_email']);
            $this->mailer->Subject = 'Message from website ' . $_SERVER['SERVER_NAME'];
            
            // Build email body
            $this->mailer->Body = "New message from your website contact form:\r\n\n"
                               . "Name: " . $name . "\r\n"
                               . "Email: " . $email . "\r\n"
                               . "Message:\r\n" . $message . "\r\n\n"
                               . "Sent from: " . $_SERVER['REMOTE_ADDR'] . "\r\n"
                               . "Browser: " . $_SERVER['HTTP_USER_AGENT'];
            
            $this->mailer->WordWrap = 80;
            $this->mailer->IsHTML(false);
            
            if ($this->mailer->send()) {
                // Update rate limit timestamp
                $_SESSION['last_email_time'] = time();
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log("Mail error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function handleRequest(): void {
        // Check if it's a POST request
        if (!$_POST) {
            self::jsonResponse('error', 'Invalid request method');
        }
        
        // Check if it's an AJAX request
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            self::jsonResponse('error', 'Invalid request method');
        }
        
        // Check required fields
        if (!isset($_POST["userName"]) || !isset($_POST["userEmail"]) || !isset($_POST["userMessage"])) {
            self::jsonResponse('error', 'All fields are required');
        }
        
        try {
            $smtp = new self();
            $success = $smtp->send(
                $_POST["userName"],
                $_POST["userEmail"],
                $_POST["userMessage"]
            );
            
            self::jsonResponse('message', 'Thank you for your message! We will get back to you soon.');
            
        } catch (\Exception $e) {
            self::jsonResponse('error', $e->getMessage());
        }
    }
    
    private static function jsonResponse(string $type, string $text): void {
        header('Content-Type: application/json');
        die(json_encode(compact('type', 'text')));
    }
}

// Handle request if this file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    if (!self::$router) {
        $projectRoot = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        require_once $projectRoot . '/vendor/autoload.php';
    }
    Smtp::handleRequest();
} 
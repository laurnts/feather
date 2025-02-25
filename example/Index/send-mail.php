<?php
// Buffer output to ensure no content is sent before headers
ob_start();

// Define DEBUG_MODE constant if not already defined
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/var/debug.log');

// Prevent PHP errors/warnings from being displayed in AJAX responses
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// Start session for rate limiting
if (!isset($_SESSION)) {
    session_start();
}

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Laurnts\Feather\Router\Router;
use Laurnts\Feather\Mail\Smtp;
use Laurnts\Feather\Mail\SmtpConfig;

// Check if this is a direct browser visit (not an AJAX POST request)
$is_direct_visit = !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest';

// If this is a direct browser visit, display a message instead of processing
if ($is_direct_visit) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("[send-mail.php] Direct browser GET visit detected");
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Contact Form Handler</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; }
                .container { max-width: 800px; margin: 0 auto; background: #f7f7f7; padding: 20px; border-radius: 5px; }
                h1 { color: #444; }
                pre { background: #eee; padding: 10px; border-radius: 3px; overflow: auto; }
                .note { background: #fffde7; padding: 10px; border-left: 4px solid #ffd600; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Contact Form Handler</h1>
                <div class="note">
                    <p><strong>Note:</strong> This endpoint is meant to be accessed via AJAX POST requests from the contact form, not directly in the browser.</p>
                </div>
                <p>To test the contact form, please:</p>
                <ol>
                    <li>Go to the <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/contact"; ?>">contact page</a></li>
                    <li>Fill out the form</li>
                    <li>Click "Send Message"</li>
                </ol>
                <h2>Debug Information</h2>
                <pre>
Request Method: <?php echo $_SERVER['REQUEST_METHOD']; ?>
Expected: POST via XMLHttpRequest
Script Path: <?php echo __FILE__; ?>
Server Protocol: <?php echo $_SERVER['SERVER_PROTOCOL']; ?>
Request URI: <?php echo $_SERVER['REQUEST_URI']; ?>
                </pre>
                
                <div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 20px;">
                    <h3>Test AJAX Request</h3>
                    <p>Click the button below to test if this endpoint can be reached via AJAX:</p>
                    <button id="test-ajax" style="background: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 3px; cursor: pointer;">Test AJAX Request</button>
                    <div id="ajax-result" style="margin-top: 10px;"></div>
                    
                    <script>
                    document.getElementById('test-ajax').addEventListener('click', function() {
                        var result = document.getElementById('ajax-result');
                        result.innerHTML = 'Sending test request...';
                        
                        var formData = new FormData();
                        formData.append('userName', 'Test User');
                        formData.append('userEmail', 'test@example.com');
                        formData.append('userMessage', 'This is a test message from the debug page.');
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            // First log and keep the raw text response for debugging
                            return response.text().then(text => {
                                console.log('Raw response:', text);
                                
                                // Display the raw response for debugging
                                var rawResponseDisplay = document.createElement('div');
                                rawResponseDisplay.innerHTML = '<h4>Raw Server Response:</h4><pre style="background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;font-size:12px;">' + 
                                    text.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre>';
                                result.appendChild(rawResponseDisplay);
                                
                                // Try to parse as JSON
                                try {
                                    return JSON.parse(text);
                                } catch (e) {
                                    console.error('JSON parse error:', e);
                                    throw new Error('Server response is not valid JSON. See raw response above.');
                                }
                            });
                        })
                        .then(data => {
                            // Add the JSON result if parse was successful
                            var jsonResponseDisplay = document.createElement('div');
                            jsonResponseDisplay.innerHTML = '<h4>Parsed JSON Response:</h4><pre style="background:#e6ffe6;padding:10px;">' + 
                                JSON.stringify(data, null, 2) + '</pre>';
                            result.appendChild(jsonResponseDisplay);
                        })
                        .catch(error => {
                            // Show error but don't clear the previous content (raw response)
                            var errorDisplay = document.createElement('div');
                            errorDisplay.innerHTML = '<h4>Error:</h4><pre style="background:#ffe6e6;padding:10px;">Error: ' + 
                                error.message + '</pre>';
                            result.appendChild(errorDisplay);
                        });
                    });
                    </script>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST without proper headers - this could be a form submission without AJAX
        error_log("[send-mail.php] Direct browser POST visit detected without proper headers");
        header('Content-Type: application/json');
        echo json_encode([
            'type' => 'error',
            'text' => 'This endpoint requires an XMLHttpRequest header. Please use the contact form on the website.'
        ]);
        exit;
    }
}

try {
    // Initialize router
    $router = new Router(__DIR__);
    
    // Set up SMTP with router
    Smtp::setRouter($router);
    SmtpConfig::setRouter($router);
    
    // Log request received with POST data
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("[send-mail.php] Form submission received with data: " . 
            json_encode([
                'method' => $_SERVER['REQUEST_METHOD'],
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
                'has_post_data' => !empty($_POST),
                'post_data_keys' => array_keys($_POST),
                'has_userName' => isset($_POST['userName']),
                'has_userEmail' => isset($_POST['userEmail']),
                'has_userMessage' => isset($_POST['userMessage'])
            ])
        );
    }
    
    // Check required fields manually
    if (!isset($_POST["userName"]) || !isset($_POST["userEmail"]) || !isset($_POST["userMessage"])) {
        error_log("[send-mail.php] ERROR: Missing required fields");
        header('Content-Type: application/json');
        echo json_encode([
            'type' => 'error',
            'text' => 'All fields are required'
        ]);
        exit;
    }
    
    // Process mail directly
    $smtp = new Smtp();
    $smtp->send(
        $_POST["userName"],
        $_POST["userEmail"],
        $_POST["userMessage"]
    );
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'type' => 'message',
        'text' => 'Thank you for your message! We will get back to you soon.'
    ]);
    exit;
} catch (\Exception $e) {
    error_log("[send-mail.php] ERROR: " . $e->getMessage());
    
    // Get exception details for debugging
    $error_details = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => DEBUG_MODE ? $e->getTraceAsString() : 'hidden in production'
    ];
    
    error_log("[send-mail.php] Exception details: " . json_encode($error_details));
    
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'type' => 'error',
        'text' => $e->getMessage(),
        'details' => DEBUG_MODE ? $error_details : null
    ]);
    exit;
} 
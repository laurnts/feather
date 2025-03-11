<?php
// Buffer output to ensure no content is sent before headers
ob_start();

// Define DEBUG_MODE constant if not already defined
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

// Set shorter timeout for SMTP operations
define('SMTP_TIMEOUT', 10); // 10 seconds max for SMTP operations

// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/var/debug.log');
error_reporting(E_ALL);

// Set execution time limit to prevent hanging
set_time_limit(15); // Shorter timeout

// Log request information for debugging
$timestamp = date('Y-m-d H:i:s');
error_log("[$timestamp] === NEW REQUEST TO send-mail.php ===");
error_log("[$timestamp] Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("[$timestamp] Content Type: " . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'not set'));
error_log("[$timestamp] Request Headers: " . json_encode(getallheaders()));

// Load raw POST data
$raw_input = file_get_contents('php://input');
error_log("[$timestamp] Raw input: " . substr($raw_input, 0, 200) . (strlen($raw_input) > 200 ? '...' : ''));

// Log POST data
error_log("[$timestamp] POST data: " . json_encode($_POST));

// Log SERVER variables
error_log("[$timestamp] Server variables: " . json_encode([
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'], 
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
    'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE']
]));

// Start session for rate limiting
if (!isset($_SESSION)) {
    session_start();
}

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Laurnts\Feather\Router\Router;
use Laurnts\Feather\Mail\Smtp;
use Laurnts\Feather\Mail\SmtpConfig;

/**
 * Function to verify SMTP credentials without sending an email
 * @return array Result with success status and message
 */
function verifySmtpCredentials() {
    try {
        // Get SMTP config
        $config = SmtpConfig::get();
        
        // Create PHPMailer instance without exceptions for connection test
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(false);
        $mailer->isSMTP();
        $mailer->SMTPAuth = true;
        $mailer->Host = $config['host'];
        $mailer->Username = $config['username'];
        $mailer->Password = $config['password'];
        $mailer->SMTPSecure = $config['secure'];
        $mailer->Port = $config['port'];
        
        // Set shorter timeout for connection test
        $mailer->Timeout = 5;
        
        // Try to connect to SMTP server
        $connected = $mailer->smtpConnect();
        
        if ($connected) {
            $mailer->smtpClose();
            return ['success' => true, 'message' => 'SMTP credentials are valid'];
        }
        
        return ['success' => false, 'message' => 'Failed to connect to SMTP server'];
    } catch (\Exception $e) {
        return ['success' => false, 'message' => 'SMTP credential check error: ' . $e->getMessage()];
    }
}

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
    error_log("[$timestamp] Initializing Router...");
    $router = new Router(__DIR__);
    
    // Set up SMTP with router
    error_log("[$timestamp] Setting up SMTP with Router...");
    Smtp::setRouter($router);
    SmtpConfig::setRouter($router);
    
    // If data was sent as JSON, parse it and merge with $_POST
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($content_type, 'application/json') !== false) {
        $json_data = json_decode($raw_input, true);
        if ($json_data) {
            $_POST = array_merge($_POST, $json_data);
            error_log("[$timestamp] Merged JSON data with POST: " . json_encode($_POST));
        }
    }
    
    // Log processed POST data
    error_log("[$timestamp] Final POST data: " . json_encode($_POST));
    
    // Check required fields
    if (empty($_POST["userName"]) || empty($_POST["userEmail"]) || empty($_POST["userMessage"])) {
        error_log("[$timestamp] Missing required fields");
        error_log("[$timestamp] userName: " . (isset($_POST["userName"]) ? 'set' : 'not set'));
        error_log("[$timestamp] userEmail: " . (isset($_POST["userEmail"]) ? 'set' : 'not set'));
        error_log("[$timestamp] userMessage: " . (isset($_POST["userMessage"]) ? 'set' : 'not set'));
        
        throw new Exception('All fields are required');
    }
    
    // Log the message to a backup file in case SMTP fails
    $backup_log = __DIR__ . '/var/contact_messages.log';
    $message_log = sprintf(
        "[%s] From: %s <%s>\nMessage: %s\n\n",
        $timestamp,
        $_POST["userName"],
        $_POST["userEmail"],
        $_POST["userMessage"]
    );
    file_put_contents($backup_log, $message_log, FILE_APPEND);
    error_log("[$timestamp] Message logged to backup file");
    
    // Verify SMTP credentials first
    error_log("[$timestamp] Verifying SMTP credentials...");
    $credential_check = verifySmtpCredentials();
    error_log("[$timestamp] Credential check result: " . json_encode($credential_check));
    
    // Process mail with timeout protection
    if ($credential_check['success']) {
        error_log("[$timestamp] Credentials valid. Attempting to send email...");
        
        try {
            // Set an alarm for timeout
            set_time_limit(SMTP_TIMEOUT);
            
            // Try to send email
            $smtp = new Smtp();
            $start_time = microtime(true);
            
            $result = $smtp->send(
                $_POST["userName"],
                $_POST["userEmail"],
                $_POST["userMessage"]
            );
            
            $execution_time = microtime(true) - $start_time;
            error_log("[$timestamp] Email sent successfully in " . round($execution_time, 2) . " seconds");
        } catch (\Exception $mail_exception) {
            error_log("[$timestamp] SMTP Error: " . $mail_exception->getMessage());
            error_log("[$timestamp] Using fallback: Email details logged to file");
        }
    } else {
        error_log("[$timestamp] Invalid SMTP credentials or connection issues. Using log file only.");
    }
    
    // Return success response (even if SMTP failed, we logged the message)
    error_log("[$timestamp] Sending success response...");
    header('Content-Type: application/json');
    echo json_encode([
        'type' => 'message',
        'text' => 'Thank you for your message! We will get back to you soon.'
    ]);
    
} catch (\Exception $e) {
    error_log("[$timestamp] ERROR: " . $e->getMessage());
    error_log("[$timestamp] Stack trace: " . $e->getTraceAsString());
    
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'type' => 'error',
        'text' => $e->getMessage()
    ]);
} 

// Force completion of request
error_log("[$timestamp] Completing request...");
ob_end_flush(); 
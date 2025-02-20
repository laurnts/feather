<?php

namespace Laurnts\Feather\Layout;

use Laurnts\Feather\Navigation\MenuHandler;
use Laurnts\Feather\Router\Router;

class Layout {
    /** @var Router */
    private $router;
    
    /** @var MenuHandler */
    private $menuHandler;
    
    /** @var Layout */
    private static $instance = null;
    
    /**
     * Constructor
     * 
     * @param Router $router Router instance
     */
    public function __construct($router) {
        $this->router = $router;
        $this->menuHandler = new MenuHandler($router);
        self::$instance = $this;
    }
    
    /**
     * Get Layout instance
     * 
     * @return Layout
     */
    public static function getInstance() {
        return self::$instance;
    }
    
    /**
     * Render the complete page
     * 
     * @param callable $contentCallback Callback function that renders the main content
     */
    public function render($contentCallback) {
        // Set up variables that will be needed by included files
        global $router, $page_config, $menuHandler;
        $router = $this->router;
        $menuHandler = $this->menuHandler;
        
        // Start output buffering
        ob_start();
        
        // Get the final page configuration after content callback has set it
        $page_config = $this->router->getPageConfig();
        
        // Start HTML structure
        echo '<!DOCTYPE html>' . PHP_EOL;
        echo '<html lang="en">' . PHP_EOL;
        
        // Include header (contains head tag and initial elements)
        require __DIR__ . '/../../includes/header.php';
        
        // Open body tag
        echo '<body>' . PHP_EOL;
        
        // Open page wrapper
        echo '<!-- Page Wrap -->' . PHP_EOL;
        echo '<div class="page" id="top">' . PHP_EOL;
        
        // Include navigation
        require __DIR__ . '/../../includes/navigation.php';
        
        // Render main content
        call_user_func($contentCallback);
        
        // Include footer
        require __DIR__ . '/../../includes/footer.php';
        
        // Close page structure
        echo '</div>' . PHP_EOL; // close .page
        echo '</body>' . PHP_EOL;
        echo '</html>';
        
        // End output buffering and send to browser
        ob_end_flush();
    }
} 
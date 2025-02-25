<?php

namespace Laurnts\Feather\Router;

use Laurnts\Feather\Layout\Layout;

class Router {
    private $routes = [];
    private $notFoundCallback;
    private $basePath;
    private $request;
    private $pageConfig = [];
    private static $instance = null;
    private $devPath;
    private $layout;
    private $projectRoot;
    private $staticExtensions = ['js', 'css', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'eot', 'ico', 'map'];
    
    public function __construct(string $projectRoot) {
        // Set global instance
        self::$instance = $this;
        
        // Store project root
        $this->projectRoot = rtrim($projectRoot, '/');
        error_log("Project root set to: " . $this->projectRoot);
        
        // Get the script path relative to document root
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        // Set development path if in subdirectory
        $this->devPath = ($scriptDir !== '/' && $scriptDir !== '\\') ? $scriptDir : '';
        error_log("Dev path set to: " . $this->devPath);
        
        // Get the request path
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Clean the request path
        $this->request = trim(str_replace($this->devPath, '', $requestUri), '/');
        if (empty($this->request)) {
            $this->request = 'home';
        }
        error_log("Request path set to: " . $this->request);
        
        // Set base path same as devPath
        $this->basePath = $this->devPath;
        
        // Set default page configuration
        $this->pageConfig = [
            'page_title' => 'Website',
            'meta_title' => 'Website',
            'meta_description' => 'Website Description',
            'meta_keywords' => '',
            'current_page' => $this->request,
            'base_url' => $this->basePath,
            'menu_order' => 0
        ];
        
        // Initialize layout
        $this->layout = new Layout($this);
    }
    
    public static function getInstance() {
        return self::$instance;
    }
    
    public function getUrl($path = '') {
        return $this->basePath . '/' . trim($path, '/');
    }
    
    public function getAssetUrl($path = '') {
        return $this->getUrl($path);
    }
    
    public function setPageConfig($config) {
        $this->pageConfig = array_merge($this->pageConfig, $config);
    }
    
    public function getPageConfig($page = null) {
        if ($page === null) {
            return $this->pageConfig;
        }
        
        // Try to load page config
        $pagePath = $this->projectRoot . '/pages/' . $page . '.php';
        error_log("Loading page config from: " . $pagePath);
        
        if (file_exists($pagePath)) {
            // Load page configuration
            ob_start();
            require $pagePath;
            ob_get_clean();
            
            return $this->pageConfig;
        }
        
        return null;
    }
    
    /**
     * Check if the request is for a static file
     * 
     * @return bool
     */
    private function isStaticAsset() {
        $pathInfo = pathinfo($this->request);
        return isset($pathInfo['extension']) && in_array(strtolower($pathInfo['extension']), $this->staticExtensions);
    }
    
    public function dispatch() {
        // If this is a static asset request, don't try to render a page
        if ($this->isStaticAsset()) {
            // For static assets, let the web server handle it directly
            // No need to do anything here, as the router won't be involved for static files in production
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("Static asset request detected: " . $this->request . " - letting web server handle it");
            }
            return;
        }
        
        // Build the full page path
        $pagePath = $this->projectRoot . '/pages/' . $this->request . '.php';
        error_log("Looking for page at: " . $pagePath);
        
        // If direct file not found, try as directory with index.php
        if (!file_exists($pagePath)) {
            $pagePath = $this->projectRoot . '/pages/' . $this->request . '/index.php';
            error_log("Not found, trying: " . $pagePath);
        }
        
        // If found, load config first then render
        if (file_exists($pagePath)) {
            error_log("Found page at: " . $pagePath);
            // Load page configuration first
            ob_start();
            require $pagePath;
            ob_get_clean();
            
            // Now render with loaded configuration
            $this->layout->render(function() use ($pagePath) {
                require $pagePath;
            });
            
            return;
        }
        
        error_log("Page not found at: " . $pagePath);
        
        // If not found, show 404
        if ($this->notFoundCallback) {
            $this->layout->render(function() {
                call_user_func($this->notFoundCallback);
            });
        } else {
            header("HTTP/1.0 404 Not Found");
            echo '404 Page Not Found';
        }
    }
    
    public function getCurrentPage() {
        return $this->request;
    }
    
    public function isCurrentPage($page) {
        return $this->request === $page;
    }
    
    public function setNotFound($callback) {
        $this->notFoundCallback = $callback;
    }
    
    public function getDevPath() {
        return $this->devPath;
    }
    
    public function getProjectRoot() {
        return $this->projectRoot;
    }
} 
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
    
    public function __construct(string $projectRoot) {
        // Set global instance
        self::$instance = $this;
        
        // Store project root
        $this->projectRoot = rtrim($projectRoot, '/');
        
        // Detect base path by comparing document root with project root
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        $relativePath = str_replace($docRoot, '', $this->projectRoot);
        $this->basePath = $relativePath;
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[Router] Document Root: " . $docRoot);
            error_log("[Router] Project Root: " . $this->projectRoot);
            error_log("[Router] Base Path: " . $this->basePath);
        }
        
        // Get the request path
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Clean the request path - remove base URL if present
        $this->request = trim(str_replace($this->basePath, '', $requestUri), '/');
        if (empty($this->request)) {
            $this->request = 'home';
        }
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[Router] Request URI: " . $requestUri);
            error_log("[Router] Cleaned Request: " . $this->request);
        }
        
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
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[Router] Loading config from: " . $pagePath);
        }
        
        if (file_exists($pagePath)) {
            // Load page configuration
            ob_start();
            require $pagePath;
            ob_get_clean();
            
            return $this->pageConfig;
        }
        
        return null;
    }
    
    public function dispatch() {
        // Build the full page path
        $pagePath = $this->projectRoot . '/pages/' . $this->request . '.php';
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[Router] Looking for page: " . $pagePath);
        }
        
        // If direct file not found, try as directory with index.php
        if (!file_exists($pagePath)) {
            $pagePath = $this->projectRoot . '/pages/' . $this->request . '/index.php';
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[Router] Trying alternate path: " . $pagePath);
            }
        }
        
        // If found, load config first then render
        if (file_exists($pagePath)) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[Router] Found page at: " . $pagePath);
            }
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
        
        error_log("[Router] ERROR: Page not found: " . $pagePath);
        
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
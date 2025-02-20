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
    
    public function __construct() {
        // Set global instance
        self::$instance = $this;
        
        // Get the script path relative to document root
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        // Set project root as the directory containing index.php
        $this->projectRoot = dirname($_SERVER['SCRIPT_FILENAME']);
        error_log("Project root set to: " . $this->projectRoot);
        
        // Set development path if in subdirectory
        $this->devPath = ($scriptDir !== '/' && $scriptDir !== '\\') ? $scriptDir : '';
        
        // Get the request path
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Clean the request path
        $this->request = trim(str_replace($this->devPath, '', $requestUri), '/');
        if (empty($this->request)) {
            $this->request = 'home';
        }
        
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
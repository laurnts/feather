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
    
    public function __construct() {
        // Set global instance
        self::$instance = $this;
        
        // Get the request path
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Get the script path relative to document root
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        // Set development path if in subdirectory
        $this->devPath = ($scriptDir !== '/' && $scriptDir !== '\\') ? $scriptDir : '';
        
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
    
    public function getPageConfig() {
        return $this->pageConfig;
    }
    
    public function dispatch() {
        // Build the full page path
        $pagePath = __DIR__ . '/../../pages/' . $this->request . '.php';
        
        // If direct file not found, try as directory with index.php
        if (!file_exists($pagePath)) {
            $pagePath = __DIR__ . '/../../pages/' . $this->request . '/index.php';
        }
        
        // If found, load config first then render
        if (file_exists($pagePath)) {
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
    
    public function setNotFound($callback) {
        $this->notFoundCallback = $callback;
    }
    
    public function getDevPath() {
        return $this->devPath;
    }
} 
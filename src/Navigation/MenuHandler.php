<?php

namespace Laurnts\Feather\Navigation;

use Laurnts\Feather\Router\Router;

class MenuHandler {
    private $pages = [];
    private $currentPage;
    private $router;
    
    public function __construct(Router $router) {
        $this->router = $router;
        $this->currentPage = $router->getCurrentPage();
        $this->scanPages();
    }
    
    private function scanPages() {
        $pagesDir = $this->router->getProjectRoot() . '/pages/';
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[MenuHandler] Scanning pages in: " . $pagesDir);
        }
        
        $files = glob($pagesDir . '*.php');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            
            // Skip files starting with underscore or numbers (considered partials/includes)
            if (strpos($filename, '_') === 0 || is_numeric(substr($filename, 0, 1))) {
                continue;
            }
            
            // Initialize with basic information first
            $this->pages[$filename] = [
                'title' => ucfirst($filename),  // Default title
                'description' => '',
                'url' => $this->router->getUrl($filename === 'home' ? '' : $filename),
                'active' => $this->currentPage === $filename
            ];
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[MenuHandler] Added page: " . $filename);
            }
        }
    }
    
    private function updatePageConfig($filename) {
        if (!isset($this->pages[$filename])) {
            return;
        }

        $config = $this->router->getPageConfig($filename);
        if ($config) {
            $this->pages[$filename]['title'] = isset($config['menu_title']) ? $config['menu_title'] : 
                (isset($config['page_title']) ? $config['page_title'] : 
                (isset($config['meta_title']) ? $config['meta_title'] : $this->pages[$filename]['title']));
            
            $this->pages[$filename]['description'] = isset($config['meta_description']) ? $config['meta_description'] : '';
        }
    }
    
    public function getMenuItems() {
        $menu_items = [];
        $pages_dir = $this->router->getProjectRoot() . '/pages/';
        $files = glob($pages_dir . '*.php');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            
            // Get page config
            $config = $this->router->getPageConfig($filename);
            
            if (isset($config['menu_title']) && isset($config['menu_order'])) {
                $menu_items[] = [
                    'title' => $config['menu_title'],
                    'order' => $config['menu_order'],
                    'url' => $this->router->getUrl($config['slug'] === 'home' ? '' : $config['slug']),
                    'active' => $this->router->isCurrentPage($filename)
                ];
            }
        }
        
        // Sort by menu order
        usort($menu_items, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        return $menu_items;
    }
    
    public function renderMenu() {
        $items = $this->pages;
        $orderedItems = [];
        
        // Get menu_order and menu_title from each page's config
        foreach ($items as $page => $data) {
            // Update page config before rendering
            $this->updatePageConfig($page);
            $config = $this->router->getPageConfig($page);
            
            // Only include items that have menu_order set and less than 10 (main menu items)
            if (isset($config['menu_order']) && $config['menu_order'] < 10) {
                $orderedItems[$page] = [
                    'order' => $config['menu_order'],
                    'title' => isset($config['menu_title']) ? $config['menu_title'] : $data['title']
                ];
            }
        }
        
        // Sort by menu_order
        uasort($orderedItems, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        $output = '';
        
        // Render menu items in order
        foreach ($orderedItems as $page => $menuData) {
            if (isset($items[$page])) {
                $data = $items[$page];
                $activeClass = $data['active'] ? ' active' : '';
                $output .= sprintf(
                    '<li><a href="%s" class="%s" data-btn-animate="y">%s</a></li>',
                    $data['url'],
                    $activeClass,
                    $menuData['title']  // Use menu_title from config if available
                );
            }
        }
        
        return $output;
    }
} 
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
            
            // Get page metadata
            $content = file_get_contents($file);
            
            // Get title from meta tags
            preg_match('/<meta name="title" content="(.*?)">/i', $content, $titleMatches);
            preg_match('/<title>(.*?)<\/title>/i', $content, $htmlTitleMatches);
            
            // Get description from meta tags
            preg_match('/<meta name="description" content="(.*?)">/i', $content, $descMatches);
            
            $title = isset($titleMatches[1]) ? $titleMatches[1] : 
                    (isset($htmlTitleMatches[1]) ? $htmlTitleMatches[1] : ucfirst($filename));
            
            $this->pages[$filename] = [
                'title' => $title,
                'description' => isset($descMatches[1]) ? $descMatches[1] : '',
                'url' => $this->router->getUrl($filename === 'home' ? '' : $filename),
                'active' => $this->currentPage === $filename
            ];
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[MenuHandler] Added page: " . $filename);
            }
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
        
        // Get menu_order from each page's config
        foreach ($items as $page => $data) {
            $pageFile = $this->router->getProjectRoot() . '/pages/' . $page . '.php';
            if (file_exists($pageFile)) {
                $content = file_get_contents($pageFile);
                if (preg_match("/menu_order'\s*=>\s*(\d+)/", $content, $matches)) {
                    // Only include items with menu_order less than 10 (main menu items)
                    $order = (int)$matches[1];
                    if ($order < 10) {
                        $orderedItems[$page] = $order;
                    }
                }
            }
        }
        
        // Sort by menu_order
        asort($orderedItems);
        
        $output = '';
        
        // Render menu items in order
        foreach ($orderedItems as $page => $order) {
            if (isset($items[$page])) {
                $data = $items[$page];
                $activeClass = $data['active'] ? ' active' : '';
                $output .= sprintf(
                    '<li><a href="%s" class="%s" data-btn-animate="y">%s</a></li>',
                    $data['url'],
                    $activeClass,
                    $data['title']
                );
            }
        }
        
        return $output;
    }
} 
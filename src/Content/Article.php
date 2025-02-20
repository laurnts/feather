<?php

namespace Laurnts\Feather\Content;

use Laurnts\Feather\Router\Router;
use Psr\Log\LoggerInterface;

class Article {
    private $metadata;
    private static $cache = [];
    private static $cacheFile = '';
    private static $indexCache = [];
    private static $indexCacheFile = '';
    private static $router;
    
    public function __construct(array $metadata) {
        $this->metadata = $metadata;
    }
    
    public static function setRouter(Router $router) {
        self::$router = $router;
    }
    
    /**
     * Initialize the cache system
     */
    private static function initCache(): void {
        if (empty(self::$cacheFile)) {
            self::$cacheFile = sys_get_temp_dir() . '/article_cache.php';
            self::$indexCacheFile = sys_get_temp_dir() . '/article_index_cache.php';
            
            if (file_exists(self::$cacheFile)) {
                self::$cache = include self::$cacheFile;
            }
            if (file_exists(self::$indexCacheFile)) {
                self::$indexCache = include self::$indexCacheFile;
            }
        }
    }
    
    /**
     * Save cache to disk
     */
    private static function saveCache(): void {
        if (!empty(self::$cacheFile)) {
            $content = '<?php return ' . var_export(self::$cache, true) . ';';
            file_put_contents(self::$cacheFile, $content);
        }
    }
    
    /**
     * Save index cache to disk
     */
    private static function saveIndexCache(): void {
        if (!empty(self::$indexCacheFile)) {
            $content = '<?php return ' . var_export(self::$indexCache, true) . ';';
            file_put_contents(self::$indexCacheFile, $content);
        }
    }
    
    /**
     * Extract metadata from PHP file content
     */
    private static function extractMetadata(string $content): array {
        $metadata = [];
        
        // Extract article data
        if (preg_match('/\$article\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            $articleStr = $matches[1];
            error_log("Found article data: " . $articleStr);
            if (preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $articleStr, $pairs, PREG_SET_ORDER)) {
                foreach ($pairs as $pair) {
                    $metadata[$pair[1]] = $pair[2];
                }
            }
        } else {
            error_log("No article data found in content");
        }
        
        // Extract page config data
        if (preg_match('/setPageConfig\(\[(.*?)\]\)/s', $content, $matches)) {
            $configStr = $matches[1];
            error_log("Found page config: " . $configStr);
            if (preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $configStr, $pairs, PREG_SET_ORDER)) {
                foreach ($pairs as $pair) {
                    $metadata['page_' . $pair[1]] = $pair[2];
                }
            }
        } else {
            error_log("No page config found in content");
        }
        
        return $metadata;
    }
    
    public static function getMetadata(string $filepath): ?array {
        try {
            self::initCache();
            
            error_log("Getting metadata for file: " . $filepath);
            
            // Check if cached version is still valid
            $mtime = filemtime($filepath);
            if (isset(self::$cache[$filepath]['time']) && 
                self::$cache[$filepath]['time'] === $mtime) {
                error_log("Using cached metadata for: " . $filepath);
                return self::$cache[$filepath]['data'];
            }
            
            // Read file content
            $content = file_get_contents($filepath);
            if (!$content) {
                error_log("Could not read file: " . $filepath);
                return null;
            }
            
            error_log("File content length: " . strlen($content));
            $metadata = self::extractMetadata($content);
            
            if (!empty($metadata)) {
                error_log("Found metadata: " . json_encode($metadata));
                self::$cache[$filepath] = [
                    'time' => $mtime,
                    'data' => $metadata
                ];
                self::saveCache();
                return $metadata;
            }
            
            error_log("No metadata found in file: " . $filepath);
            return null;
            
        } catch (\Exception $e) {
            error_log("Error processing file {$filepath}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get total count of articles in a directory
     */
    public static function getCount(string $directory): int {
        try {
            self::initCache();
            
            if (!self::$router) {
                throw new \Exception('Router not set. Call Article::setRouter() first.');
            }
            
            // Ensure directory starts from project root
            $directory = ltrim($directory, '/');
            $fullPath = self::$router->getProjectRoot() . '/' . $directory;
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[Article] Counting articles in: " . $fullPath);
            }
            
            // Check if we have a valid index cache
            $dirHash = md5($fullPath);
            if (isset(self::$indexCache[$dirHash]['count'])) {
                // Verify if any file has been modified
                $lastCheck = self::$indexCache[$dirHash]['last_check'] ?? 0;
                if (time() - $lastCheck < 300) { // Cache for 5 minutes
                    return self::$indexCache[$dirHash]['count'];
                }
            }
            
            $files = glob($fullPath . '*.php');
            $count = count($files);
            
            // Cache the count
            self::$indexCache[$dirHash] = [
                'count' => $count,
                'last_check' => time()
            ];
            self::saveIndexCache();
            
            return $count;
        } catch (\Exception $e) {
            error_log("[Article] ERROR: Getting article count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get paginated articles
     */
    public static function getPaginated(string $directory, int $page = 1, int $perPage = 10, array $options = []): array {
        try {
            if (!self::$router) {
                throw new \Exception('Router not set. Call Article::setRouter() first.');
            }
            
            // Ensure directory starts from project root
            $directory = ltrim($directory, '/');
            $fullPath = self::$router->getProjectRoot() . '/' . $directory;
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[Article] Loading articles from: " . $fullPath);
            }
            
            // Get all PHP files in directory
            $files = glob($fullPath . '*.php');
            if (empty($files)) {
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("[Article] No articles found in: " . basename($directory));
                }
                return [
                    'items' => [],
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => 0
                ];
            }
            
            error_log("Found files: " . json_encode($files));
            
            // Sort files by modification time if needed
            if (empty($options['skip_sort'])) {
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
            }
            
            // Calculate pagination
            $total = count($files);
            $totalPages = ceil($total / $perPage);
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * $perPage;
            
            // Get files for current page
            $pageFiles = array_slice($files, $offset, $perPage);
            error_log("Processing page files: " . json_encode($pageFiles));
            
            $items = [];
            foreach ($pageFiles as $file) {
                $metadata = self::getMetadata($file);
                if ($metadata) {
                    $metadata['filename'] = basename($file, '.php');
                    $items[] = new self($metadata);
                }
            }
            
            error_log("Found " . count($items) . " items with metadata");
            
            // Sort items by date
            usort($items, function($a, $b) {
                $aDate = $a->get('date');
                $bDate = $b->get('date');
                
                if ($aDate === 'Coming soon' && $bDate === 'Coming soon') {
                    return strcmp($a->get('title'), $b->get('title'));
                }
                if ($aDate === 'Coming soon') return -1;
                if ($bDate === 'Coming soon') return 1;
                
                $dateCompare = strtotime($bDate) - strtotime($aDate);
                return $dateCompare === 0 ? strcmp($a->get('title'), $b->get('title')) : $dateCompare;
            });
            
            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ];
            
        } catch (\Exception $e) {
            error_log("Error loading paginated articles: " . $e->getMessage());
            return [
                'items' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }
    }
    
    public static function getAll(string $directory, array $options = []): array {
        try {
            // If limit is set, use it as per_page for first page
            if (!empty($options['limit']) && is_numeric($options['limit'])) {
                $result = self::getPaginated($directory, 1, (int)$options['limit'], $options);
                return $result['items'];
            }
            
            // Otherwise get all articles
            $result = self::getPaginated($directory, 1, PHP_INT_MAX, $options);
            return $result['items'];
        } catch (\Exception $e) {
            error_log("Error getting all articles: " . $e->getMessage());
            return [];
        }
    }
    
    public function get(string $key) {
        return $this->metadata[$key] ?? null;
    }
    
    public function formatDate(string $format = 'F j, Y'): string {
        $date = $this->get('date');
        return $date === 'Coming soon' ? $date : date($format, strtotime($date));
    }
    
    /**
     * Clear the cache (useful after updates)
     */
    public static function clearCache(): void {
        self::$cache = [];
        self::$indexCache = [];
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
        if (file_exists(self::$indexCacheFile)) {
            unlink(self::$indexCacheFile);
        }
    }
} 
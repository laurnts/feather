<?php

namespace Laurnts\Feather\Content;

class Article {
    private $metadata;
    private static $cache = [];
    private static $cacheFile = '';
    private static $indexCache = [];
    private static $indexCacheFile = '';
    
    public function __construct(array $metadata) {
        $this->metadata = $metadata;
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
            if (preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $articleStr, $pairs, PREG_SET_ORDER)) {
                foreach ($pairs as $pair) {
                    $metadata[$pair[1]] = $pair[2];
                }
            }
        }
        
        // Extract page config data
        if (preg_match('/setPageConfig\(\[(.*?)\]\)/s', $content, $matches)) {
            $configStr = $matches[1];
            if (preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $configStr, $pairs, PREG_SET_ORDER)) {
                foreach ($pairs as $pair) {
                    $metadata['page_' . $pair[1]] = $pair[2];
                }
            }
        }
        
        return $metadata;
    }
    
    public static function getMetadata(string $filepath): ?array {
        try {
            self::initCache();
            
            // Check if cached version is still valid
            $mtime = filemtime($filepath);
            if (isset(self::$cache[$filepath]['time']) && 
                self::$cache[$filepath]['time'] === $mtime) {
                return self::$cache[$filepath]['data'];
            }
            
            // Read file content up to getPageConfig
            $content = '';
            $handle = fopen($filepath, 'r');
            if (!$handle) {
                error_log("Could not open file: " . $filepath);
                return null;
            }
            
            while (($line = fgets($handle)) !== false) {
                $content .= $line;
                if (strpos($line, '$page_config = $router->getPageConfig();') !== false) {
                    break;
                }
            }
            fclose($handle);
            
            $metadata = self::extractMetadata($content);
            
            if (!empty($metadata)) {
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
            
            // Check if we have a valid index cache
            $dirHash = md5($directory);
            if (isset(self::$indexCache[$dirHash]['count'])) {
                // Verify if any file has been modified
                $lastCheck = self::$indexCache[$dirHash]['last_check'] ?? 0;
                if (time() - $lastCheck < 300) { // Cache for 5 minutes
                    return self::$indexCache[$dirHash]['count'];
                }
            }
            
            $files = glob($directory . '*.php');
            $count = count($files);
            
            // Cache the count
            self::$indexCache[$dirHash] = [
                'count' => $count,
                'last_check' => time()
            ];
            self::saveIndexCache();
            
            return $count;
        } catch (\Exception $e) {
            error_log("Error getting article count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get paginated articles
     */
    public static function getPaginated(string $directory, int $page = 1, int $perPage = 10, array $options = []): array {
        try {
            error_log("Loading paginated articles from directory: " . $directory);
            
            // Get all PHP files in directory
            $files = glob($directory . '*.php');
            if (empty($files)) {
                error_log("No PHP files found in directory: " . $directory);
                return [
                    'items' => [],
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => 0
                ];
            }
            
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
            
            $items = [];
            foreach ($pageFiles as $file) {
                $metadata = self::getMetadata($file);
                if ($metadata) {
                    $metadata['filename'] = basename($file, '.php');
                    $items[] = new self($metadata);
                }
            }
            
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
            
            // Otherwise, get all items (not recommended for large datasets)
            $result = self::getPaginated($directory, 1, PHP_INT_MAX, $options);
            return $result['items'];
            
        } catch (\Exception $e) {
            error_log("Error loading all articles: " . $e->getMessage());
            return [];
        }
    }
    
    public function get(string $key) {
        return $this->metadata[$key] ?? null;
    }
    
    public function formatDate(string $format = 'F j, Y'): string {
        $date = $this->get('date');
        return $date !== 'Coming soon' ? date($format, strtotime($date)) : 'Coming soon';
    }
    
    /**
     * Clear the cache (useful after updates)
     */
    public static function clearCache(): void {
        self::initCache();
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
# Feather Framework

A lightweight PHP framework for building modern websites with clean architecture and modular structure.

## Features

- Clean architecture with separation of concerns
- Server-agnostic routing (Apache/Nginx)
- Dynamic menu system with auto-discovery
- Modular page and section structure
- Template-based development
- Built-in content management
- SEO-friendly configuration

## Requirements

- PHP 7.4 or higher
- Composer

## Installation

```bash
composer require laurnts/feather
```

## Basic Usage

### 1. Directory Structure

```
your-project/
├── pages/           # Individual page files
├── sections/        # Reusable components
├── includes/        # Core template parts
└── public/         # Public assets
```

### 2. Initialize Router

```php
use Laurnts\Feather\Router\Router;

require_once 'vendor/autoload.php';

// Initialize router
$router = new Router();

// Add 404 handler
$router->setNotFound(function() use ($router) {
    $router->setPageConfig([
        'page_title' => '404 - Not Found',
        'meta_description' => 'Page not found'
    ]);
    require_once 'pages/404.php';
});

// Dispatch the route
$router->dispatch();
```

### 3. Create Pages

```php
// pages/about.php
$router->setPageConfig([
    'page_title' => 'About Us',
    'menu_title' => 'About',
    'menu_order' => 2,
    'meta_description' => 'About our company'
]);

// Your page content here
```

### 4. Use Layout System

```php
use Laurnts\Feather\Layout\Layout;

$layout = new Layout($router);
$layout->render(function() {
    // Your content here
});
```

## Configuration

### Page Configuration Options

```php
$router->setPageConfig([
    'page_title' => 'Page Title',      // Browser title
    'menu_title' => 'Menu Title',      // Navigation menu title
    'meta_description' => 'Description', // Meta description
    'menu_order' => 1,                 // Menu position (1-999)
]);
```

### Menu Ordering

- 1-5: Main menu items
- 6-19: Secondary menu items
- 20+: Footer items

## Content Management

### Using Article Class

```php
use Laurnts\Feather\Content\Article;

// Get paginated articles
$result = Article::getPaginated('/path/to/articles', 1, 10);

// Get all articles
$articles = Article::getAll('/path/to/articles');
```

## Best Practices

1. **Routing**
   - Use `$router->getUrl()` for links
   - Never use direct paths
   - Let router handle all URLs

2. **Layout**
   - Keep HTML structure in Layout class
   - Use output buffering properly
   - Maintain clean template files

3. **Security**
   - Always escape output
   - Use proper input validation
   - Follow security best practices

4. **Performance**
   - Minimize database queries
   - Use efficient code patterns
   - Keep templates clean

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details. 
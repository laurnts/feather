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
- Cross-server compatible contact form

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
└── public/          # Public assets
├── var/             # Log files and temporary data
└── .htaccess        # Web server configuration
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

## Example Files and Their Purpose

The `example` directory in the Feather Framework package contains reference files to help you quickly implement common features:

### Directory Structure

```
example/
├── .htaccess                # Apache server configuration for routing
├── nginx.conf               # Nginx server configuration for routing
├── env.php                  # Environment configuration template
├── Index/                   # Example homepage implementation
│   └── index.php            # Sample page with router configuration
└── Contact/                 # Contact form implementation example
    ├── form.php             # Contact form HTML structure
    └── contact-form.js      # JavaScript for form handling
```

### File Descriptions

1. **`.htaccess`** - Should be placed in your project's root directory. Contains essential Apache rewrite rules to enable routing through index.php while allowing direct access to special files like send-mail.php.

2. **`nginx.conf`** - A reference Nginx configuration based on real-world implementation. You'll need to adapt this to your server by replacing paths and domain names. The critical sections ensure proper routing through index.php while allowing direct access to send-mail.php.

3. **`env.php`** - Should be placed in your project's root directory. Contains configuration settings, particularly SMTP credentials for the contact form. You must customize this with your own settings.

4. **`Index/index.php`** - Reference for implementing a basic homepage with proper router initialization and page configuration. Shows how to structure page files in the `pages/` directory.

5. **`Contact/form.php`** - Template for implementing a contact form in your project. Should be placed in `sections/contact/form.php` or similar location depending on your project structure.

6. **`Contact/contact-form.js`** - Front-end JavaScript to handle form submission with validation and AJAX. Should be placed in your `js/` directory.

### Additional Files You'll Need

1. **`send-mail.php`** - Should be placed in your project's root directory. Processes form submissions, interacts with the SMTP server, and provides fallback logging.

2. **`var/`** - Create this directory in your project root to store log files and temporary data, especially for contact form submission backups.

## Cross-Server Compatibility

Feather is designed to work seamlessly on both Apache and Nginx servers with minimal configuration changes. The example files include configuration samples for both server types, allowing you to:

- Properly set up URL routing
- Enable direct access to processing scripts
- Configure proper MIME types and caching
- Implement security headers

For Nginx servers, the provided `nginx.conf` sample is structured to work with various hosting environments. You'll need to:

1. Adapt paths to match your server structure
2. Update domain names
3. Adjust PHP-FPM socket paths for your PHP version
4. Ensure the server block includes the necessary directives for accessing send-mail.php directly

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

5. **Contact Form**
   - Always implement fallback mechanisms
   - Set appropriate timeouts
   - Log all form submissions to a backup file

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details. 
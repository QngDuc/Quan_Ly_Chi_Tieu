<?php
namespace App\Core;

class App
{
    protected $controller = 'Login'; // Default controller (Auth\Login)
    protected $method = 'index'; // Default method
    protected $params = [];
    
        public function __construct()
        {
        }
    
    public function run()
    {
        $url = $this->parseUrl();
        // Special-case: serve resource files from /resources via route `/resources/...`
        // Allows links like BASE_URL/resources/css/login.css to be streamed to browser
        if (!empty($url) && $url[0] === 'resources') {
            $resourceType = $url[1] ?? '';
            // Allow streaming for css and js under resources/css and resources/js
            if (in_array($resourceType, ['css', 'js']) && isset($url[2])) {
                $segments = array_slice($url, 2);
                $requested = implode('/', $segments);
                // Prevent path traversal and suspicious chars
                if (preg_match('/(^|\\/)\.\.|~|:|\\\\/', $requested)) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
                    echo 'Invalid resource path';
                    exit;
                }
                $projectRoot = dirname(APP_PATH);
                $filePath = $projectRoot . '/resources/' . ($resourceType === 'css' ? 'css/' : 'js/') . $requested;
                if (file_exists($filePath) && is_file($filePath)) {
                    if ($resourceType === 'css') {
                        header('Content-Type: text/css; charset=utf-8');
                    } else {
                        header('Content-Type: application/javascript; charset=utf-8');
                    }
                    header('Cache-Control: public, max-age=86400');
                    readfile($filePath);
                    exit;
                } else {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                    echo 'Resource not found';
                    exit;
                }
            }
        }
        $namespace = 'App\\Http\\Controllers';
        $folderPath = '/Http/Controllers';

        // If no controller is specified, use the default Auth\\Login controller
        if (empty($url[0])) {
            $namespace = 'App\\Http\\Controllers\\Auth';
            $folderPath = '/Http/Controllers/Auth';
            $this->controller = 'Login';
        } else {
            // Check if this is an admin route
            if ($url[0] === 'admin') {
                $namespace = 'App\\Http\\Controllers\\Admin';
                $folderPath = '/Http/Controllers/Admin';
                unset($url[0]);
                $url = array_values($url);
                
                // Set admin controller (default to Users)
                if (empty($url[0])) {
                    $this->controller = 'Users';
                } else {
                    $this->controller = ucfirst($url[0]);
                    unset($url[0]);
                }
            } elseif ($url[0] === 'auth') {
                // Auth routes
                $namespace = 'App\\Http\\Controllers\\Auth';
                $folderPath = '/Http/Controllers/Auth';
                unset($url[0]);
                $url = array_values($url);

                // Default auth controller
                if (empty($url[0])) {
                    $this->controller = 'Login';
                } else {
                    $this->controller = ucfirst($url[0]);
                    unset($url[0]);
                }
            } else {
                // User routes
                $namespace = 'App\\Http\\Controllers\\User';
                $folderPath = '/Http/Controllers/User';
                $this->controller = ucfirst($url[0]);
                unset($url[0]);
            }
        }

        // Require the controller file
        $controllerFile = APP_PATH . $folderPath . '/' . $this->controller . '.php';
        if (!file_exists($controllerFile)) {
            // Fallback to Auth\\Login if controller not found
            $namespace = 'App\\Http\\Controllers\\Auth';
            $this->controller = 'Login';
            $controllerFile = APP_PATH . '/Http/Controllers/Auth/Login.php';
        }
        
        require_once $controllerFile;

        // Instantiate the controller with its full namespace
        $controllerClass = $namespace . '\\' . $this->controller;
        $this->controller = new $controllerClass();

        if (isset($url[1])) {
            // Check if the method exists in the controller
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                // Method not found, default to index method
                $this->method = 'index';
            }
        }
        
        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$this->controller, $this->method], $this->params);
    }    public function parseUrl()
    {
        $url = [];
        if (isset($_GET['url'])) {
            $url = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        } else {
            // Fallback for when $_GET['url'] is not populated by mod_rewrite
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $baseUrl = BASE_URL; // BASE_URL is defined in public/index.php

            // Remove BASE_URL from REQUEST_URI to get the clean path
            if ($baseUrl !== '' && strpos($requestUri, $baseUrl) === 0) {
                $path = substr($requestUri, strlen($baseUrl));
            } else {
                $path = $requestUri;
            }

            // Remove any query string parameters
            $path = strtok($path, '?');
            // Remove leading/trailing slashes
            $path = trim($path, '/');
            
            if (!empty($path)) {
                $url = explode('/', $path);
            }
        }
        return $url;
    }
}

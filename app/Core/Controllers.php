<?php
namespace App\Core;

/**
 * Base Controller with Dependency Injection support
 * Refactored to remove hard coupling and support testability
 */
class Controllers
{
    protected $view;
    protected $userModel;
    protected $request;
    protected $response;
    protected $session;
    protected $container;
    protected $db;

    /**
     * Constructor with dependency injection
     * Dependencies can be overridden for testing
     */
    public function __construct(
        ?Views $view = null,
        ?Request $request = null,
        ?Response $response = null,
        ?SessionManager $session = null
    ) {
        $this->container = Container::getInstance();
        
        // Use injected dependencies or create defaults
        $this->view = $view ?? new Views();
        $this->request = $request ?? new Request();
        $this->response = $response ?? new Response();
        $this->session = $session ?? new SessionManager();
        
        // Ensure session is started
        $this->session->start();
    }

    /**
     * Load a model using the container for DI
     */
    public function model($model)
    {
        $modelPath = APP_PATH . '/models/' . $model . '.php';
        if (file_exists($modelPath)) {
            require_once $modelPath;
            $modelClass = 'App\Models\\' . $model;
            
            // Try to resolve from container for better DI
            try {
                return $this->container->make($modelClass);
            } catch (\Exception $e) {
                // Fallback to direct instantiation
                return new $modelClass();
            }
        }
        return null;
    }

    public function view($view, $data = [])
    {
        $this->view->render($view, $data);
    }

    public function redirect($path)
    {
        header('Location: ' . BASE_URL . $path);
        exit();
    }

    /**
     * Check if user is logged in (using SessionManager)
     */
    protected function isLoggedIn()
    {
        return $this->session->isLoggedIn();
    }

    /**
     * Get current user ID (using SessionManager)
     */
    protected function getCurrentUserId()
    {
        return $this->session->getUserId();
    }

    /**
     * Get current user data
     */
    protected function getCurrentUser()
    {
        if ($this->isLoggedIn()) {
            // Lazy load user model
            if (!$this->userModel) {
                $this->userModel = $this->model('User');
            }
            return $this->userModel->getUserById($this->getCurrentUserId());
        }
        return null;
    }

    /**
     * Get database connection (for backward compatibility)
     */
    protected function getDb()
    {
        if ($this->db === null) {
            $this->db = (new ConnectDB())->getConnection();
        }
        return $this->db;
    }
}
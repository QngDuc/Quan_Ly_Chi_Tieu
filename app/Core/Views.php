<?php
namespace App\Core;

class Views
{
    protected $data = [];

    public function render($view, $data = [])
    {
        $this->data = array_merge($this->data, $data);
        extract($this->data);

        $viewPath = dirname(APP_PATH) . '/resources/views/' . $view . '.php';

        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            // Handle view not found
            echo "Error: View file '$viewPath' not found.";
        }
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public function partial($view, $data = [])
    {
        extract($data);
        $partialPath = dirname(APP_PATH) . '/resources/views/partials/' . $view . '.php';
        if (file_exists($partialPath)) {
            require_once $partialPath;
        } else {
            echo "Error: Partial view file '$partialPath' not found.";
        }
    }
}

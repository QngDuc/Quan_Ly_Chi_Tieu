<?php
namespace App\Controllers;

use App\Core\Controllers;

// Deprecated controller kept for backward compatibility.
// Routes should use App\Controllers\Auth\Login instead.
class Login_signup extends Controllers
{
    public function index()
    {
        $this->redirect('/auth/login');
    }
}
<?php defined('GALLERY') or die('No direct script access.');

/**
 * Basic user management
 */
class User {
    
    private $users = array();
    
    /**
     * Login the a user
     */
    public function login($name, $password) {
        $name = (string)$name;
        if (isset($this->users[$name]) && md5($password) == $this->users[$name]) {
            $_SESSION['logged_in'] = true;
            return true;
        }
        return false;
    }
    
    /**
     * Return status of current user
     */
    public function logged_in() {
        return !empty($_SESSION['logged_in']);
    }
    
    /**
     * Logout the current user
     */
    public function logout() {
        session_unset();
    }
    
    /**
     * Create a user
     */
    public function create($name, $password) {
        $name = (string)$name;
        $this->users[$name] = md5($password);
    }
    
}
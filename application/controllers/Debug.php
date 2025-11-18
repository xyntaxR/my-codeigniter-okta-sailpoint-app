<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Debug Controller
 * Temporary controller to help debug authentication flow
 */
class Debug extends CI_Controller {
    
    public function session() {
        // Display current session data
        echo "<!DOCTYPE html><html><head><title>Session Debug</title>";
        echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
        echo ".card{background:white;padding:20px;border-radius:8px;max-width:800px;margin:20px auto;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
        echo "h1{color:#667eea;}h2{color:#333;margin-top:20px;}";
        echo "pre{background:#f8f8f8;padding:10px;border-radius:4px;overflow:auto;}";
        echo ".btn{display:inline-block;padding:10px 20px;margin:10px 5px 0 0;background:#667eea;color:white;text-decoration:none;border-radius:5px;}";
        echo "</style></head><body>";
        
        echo "<div class='card'>";
        echo "<h1>🔍 Session Debug Information</h1>";
        
        echo "<h2>Session Status:</h2>";
        echo "<pre>";
        echo "Session ID: " . session_id() . "\n";
        echo "Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n";
        echo "</pre>";
        
        echo "<h2>CodeIgniter Session Data:</h2>";
        echo "<pre>";
        $session_data = array(
            'logged_in' => $this->session->userdata('logged_in'),
            'user_id' => $this->session->userdata('user_id'),
            'username' => $this->session->userdata('username'),
            'email' => $this->session->userdata('email'),
            'full_name' => $this->session->userdata('full_name'),
            'user_type' => $this->session->userdata('user_type'),
            'primary_role' => $this->session->userdata('primary_role'),
            'roles' => $this->session->userdata('roles'),
            'login_time' => $this->session->userdata('login_time'),
            'access_token' => $this->session->userdata('access_token') ? 'SET (hidden)' : null,
            'id_token' => $this->session->userdata('id_token') ? 'SET (hidden)' : null,
        );
        echo json_encode($session_data, JSON_PRETTY_PRINT);
        echo "</pre>";
        
        echo "<h2>All Session Variables:</h2>";
        echo "<pre>";
        $all_session = $this->session->userdata();
        foreach ($all_session as $key => $value) {
            if (in_array($key, array('access_token', 'id_token', 'refresh_token'))) {
                echo "$key => [HIDDEN]\n";
            } else {
                echo "$key => " . print_r($value, true) . "\n";
            }
        }
        echo "</pre>";
        
        echo "<a href='" . base_url('dashboard') . "' class='btn'>Go to Dashboard</a>";
        echo "<a href='" . base_url('auth/login') . "' class='btn'>Go to Login</a>";
        echo "<a href='" . base_url('auth/logout') . "' class='btn' style='background:#e74c3c;'>Logout</a>";
        
        echo "</div></body></html>";
    }
}

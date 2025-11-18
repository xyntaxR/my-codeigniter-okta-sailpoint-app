<?php
/**
 * Quick authentication flow test
 * Access this file directly to check authentication status
 */

// Start session
session_start();

echo "<!DOCTYPE html>";
echo "<html><head><title>Auth Test</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".card{background:white;padding:20px;border-radius:8px;max-width:600px;margin:20px auto;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo "h1{color:#667eea;}h2{color:#333;margin-top:20px;}";
echo "pre{background:#f8f8f8;padding:10px;border-radius:4px;overflow:auto;}";
echo ".status{padding:10px;border-radius:4px;margin:10px 0;}";
echo ".success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}";
echo ".error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}";
echo ".btn{display:inline-block;padding:10px 20px;margin:10px 5px 0 0;background:#667eea;color:white;text-decoration:none;border-radius:5px;}";
echo ".btn:hover{background:#5568d3;}</style></head><body>";

echo "<div class='card'>";
echo "<h1>🔐 CodeIgniter Okta Authentication Test</h1>";

// Check if CodeIgniter session exists
if (isset($_SESSION)) {
    echo "<h2>Session Status:</h2>";
    
    $has_ci_session = false;
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'ci_session') !== false || strpos($key, '__ci_') !== false) {
            $has_ci_session = true;
            break;
        }
    }
    
    // Check for logged_in flag
    $logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    
    if ($logged_in) {
        echo "<div class='status success'><strong>✅ User is authenticated!</strong></div>";
        
        echo "<h2>Session Data:</h2>";
        echo "<pre>";
        $session_data = array(
            'logged_in' => $_SESSION['logged_in'] ?? 'Not set',
            'user_id' => $_SESSION['user_id'] ?? 'Not set',
            'username' => $_SESSION['username'] ?? 'Not set',
            'email' => $_SESSION['email'] ?? 'Not set',
            'full_name' => $_SESSION['full_name'] ?? 'Not set',
            'user_type' => $_SESSION['user_type'] ?? 'Not set',
            'primary_role' => $_SESSION['primary_role'] ?? 'Not set',
            'roles' => $_SESSION['roles'] ?? 'Not set',
            'login_time' => isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'Not set'
        );
        echo json_encode($session_data, JSON_PRETTY_PRINT);
        echo "</pre>";
        
        echo "<a href='index.php/dashboard' class='btn'>Go to Dashboard</a>";
        echo "<a href='index.php/auth/logout' class='btn' style='background:#e74c3c;'>Logout</a>";
        
    } else {
        echo "<div class='status error'><strong>❌ User is NOT authenticated</strong></div>";
        echo "<p>The 'logged_in' session variable is not set or is false.</p>";
        
        if ($has_ci_session) {
            echo "<h2>Available Session Keys:</h2>";
            echo "<pre>";
            $keys = array_keys($_SESSION);
            foreach ($keys as $key) {
                echo "- " . htmlspecialchars($key) . "\n";
            }
            echo "</pre>";
        }
        
        echo "<a href='index.php/auth/login' class='btn'>Go to Login</a>";
        echo "<a href='index.php/auth/okta_login' class='btn'>Login with Okta</a>";
    }
    
} else {
    echo "<div class='status error'><strong>❌ No session found</strong></div>";
    echo "<p>PHP session is not started or CodeIgniter session is not initialized.</p>";
    echo "<a href='index.php/auth/login' class='btn'>Go to Login</a>";
}

echo "<h2>PHP Session Info:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "</pre>";

echo "<h2>Environment:</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "</pre>";

echo "</div></body></html>";
?>

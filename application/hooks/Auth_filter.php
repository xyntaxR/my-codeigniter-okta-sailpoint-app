<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Filter Hook
 * 
 * Checks authentication for protected routes
 */
class Auth_filter {
    
    protected $CI;
    
    // Public routes that don't require authentication
    private $public_routes = array(
        'auth/login',
        'auth/okta_login',
        'auth/callback',
        'auth/local_login',
        'welcome',
        'debug/session'
    );
    
    public function __construct() {
        $this->CI =& get_instance();
    }
    
    /**
     * Check if user is authenticated
     */
    public function check_authentication() {
        // Get current route
        $route = $this->CI->router->fetch_class() . '/' . $this->CI->router->fetch_method();
        $controller = $this->CI->router->fetch_class();
        
        log_message('debug', 'Auth_filter: Checking route: ' . $route);
        
        // Check if current route is public
        if ($this->is_public_route($route) || $this->is_public_route($controller)) {
            log_message('debug', 'Auth_filter: Route is public, allowing access');
            return;
        }
        
        // Check if user is logged in
        $logged_in = $this->CI->session->userdata('logged_in');
        log_message('debug', 'Auth_filter: logged_in status: ' . ($logged_in ? 'TRUE' : 'FALSE'));
        
        if (!$logged_in) {
            // Save intended destination
            $this->CI->session->set_userdata('redirect_after_login', current_url());
            
            log_message('info', 'Auth_filter: User not logged in, redirecting to login');
            // Redirect to login
            redirect('auth/login');
            return;
        }
        
        log_message('debug', 'Auth_filter: User is authenticated, allowing access to ' . $route);
        
        // Check session timeout
        $login_time = $this->CI->session->userdata('login_time');
        $session_timeout = $this->CI->config->item('okta_session_timeout') ?: 3600;
        
        if (time() - $login_time > $session_timeout) {
            // Session expired
            log_message('info', 'Session expired for user: ' . $this->CI->session->userdata('username'));
            $this->CI->session->sess_destroy();
            $this->CI->session->set_flashdata('error', 'Your session has expired. Please login again.');
            redirect('auth/login');
            return;
        }
        
        // Check token expiration for external users
        if ($this->CI->session->userdata('user_type') === 'external') {
            $token_expires_at = $this->CI->session->userdata('token_expires_at');
            
            if ($token_expires_at && time() > $token_expires_at) {
                // Try to refresh token
                $refresh_token = $this->CI->session->userdata('refresh_token');
                
                if ($refresh_token) {
                    $this->CI->load->library('okta_service');
                    $new_tokens = $this->CI->okta_service->refresh_token($refresh_token);
                    
                    if ($new_tokens) {
                        // Update session with new tokens
                        $this->CI->session->set_userdata(array(
                            'access_token' => $new_tokens['access_token'],
                            'token_expires_at' => time() + $new_tokens['expires_in']
                        ));
                        
                        if (isset($new_tokens['id_token'])) {
                            $this->CI->session->set_userdata('id_token', $new_tokens['id_token']);
                        }
                        
                        log_message('info', 'Token refreshed for user: ' . $this->CI->session->userdata('username'));
                    } else {
                        // Token refresh failed, force logout
                        log_message('warning', 'Token refresh failed for user: ' . $this->CI->session->userdata('username'));
                        $this->CI->session->sess_destroy();
                        $this->CI->session->set_flashdata('error', 'Your session has expired. Please login again.');
                        redirect('auth/login');
                        return;
                    }
                } else {
                    // No refresh token available, force logout
                    $this->CI->session->sess_destroy();
                    $this->CI->session->set_flashdata('error', 'Your session has expired. Please login again.');
                    redirect('auth/login');
                    return;
                }
            }
        }
    }
    
    /**
     * Check if route is public
     * 
     * @param string $route
     * @return bool
     */
    private function is_public_route($route) {
        // Normalize route
        $route = strtolower(trim($route, '/'));
        
        foreach ($this->public_routes as $public_route) {
            if ($route === strtolower($public_route) || strpos($route, strtolower($public_route)) === 0) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    /**
     * Check if user has required role
     * 
     * @param array|string $required_roles
     * @return bool
     */
    public function check_role($required_roles) {
        if (!$this->CI->session->userdata('logged_in')) {
            return FALSE;
        }
        
        $user_roles = $this->CI->session->userdata('roles');
        
        if (!is_array($required_roles)) {
            $required_roles = array($required_roles);
        }
        
        // Check if user has any of the required roles
        return !empty(array_intersect($user_roles, $required_roles));
    }
}

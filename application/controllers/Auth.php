<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Controller
 * 
 * Handles authentication flows for both Okta and local login
 */
class Auth extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('okta_service');
        $this->load->library('role_mapper');
        $this->load->model('user_model');
        $this->load->helper('form');
        $this->load->library('form_validation');
    }
    
    /**
     * Display login page
     */
    public function login() {
        // If already logged in, redirect to dashboard
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
        }
        
        $data['okta_enabled'] = $this->config->item('okta_enabled');
        $data['local_fallback'] = $this->config->item('okta_local_fallback');
        
        $this->load->view('auth/login', $data);
    }
    
    /**
     * Initiate Okta login
     */
    public function okta_login() {
        if (!$this->config->item('okta_enabled')) {
            show_error('Okta authentication is not enabled', 403);
            return;
        }
        
        // Get authorization URL and redirect
        $auth_url = $this->okta_service->get_authorization_url();
        redirect($auth_url);
    }
    
    /**
     * Okta callback handler
     */
    public function callback() {
        // Verify state parameter for CSRF protection
        $state = $this->input->get('state');
        $stored_state = $this->session->userdata('okta_state');
        
        if (!$state || $state !== $stored_state) {
            log_message('error', 'State parameter mismatch in Okta callback');
            $this->session->set_flashdata('error', 'Authentication failed. Invalid state parameter.');
            redirect('auth/login');
            return;
        }
        
        // Check for errors
        $error = $this->input->get('error');
        if ($error) {
            $error_description = $this->input->get('error_description');
            log_message('error', 'Okta authentication error: ' . $error . ' - ' . $error_description);
            $this->session->set_flashdata('error', 'Authentication failed: ' . $error_description);
            redirect('auth/login');
            return;
        }
        
        // Get authorization code
        $code = $this->input->get('code');
        if (!$code) {
            $this->session->set_flashdata('error', 'Authentication failed. No authorization code received.');
            redirect('auth/login');
            return;
        }
        
        // Exchange code for tokens
        $tokens = $this->okta_service->exchange_code_for_tokens($code);
        if (!$tokens) {
            log_message('error', 'Failed to exchange authorization code for tokens');
            $this->session->set_flashdata('error', 'Authentication failed. Could not obtain tokens.');
            redirect('auth/login');
            return;
        }
        
        // Verify ID token
        $user_info = $this->okta_service->verify_id_token($tokens['id_token']);
        if (!$user_info) {
            log_message('error', 'ID token verification failed');
            $this->session->set_flashdata('error', 'Authentication failed. Invalid token.');
            redirect('auth/login');
            return;
        }
        
        // Extract user information
        $username = $user_info->preferred_username ?? $user_info->email ?? $user_info->sub;
        $email = $user_info->email ?? '';
        $full_name = $user_info->name ?? '';
        $external_id = $user_info->sub;
        
        // Extract and map groups to roles
        $groups = $this->okta_service->extract_groups($user_info);
        $roles = $this->role_mapper->map_groups_to_roles($groups);
        
        // Create or update user in database
        $user_data = array(
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
            'external_id' => $external_id,
            'roles' => $roles
        );
        
        $user_id = $this->user_model->create_or_update_external_user($user_data);
        
        if (!$user_id) {
            log_message('error', 'Failed to create/update external user');
            $this->session->set_flashdata('error', 'Failed to create user account.');
            redirect('auth/login');
            return;
        }
        
        // Get complete user data
        $user = $this->user_model->get_by_id($user_id);
        
        if (!$user) {
            log_message('error', 'User not found after creation/update. User ID: ' . $user_id);
            $this->session->set_flashdata('error', 'Failed to retrieve user account.');
            redirect('auth/login');
            return;
        }
        
        log_message('info', 'Retrieved user data for: ' . $username . ' (ID: ' . $user_id . ')');
        
        // Create session
        $this->create_session($user, $tokens);
        
        // Clear Okta state and nonce
        $this->session->unset_userdata('okta_state');
        $this->session->unset_userdata('okta_nonce');
        
        // Verify session was created
        $logged_in = $this->session->userdata('logged_in');
        log_message('info', 'Session created. logged_in status: ' . ($logged_in ? 'TRUE' : 'FALSE'));
        log_message('info', 'User ' . $username . ' logged in via Okta - redirecting to dashboard');
        
        // Set success message
        $this->session->set_flashdata('success', 'Welcome back, ' . $full_name . '!');
        
        // Redirect to dashboard
        redirect('dashboard');
    }
    
    /**
     * Local login handler
     */
    public function local_login() {
        if (!$this->config->item('okta_local_fallback')) {
            show_error('Local authentication is not enabled', 403);
            return;
        }
        
        // Validate form input
        $this->form_validation->set_rules('username', 'Username', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->login();
            return;
        }
        
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        
        // Verify credentials
        $user = $this->user_model->verify_local_credentials($username, $password);
        
        if (!$user) {
            log_message('warning', 'Failed login attempt for username: ' . $username);
            $this->session->set_flashdata('error', 'Invalid username or password.');
            redirect('auth/login');
            return;
        }
        
        // Create session
        $this->create_session($user);
        
        log_message('info', 'User ' . $username . ' logged in locally');
        redirect('dashboard');
    }
    
    /**
     * Create user session
     * 
     * @param object $user
     * @param array $tokens Optional Okta tokens
     */
    private function create_session($user, $tokens = null) {
        $session_data = array(
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'user_type' => $user->user_type,
            'roles' => $user->roles,
            'primary_role' => $this->role_mapper->get_primary_role($user->roles),
            'logged_in' => TRUE,
            'login_time' => time()
        );
        
        if ($tokens) {
            $session_data['access_token'] = $tokens['access_token'];
            $session_data['id_token'] = $tokens['id_token'];
            if (isset($tokens['refresh_token'])) {
                $session_data['refresh_token'] = $tokens['refresh_token'];
            }
            if (isset($tokens['expires_in'])) {
                $session_data['token_expires_at'] = time() + $tokens['expires_in'];
            }
        }
        
        log_message('debug', 'Setting session data: ' . json_encode(array_keys($session_data)));
        
        // Regenerate session ID for security and to ensure fresh session
        $this->session->sess_regenerate(TRUE);
        
        // Set session data
        $this->session->set_userdata($session_data);
    }
    
    /**
     * Logout
     */
    public function logout() {
        $user_type = $this->session->userdata('user_type');
        $id_token = $this->session->userdata('id_token');
        
        // Destroy session
        $this->session->sess_destroy();
        
        // If external user, redirect to Okta logout
        if ($user_type === 'external' && $id_token && $this->config->item('okta_enabled')) {
            $logout_url = $this->okta_service->get_logout_url($id_token);
            redirect($logout_url);
        } else {
            // Local logout
            $this->session->set_flashdata('success', 'You have been logged out successfully.');
            redirect('auth/login');
        }
    }
    
    /**
     * Access denied page
     */
    public function access_denied() {
        $this->load->view('auth/access_denied');
    }
}

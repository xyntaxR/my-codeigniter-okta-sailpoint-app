<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Okta Service Library
 * 
 * Handles Okta OIDC authentication flow and user management
 */
class Okta_service {
    
    protected $CI;
    protected $okta_domain;
    protected $client_id;
    protected $client_secret;
    protected $auth_server_id;
    protected $redirect_uri;
    protected $scopes;
    protected $issuer;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->config->load('okta');
        $this->CI->load->library('okta_jwt_verifier');
        
        $this->okta_domain = $this->CI->config->item('okta_domain');
        $this->client_id = $this->CI->config->item('okta_client_id');
        $this->client_secret = $this->CI->config->item('okta_client_secret');
        $this->auth_server_id = $this->CI->config->item('okta_auth_server_id');
        $this->redirect_uri = $this->CI->config->item('okta_redirect_uri');
        $this->scopes = implode(' ', $this->CI->config->item('okta_scopes'));
        
        $this->issuer = "https://{$this->okta_domain}/oauth2/{$this->auth_server_id}";
    }
    
    /**
     * Get authorization URL for Okta login
     * 
     * @return string
     */
    public function get_authorization_url() {
        // Generate state parameter for CSRF protection
        $state = bin2hex(random_bytes(16));
        $this->CI->session->set_userdata('okta_state', $state);
        
        // Generate nonce for ID token validation
        $nonce = bin2hex(random_bytes(16));
        $this->CI->session->set_userdata('okta_nonce', $nonce);
        
        $params = array(
            'client_id' => $this->client_id,
            'response_type' => 'code',
            'scope' => $this->scopes,
            'redirect_uri' => $this->redirect_uri,
            'state' => $state,
            'nonce' => $nonce
        );
        
        $authorize_url = $this->issuer . '/v1/authorize?' . http_build_query($params);
        
        return $authorize_url;
    }
    
    /**
     * Exchange authorization code for tokens
     * 
     * @param string $code Authorization code
     * @return array|false Array with access_token, id_token, etc. or false on failure
     */
    public function exchange_code_for_tokens($code) {
        $token_endpoint = $this->issuer . '/v1/token';
        
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        );
        
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $token_endpoint, [
                'form_params' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                return $body;
            }
            
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'Token exchange failed: ' . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Verify ID token and extract user information
     * 
     * @param string $id_token
     * @return object|false User information or false on failure
     */
    public function verify_id_token($id_token) {
        $decoded = $this->CI->okta_jwt_verifier->verify($id_token);
        
        if (!$decoded) {
            return FALSE;
        }
        
        // Validate nonce
        $stored_nonce = $this->CI->session->userdata('okta_nonce');
        if (!isset($decoded->nonce) || $decoded->nonce !== $stored_nonce) {
            log_message('error', 'Nonce validation failed');
            return FALSE;
        }
        
        return $decoded;
    }
    
    /**
     * Get user information from Okta
     * 
     * @param string $access_token
     * @return array|false
     */
    public function get_user_info($access_token) {
        $userinfo_endpoint = $this->issuer . '/v1/userinfo';
        
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $userinfo_endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept' => 'application/json'
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'Failed to get user info: ' . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Extract groups/roles from token
     * 
     * @param object $token_data
     * @return array
     */
    public function extract_groups($token_data) {
        $groups = array();
        
        if (isset($token_data->groups)) {
            $groups = is_array($token_data->groups) ? $token_data->groups : array($token_data->groups);
        }
        
        return $groups;
    }
    
    /**
     * Get logout URL
     * 
     * @param string $id_token_hint Optional ID token for logout
     * @return string
     */
    public function get_logout_url($id_token_hint = null) {
        $params = array(
            'post_logout_redirect_uri' => $this->CI->config->item('okta_post_logout_redirect_uri')
        );
        
        if ($id_token_hint) {
            $params['id_token_hint'] = $id_token_hint;
        }
        
        $logout_url = $this->issuer . '/v1/logout?' . http_build_query($params);
        
        return $logout_url;
    }
    
    /**
     * Refresh access token
     * 
     * @param string $refresh_token
     * @return array|false
     */
    public function refresh_token($refresh_token) {
        $token_endpoint = $this->issuer . '/v1/token';
        
        $params = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => $this->scopes
        );
        
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $token_endpoint, [
                'form_params' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'Token refresh failed: ' . $e->getMessage());
            return FALSE;
        }
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SailPoint Governance Service Library
 * 
 * Handles SailPoint IdentityIQ/IdentityNow API integration for:
 * - Identity governance and administration
 * - Access certifications and reviews
 * - Entitlement and role management
 * - User access data retrieval
 * 
 * Note: Authentication is handled by Okta. This service provides governance data.
 */
class Sailpoint_service {
    
    protected $CI;
    protected $sailpoint_domain;
    protected $client_id;
    protected $client_secret;
    protected $token_url;
    protected $api_base_url;
    protected $timeout;
    protected $auth_method;
    protected $access_token;
    protected $cached_token;
    protected $token_expires_at;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->config->load('sailpoint');
        
        $this->sailpoint_domain = $this->CI->config->item('sailpoint_domain');
        $this->client_id = $this->CI->config->item('sailpoint_client_id');
        $this->client_secret = $this->CI->config->item('sailpoint_client_secret');
        $this->token_url = $this->CI->config->item('sailpoint_token_url');
        $this->api_base_url = $this->CI->config->item('sailpoint_api_base_url');
        $this->timeout = $this->CI->config->item('sailpoint_timeout');
        $this->auth_method = $this->CI->config->item('sailpoint_auth_method');
        $this->access_token = $this->CI->config->item('sailpoint_access_token');
    }
    
    /**
     * Get API access token using client credentials
     * 
     * @return string|false Access token or false on failure
     */
    private function get_api_token() {
        // Check if we have a cached valid token
        if ($this->cached_token && $this->token_expires_at && time() < $this->token_expires_at) {
            return $this->cached_token;
        }
        
        // Use personal access token if configured
        if ($this->auth_method === 'personal_access_token' && !empty($this->access_token)) {
            return $this->access_token;
        }
        
        // Get token using client credentials
        if ($this->auth_method === 'client_credentials') {
            $params = array(
                'grant_type' => 'client_credentials',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret
            );
            
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', $this->token_url, [
                    'form_params' => $params,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'timeout' => $this->timeout
                ]);
                
                if ($response->getStatusCode() === 200) {
                    $body = json_decode($response->getBody()->getContents(), true);
                    
                    // Cache the token
                    $this->cached_token = $body['access_token'];
                    $this->token_expires_at = time() + ($body['expires_in'] ?? 3600) - 60; // 1 min buffer
                    
                    return $this->cached_token;
                }
                
                return FALSE;
                
            } catch (Exception $e) {
                log_message('error', 'SailPoint API token request failed: ' . $e->getMessage());
                return FALSE;
            }
        }
        
        return FALSE;
    }
    
    /**
     * Make authenticated API request to SailPoint
     * 
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $params Query parameters or body data
     * @return array|false API response or false on failure
     */
    private function api_request($endpoint, $method = 'GET', $params = array()) {
        $token = $this->get_api_token();
        
        if (!$token) {
            log_message('error', 'Failed to get SailPoint API token');
            return FALSE;
        }
        
        $url = $this->api_base_url . $endpoint;
        
        try {
            $client = new \GuzzleHttp\Client();
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'timeout' => $this->timeout
            ];
            
            if ($method === 'GET' && !empty($params)) {
                $options['query'] = $params;
            } elseif (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($params)) {
                $options['json'] = $params;
            }
            
            $response = $client->request($method, $url, $options);
            
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $body = $response->getBody()->getContents();
                return json_decode($body, true);
            }
            
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'SailPoint API request failed: ' . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Search for identity by email or username
     * 
     * @param string $identifier Email or username
     * @return array|false Identity data or false if not found
     */
    public function get_identity_by_email($identifier) {
        $params = array(
            'filters' => 'email eq "' . $identifier . '"'
        );
        
        $result = $this->api_request('/identities', 'GET', $params);
        
        if ($result && is_array($result) && count($result) > 0) {
            return $result[0]; // Return first match
        }
        
        // Try by name if email search failed
        $params = array(
            'filters' => 'name eq "' . $identifier . '"'
        );
        
        $result = $this->api_request('/identities', 'GET', $params);
        
        if ($result && is_array($result) && count($result) > 0) {
            return $result[0];
        }
        
        return FALSE;
    }
    
    /**
     * Get identity by ID
     * 
     * @param string $identity_id Identity ID
     * @return array|false Identity data or false on failure
     */
    public function get_identity_by_id($identity_id) {
        return $this->api_request('/identities/' . urlencode($identity_id));
    }
    
    /**
     * Get user's entitlements from SailPoint
     * 
     * @param string $identity_id Identity ID
     * @return array Array of entitlements
     */
    public function get_user_entitlements($identity_id) {
        $result = $this->api_request('/identities/' . urlencode($identity_id) . '/entitlements');
        
        if ($result && is_array($result)) {
            return $result;
        }
        
        return array();
    }
    
    /**
     * Get user's access profiles/roles from SailPoint
     * 
     * @param string $identity_id Identity ID
     * @return array Array of access profiles/roles
     */
    public function get_user_access_profiles($identity_id) {
        $result = $this->api_request('/identities/' . urlencode($identity_id) . '/access-profiles');
        
        if ($result && is_array($result)) {
            return $result;
        }
        
        return array();
    }
    
    /**
     * Get user's assigned roles from SailPoint
     * 
     * @param string $identity_id Identity ID
     * @return array Array of assigned roles
     */
    public function get_user_roles($identity_id) {
        $result = $this->api_request('/identities/' . urlencode($identity_id) . '/roles');
        
        if ($result && is_array($result)) {
            return $result;
        }
        
        return array();
    }
    
    /**
     * Get comprehensive access data for a user
     * 
     * @param string $identifier Email or username
     * @return array|false Comprehensive access data or false on failure
     */
    public function get_user_access_data($identifier) {
        // First, find the identity
        $identity = $this->get_identity_by_email($identifier);
        
        if (!$identity) {
            log_message('warning', 'SailPoint identity not found for: ' . $identifier);
            return FALSE;
        }
        
        $identity_id = $identity['id'];
        
        $role_source = $this->CI->config->item('sailpoint_role_source');
        
        $access_data = array(
            'identity' => $identity,
            'roles' => array(),
            'entitlements' => array(),
            'access_profiles' => array()
        );
        
        // Get roles
        if ($role_source === 'roles' || $role_source === 'all') {
            $access_data['roles'] = $this->get_user_roles($identity_id);
        }
        
        // Get entitlements
        if ($role_source === 'entitlements' || $role_source === 'all') {
            $access_data['entitlements'] = $this->get_user_entitlements($identity_id);
        }
        
        // Get access profiles
        if ($role_source === 'access' || $role_source === 'all') {
            $access_data['access_profiles'] = $this->get_user_access_profiles($identity_id);
        }
        
        return $access_data;
    }
    
    /**
     * Map SailPoint roles/entitlements to application role
     * 
     * @param array $access_data Access data from get_user_access_data()
     * @return string Application role
     */
    public function map_access_to_role($access_data) {
        $role_mapping = $this->CI->config->item('sailpoint_role_mapping');
        $default_role = $this->CI->config->item('sailpoint_default_role');
        
        if (!$access_data) {
            return $default_role;
        }
        
        $all_role_names = array();
        
        // Collect all role/entitlement names
        if (isset($access_data['roles']) && is_array($access_data['roles'])) {
            foreach ($access_data['roles'] as $role) {
                $all_role_names[] = $role['name'] ?? $role;
            }
        }
        
        if (isset($access_data['entitlements']) && is_array($access_data['entitlements'])) {
            foreach ($access_data['entitlements'] as $entitlement) {
                $all_role_names[] = $entitlement['name'] ?? $entitlement;
            }
        }
        
        if (isset($access_data['access_profiles']) && is_array($access_data['access_profiles'])) {
            foreach ($access_data['access_profiles'] as $profile) {
                $all_role_names[] = $profile['name'] ?? $profile;
            }
        }
        
        // Check each role name against the mapping
        foreach ($all_role_names as $sp_role) {
            if (is_string($sp_role) && isset($role_mapping[$sp_role])) {
                log_message('info', 'SailPoint role mapped: ' . $sp_role . ' => ' . $role_mapping[$sp_role]);
                return $role_mapping[$sp_role];
            }
        }
        
        // Return default role if no mapping found
        log_message('info', 'No SailPoint role mapping found, using default: ' . $default_role);
        return $default_role;
    }
    
    /**
     * Sync user data from SailPoint to local database
     * 
     * @param string $user_email User's email from Okta
     * @param array $existing_user_data Existing user data from database
     * @return array Updated user data with SailPoint attributes
     */
    public function sync_user_from_sailpoint($user_email, $existing_user_data = array()) {
        if (!$this->CI->config->item('sailpoint_enabled')) {
            return $existing_user_data;
        }
        
        // Get access data from SailPoint
        $access_data = $this->get_user_access_data($user_email);
        
        if (!$access_data) {
            log_message('warning', 'Could not sync user from SailPoint: ' . $user_email);
            return $existing_user_data;
        }
        
        $identity = $access_data['identity'];
        $attribute_mapping = $this->CI->config->item('sailpoint_attribute_mapping');
        
        // Map SailPoint attributes to application fields
        $updated_data = $existing_user_data;
        
        foreach ($attribute_mapping as $sp_attr => $app_field) {
            if (isset($identity[$sp_attr])) {
                $updated_data[$app_field] = $identity[$sp_attr];
            }
        }
        
        // Map roles
        $updated_data['roles'] = $this->map_access_to_role($access_data);
        
        // Store SailPoint identity ID for future reference
        $updated_data['sailpoint_id'] = $identity['id'];
        
        // Mark last sync time
        $updated_data['sailpoint_synced_at'] = date('Y-m-d H:i:s');
        
        log_message('info', 'User synced from SailPoint: ' . $user_email);
        
        return $updated_data;
    }
    
    /**
     * Check if user should be synced from SailPoint
     * 
     * @param array $user_data User data from database
     * @return boolean True if sync is needed
     */
    public function should_sync_user($user_data) {
        if (!$this->CI->config->item('sailpoint_enabled')) {
            return FALSE;
        }
        
        if (!$this->CI->config->item('sailpoint_sync_on_login')) {
            return FALSE;
        }
        
        // Check if never synced
        if (!isset($user_data['sailpoint_synced_at'])) {
            return TRUE;
        }
        
        // Check if sync interval has passed
        $sync_interval = $this->CI->config->item('sailpoint_sync_interval');
        $last_sync = strtotime($user_data['sailpoint_synced_at']);
        
        if (time() - $last_sync > $sync_interval) {
            return TRUE;
        }
        
        return FALSE;
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Role Mapping Service Library
 * 
 * Handles mapping between Okta groups and application roles
 */
class Role_mapper {
    
    protected $CI;
    protected $role_mapping;
    protected $default_role;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->config->load('okta');
        
        $this->role_mapping = $this->CI->config->item('okta_role_mapping');
        $this->default_role = $this->CI->config->item('okta_default_role');
    }
    
    /**
     * Map Okta groups to application roles
     * 
     * @param array $okta_groups Array of Okta group names
     * @return array Array of application role names
     */
    public function map_groups_to_roles($okta_groups) {
        $roles = array();
        
        if (empty($okta_groups)) {
            return array($this->default_role);
        }
        
        foreach ($okta_groups as $group) {
            if (isset($this->role_mapping[$group])) {
                $roles[] = $this->role_mapping[$group];
            }
        }
        
        // If no matching roles found, assign default role
        if (empty($roles)) {
            $roles[] = $this->default_role;
        }
        
        // Remove duplicates
        $roles = array_unique($roles);
        
        return $roles;
    }
    
    /**
     * Get primary role (first/highest priority role)
     * 
     * @param array $roles
     * @return string
     */
    public function get_primary_role($roles) {
        if (empty($roles)) {
            return $this->default_role;
        }
        
        // Priority order: admin > user > viewer
        $priority = array('admin', 'user', 'viewer');
        
        foreach ($priority as $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }
        
        return $roles[0];
    }
    
    /**
     * Check if user has a specific role
     * 
     * @param array $user_roles
     * @param string $required_role
     * @return bool
     */
    public function has_role($user_roles, $required_role) {
        return in_array($required_role, $user_roles);
    }
    
    /**
     * Check if user has any of the specified roles
     * 
     * @param array $user_roles
     * @param array $allowed_roles
     * @return bool
     */
    public function has_any_role($user_roles, $allowed_roles) {
        return !empty(array_intersect($user_roles, $allowed_roles));
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User Model
 * 
 * Handles user data management for both local and external (Okta) users
 */
class User_model extends CI_Model {
    
    private $table = 'users';
    
    public function __construct() {
        parent::__construct();
        $this->create_table_if_not_exists();
    }
    
    /**
     * Create users table if it doesn't exist
     */
    private function create_table_if_not_exists() {
        $query = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NULL,
            full_name VARCHAR(255),
            user_type ENUM('local', 'external') DEFAULT 'local',
            external_id VARCHAR(255) NULL,
            roles TEXT NULL,
            is_active TINYINT(1) DEFAULT 1,
            last_login DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_external_id (external_id)
        )";
        
        $this->db->query($query);
    }
    
    /**
     * Get user by username
     * 
     * @param string $username
     * @return object|null
     */
    public function get_by_username($username) {
        $query = $this->db->get_where($this->table, array('username' => $username, 'is_active' => 1));
        $user = $query->row();
        
        if ($user && $user->roles) {
            $user->roles = json_decode($user->roles, true);
        }
        
        return $user ?: null;
    }
    
    /**
     * Get user by email
     * 
     * @param string $email
     * @return object|null
     */
    public function get_by_email($email) {
        $query = $this->db->get_where($this->table, array('email' => $email, 'is_active' => 1));
        $user = $query->row();
        
        if ($user && $user->roles) {
            $user->roles = json_decode($user->roles, true);
        }
        
        return $user ?: null;
    }
    
    /**
     * Get user by external ID (Okta user ID)
     * 
     * @param string $external_id
     * @return object|null
     */
    public function get_by_external_id($external_id) {
        $query = $this->db->get_where($this->table, array(
            'external_id' => $external_id,
            'user_type' => 'external',
            'is_active' => 1
        ));
        $user = $query->row();
        
        if ($user && $user->roles) {
            $user->roles = json_decode($user->roles, true);
        }
        
        return $user ?: null;
    }
    
    /**
     * Get user by ID
     * 
     * @param int $id
     * @return object|null
     */
    public function get_by_id($id) {
        $query = $this->db->get_where($this->table, array('id' => $id, 'is_active' => 1));
        $user = $query->row();
        
        if ($user && $user->roles) {
            $user->roles = json_decode($user->roles, true);
        }
        
        return $user ?: null;
    }
    
    /**
     * Create a new local user
     * 
     * @param array $data
     * @return int|false User ID or false on failure
     */
    public function create_local_user($data) {
        $insert_data = array(
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'full_name' => $data['full_name'] ?? '',
            'user_type' => 'local',
            'roles' => json_encode($data['roles'] ?? array('user')),
            'is_active' => 1
        );
        
        if ($this->db->insert($this->table, $insert_data)) {
            return $this->db->insert_id();
        }
        
        return FALSE;
    }
    
    /**
     * Create or update external user (from Okta)
     * 
     * @param array $data
     * @return int|false User ID or false on failure
     */
    public function create_or_update_external_user($data) {
        // Check if user already exists
        $existing_user = $this->get_by_external_id($data['external_id']);
        
        $user_data = array(
            'username' => $data['username'],
            'email' => $data['email'],
            'full_name' => $data['full_name'] ?? '',
            'user_type' => 'external',
            'external_id' => $data['external_id'],
            'roles' => json_encode($data['roles'] ?? array('user')),
            'is_active' => 1,
            'last_login' => date('Y-m-d H:i:s')
        );
        
        if ($existing_user) {
            // Update existing user
            $this->db->where('id', $existing_user->id);
            if ($this->db->update($this->table, $user_data)) {
                return $existing_user->id;
            }
        } else {
            // Create new user
            if ($this->db->insert($this->table, $user_data)) {
                return $this->db->insert_id();
            }
        }
        
        return FALSE;
    }
    
    /**
     * Verify local user credentials
     * 
     * @param string $username
     * @param string $password
     * @return object|false User object or false on failure
     */
    public function verify_local_credentials($username, $password) {
        $user = $this->get_by_username($username);
        
        if ($user && $user->user_type === 'local' && $user->password_hash) {
            if (password_verify($password, $user->password_hash)) {
                // Update last login
                $this->update_last_login($user->id);
                return $user;
            }
        }
        
        return FALSE;
    }
    
    /**
     * Update last login timestamp
     * 
     * @param int $user_id
     * @return bool
     */
    public function update_last_login($user_id) {
        $this->db->where('id', $user_id);
        return $this->db->update($this->table, array('last_login' => date('Y-m-d H:i:s')));
    }
    
    /**
     * Update user roles
     * 
     * @param int $user_id
     * @param array $roles
     * @return bool
     */
    public function update_roles($user_id, $roles) {
        $this->db->where('id', $user_id);
        return $this->db->update($this->table, array('roles' => json_encode($roles)));
    }
    
    /**
     * Deactivate user
     * 
     * @param int $user_id
     * @return bool
     */
    public function deactivate_user($user_id) {
        $this->db->where('id', $user_id);
        return $this->db->update($this->table, array('is_active' => 0));
    }
    
    /**
     * Get all users
     * 
     * @param string $user_type Optional filter by user type
     * @return array
     */
    public function get_all_users($user_type = null) {
        if ($user_type) {
            $this->db->where('user_type', $user_type);
        }
        $this->db->where('is_active', 1);
        $query = $this->db->get($this->table);
        $users = $query->result();
        
        foreach ($users as $user) {
            if ($user->roles) {
                $user->roles = json_decode($user->roles, true);
            }
        }
        
        return $users;
    }
}

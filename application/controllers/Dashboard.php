<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard Controller
 * 
 * Protected area requiring authentication
 */
class Dashboard extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        // Authentication will be handled by Auth_filter hook
    }
    
    /**
     * Dashboard home page
     */
    public function index() {
        $data['user'] = array(
            'username' => $this->session->userdata('username'),
            'email' => $this->session->userdata('email'),
            'full_name' => $this->session->userdata('full_name'),
            'user_type' => $this->session->userdata('user_type'),
            'primary_role' => $this->session->userdata('primary_role'),
            'roles' => $this->session->userdata('roles')
        );
        
        $this->load->view('dashboard/index', $data);
    }
}

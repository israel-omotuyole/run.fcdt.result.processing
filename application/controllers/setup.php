<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setup extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library('session');

        // Kick out anyone who isn't logged in
        if (!$this->session->userdata('logged_in')) {
            redirect('auth');
        }
    }

    public function index() {
        $this->load->view('setup_view');
    }

    public function process_setup() {
        // 1. Capture the user's selections
        $setup_data = array(
            'active_dept'     => $this->input->post('department', TRUE),
            'active_level'    => $this->input->post('level', TRUE),
            'active_session'  => $this->input->post('academic_session', TRUE),
            'active_semester' => $this->input->post('semester', TRUE)
        );

        // 2. Save them to the session
        $this->session->set_userdata($setup_data);
        
        // 3. Clear any old temporary audit data so the dashboard loads fresh
        $this->session->unset_userdata('current_audit_candidates');

        // 4. Route them to their specific dashboard based on their role
        if ($this->session->userdata('role') === 'dean') {
            redirect('dean');
        } else {
            redirect('result');
        }
    }
}
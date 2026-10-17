<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library('session');
        $this->load->database();
    }

    // ==========================================
    // LOGIN LOGIC
    // ==========================================
    public function index() {
        if ($this->session->userdata('logged_in')) {
            $this->route_user($this->session->userdata('role'));
        }
        $this->load->view('login_view');
    }

    public function process_login() {
        $email = $this->input->post('email', TRUE);
        $password = $this->input->post('password', TRUE);

        $this->db->where('email', $email);
        $query = $this->db->get('users');

        if ($query->num_rows() == 1) {
            $user = $query->row();
            
            if (password_verify($password, $user->password)) {
                $session_data = array(
                    'user_id'   => $user->id,
                    'fullname'  => $user->fullname,
                    'email'     => $user->email,
                    'role'      => $user->role,
                    'deptId'    => $user->deptId,
                    'logged_in' => TRUE
                );
                $this->session->set_userdata($session_data);
                $this->route_user($user->role);
            } else {
                $this->session->set_flashdata('error', 'Invalid password.');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('error', 'Account not found.');
            redirect('auth');
        }
    }

    // ==========================================
    // SIGN-UP LOGIC
    // ==========================================
    public function register() {
        if ($this->session->userdata('logged_in')) {
            $this->route_user($this->session->userdata('role'));
        }
        $this->load->view('register_view');
    }

    public function process_register() {
        $fullname    = $this->input->post('fullname', TRUE);
        $email       = $this->input->post('email', TRUE);
        $password    = $this->input->post('password', TRUE);
        $role        = $this->input->post('role', TRUE);
        $faculty_pin = $this->input->post('faculty_pin', TRUE);
        $deptId      = 1; 

        // 1. Role-Specific PIN Validation
        $exam_officer_pin = 'RUN-EXAM-2026';
        $dean_pin         = 'RUN-DEAN-SECURE';

        if ($role === 'exam_officer' && $faculty_pin !== $exam_officer_pin) {
            $this->session->set_flashdata('error', 'Invalid access PIN for Exam Officer registration.');
            redirect('auth/register');
            return;
        } elseif ($role === 'dean' && $faculty_pin !== $dean_pin) {
            $this->session->set_flashdata('error', 'Invalid access PIN for Dean/Sub-Dean registration.');
            redirect('auth/register');
            return;
        }

        // 2. Acceptable Email Domain Check
        if (!preg_match('/@run\.edu\.ng$/i', $email)) {
            $this->session->set_flashdata('error', 'Registration is restricted to official @run.edu.ng staff emails.');
            redirect('auth/register');
            return;
        }

        // 3. Prevent Duplicate Accounts
        $this->db->where('email', $email);
        if ($this->db->get('users')->num_rows() > 0) {
            $this->session->set_flashdata('error', 'This email is already registered. Please log in.');
            redirect('auth/register');
            return;
        }

        // 4. Hash Password and Save User
        $data = array(
            'fullname' => $fullname,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role'     => $role,
            'deptId'   => $deptId
        );

        if ($this->db->insert('users', $data)) {
            $this->session->set_flashdata('success', 'Account created successfully! You can now log in.');
            redirect('auth');
        } else {
            $this->session->set_flashdata('error', 'Failed to create account. Please try again.');
            redirect('auth/register');
        }
    }

    // ==========================================
    // ROUTING & LOGOUT
    // ==========================================
    private function route_user($role) {
        // Send everyone to the setup page first to select Dept and Level
        redirect('setup'); 
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('auth');
    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'form']);
    }

    public function index() {
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
        }
        redirect('login');
    }

    public function login() {
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
        }

        if ($this->input->method() === 'post') {
            $username = $this->input->post('username', TRUE);
            $password = $this->input->post('password');

            $user = $this->User_model->login($username, $password);
            if ($user) {
                $this->session->set_userdata([
                    'logged_in'  => TRUE,
                    'user_id'    => $user['id'],
                    'username'   => $user['username'],
                    'full_name'  => $user['full_name'],
                    'role'       => $user['role'],
                ]);
                redirect('dashboard');
            } else {
                $this->session->set_flashdata('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
                redirect('login');
            }
        }

        $this->load->view('auth/login');
    }

    public function register() {
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('username', 'ชื่อผู้ใช้', 'required|min_length[3]|is_unique[users.username]');
            $this->form_validation->set_rules('email', 'อีเมล', 'required|valid_email|is_unique[users.email]');
            $this->form_validation->set_rules('password', 'รหัสผ่าน', 'required|min_length[6]');
            $this->form_validation->set_rules('full_name', 'ชื่อ-นามสกุล', 'required');

            if ($this->form_validation->run()) {
                $data = [
                    'username'  => $this->input->post('username', TRUE),
                    'email'     => $this->input->post('email', TRUE),
                    'password'  => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
                    'full_name' => $this->input->post('full_name', TRUE),
                    'role'      => 'staff',
                ];
                $this->User_model->create($data);
                $this->session->set_flashdata('success', 'ลงทะเบียนสำเร็จ กรุณาเข้าสู่ระบบ');
                redirect('login');
            } else {
                $this->session->set_flashdata('error', validation_errors());
                redirect('register');
            }
        }

        $this->load->view('auth/register');
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('login');
    }
}

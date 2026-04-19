<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    protected $table = 'users';

    // Superadmin credentials (hardcoded, not stored in DB)
    private $superadmin = [
        'username'  => 'superadmin',
        'password'  => 'example123E$',
        'id'        => null,   // null เพื่อไม่ติด FK กับ users table
        'full_name' => 'Super Administrator',
        'role'      => 'superadmin',
    ];

    public function login($username, $password) {
        // ตรวจสอบ superadmin ก่อน
        if ($username === $this->superadmin['username'] && $password === $this->superadmin['password']) {
            return $this->superadmin;
        }
        // ตรวจสอบ user ในฐานข้อมูล
        $user = $this->db->where('username', $username)->or_where('email', $username)->get($this->table)->row_array();
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }
}

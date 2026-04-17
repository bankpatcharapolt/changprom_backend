<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    protected $table = 'users';

    public function login($username, $password) {
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

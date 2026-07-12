<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Branch_model extends CI_Model {

    protected $table = 'branches';

    public function get_all() {
        return $this->db
            ->order_by('active', 'DESC')
            ->order_by('name', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function get_active() {
        return $this->db
            ->where('active', 1)
            ->order_by('name', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function get_by_id($id) {
        return $this->db
            ->where('id', $id)
            ->get($this->table)
            ->row_array();
    }

    public function create($data) {
        $this->db->insert($this->table, $this->_clean($data, true));
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id)->update($this->table, $this->_clean($data));
    }

    public function delete($id) {
        $this->db->where('id', $id)->delete($this->table);
    }

    private function _clean($data, $is_new = false) {
        $allowed = ['name', 'lat', 'lng', 'address', 'phone', 'active'];
        $row = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $row[$f] = ($data[$f] === '' || $data[$f] === null) ? null : $data[$f];
            }
        }
        if ($is_new) $row['created_at'] = date('Y-m-d H:i:s');
        $row['updated_at'] = date('Y-m-d H:i:s');
        return $row;
    }
}

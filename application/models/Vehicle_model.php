<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vehicle_model extends CI_Model {

    protected $table = 'vehicles';

    public function get_all() {
        return $this->db
            ->select('id, vehicle_type, license_plate, province, brand, model, color, map_link, note, active, created, updated')
            ->from($this->table)
            ->order_by('active', 'DESC')
            ->order_by('vehicle_type', 'ASC')
            ->order_by('id', 'ASC')
            ->get()->result_array();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function create($data) {
        $this->db->insert($this->table, $this->_clean($data, true));
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id)->update($this->table, $this->_clean($data, false));
    }

    public function delete($id) {
        $this->db->where('id', $id)->delete($this->table);
    }

    public function count_by_type() {
        return $this->db
            ->select('vehicle_type, COUNT(*) as total, SUM(active) as active_count')
            ->group_by('vehicle_type')
            ->get($this->table)->result_array();
    }

    private function _clean($data, $is_new = false) {
        $allowed = ['vehicle_type','license_plate','province','brand','model','color','map_link','note','active'];
        $row = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $row[$f] = $data[$f] === '' ? null : $data[$f];
            }
        }
        // license_plate บังคับมี
        if (isset($row['license_plate'])) $row['license_plate'] = strtoupper(trim($row['license_plate']));
        if (!isset($row['active'])) $row['active'] = 1;
        $row['updated'] = date('Y-m-d H:i:s');
        if ($is_new) $row['created'] = date('Y-m-d H:i:s');
        return $row;
    }
}

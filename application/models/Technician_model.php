<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Technician_model extends CI_Model {

    private $table = 'register';

    public function __construct() { parent::__construct(); }

    public function get_all() {
        return $this->db
            ->select('id, reg_name, reg_address, reg_telephone, reg_email, latitude, longitude, active, created, updated')
            ->from($this->table)->get()->result_array();
    }

    // active only — ใช้ใน calendar.js dropdown
    public function get_active() {
        return $this->db
            ->select('id, reg_name, reg_telephone')
            ->from($this->table)
            ->where('active', 1)
            ->order_by('reg_name', 'asc')
            ->get()->result_array();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('id, reg_name, reg_address, reg_telephone, reg_email, latitude, longitude, active, created, updated, reg_username')
            ->from($this->table)->where('id', $id)->get()->row_array();
    }

    public function datatable($post) {
        $start     = isset($post['start'])  ? (int)$post['start']  : 0;
        $length    = isset($post['length']) ? (int)$post['length'] : 25;
        $search    = isset($post['search']['value']) ? trim($post['search']['value']) : '';
        $order_col = isset($post['order'][0]['column']) ? (int)$post['order'][0]['column'] : 1;
        $order_dir = isset($post['order'][0]['dir'])    ? $post['order'][0]['dir']          : 'asc';
        $cols      = ['id','reg_name','reg_telephone','reg_address','latitude','active','id'];
        $order_fld = isset($cols[$order_col]) ? $cols[$order_col] : 'reg_name';

        $total = $this->db->count_all($this->table);
        $this->db->select('id,reg_name,reg_address,reg_telephone,reg_email,latitude,longitude,active,created,updated')
                 ->from($this->table);
        if ($search !== '') {
            $this->db->group_start()
                ->like('reg_name', $search)->or_like('reg_telephone', $search)->or_like('reg_address', $search)
                ->group_end();
        }
        $filtered = $this->db->count_all_results('', false);
        $data = $this->db->order_by($order_fld, $order_dir)->limit($length, $start)->get()->result_array();
        return ['recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data];
    }

    public function create($data) {
        $this->db->insert($this->table, $this->_clean($data, true));
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $row = $this->_clean($data, false);
        $this->db->where('id', $id)->update($this->table, $row);
        return $this->db->affected_rows();
    }

    public function delete($id) {
        $this->db->where('id', $id)->delete($this->table);
        return $this->db->affected_rows();
    }

    public function search($q) {
        return $this->db->select('id,reg_name,reg_telephone')->from($this->table)
            ->where('active', 1)->like('reg_name', $q)->limit(10)->get()->result_array();
    }

    private function _clean($data, $is_new = false) {
        $row = [
            'reg_name'      => isset($data['reg_name'])      ? trim($data['reg_name'])      : '',
            'reg_address'   => isset($data['reg_address'])   ? trim($data['reg_address'])   : null,
            'reg_telephone' => isset($data['reg_telephone']) ? trim($data['reg_telephone']) : null,
            'reg_email'     => isset($data['reg_email'])     ? trim($data['reg_email'])     : null,
            'latitude'      => (isset($data['latitude'])  && $data['latitude']  !== '') ? $data['latitude']  : null,
            'longitude'     => (isset($data['longitude']) && $data['longitude'] !== '') ? $data['longitude'] : null,
            'active'        => isset($data['active']) ? (int)$data['active'] : 1,
            'updated'       => date('Y-m-d H:i:s'),
        ];
        // บันทึก username
        if (isset($data['login_username'])) {
            $row['reg_username'] = trim($data['login_username']) ?: null;
        }
        // บันทึก password เฉพาะตอนสร้างใหม่หรือถ้ามีการระบุมา
        if (!empty($data['login_password'])) {
            $row['reg_pass'] = password_hash(trim($data['login_password']), PASSWORD_DEFAULT);
        } elseif ($is_new && !empty($data['login_username'])) {
            // ถ้าสร้างใหม่และมี username แต่ไม่มี password — ค่าว่าง
            $row['reg_pass'] = null;
        }
        if ($is_new) $row['created'] = date('Y-m-d H:i:s');
        return $row;
    }
}

<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Employee_model.php
 * จัดการพนักงานทั่วไป (users.role = 'employee') + สิทธิ์ดูแผนที่ลูกค้า
 *
 * หมายเหตุ: ทุก query ที่แก้ไข/อ่านพนักงานจะ where('role','employee') เสมอ
 * เพื่อกันไม่ให้หน้านี้ไปกระทบบัญชี admin/staff/superadmin โดยไม่ตั้งใจ
 */
class Employee_model extends CI_Model {

    private $table    = 'users';
    private $reqtable = 'employee_map_requests';

    public function __construct() { parent::__construct(); }

    // ============================================================
    // Employee CRUD
    // ============================================================

    public function get_all() {
        $sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.full_name, u.phone,
                       u.position, u.active, u.map_access, u.created_at,
                      (SELECT COUNT(*) FROM employee_map_requests r WHERE r.employee_id = u.id AND r.status = 'pending') AS has_pending_request
                FROM users u
                WHERE u.role = 'employee'
                ORDER BY u.first_name ASC";
        return $this->db->query($sql)->result_array();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('id, username, email, first_name, last_name, full_name, phone, position, active, map_access, created_at')
            ->from($this->table)
            ->where('id', $id)
            ->where('role', 'employee')
            ->get()->row_array();
    }

    // เช็ค username ซ้ำ (ทั้งระบบ ไม่ใช่แค่ในกลุ่ม employee เพราะ username ต้องไม่ชนกับ admin/staff ด้วย)
    public function username_exists($username, $exclude_id = null) {
        $this->db->where('username', $username)->from($this->table);
        if (!empty($exclude_id)) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results() > 0;
    }

    // เช็ค email ซ้ำ (ทั้งระบบ — คอลัมน์ email มี UNIQUE constraint ที่ระดับ DB อยู่แล้ว)
    public function email_exists($email, $exclude_id = null) {
        $this->db->where('email', $email)->from($this->table);
        if (!empty($exclude_id)) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results() > 0;
    }

    public function datatable($post) {
        $start     = isset($post['start'])  ? (int)$post['start']  : 0;
        $length    = isset($post['length']) ? (int)$post['length'] : 25;
        $search    = isset($post['search']['value']) ? trim($post['search']['value']) : '';
        $order_col = isset($post['order'][0]['column']) ? (int)$post['order'][0]['column'] : 1;
        $order_dir = isset($post['order'][0]['dir'])    ? $post['order'][0]['dir']          : 'asc';
        $cols      = ['id', 'first_name', 'username', 'email', 'phone', 'position', 'active', 'map_access', 'id'];
        $order_fld = isset($cols[$order_col]) ? $cols[$order_col] : 'first_name';

        // total (ไม่กรอง) — เรียกแบบ reset (ค่า default) เพื่อให้ query builder ว่างก่อนเริ่ม query ถัดไป
        // (ถ้าไม่ reset ตรงนี้ where('role','employee') ที่ยังค้างอยู่จะไปปนกับ from(users u) ด้านล่าง
        //  กลายเป็น query ที่มีตาราง users ซ้อนกัน 2 ตัว แล้ว 'role' ที่ไม่ระบุ alias จะ ambiguous ทันที)
        $total = $this->db->where('role', 'employee')->count_all_results($this->table);

        $this->db->select(
                "u.id, u.username, u.email, u.first_name, u.last_name, u.full_name, u.phone, u.position, u.active, u.map_access, u.created_at, "
                . "(SELECT COUNT(*) FROM {$this->reqtable} r WHERE r.employee_id = u.id AND r.status = 'pending') AS has_pending_request",
                false
            )
            ->from($this->table . ' u')
            ->where('u.role', 'employee');
        if ($search !== '') {
            $this->db->group_start()
                ->like('u.first_name', $search)
                ->or_like('u.last_name', $search)
                ->or_like('u.username', $search)
                ->or_like('u.email', $search)
                ->or_like('u.phone', $search)
                ->group_end();
        }
        $filtered = $this->db->count_all_results('', false);

        $data = $this->db->order_by('u.' . $order_fld, $order_dir)->limit($length, $start)->get()->result_array();

        return ['recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data];
    }

    public function create($data) {
        $row = $this->_clean($data, true);
        $row['role'] = 'employee';
        try {
            $ok = $this->db->insert($this->table, $row);
        } catch (\Throwable $e) {
            log_message('error', 'Employee_model::create exception: ' . $e->getMessage());
            return ['success' => false];
        }
        if (!$ok) {
            $err = $this->db->error();
            log_message('error', 'Employee_model::create query error: ' . ($err['message'] ?? 'unknown'));
            return ['success' => false];
        }
        return ['success' => true, 'id' => $this->db->insert_id()];
    }

    public function update($id, $data) {
        $row = $this->_clean($data, false);
        try {
            $ok = $this->db->where('id', $id)->where('role', 'employee')->update($this->table, $row);
        } catch (\Throwable $e) {
            log_message('error', 'Employee_model::update exception: ' . $e->getMessage());
            return ['success' => false];
        }
        if (!$ok) {
            $err = $this->db->error();
            log_message('error', 'Employee_model::update query error: ' . ($err['message'] ?? 'unknown'));
            return ['success' => false];
        }
        return ['success' => true];
    }

    public function delete($id) {
        $this->db->where('id', $id)->where('role', 'employee')->delete($this->table);
        $affected = $this->db->affected_rows();
        // ลบประวัติคำขอของพนักงานคนนี้ไปด้วย กันข้อมูลค้าง
        $this->db->where('employee_id', $id)->delete($this->reqtable);
        return $affected;
    }

    // หมายเหตุ: first_name/last_name/username/phone/email ทุกตัวถูก validate ว่าห้ามว่าง
    // ไว้แล้วใน Employee::_validate() ก่อนจะเรียก create()/update() เสมอ จึงไม่ใส่ fallback
    // เป็น null ให้ email/phone ตรงนี้อีก (ของเดิมเคย fallback เป็น null ซึ่งชนกับคอลัมน์
    // email ที่เป็น NOT NULL + UNIQUE ในฐานข้อมูล ทำให้ insert พังตอนเว้นว่าง — position
    // ยังคง optional ตามเดิมเพราะไม่ได้ถูกกำหนดให้บังคับกรอก)
    private function _clean($data, $is_new) {
        $first = isset($data['first_name']) ? trim($data['first_name']) : '';
        $last  = isset($data['last_name'])  ? trim($data['last_name'])  : '';

        $row = [
            'first_name' => $first,
            'last_name'  => $last,
            'full_name'  => trim($first . ' ' . $last),
            'email'      => trim($data['email'] ?? ''),
            'phone'      => trim($data['phone'] ?? ''),
            'position'   => (isset($data['position']) && trim($data['position']) !== '') ? trim($data['position']) : null,
            'active'     => isset($data['active']) ? (int)$data['active'] : 1,
        ];

        if (isset($data['username']) && trim($data['username']) !== '') {
            $row['username'] = trim($data['username']);
        }
        // บันทึก password เฉพาะตอนสร้างใหม่ หรือถ้ามีการระบุมา (edit เว้นว่าง = ไม่เปลี่ยน)
        if (!empty($data['password'])) {
            $row['password'] = password_hash(trim($data['password']), PASSWORD_DEFAULT);
        }

        if ($is_new) {
            $row['created_at'] = date('Y-m-d H:i:s');
            $row['map_access'] = 0;
        }
        return $row;
    }

    // ============================================================
    // สิทธิ์ดูแผนที่ลูกค้า
    // ============================================================

    // ใช้เช็คตอนโหลดหน้า /map จริงๆ ทุกครั้ง (ไม่ cache ใน session)
    // เพื่อให้พอ superadmin อนุมัติแล้ว พนักงานเห็นผลทันทีโดยไม่ต้อง login ใหม่
    public function has_map_access($user_id) {
        $row = $this->db->select('map_access')
                        ->where('id', $user_id)
                        ->where('role', 'employee')
                        ->get($this->table)->row_array();
        return $row && (int)$row['map_access'] === 1;
    }

    public function set_map_access($id, $value) {
        $this->db->where('id', $id)->where('role', 'employee')
                 ->update($this->table, ['map_access' => (int)$value]);
    }

    public function has_pending_request($employee_id) {
        return (int)$this->db->where('employee_id', $employee_id)
                             ->where('status', 'pending')
                             ->count_all_results($this->reqtable) > 0;
    }

    // admin (หรือ superadmin) สร้างคำขอสิทธิ์ให้พนักงานคนหนึ่ง
    public function create_request($employee_id, $requested_by, $requested_by_id) {
        $this->db->insert($this->reqtable, [
            'employee_id'     => $employee_id,
            'requested_by'    => $requested_by,
            'requested_by_id' => $requested_by_id,
            'status'          => 'pending',
            'requested_at'    => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    // รายการคำขอทั้งหมด (หรือกรองตาม status) — สำหรับหน้า superadmin
    public function list_requests($status = null) {
        $this->db->select(
                'r.id, r.employee_id, r.requested_by, r.status, r.note, r.requested_at, r.decided_by, r.decided_at, '
                . 'u.first_name, u.last_name, u.full_name, u.username'
            )
            ->from($this->reqtable . ' r')
            ->join($this->table . ' u', 'u.id = r.employee_id', 'left');
        if (!empty($status)) {
            $this->db->where('r.status', $status);
        }
        return $this->db->order_by('r.requested_at', 'desc')->get()->result_array();
    }

    public function approve_request($request_id, $decided_by, $decided_by_id) {
        $req = $this->db->where('id', $request_id)->where('status', 'pending')
                        ->get($this->reqtable)->row_array();
        if (!$req) { return false; }

        $this->db->where('id', $request_id)->update($this->reqtable, [
            'status'        => 'approved',
            'decided_by'    => $decided_by,
            'decided_by_id' => $decided_by_id,
            'decided_at'    => date('Y-m-d H:i:s'),
        ]);
        $this->set_map_access($req['employee_id'], 1);
        return true;
    }

    public function reject_request($request_id, $decided_by, $decided_by_id, $note = '') {
        $req = $this->db->where('id', $request_id)->where('status', 'pending')
                        ->get($this->reqtable)->row_array();
        if (!$req) { return false; }

        $this->db->where('id', $request_id)->update($this->reqtable, [
            'status'        => 'rejected',
            'decided_by'    => $decided_by,
            'decided_by_id' => $decided_by_id,
            'decided_at'    => date('Y-m-d H:i:s'),
            'note'          => ($note !== '') ? $note : null,
        ]);
        return true;
    }

    // superadmin ให้สิทธิ์ตรงโดยไม่ต้องผ่านคำขอ — บันทึกเป็นรายการที่อนุมัติแล้วทันที เพื่อให้ประวัติครบ
    public function grant_direct($employee_id, $decided_by, $decided_by_id) {
        $this->db->insert($this->reqtable, [
            'employee_id'     => $employee_id,
            'requested_by'    => $decided_by,
            'requested_by_id' => $decided_by_id,
            'status'          => 'approved',
            'decided_by'      => $decided_by,
            'decided_by_id'   => $decided_by_id,
            'requested_at'    => date('Y-m-d H:i:s'),
            'decided_at'      => date('Y-m-d H:i:s'),
        ]);
        $this->set_map_access($employee_id, 1);
    }
}

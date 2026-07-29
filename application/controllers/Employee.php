<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Employee extends CI_Controller {

    public function __construct() {
        parent::__construct();

        if (!$this->session->userdata('logged_in')) {
            redirect('login'); return;
        }
        if (!in_array($this->session->userdata('role'), ['superadmin', 'admin'])) {
            $this->session->set_flashdata('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            redirect('dashboard'); return;
        }

        $this->load->model('Employee_model');
    }

    // ---- หน้า View ----
    public function index() {
        $data['title']         = 'จัดการพนักงาน';
        $data['page_js']       = ['employee'];
        $data['is_superadmin'] = $this->_is_superadmin();
        $this->load->view('templates/header', $data);
        $this->load->view('employee/index', $data);
        $this->load->view('templates/footer');
    }

    // ============================================================
    // API: Employee CRUD
    // ============================================================

    // GET /api/employee
    public function api_list() {
        $this->_json(['success' => true, 'data' => $this->Employee_model->get_all()]);
    }

    // POST /api/employee/datatable
    public function api_datatable() {
        $result = $this->Employee_model->datatable($this->input->post(null, true));
        $this->_json([
            'draw'            => (int)$this->input->post('draw'),
            'recordsTotal'    => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data'            => $result['data'],
        ]);
    }

    // GET /api/employee/get/{id}
    public function api_get($id) {
        $row = $this->Employee_model->get_by_id((int)$id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404); return; }
        $this->_json(['success' => true, 'data' => $row]);
    }

    // POST /api/employee/create
    public function api_create() {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $err = $this->_validate($data, null);
        if ($err) { $this->_json(['success' => false, 'message' => $err], 422); return; }

        $password = trim($data['password'] ?? '');
        if ($password === '')        { $this->_json(['success' => false, 'message' => 'กรุณาระบุ Password'], 422); return; }
        if (mb_strlen($password) < 6) { $this->_json(['success' => false, 'message' => 'Password ต้องมีอย่างน้อย 6 ตัวอักษร'], 422); return; }

        $result = $this->Employee_model->create($data);
        if (empty($result['success'])) {
            $this->_json(['success' => false, 'message' => 'บันทึกข้อมูลไม่สำเร็จ กรุณาตรวจสอบข้อมูลอีกครั้ง หรือติดต่อผู้ดูแลระบบ'], 500);
            return;
        }
        $this->_json(['success' => true, 'message' => 'เพิ่มพนักงานสำเร็จ', 'id' => $result['id']], 201);
    }

    // POST /api/employee/update/{id}
    public function api_update($id) {
        $id  = (int)$id;
        $row = $this->Employee_model->get_by_id($id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404); return; }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $err = $this->_validate($data, $id);
        if ($err) { $this->_json(['success' => false, 'message' => $err], 422); return; }

        // password ตอนแก้ไข: เว้นว่าง = ไม่เปลี่ยน, ถ้าระบุมาต้องยาวอย่างน้อย 6 ตัวอักษร
        if (!empty($data['password']) && mb_strlen(trim($data['password'])) < 6) {
            $this->_json(['success' => false, 'message' => 'Password ต้องมีอย่างน้อย 6 ตัวอักษร'], 422); return;
        }

        $result = $this->Employee_model->update($id, $data);
        if (empty($result['success'])) {
            $this->_json(['success' => false, 'message' => 'บันทึกข้อมูลไม่สำเร็จ กรุณาตรวจสอบข้อมูลอีกครั้ง หรือติดต่อผู้ดูแลระบบ'], 500);
            return;
        }
        $this->_json(['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ']);
    }

    // ตรวจสอบทุก field ที่ใช้ร่วมกันระหว่าง create/update (ยกเว้น password ซึ่งกฎต่างกันตอน edit)
    // คืนค่า string ข้อความ error ตัวแรกที่เจอ หรือ null ถ้าผ่านหมด
    private function _validate($data, $exclude_id) {
        $first    = trim($data['first_name'] ?? '');
        $last     = trim($data['last_name']  ?? '');
        $username = trim($data['username']   ?? '');
        $phone    = trim($data['phone']      ?? '');
        $email    = trim($data['email']      ?? '');
        $position = trim($data['position']  ?? '');

        if ($first === '')              return 'กรุณาระบุชื่อ';
        if (mb_strlen($first) > 100)    return 'ชื่อยาวเกินไป (ไม่เกิน 100 ตัวอักษร)';

        if ($last === '')               return 'กรุณาระบุนามสกุล';
        if (mb_strlen($last) > 100)     return 'นามสกุลยาวเกินไป (ไม่เกิน 100 ตัวอักษร)';

        if ($username === '')           return 'กรุณาระบุ Username';
        if (mb_strlen($username) > 100) return 'Username ยาวเกินไป (ไม่เกิน 100 ตัวอักษร)';
        if (!preg_match('/^[A-Za-z0-9_.\-]{3,100}$/', $username)) {
            return 'Username ต้องมีอย่างน้อย 3 ตัวอักษร ใช้ได้เฉพาะ A-Z, a-z, 0-9, _ . - เท่านั้น (ห้ามเว้นวรรค)';
        }
        if ($this->Employee_model->username_exists($username, $exclude_id)) {
            return 'Username นี้มีอยู่แล้ว';
        }

        if ($phone === '')               return 'กรุณาระบุเบอร์โทร';
        if (mb_strlen($phone) > 30)      return 'เบอร์โทรยาวเกินไป';
        $phone_digits = preg_replace('/[^0-9]/', '', $phone);
        if (!preg_match('/^0\d{8,9}$/', $phone_digits)) {
            return 'เบอร์โทรไม่ถูกต้อง (ต้องขึ้นต้นด้วย 0 และเป็นตัวเลข 9-10 หลัก)';
        }

        if ($email === '')              return 'กรุณาระบุอีเมล';
        if (mb_strlen($email) > 150)    return 'อีเมลยาวเกินไป (ไม่เกิน 150 ตัวอักษร)';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'รูปแบบอีเมลไม่ถูกต้อง';
        }
        if ($this->Employee_model->email_exists($email, $exclude_id)) {
            return 'อีเมลนี้มีอยู่แล้ว';
        }

        if (mb_strlen($position) > 150) return 'ตำแหน่งยาวเกินไป (ไม่เกิน 150 ตัวอักษร)';

        if (isset($data['active']) && !in_array((string)$data['active'], ['0', '1'], true)) {
            return 'ค่าสถานะไม่ถูกต้อง';
        }

        return null;
    }

    // POST /api/employee/delete/{id}
    public function api_delete($id) {
        $id  = (int)$id;
        $row = $this->Employee_model->get_by_id($id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404); return; }
        $this->Employee_model->delete($id);
        $this->_json(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
    }

    // ============================================================
    // API: สิทธิ์ดูแผนที่ลูกค้า (admin ร้องขอ / superadmin อนุมัติ)
    // ============================================================

    // POST /api/employee/request_access  — body: {employee_id}
    // admin หรือ superadmin กดขอสิทธิ์ให้พนักงานคนหนึ่ง
    public function api_request_access() {
        $data        = json_decode(file_get_contents('php://input'), true) ?: [];
        $employee_id = (int)($data['employee_id'] ?? 0);

        $row = $this->Employee_model->get_by_id($employee_id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบพนักงาน'], 404); return; }

        if ((int)$row['map_access'] === 1) {
            $this->_json(['success' => false, 'message' => 'พนักงานคนนี้มีสิทธิ์ดูแผนที่อยู่แล้ว'], 409); return;
        }
        if ($this->Employee_model->has_pending_request($employee_id)) {
            $this->_json(['success' => false, 'message' => 'มีคำขอค้างอยู่แล้ว รอ superadmin อนุมัติ'], 409); return;
        }

        $this->Employee_model->create_request(
            $employee_id,
            $this->session->userdata('username'),
            $this->session->userdata('user_id')
        );
        $this->_json(['success' => true, 'message' => 'ส่งคำขอสิทธิ์แล้ว รอ superadmin อนุมัติ'], 201);
    }

    // GET /api/employee/requests?status=pending|approved|rejected (ไม่ระบุ = ทั้งหมด)
    // superadmin เท่านั้น
    public function api_requests() {
        if (!$this->_is_superadmin()) { $this->_json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403); return; }
        $status = $this->input->get('status', true);
        $this->_json(['success' => true, 'data' => $this->Employee_model->list_requests($status ?: null)]);
    }

    // POST /api/employee/requests/approve/{id} — superadmin เท่านั้น
    public function api_approve_request($request_id) {
        if (!$this->_is_superadmin()) { $this->_json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403); return; }

        $ok = $this->Employee_model->approve_request(
            (int)$request_id,
            $this->session->userdata('username'),
            $this->session->userdata('user_id')
        );
        if (!$ok) { $this->_json(['success' => false, 'message' => 'ไม่พบคำขอ หรือดำเนินการไปแล้ว'], 404); return; }
        $this->_json(['success' => true, 'message' => 'อนุมัติสิทธิ์แล้ว']);
    }

    // POST /api/employee/requests/reject/{id} — superadmin เท่านั้น
    public function api_reject_request($request_id) {
        if (!$this->_is_superadmin()) { $this->_json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403); return; }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $ok = $this->Employee_model->reject_request(
            (int)$request_id,
            $this->session->userdata('username'),
            $this->session->userdata('user_id'),
            trim($data['note'] ?? '')
        );
        if (!$ok) { $this->_json(['success' => false, 'message' => 'ไม่พบคำขอ หรือดำเนินการไปแล้ว'], 404); return; }
        $this->_json(['success' => true, 'message' => 'ปฏิเสธคำขอแล้ว']);
    }

    // POST /api/employee/grant/{employee_id} — superadmin ให้สิทธิ์ตรง ไม่ต้องผ่านคำขอ
    public function api_grant($employee_id) {
        if (!$this->_is_superadmin()) { $this->_json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403); return; }

        $row = $this->Employee_model->get_by_id((int)$employee_id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบพนักงาน'], 404); return; }

        $this->Employee_model->grant_direct(
            (int)$employee_id,
            $this->session->userdata('username'),
            $this->session->userdata('user_id')
        );
        $this->_json(['success' => true, 'message' => 'ให้สิทธิ์ดูแผนที่แล้ว']);
    }

    // POST /api/employee/revoke/{employee_id} — superadmin ยกเลิกสิทธิ์
    public function api_revoke($employee_id) {
        if (!$this->_is_superadmin()) { $this->_json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403); return; }

        $row = $this->Employee_model->get_by_id((int)$employee_id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบพนักงาน'], 404); return; }

        $this->Employee_model->set_map_access((int)$employee_id, 0);
        $this->_json(['success' => true, 'message' => 'ยกเลิกสิทธิ์แล้ว']);
    }

    // ============================================================
    private function _is_superadmin() {
        return $this->session->userdata('role') === 'superadmin';
    }

    private function _json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Technician.php — Controller
 * ใช้ header/footer เหมือน Service controller ในโปรเจกต์
 */
class Technician extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Technician_model', 'tech_model');
        // ถ้ามีการ auth
        // if (!$this->session->userdata('logged_in')) { redirect('login'); }
    }

    // ---- หน้า View ----
    public function index() {
        $data['title']   = 'จัดการช่าง';
        $data['page_js'] = ['technician'];
        $this->load->view('templates/header', $data);
        $this->load->view('technician/index', $data);
        $this->load->view('templates/footer', $data);

             
    }

    public function _remap($method, $params = []) {
        $http = $this->input->method(true);
        $id   = isset($params[0]) ? (int)$params[0] : 0;

        if ($method === 'api_list') {
            if ($http === 'POST') return $this->_api_create();
            return $this->_api_list();
        }
        if ($method === 'api_get' && $id) {
            if ($http === 'PUT')    return $this->_api_update($id);
            if ($http === 'DELETE') return $this->_api_delete($id);
            return $this->_api_get($id);
        }
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $params);
        }
        show_404();
    }

    // ============================================================
    // API
    // ============================================================

    // GET /api/technician
    public function api_list() { $this->_api_list(); }
    private function _api_list() {
        $this->_json(['success' => true, 'data' => $this->tech_model->get_all()]);
    }

   
    public function api_all() {
        $this->_json(['success' => true, 'data' => $this->tech_model->get_active()]);
    }

    // GET /dashboard/api_technicians_search?q= — autocomplete (service.js)
    public function api_search() {
        $q = $this->input->get('q', true);
        if (!$q) { $this->_json(['success' => false, 'data' => []]); return; }
        $this->_json(['success' => true, 'data' => $this->tech_model->search($q)]);
    }

    // POST /api/technician/datatable
    public function api_datatable() {
        $result = $this->tech_model->datatable($this->input->post(null, true));
        $this->_json([
            'draw'            => (int)$this->input->post('draw'),
            'recordsTotal'    => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data'            => $result['data'],
        ]);
    }

    // GET /api/technician/{id}
    private function _api_get($id) {
        $row = $this->tech_model->get_by_id($id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404); return; }
        $this->_json(['success' => true, 'data' => $row]);
    }

    // POST /api/technician
    private function _api_create() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['reg_name'])) {
            $this->_json(['success' => false, 'message' => 'กรุณาระบุชื่อช่าง'], 422); return;
        }
        // เช็ค username ซ้ำ (ถ้ามีการระบุ)
        if (!empty(trim($data['login_username'] ?? ''))) {
            $dup = $this->db->where('reg_username', trim($data['login_username']))->get('register')->row_array();
            if ($dup) { $this->_json(['success' => false, 'message' => 'Username นี้มีอยู่แล้ว'], 409); return; }
            if (empty(trim($data['login_password'] ?? ''))) {
                $this->_json(['success' => false, 'message' => 'กรุณาระบุรหัสผ่านสำหรับบัญชีใหม่'], 422); return;
            }
        }
        $id = $this->tech_model->create($data);
        $this->_json(['success' => true, 'message' => 'เพิ่มช่างสำเร็จ', 'id' => $id], 201);
    }

    // PUT /api/technician/{id}
    private function _api_update($id) {
        $row = $this->tech_model->get_by_id($id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404); return; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['reg_name'])) {
            $this->_json(['success' => false, 'message' => 'กรุณาระบุชื่อช่าง'], 422); return;
        }
        // เช็ค username และ password เฉพาะกรณีที่มีการเปลี่ยนแปลงจริง
        $new_username = trim($data['login_username'] ?? '');
        $new_password = trim($data['login_password'] ?? '');
        $old_username = trim($row['reg_username'] ?? '');

        if (!empty($new_username)) {
            // เช็ค username ซ้ำกับช่างคนอื่น
            $dup = $this->db->where('reg_username', $new_username)->where('id !=', $id)->get('register')->row_array();
            if ($dup) { $this->_json(['success' => false, 'message' => 'Username นี้มีอยู่แล้ว'], 409); return; }

            // บังคับใส่ password เฉพาะกรณีตั้ง username ใหม่ที่ยังไม่เคยมี password มาก่อน
            $has_pass = $this->db->select('reg_pass')->where('id', $id)->get('register')->row_array();
            if (empty($has_pass['reg_pass']) && empty($new_password)) {
                $this->_json(['success' => false, 'message' => 'กรุณาระบุรหัสผ่านสำหรับบัญชีใหม่'], 422); return;
            }
        }
        $this->tech_model->update($id, $data);
        $this->_json(['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ']);
    }

    // DELETE /api/technician/{id}
    private function _api_delete($id) {
        $row = $this->tech_model->get_by_id($id);
        if (!$row) { $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404); return; }
        $this->tech_model->delete($id);
        $this->_json(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
    }

    private function _json($data, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}

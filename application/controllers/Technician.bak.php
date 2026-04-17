<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Technician.php — Controller
 *
 * Routes:
 *   GET    /technician                  → หน้า View
 *   GET    /api/technician              → list ทั้งหมด (stats)
 *   POST   /api/technician/datatable    → DataTables server-side
 *   GET    /api/technician/{id}         → รายการเดียว
 *   POST   /api/technician              → สร้างใหม่
 *   PUT    /api/technician/{id}         → แก้ไข
 *   DELETE /api/technician/{id}         → ลบ
 *
 *   GET    /dashboard/api_technicians_search?q=  → autocomplete (ใช้กับ service.js)
 */
class Technician extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Technician_model', 'tech_model');
        // ถ้าโปรเจกต์มีการ auth ให้เพิ่มที่นี่
        // $this->load->library('session');
        // if (!$this->session->userdata('logged_in')) redirect('login');
    }

   
    public function index() {
        $this->load->view('technician_view');
    }

    // ============================================================
    // API endpoints
    // ============================================================

    // GET /api/technician — list ทั้งหมด (ใช้ loadTechStats)
    public function api_list() {
        $data = $this->tech_model->get_all();
        $this->_json(['success' => true, 'data' => $data]);
    }

    // POST /api/technician/datatable — DataTables server-side
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
    public function api_get($id) {
        $row = $this->tech_model->get_by_id((int)$id);
        if (!$row) {
            $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404);
            return;
        }
        $this->_json(['success' => true, 'data' => $row]);
    }

    // POST /api/technician
    public function api_create() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['reg_name'])) {
            $this->_json(['success' => false, 'message' => 'กรุณาระบุชื่อช่าง'], 422);
            return;
        }
        $id = $this->tech_model->create($data);
        $this->_json(['success' => true, 'message' => 'เพิ่มช่างสำเร็จ', 'id' => $id], 201);
    }

    // PUT /api/technician/{id}
    public function api_update($id) {
        $row = $this->tech_model->get_by_id((int)$id);
        if (!$row) {
            $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['reg_name'])) {
            $this->_json(['success' => false, 'message' => 'กรุณาระบุชื่อช่าง'], 422);
            return;
        }
        $this->tech_model->update((int)$id, $data);
        $this->_json(['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ']);
    }

    // DELETE /api/technician/{id}
    public function api_delete($id) {
        $row = $this->tech_model->get_by_id((int)$id);
        if (!$row) {
            $this->_json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404);
            return;
        }
        $this->tech_model->delete((int)$id);
        $this->_json(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
    }

    // GET /dashboard/api_technicians_search?q= — autocomplete ใน service.js เดิม
    public function api_technicians_search() {
        $q = $this->input->get('q', true);
        if (!$q) {
            $this->_json(['success' => false, 'data' => []]);
            return;
        }
        $data = $this->tech_model->search($q);
        $this->_json(['success' => true, 'data' => $data]);
    }

    // ---- Helper ----
    private function _json($data, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vehicle extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // ต้อง login ก่อนเข้าได้
        if (!$this->session->userdata('logged_in')) {
            redirect('login'); return;
        }
        $this->load->model('Vehicle_model');
    }

    // ── หน้าหลัก ──────────────────────────────────────────────
    public function index() {
        $data['title']    = 'จัดการยานพาหนะ';
        $data['page_js']  = ['vehicle'];
        $this->load->view('templates/header', $data);
        $this->load->view('vehicle/index', $data);
        $this->load->view('templates/footer');
    }

    // ── _remap: route GET/POST/PUT/DELETE ──────────────────────
    public function _remap($method, $params = []) {
        // หน้าหลัก
        if ($method === 'index') { $this->index(); return; }

        $id = isset($params[0]) ? (int)$params[0] : null;
        $reqMethod = $_SERVER['REQUEST_METHOD'];

        // GET /api/vehicle
        if ($method === 'api_list' && $reqMethod === 'GET') {
            $this->_api_list(); return;
        }
        // POST /api/vehicle
        if ($method === 'api_list' && $reqMethod === 'POST') {
            $this->_api_create(); return;
        }
        // GET /api/vehicle/:id
        if ($method === 'api_get' && $reqMethod === 'GET') {
            $this->_api_get($id); return;
        }
        // PUT /api/vehicle/:id
        if ($method === 'api_get' && $reqMethod === 'PUT') {
            $this->_api_update($id); return;
        }
        // DELETE /api/vehicle/:id
        if ($method === 'api_get' && $reqMethod === 'DELETE') {
            $this->_api_delete($id); return;
        }
        show_404();
    }

    // ── API: list ──────────────────────────────────────────────
    private function _api_list() {
        $rows = $this->Vehicle_model->get_all();
        $this->_json(['success' => true, 'data' => $rows]);
    }

    // ── API: get one ───────────────────────────────────────────
    private function _api_get($id) {
        $row = $this->Vehicle_model->get_by_id($id);
        if (!$row) { $this->_json(['success'=>false,'message'=>'ไม่พบข้อมูล'], 404); return; }
        $this->_json(['success' => true, 'data' => $row]);
    }

    // ── API: create ────────────────────────────────────────────
    private function _api_create() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty(trim($data['license_plate'] ?? ''))) {
            $this->_json(['success'=>false,'message'=>'กรุณาระบุป้ายทะเบียน'], 422); return;
        }
        if (empty($data['vehicle_type'])) {
            $this->_json(['success'=>false,'message'=>'กรุณาเลือกประเภทยานพาหนะ'], 422); return;
        }
        // เช็คทะเบียนซ้ำ
        $dup = $this->db->where('license_plate', strtoupper(trim($data['license_plate'])))->get('vehicles')->row_array();
        if ($dup) { $this->_json(['success'=>false,'message'=>'ป้ายทะเบียนนี้มีอยู่แล้ว'], 409); return; }

        $id = $this->Vehicle_model->create($data);
        $this->_json(['success'=>true,'message'=>'เพิ่มยานพาหนะสำเร็จ','id'=>$id], 201);
    }

    // ── API: update ────────────────────────────────────────────
    private function _api_update($id) {
        $row = $this->Vehicle_model->get_by_id($id);
        if (!$row) { $this->_json(['success'=>false,'message'=>'ไม่พบข้อมูล'], 404); return; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty(trim($data['license_plate'] ?? ''))) {
            $this->_json(['success'=>false,'message'=>'กรุณาระบุป้ายทะเบียน'], 422); return;
        }
        // เช็คซ้ำกับคันอื่น
        $dup = $this->db->where('license_plate', strtoupper(trim($data['license_plate'])))
                        ->where('id !=', $id)->get('vehicles')->row_array();
        if ($dup) { $this->_json(['success'=>false,'message'=>'ป้ายทะเบียนนี้มีอยู่แล้ว'], 409); return; }

        $this->Vehicle_model->update($id, $data);
        $this->_json(['success'=>true,'message'=>'แก้ไขข้อมูลสำเร็จ']);
    }

    // ── API: delete ────────────────────────────────────────────
    private function _api_delete($id) {
        $row = $this->Vehicle_model->get_by_id($id);
        if (!$row) { $this->_json(['success'=>false,'message'=>'ไม่พบข้อมูล'], 404); return; }
        $this->Vehicle_model->delete($id);
        $this->_json(['success'=>true,'message'=>'ลบสำเร็จ']);
    }

    private function _json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

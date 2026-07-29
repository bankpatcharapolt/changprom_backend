<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Branch extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->session->userdata('logged_in')) {
            redirect('login'); return;
        }
        // พนักงานทั่วไปเห็นได้แค่หน้าแผนที่ลูกค้า ไม่มีสิทธิ์เข้าหน้านี้
        if ($this->session->userdata('role') === 'employee') { redirect('map'); return; }
        $this->load->model('Branch_model');
    }

    public function index() {
        $data['title'] = 'จัดการสาขา';
        $this->load->view('templates/header', $data);
        $this->load->view('branch/index', $data);
        $this->load->view('templates/footer');
    }

    private function _json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    // GET /api/branch
    public function api_list() {
        $rows = $this->Branch_model->get_all();
        $this->_json(['success' => true, 'data' => $rows]);
    }

    // GET /api/branch/active
    public function api_active() {
        $rows = $this->Branch_model->get_active();
        $this->_json(['success' => true, 'data' => $rows]);
    }

    // GET /api/branch/:id
    public function api_get($id) {
        $row = $this->Branch_model->get_by_id((int)$id);
        if (!$row) { $this->_json(['success'=>false,'message'=>'ไม่พบข้อมูล'], 404); return; }
        $this->_json(['success' => true, 'data' => $row]);
    }

    // POST /api/branch/create
    public function api_create() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty(trim($data['name'] ?? ''))) {
            $this->_json(['success'=>false,'message'=>'กรุณาระบุชื่อสาขา'], 422); return;
        }
        $id = $this->Branch_model->create($data);
        $this->_json(['success'=>true,'message'=>'เพิ่มสาขาสำเร็จ','id'=>$id], 201);
    }

    // POST /api/branch/update/:id
    public function api_update($id) {
        $row = $this->Branch_model->get_by_id((int)$id);
        if (!$row) { $this->_json(['success'=>false,'message'=>'ไม่พบข้อมูล'], 404); return; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty(trim($data['name'] ?? ''))) {
            $this->_json(['success'=>false,'message'=>'กรุณาระบุชื่อสาขา'], 422); return;
        }
        $this->Branch_model->update((int)$id, $data);
        $this->_json(['success'=>true,'message'=>'บันทึกสำเร็จ']);
    }

    // POST /api/branch/delete/:id
    public function api_delete($id) {
        $row = $this->Branch_model->get_by_id((int)$id);
        if (!$row) { $this->_json(['success'=>false,'message'=>'ไม่พบข้อมูล'], 404); return; }
        $this->Branch_model->delete((int)$id);
        $this->_json(['success'=>true,'message'=>'ลบสำเร็จ']);
    }
}

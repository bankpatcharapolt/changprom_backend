<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Service_model');
        $this->load->library('session');
        header('Content-Type: application/json; charset=utf-8');
    }

    private function _check_auth() {
        if (!$this->session->userdata('logged_in')) {
            http_response_code(401);
            echo json_encode(['success'=>false,'message'=>'Unauthorized']);
            exit;
        }
    }

    private function _response($data, $code=200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function get_services() {
        $this->_check_auth();
        $data = $this->Service_model->get_all($this->input->get('search'), $this->input->get('status'));
        $this->_response(['success'=>true,'data'=>$data,'total'=>count($data)]);
    }

    public function get_service($id) {
        $this->_check_auth();
        $data = $this->Service_model->get_by_id($id);
        if (!$data) { $this->_response(['success'=>false,'message'=>'ไม่พบข้อมูล'],404); return; }
        $data['checkins'] = $this->Service_model->get_checkins_by_job($id);
        $this->_response(['success'=>true,'data'=>$data]);
    }

    public function create_service() {
        $this->_check_auth();
        $input = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
        if (empty($input['bill_no'])) { $this->_response(['success'=>false,'message'=>'กรุณาระบุเลขที่บิล'],400); return; }
        if (empty($input['job_type'])) { $this->_response(['success'=>false,'message'=>'กรุณาเลือกประเภทงาน'],400); return; }
        if ($this->Service_model->get_by_bill($input['bill_no'])) { $this->_response(['success'=>false,'message'=>'เลขที่บิลซ้ำ'],409); return; }

        // เช็คชนเวลา
        $conflicts = $this->Service_model->check_time_conflict(
            $input['technician'] ?? '', $input['install_date'] ?? '', $input['install_time'] ?? ''
        );
        if (!empty($conflicts) && empty($input['force'])) {
            $this->_response(['success'=>false,'conflict'=>true,'message'=>'ชนเวลากับงาน: '.$conflicts[0]['bill_no'].' ('.$conflicts[0]['customer_name'].')', 'conflicts'=>$conflicts], 409);
            return;
        }

        $data = $this->_sanitize($input);
        $data['created_by'] = $this->session->userdata('user_id');
        $id = $this->Service_model->create($data);
        $this->_response(['success'=>true,'message'=>'บันทึกสำเร็จ','id'=>$id], 201);
    }

    public function update_service($id) {
        $this->_check_auth();
        if (!$this->Service_model->get_by_id($id)) { $this->_response(['success'=>false,'message'=>'ไม่พบข้อมูล'],404); return; }
        $input = json_decode($this->input->raw_input_stream, true) ?: [];

        // เช็คชนเวลา
        if (!empty($input['technician']) && !empty($input['install_date']) && !empty($input['install_time'])) {
            $conflicts = $this->Service_model->check_time_conflict($input['technician'], $input['install_date'], $input['install_time'], $id);
            if (!empty($conflicts) && empty($input['force'])) {
                $this->_response(['success'=>false,'conflict'=>true,'message'=>'ชนเวลากับงาน: '.$conflicts[0]['bill_no'].' ('.$conflicts[0]['customer_name'].')','conflicts'=>$conflicts], 409);
                return;
            }
        }

        $this->Service_model->update($id, $this->_sanitize($input));
        $this->_response(['success'=>true,'message'=>'อัปเดตสำเร็จ']);
    }

    public function delete_service($id) {
        $this->_check_auth();
        if (!$this->Service_model->get_by_id($id)) { $this->_response(['success'=>false,'message'=>'ไม่พบข้อมูล'],404); return; }
        $this->Service_model->delete($id);
        $this->_response(['success'=>true,'message'=>'ลบสำเร็จ']);
    }

    public function datatable() {
        $this->_check_auth();
        $draw  = $this->input->post('draw');
        $start = (int)$this->input->post('start');
        $len   = (int)$this->input->post('length');
        $search = $this->input->post('search')['value'] ?? '';
        $orderCol = (int)($this->input->post('order')[0]['column'] ?? 0);
        $orderDir = $this->input->post('order')[0]['dir'] ?? 'desc';
        $cols = ['id','job_type','bill_no','customer_name','purchase_date','install_date','phone','technician','status','product_service','tags'];
        $orderField = $cols[$orderCol] ?? 'id';
        $result = $this->Service_model->datatable($start, $len, $search, $orderField, $orderDir);
        $total  = $this->Service_model->count_all($search);
        echo json_encode(['draw'=>(int)$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$result], JSON_UNESCAPED_UNICODE);
    }

    private function _sanitize($input) {
        $allowed = ['job_type','bill_no','customer_name','purchase_date','address','location','install_date',
                    'install_time','phone','technician','tech_note','status','product_service','bill_note',
                    'tags','sale_code','team','branch','amount'];
        $data = [];
        foreach ($allowed as $f) {
            if (isset($input[$f])) {
                $v = is_string($input[$f]) ? trim($input[$f]) : $input[$f];
                $data[$f] = ($v === '') ? null : $v;
            }
        }
        return $data;
    }
}

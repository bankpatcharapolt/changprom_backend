<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->session->userdata('logged_in')) redirect('login');
        $this->load->model('Service_model');
        $this->load->model('Dashboard_model');
        $this->load->helper(['url','form']);
    }

    public function index() {
        $data['title'] = 'Dashboard คุมงาน';
        $data['page_js'] = ['dashboard'];
        $this->load->view('templates/header', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('templates/footer');
    }

   public function calendar() {
    $data['title'] = 'ตารางคิวช่าง';
    $data['extra_js'] = [
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/th.global.min.js',
    ];
    $data['page_js'] = ['calendar'];

    // ✅ ส่งทั้ง id และ reg_name
    $data['technicians'] = $this->Dashboard_model->get_technicians();
    // return [['id'=>1,'reg_name'=>'ธีระพงษ์'], ...]

    $this->load->view('templates/header', $data);
    $this->load->view('dashboard/calendar', $data);
    $this->load->view('templates/footer');
}

    // API: get dashboard stats
    public function api_stats() {
        header('Content-Type: application/json; charset=utf-8');
        $stats = $this->Dashboard_model->get_stats();
        echo json_encode(['success' => true, 'data' => $stats], JSON_UNESCAPED_UNICODE);
    }

    // API: get technician workload
    public function api_technician_load() {
        header('Content-Type: application/json; charset=utf-8');
        $date = $this->input->get('date') ?: date('Y-m-d');
        $data = $this->Dashboard_model->get_technician_load($date);
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    }

    // API: get calendar events
    public function api_calendar_events() {
        header('Content-Type: application/json; charset=utf-8');
        $start = $this->input->get('start');
        $end   = $this->input->get('end');
        $events = $this->Dashboard_model->get_calendar_events($start, $end);
        echo json_encode($events, JSON_UNESCAPED_UNICODE);
    }

    // API: assign job to technician/date
    // public function api_assign() {
    //     header('Content-Type: application/json; charset=utf-8');
    //     $input = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
    //     $id   = (int)($input['job_id'] ?? 0);
    //     $tech = trim($input['technician'] ?? '');
    //     $date = trim($input['install_date'] ?? '');
    //     $time = trim($input['install_time'] ?? '');

    //     if (!$id) { echo json_encode(['success'=>false,'message'=>'ไม่พบรายการ']); return; }

    //     $this->load->model('Service_model');
    //     $updateData = [];
    //     if ($tech) $updateData['technician']   = $tech;
    //     if ($date) $updateData['install_date'] = $date;
    //     if ($time) $updateData['install_time'] = $time;

    //     $this->Service_model->update($id, $updateData);
    //     echo json_encode(['success'=>true,'message'=>'บันทึกคิวสำเร็จ'], JSON_UNESCAPED_UNICODE);
    // }
  public function api_assign() {
    header('Content-Type: application/json; charset=utf-8');
    $input    = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
    $id       = (int)($input['job_id']       ?? 0);
    $tech     = $input['technician']          ?? null;
    $date     = $input['install_date']        ?? null;
    $time     = $input['install_time']        ?? null;
    $jobType  = trim($input['job_type']       ?? '');
    $wage     = isset($input['tech_wage']) && $input['tech_wage'] !== null ? (float)$input['tech_wage'] : null;
    $techNote = trim($input['tech_note']      ?? '');
    $force    = !empty($input['force']);
    $techId   = !empty($input['technician_id']) ? (int)$input['technician_id'] : null;

    // ✅ เช็ค id ก่อนเสมอ
    if (!$id) {
        echo json_encode(['success'=>false,'message'=>'ไม่พบรายการ']);
        return;
    }

    $this->load->model('Service_model');
    $isDelete = ($tech === '' || $tech === null) && ($date === null || $date === '');

    if (!$isDelete) {
        if (!$force && !empty($tech) && !empty($date)) {
            $dups = $this->Dashboard_model->check_duplicate_assign($tech, $date, $time, $id);
            if (!empty($dups)) {
                $dupInfo = array_map(function($d) {
                    return ($d['bill_no'] ?: '#'.$d['id']) . ' ' . $d['customer_name']
                           . ($d['install_time'] ? ' เวลา ' . substr($d['install_time'],0,5) : '');
                }, $dups);
                echo json_encode([
                    'success'   => false,
                    'duplicate' => true,
                    'message'   => 'ช่างนี้มีงานในวันเดียวกัน: ' . implode(', ', $dupInfo),
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $updateData = [];
        if (!empty($tech))    $updateData['technician']    = trim($tech);
        if ($techId)          $updateData['technician_id'] = $techId;   // ✅ อยู่ในที่ถูกต้อง
        if (!empty($date))    $updateData['install_date']  = trim($date);
        if (!empty($time))    $updateData['install_time']  = trim($time);
        if (!empty($jobType)) $updateData['job_type']      = $jobType;
        if ($wage !== null)   $updateData['tech_wage']     = $wage;
        if ($techNote !== '') $updateData['tech_note']     = $techNote;

    } else {
        $updateData = [
            'technician'    => null,
            'technician_id' => null,
            'install_date'  => null,
            'install_time'  => null,
        ];
    }

    $this->Service_model->update($id, $updateData);
    echo json_encode(['success'=>true,'message'=>'บันทึกสำเร็จ'], JSON_UNESCAPED_UNICODE);
}
    // API: search jobs for calendar assign
    public function api_search_jobs() {
        header('Content-Type: application/json; charset=utf-8');
        $q = $this->input->get('q');
        $jobs = $this->Dashboard_model->search_jobs($q);
        echo json_encode(['success'=>true,'data'=>$jobs], JSON_UNESCAPED_UNICODE);
    }

    public function api_debug_phone() {
    header('Content-Type: application/json; charset=utf-8');
    
    $this->load->model('Technician_model');
    
    $connected = $this->Technician_model->is_connected();
    $techList  = $this->Technician_model->get_all();
    
    // หา id 206 โดยเฉพาะ
    $found = null;
    foreach ($techList as $t) {
        if ((int)$t['id'] === 206) {
            $found = $t;
            break;
        }
    }
    
    echo json_encode([
        'connected'    => $connected,
        'total_techs'  => count($techList),
        'id_206'       => $found,
        'sample_3'     => array_slice($techList, 0, 3),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
    // API: get technicians list
   public function api_technicians() {
        header('Content-Type: application/json');
        try {
            $this->load->model('Dashboard_model');
            $data = $this->Dashboard_model->get_technicians();
            echo json_encode($data);
        } catch (Exception $e) {
           
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

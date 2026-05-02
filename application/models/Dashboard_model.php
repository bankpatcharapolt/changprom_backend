<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

    protected $table = 'service_jobs';

    public function get_stats() {
        $today = date('Y-m-d');

        // status ภาษาไทยตามที่ใช้จริงในระบบ
        $s_pending   = 'รอดำเนินการ';
        $s_confirmed = 'ยืนยันแล้ว';
        $s_inprog    = 'กำลังดำเนินงาน';
        $s_done      = 'เสร็จแล้ว';
        $s_postpone  = 'เลื่อนนัด';
        $s_cancel    = 'ยกเลิกนัด';
        $closed      = [$s_done, $s_cancel];

        // งานวันนี้ทั้งหมด
        $today_total = $this->db->where('install_date', $today)->count_all_results($this->table);

        // งานวันนี้แยกตามสถานะ
        $today_pending   = $this->db->where('install_date',$today)->where('status',$s_pending)  ->count_all_results($this->table);
        $today_confirmed = $this->db->where('install_date',$today)->where('status',$s_confirmed)->count_all_results($this->table);
        $today_inprog    = $this->db->where('install_date',$today)->where('status',$s_inprog)   ->count_all_results($this->table);
        $today_done      = $this->db->where('install_date',$today)->where('status',$s_done)     ->count_all_results($this->table);

        // งานยังไม่ปิด (ไม่ใช่ เสร็จแล้ว/ยกเลิกนัด)
        $open_jobs = $this->db->where_not_in('status', $closed)->count_all_results($this->table);

        // งานเกินกำหนด (install_date < today ยังไม่ปิด มีวันนัด)
        $overdue = $this->db
            ->where('install_date <', $today)
            ->where('install_date IS NOT NULL', null, false)
            ->where('install_date !=', '')
            ->where_not_in('status', $closed)
            ->count_all_results($this->table);

        // งานทั้งหมด / งานเสร็จทั้งหมด
        $total_all  = $this->db->count_all($this->table);
        $total_done = $this->db->where('status', $s_done)->count_all_results($this->table);

        // งานสัปดาห์นี้ (จันทร์-อาทิตย์)
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end   = date('Y-m-d', strtotime('sunday this week'));
        $this_week  = $this->db
            ->where('install_date >=', $week_start)
            ->where('install_date <=', $week_end)
            ->count_all_results($this->table);

        // งานช่างวันนี้
        $tech_load = $this->get_technician_load($today);

        // งานใกล้ถึงกำหนด 7 วัน (ยังไม่ปิด)
        $upcoming = $this->db
            ->where('install_date >=', $today)
            ->where('install_date <=', date('Y-m-d', strtotime('+7 days')))
            ->where_not_in('status', $closed)
            ->order_by('install_date','ASC')
            ->order_by('install_time','ASC')
            ->limit(10)
            ->get($this->table)->result_array();

        // สถิติรายเดือน 6 เดือนย้อนหลัง
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m   = date('Y-m', strtotime("-$i months"));
            $cnt = $this->db->like('install_date', $m, 'after')->count_all_results($this->table);
            $monthly[] = ['month' => $m, 'count' => $cnt];
        }

        return compact(
            'today_total','today_pending','today_confirmed','today_inprog','today_done',
            'open_jobs','overdue','total_all','total_done','this_week',
            'tech_load','upcoming','monthly','today'
        );
    }

    public function get_technician_load($date) {
        $rows = $this->db->select('technician, COUNT(*) as job_count, 
            SUM(CASE WHEN status="เสร็จแล้ว" THEN 1 ELSE 0 END) as done,
            SUM(CASE WHEN status NOT IN ("เสร็จแล้ว","ยกเลิกนัด") THEN 1 ELSE 0 END) as open')
            ->where('install_date', $date)
            ->where('technician !=', '')
            ->where('technician IS NOT NULL')
            ->group_by('technician')
            ->order_by('job_count','DESC')
            ->get($this->table)->result_array();
        return $rows;
    }

   public function get_calendar_events($start, $end) {
    $rows = $this->db->select('id, bill_no, customer_name, technician, technician_id,
                               install_date, install_time, status, job_type,
                               product_service, phone, tech_wage, tech_note,
                               tech_zone, map_link')
        ->where('install_date IS NOT NULL')
        ->where('install_date !=', '');
    if ($start) $rows->where('install_date >=', substr($start,0,10));
    if ($end)   $rows->where('install_date <=', substr($end,0,10));
    $rows = $rows->get($this->table)->result_array();

    $events = []; // ← initialize ก่อนเสมอ

    $this->load->model('Technician_model');
    $techList = $this->Technician_model->get_all();
    $techMap  = []; // id => reg_telephone
    foreach ($techList as $t) {
        $techMap[(int)$t['id']] = $t['reg_telephone'] ?? '';
    }

     $colorMap = [
        'รอดำเนินการ'    => '#f59e0b',
        'ยืนยันแล้ว'     => '#3b82f6',
        'กำลังดำเนินงาน' => '#8b5cf6',
        'เสร็จแล้ว'      => '#10b981',
        'เลื่อนนัด'      => '#6b7280',
        'ยกเลิกนัด'      => '#ef4444',
    ];
    foreach ($rows as $r) {
        // ✅ หาเบอร์จาก technician_id
         $start_dt = $r['install_date'];           // ← ตรงนี้ถูก
    if (!empty($r['install_time'])) {
        $start_dt .= 'T' . substr($r['install_time'], 0, 5);
    }
    $color   = $colorMap[$r['status']] ?? '#6b7280';
    $time    = !empty($r['install_time']) ? substr($r['install_time'], 0, 5) : '';
    $zone    = !empty($r['tech_zone'])   ? '[' . $r['tech_zone']   . ']' : '';
    $tech    = !empty($r['technician'])  ? '[' . $r['technician']  . ']' : '';
    $jobType = !empty($r['job_type'])    ? '[' . $r['job_type']    . ']' : '';

        $r['tech_phone'] = '';
        if (!empty($r['technician_id']) && isset($techMap[(int)$r['technician_id']])) {
            $r['tech_phone'] = $techMap[(int)$r['technician_id']];
        }
   
$events[] = [
        'id'              => $r['id'],
        'title'           => implode(' ', array_filter([
                                $r['customer_name'] ?? '',
                                $zone,
                                $tech,
                                $jobType,
                            ])),
        'start'           => $start_dt,       // ← แต่ถ้า $start_dt ไม่ถูก assign จะเป็น null
        'backgroundColor' => $color,
        'borderColor'     => $color,
        'textColor'       => '#ffffff',
        'extendedProps'   => $r,
    ];
    }
    return $events;
}
public function check_duplicate_assign($technician, $install_date, $install_time, $exclude_id = 0) {
    if (!$technician || !$install_date) return [];

    $this->db->where('technician', $technician)
             ->where('install_date', $install_date)
             ->where_not_in('status', ['ยกเลิกนัด', 'เลื่อนนัด']);

    if ($install_time) {
       
        $this->db->where('install_time IS NOT NULL')
                 ->where('install_time !=', '');
    }

    if ($exclude_id) $this->db->where('id !=', $exclude_id);

    $jobs = $this->db->select('id, bill_no, customer_name, install_time, status')
                     ->get('service_jobs')->result_array();

    if (!$install_time) return $jobs; // ถ้าไม่มีเวลา return ทั้งหมดที่วันเดียวกัน

    $conflicts = [];
    $newTime = strtotime($install_date . ' ' . $install_time);
    foreach ($jobs as $j) {
        $diff = abs($newTime - strtotime($install_date . ' ' . $j['install_time'])) / 3600;
        if ($diff < 2) $conflicts[] = $j;
    }
    return $conflicts;
}
   public function search_jobs($q) {
     
        $this->db->where_not_in('status', ['เสร็จแล้ว', 'ยกเลิกนัด']);
        if ($q) {
            $this->db->group_start();
            foreach (['bill_no','customer_name','phone','product_service'] as $col) {
                $this->db->or_like($col, $q);
            }
            $this->db->group_end();
        }
      
        return $this->db->select('id, bill_no, customer_name, phone, product_service,
                                  install_date, install_time, technician, status, job_type')
            ->limit(20)->get($this->table)->result_array();
    }

   public function get_technicians() {
        // ดึงทั้ง ID และชื่อจากตาราง register
        $rows = $this->db->select('id, TRIM(reg_name) as reg_name', FALSE)
            ->from('register')
            ->where('active', 1) 
            ->order_by('reg_name', 'ASC')
            ->get()
            ->result_array();

        return $rows; 
    }
}

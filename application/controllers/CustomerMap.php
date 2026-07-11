<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CustomerMap extends CI_Controller {

    // method ที่ไม่ต้อง login (public access)
    private $public_methods = ['public_index', 'api_markers', 'api_techs', 'api_history'];

    public function __construct() {
        parent::__construct();
        $method = $this->router->fetch_method();
        if (!in_array($method, $this->public_methods) && !$this->session->userdata('logged_in')) {
            redirect('login');
        }
    }

    // หน้าสำหรับ admin (ต้อง login, มี header/footer)
    public function index() {
        $data['title'] = 'แผนที่ลูกค้า';
        $this->load->view('templates/header', $data);
        $this->load->view('customer_map/index', $data);
        $this->load->view('templates/footer');
    }

    // หน้า public (ไม่ต้อง login, ไม่มี header/footer)
    public function public_index() {
        $data['title'] = 'แผนที่ลูกค้า';
        $this->load->view('customer_map/public', $data);
    }

    // API: markers
    public function api_markers() {
        header('Content-Type: application/json; charset=utf-8');
        try {

        $q      = trim($this->input->get('q',      TRUE) ?? '');
        $filter = trim($this->input->get('filter',  TRUE) ?? 'all'); // all|green|yellow|red|overdue
        $tech   = trim($this->input->get('tech',    TRUE) ?? '');
        $today  = date('Y-m-d');

        // ── ดึง "วันที่เปลี่ยนไส้กรองล่าสุด" ต่อลูกค้า (customer_name) ──
        // ถ้ามี job_type='เปลี่ยนไส้กรอง' ใช้ start_time ล่าสุดของ row นั้น
        // ถ้าไม่มี ใช้ start_time ล่าสุดของงาน job_type='ติดตั้ง'
        // จับกลุ่มด้วย customer_name

        $sql_last_service = "
            SELECT
                base.customer_name,
                COALESCE(
                    MAX(CASE WHEN base.job_type = 'เปลี่ยนไส้กรอง' THEN DATE(base.start_time) END),
                    MAX(CASE WHEN base.job_type = 'ติดตั้ง'         THEN DATE(base.start_time) END),
                    MAX(DATE(base.start_time))
                ) AS last_service_date
            FROM service_jobs base
            WHERE base.start_time IS NOT NULL
            GROUP BY base.customer_name
        ";

        // ── ดึง job id ตัวแทนต่อลูกค้า (job_type=ติดตั้ง ที่มีพิกัด ล่าสุด) ──
        // ใช้ subquery หา id ก่อน แล้วค่อย JOIN เพื่อหลีกเลี่ยง ONLY_FULL_GROUP_BY
        $where_extra = '';
        $binds = [];
        if (!empty($q)) {
            $where_extra .= " AND (sj2.customer_name LIKE ? OR sj2.bill_no LIKE ? OR sj2.address LIKE ?)";
            $binds[] = "%{$q}%";
            $binds[] = "%{$q}%";
            $binds[] = "%{$q}%";
        }
        if (!empty($tech)) {
            $where_extra .= " AND sj2.technician LIKE ?";
            $binds[] = "%{$tech}%";
        }

        $sql_representative = "
            SELECT MAX(sj2.id) AS rep_id
            FROM service_jobs sj2
            WHERE (
                (sj2.close_lat IS NOT NULL AND sj2.close_lat != '')
                OR (sj2.start_lat IS NOT NULL AND sj2.start_lat != '')
              )
              AND sj2.status NOT IN ('ยกเลิกนัด')
              AND sj2.job_type = 'ติดตั้ง'
              {$where_extra}
            GROUP BY sj2.customer_name
        ";

        $sql = "
            SELECT
                sj.id, sj.bill_no, sj.customer_name, sj.phone,
                sj.address, sj.location, sj.map_link,
                sj.install_date, sj.purchase_date,
                sj.product_service, sj.job_type,
                sj.technician, sj.status, sj.tech_zone,
                sj.close_lat, sj.close_lng,
                sj.start_lat, sj.start_lng,
                ls.last_service_date,
                DATEDIFF(CURDATE(), ls.last_service_date) AS days_since
            FROM service_jobs sj
            INNER JOIN ({$sql_representative}) rep ON rep.rep_id = sj.id
            LEFT JOIN ({$sql_last_service}) ls ON ls.customer_name = sj.customer_name
            ORDER BY sj.install_date DESC
        ";

        $query = $this->db->query($sql, $binds);
        $rows  = $query->result_array();

        $markers = [];
        $counts  = ['all' => 0, 'green' => 0, 'yellow' => 0, 'red' => 0, 'overdue' => 0];

        foreach ($rows as $r) {
            // ใช้ close_lat/close_lng ก่อน ถ้าไม่มีใช้ start_lat/start_lng
            if (!empty($r['close_lat']) && !empty($r['close_lng'])
                && is_numeric(trim($r['close_lat'])) && is_numeric(trim($r['close_lng']))) {
                $lat = (float)trim($r['close_lat']);
                $lng = (float)trim($r['close_lng']);
            } elseif (!empty($r['start_lat']) && !empty($r['start_lng'])
                && is_numeric(trim($r['start_lat'])) && is_numeric(trim($r['start_lng']))) {
                $lat = (float)trim($r['start_lat']);
                $lng = (float)trim($r['start_lng']);
            } else {
                continue; // ไม่มีพิกัดเลย ข้ามไป
            }

            // ── คำนวณครบกำหนด ──────────────────────────────────────
            // กำหนดเปลี่ยนไส้กรองทุก 6 เดือน นับจาก last_service_date เสมอ
            $base    = $r['last_service_date'] ?? $r['install_date'];
            $ts_now  = strtotime($today);
            $due_next = null;  // วันครบกำหนดถัดไป (Y-m-d)
            $days_to_due = null;

            if ($base) {
                $ts_base = strtotime($base);

                // หา "รอบแรกที่ครบกำหนด" (6 เดือนแรกหลัง last_service)
                $ts_first_due = strtotime(date('Y-m-d', $ts_base) . ' +6 months');

                if ($ts_now < $ts_first_due) {
                    // ยังไม่ถึงรอบแรก → หาวันครบกำหนดถัดไป = $ts_first_due
                    $due_next    = date('Y-m-d', $ts_first_due);
                    $days_to_due = (int)ceil(($ts_first_due - $ts_now) / 86400);
                    $overdue_days = 0;
                } else {
                    // เลยรอบแรกแล้ว → นับวันที่เลยกำหนดมาจากรอบแรก
                    // หารอบที่เลยมาล่าสุด (รอบสุดท้ายที่ $ts_due <= $ts_now)
                    $ts_due = $ts_first_due;
                    $ts_last_missed = $ts_first_due;
                    while ($ts_due <= $ts_now) {
                        $ts_last_missed = $ts_due;
                        $ts_due = strtotime(date('Y-m-d', $ts_due) . ' +6 months');
                    }
                    $due_next     = date('Y-m-d', $ts_last_missed); // รอบที่เลยมาล่าสุด
                    $overdue_days = (int)floor(($ts_now - $ts_last_missed) / 86400);
                    $days_to_due  = -$overdue_days; // ติดลบ = เลยกำหนดไปแล้ว
                }
            }

            // days_since จาก last_service_date
            $days = $base ? (int)floor(($ts_now - strtotime($base)) / 86400) : -1;

            // marker_status
            if ($days < 0 || $base === null) {
                $marker_status = 'green';
                $label         = 'ติดตั้งแล้ว (ยังไม่มีข้อมูลบริการ)';
            } elseif ($days_to_due !== null && $days_to_due > 60) {
                $marker_status = 'green';
                $label         = 'ติดตั้งแล้ว (ยังไม่ครบกำหนด)';
            } elseif ($days_to_due !== null && $days_to_due > 0) {
                $marker_status = 'yellow';
                $label         = 'ใกล้ถึงกำหนดเปลี่ยน (เหลืออีก ' . $days_to_due . ' วัน)';
            } elseif ($days_to_due !== null && $days_to_due > -30) {
                $marker_status = 'red';
                $label         = 'ครบกำหนดเปลี่ยนแล้ว (เลยกำหนด ' . abs($days_to_due) . ' วัน)';
            } else {
                $marker_status = 'overdue';
                $label         = 'เกินกำหนด (เลยกำหนด ' . abs($days_to_due) . ' วัน)';
            }

            $counts['all']++;
            $counts[$marker_status]++;

            // กรองตาม filter
            if ($filter !== 'all' && $marker_status !== $filter) {
                continue;
            }

            // ส่งวันครบกำหนดถัดไปเป็น due_1y (ใช้ชื่อเดิมเพื่อไม่ต้องแก้ JS)
            $due_6m = null;   // ไม่ได้ใช้แล้ว
            $due_1y = $due_next;

            $markers[] = [
                'id'               => (int)$r['id'],
                'bill_no'          => $r['bill_no'],
                'customer_name'    => $r['customer_name'],
                'phone'            => $r['phone'],
                'address'          => $r['address'],
                'map_link'         => $r['map_link'],
                'lat'              => $lat,
                'lng'              => $lng,
                'install_date'     => $r['install_date'],
                'last_service_date'=> $r['last_service_date'],
                'product_service'  => $r['product_service'],
                'job_type'         => $r['job_type'],
                'technician'       => $r['technician'],
                'status'           => $r['status'],
                'tech_zone'        => $r['tech_zone'],
                'marker_status'    => $marker_status,
                'marker_label'     => $label,
                'days_since'       => $days,
                'days_to_due'      => $days_to_due,
                'due_6m'           => $due_6m,
                'due_1y'           => $due_1y,
            ];
        }

        echo json_encode([
            'success' => true,
            'data'    => $markers,
            'counts'  => $counts,
        ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // API: ประวัติการให้บริการของลูกค้า (ค้นจาก customer_name)
    public function api_history() {
        header('Content-Type: application/json; charset=utf-8');
        $name = trim($this->input->get('name', TRUE) ?? '');
        if (empty($name)) {
            echo json_encode(['success'=>false,'message'=>'ไม่ระบุชื่อลูกค้า'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $rows = $this->db
            ->select('id, bill_no, job_type, install_date, start_time, status, product_service, technician, tech_note')
            ->where('customer_name', $name)
            ->order_by('install_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get('service_jobs')
            ->result_array();
        echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
    }

    // API: technician list สำหรับ filter dropdown
    public function api_techs() {
        header('Content-Type: application/json; charset=utf-8');
        $rows = $this->db
            ->distinct()
            ->select('technician')
            ->where('technician IS NOT NULL')
            ->where('technician !=', '')
            ->order_by('technician')
            ->get('service_jobs')
            ->result_array();
        $techs = array_column($rows, 'technician');
        echo json_encode(['success' => true, 'data' => $techs], JSON_UNESCAPED_UNICODE);
    }
}

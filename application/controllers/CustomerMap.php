<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CustomerMap extends CI_Controller {

    // method ที่ไม่ต้อง login (public access)
    private $public_methods = ['public_index', 'api_markers', 'api_techs', 'api_history', 'api_job_types'];

    public function __construct() {
        parent::__construct();
        $method = $this->router->fetch_method();
        if (!in_array($method, $this->public_methods) && !$this->session->userdata('logged_in')) {
            redirect('login');
        }
    }

    // หน้าสำหรับ admin (ต้อง login, มี header/footer)
    public function index() {
        $data['title']     = 'แผนที่ลูกค้า';
        $data['gmaps_key'] = $this->config->item('gmaps_key') ?? '';
        $this->load->view('templates/header', $data);
        $this->load->view('customer_map/index', $data);
        $this->load->view('templates/footer');
    }

    // หน้า public (ไม่ต้อง login, ไม่มี header/footer)
    public function public_index() {
        $data['title']     = 'แผนที่ลูกค้า';
        $data['gmaps_key'] = $this->config->item('gmaps_key') ?? '';
        $this->load->view('customer_map/public', $data);
    }

    // API: markers
    public function api_markers() {
        header('Content-Type: application/json; charset=utf-8');
        try {

        $q        = trim($this->input->get('q',        TRUE) ?? '');
        $filter   = trim($this->input->get('filter',    TRUE) ?? 'all');
        $tech     = trim($this->input->get('tech',      TRUE) ?? '');
        $job_type = trim($this->input->get('job_type',  TRUE) ?? '');
        $today    = date('Y-m-d');

        // ── ดึง "วันที่เปลี่ยนไส้กรองล่าสุด" ต่อลูกค้า (customer_name) ──
        // ถ้ามี job_type='เปลี่ยนไส้กรอง' ใช้ start_time ล่าสุดของ row นั้น
        // ถ้าไม่มี ใช้ start_time ล่าสุดของงาน job_type='ติดตั้ง'
        // จับกลุ่มด้วย customer_name

        // last_service_date: นับเฉพาะ ติดตั้ง และ เปลี่ยนไส้กรอง (สำหรับคำนวณวันครบกำหนด)
        // last_service_type: ประเภทล่าสุดใน 2 ประเภทนี้
        $sql_last_service = "
            SELECT
                base.customer_name,
                COALESCE(
                    MAX(CASE WHEN base.job_type = 'เปลี่ยนไส้กรอง' THEN DATE(base.start_time) END),
                    MAX(CASE WHEN base.job_type = 'ติดตั้ง'         THEN DATE(base.start_time) END)
                ) AS last_service_date,
                CASE
                    WHEN MAX(CASE WHEN base.job_type = 'เปลี่ยนไส้กรอง' THEN DATE(base.start_time) END) IS NOT NULL
                     AND (
                          MAX(CASE WHEN base.job_type = 'เปลี่ยนไส้กรอง' THEN DATE(base.start_time) END)
                          >=
                          COALESCE(MAX(CASE WHEN base.job_type = 'ติดตั้ง' THEN DATE(base.start_time) END), '1900-01-01')
                         )
                    THEN 'เปลี่ยนไส้กรอง'
                    ELSE 'ติดตั้ง'
                END AS last_service_type
            FROM service_jobs base
            WHERE base.start_time IS NOT NULL
              AND base.job_type IN ('ติดตั้ง', 'เปลี่ยนไส้กรอง')
            GROUP BY base.customer_name
        ";

        // last_any_type: job_type ล่าสุด (ทุกประเภท) ต่อลูกค้า — สำหรับ filter ประเภทอื่น
        $sql_last_any = "
            SELECT
                base.customer_name,
                SUBSTRING_INDEX(GROUP_CONCAT(base.job_type ORDER BY base.start_time DESC SEPARATOR '|'), '|', 1) AS last_any_type
            FROM service_jobs base
            WHERE base.start_time IS NOT NULL
            GROUP BY base.customer_name
        ";

        // ── ดึง job id ตัวแทนต่อลูกค้า (job ที่มีพิกัด ไม่จำกัด job_type) ──
        // Logic: หา job ที่มีพิกัด ล่าสุดต่อลูกค้า 1 คน แล้วเอาพิกัดจากนั้น
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

        // subquery 1: หา job ที่มีพิกัดล่าสุด (any job_type) ต่อลูกค้า → เพื่อเอาพิกัด
        $sql_loc = "
            SELECT sj2.customer_name,
                   MAX(sj2.id) AS loc_job_id
            FROM service_jobs sj2
            WHERE (
                (sj2.close_lat IS NOT NULL AND sj2.close_lat != '')
                OR (sj2.start_lat IS NOT NULL AND sj2.start_lat != '')
              )
              AND sj2.status NOT IN ('ยกเลิกนัด')
              {$where_extra}
            GROUP BY sj2.customer_name
        ";

        // subquery 2: หา job ติดตั้งล่าสุด ต่อลูกค้า → เพื่อเอาข้อมูลสินค้า/ที่อยู่
        $sql_install = "
            SELECT sj3.customer_name,
                   MAX(sj3.id) AS install_job_id
            FROM service_jobs sj3
            WHERE sj3.job_type = 'ติดตั้ง'
              AND sj3.status NOT IN ('ยกเลิกนัด')
            GROUP BY sj3.customer_name
        ";

        $sql = "
            SELECT
                COALESCE(sj_install.id, sj_loc.id)           AS id,
                COALESCE(sj_install.bill_no, sj_loc.bill_no) AS bill_no,
                sj_loc.customer_name,
                COALESCE(sj_install.phone, sj_loc.phone)     AS phone,
                COALESCE(sj_install.address, sj_loc.address) AS address,
                COALESCE(sj_install.location, sj_loc.location) AS location,
                COALESCE(sj_install.map_link, sj_loc.map_link) AS map_link,
                COALESCE(sj_install.install_date, sj_loc.install_date) AS install_date,
                COALESCE(sj_install.purchase_date, sj_loc.purchase_date) AS purchase_date,
                COALESCE(sj_install.product_service, sj_loc.product_service) AS product_service,
                COALESCE(sj_install.job_type, sj_loc.job_type) AS job_type,
                COALESCE(sj_install.technician, sj_loc.technician) AS technician,
                COALESCE(sj_install.status, sj_loc.status)   AS status,
                COALESCE(sj_install.tech_zone, sj_loc.tech_zone) AS tech_zone,
                sj_loc.close_lat, sj_loc.close_lng,
                sj_loc.start_lat, sj_loc.start_lng,
                ls.last_service_date,
                ls.last_service_type,
                la.last_any_type,
                DATEDIFF(CURDATE(), ls.last_service_date) AS days_since
            FROM ({$sql_loc}) loc_tbl
            JOIN service_jobs sj_loc       ON sj_loc.id = loc_tbl.loc_job_id
            LEFT JOIN ({$sql_install}) ins_tbl ON ins_tbl.customer_name = loc_tbl.customer_name
            LEFT JOIN service_jobs sj_install  ON sj_install.id = ins_tbl.install_job_id
            LEFT JOIN ({$sql_last_service}) ls  ON ls.customer_name  = loc_tbl.customer_name
            LEFT JOIN ({$sql_last_any}) la             ON la.customer_name  = loc_tbl.customer_name
            ORDER BY install_date DESC
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

            // กรองตาม filter สี
            if ($filter !== 'all' && $marker_status !== $filter) {
                continue;
            }

            // กรองตาม job_type
            if (!empty($job_type) && $job_type !== 'all') {
                $service_types = ['ติดตั้ง', 'เปลี่ยนไส้กรอง'];
                if (in_array($job_type, $service_types)) {
                    // ติดตั้ง/เปลี่ยนไส้กรอง → เทียบกับ last_service_type
                    if (($r['last_service_type'] ?? '') !== $job_type) {
                        continue;
                    }
                } else {
                    // ประเภทอื่น → เทียบกับ last_any_type (job ล่าสุดทุกประเภท)
                    if (($r['last_any_type'] ?? '') !== $job_type) {
                        continue;
                    }
                }
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
                'last_service_type'=> $r['last_service_type'] ?? '',
                'last_any_type'    => $r['last_any_type']     ?? '',
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

    // API: job_types ที่มีจริงใน last_service ของแต่ละลูกค้า
    public function api_job_types() {
        header('Content-Type: application/json; charset=utf-8');
        $sql = "
            SELECT DISTINCT
                SUBSTRING_INDEX(GROUP_CONCAT(job_type ORDER BY start_time DESC SEPARATOR '|'), '|', 1) AS last_type
            FROM service_jobs
            WHERE start_time IS NOT NULL
            GROUP BY customer_name
            HAVING last_type IS NOT NULL AND last_type != ''
            ORDER BY last_type
        ";
        $rows = $this->db->query($sql)->result_array();
        $types = array_values(array_unique(array_column($rows, 'last_type')));
        sort($types);
        echo json_encode(['success'=>true,'data'=>$types], JSON_UNESCAPED_UNICODE);
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

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CustomerMap extends CI_Controller {

    // เดิม public_index/api_* อยู่ในกลุ่ม "ไม่ต้อง login" (public access)
    // ตอนนี้เปลี่ยนตาม requirement ใหม่: ทุกหน้า/ทุก endpoint ในนี้ต้อง login เสมอ ไม่มีอะไร public แล้ว
    // ยกเว้นเมธอดใน $token_allowed_methods ที่ยอมให้เข้าด้วย token แทนได้ (ดูด้านล่าง)
    private $public_methods = [];

    // เมธอดที่ยอมให้เข้าถึงด้วย token แทนการ login — ใช้เฉพาะกรณี embed แผนที่
    // (อ่านอย่างเดียว บิลเดียว) จากหน้า tgsmartlife.com/register-product เท่านั้น
    private $token_allowed_methods = ['public_index', 'api_markers', 'api_history', 'api_warranty_info'];

    // เลขบิลที่ตรวจผ่านจาก token ของ request นี้ (null = ไม่ได้ใช้ token หรือยังไม่ตรวจ)
    private $token_bill_no = null;
    // true = มี token ส่งมาด้วยแต่ตรวจไม่ผ่าน (ปลอม/หมดอายุ) — ต้องปฏิเสธชัดเจน ห้าม fallback เงียบๆ
    private $token_invalid = false;

    public function __construct() {
        parent::__construct();
        $method = $this->router->fetch_method();
        $token  = $this->input->get('token', TRUE);

        if (!empty($token) && in_array($method, $this->token_allowed_methods)) {
            $this->load->library('map_token');
            $bill_no = $this->map_token->verify($token);
            if ($bill_no) {
                $this->token_bill_no = $bill_no;
            } else {
                $this->token_invalid = true;
            }
            // มี token มาด้วย (ไม่ว่าจะผ่านหรือไม่) ข้ามการเช็ค login ไปเลย ปล่อยให้แต่ละเมธอด
            // จัดการเอง (โชว์แผนที่ถ้า token ผ่าน / โชว์ข้อความลิงก์หมดอายุถ้าไม่ผ่าน) —
            // ไม่ redirect ไป /login เพราะผู้เข้าชมทางนี้ไม่มีบัญชีให้ login อยู่แล้ว
            return;
        }

        if (!in_array($method, $this->public_methods) && !$this->session->userdata('logged_in')) {
            redirect('login');
            return;
        }
    }

    // หน้าสำหรับ superadmin/admin/staff (ต้อง login, มี header/footer)
    // role = employee ห้ามเข้าหน้านี้ ให้ใช้ /map แทน (ซึ่งมีระบบสิทธิ์ของตัวเอง)
    public function index() {
        if ($this->session->userdata('role') === 'employee') {
            redirect('map');
            return;
        }
        $data['title']     = 'แผนที่ลูกค้า';
        $data['gmaps_key'] = $this->config->item('gmaps_key') ?? '';
        $this->load->view('templates/header', $data);
        $this->load->view('customer_map/index', $data);
        $this->load->view('templates/footer');
    }

    // หน้า /map — เดิมเป็น public ไม่ต้อง login (ไม่มี header/footer)
    // ตอนนี้ต้อง login เสมอ (เช็คใน __construct แล้ว) และถ้า role = employee ต้องได้รับสิทธิ์ (map_access)
    // ก่อนถึงจะเห็นแผนที่จริง — ไม่งั้นจะเห็นข้อความ "รอการอนุมัติสิทธิ์" แทน (ดูใน view)
    // ชื่อฟังก์ชันยังคงเป็น public_index เหมือนเดิม (ไม่ได้แปลว่า public แล้ว) เพื่อไม่แก้ routes.php เกินจำเป็น
    //
    // โหมด token (embed จาก register-product): ข้าม login/สิทธิ์พนักงานไปเลย โชว์เฉพาะบิลเดียว
    // ตาม token, ถ้า token หมดอายุ/ปลอม โชว์ข้อความแจ้งแทนแผนที่ (ไม่ redirect ไป login)
    public function public_index() {
        $data['title']         = 'แผนที่ลูกค้า';
        $data['gmaps_key']     = $this->config->item('gmaps_key') ?? '';
        $data['token_expired'] = $this->token_invalid;
        $data['token_mode']    = ($this->token_bill_no !== null);
        $data['token_bill_no'] = $this->token_bill_no;
        $data['token']         = $this->input->get('token', TRUE) ?: '';
        $data['show_map']      = $this->token_invalid
            ? false
            : (($this->token_bill_no !== null) ? true : $this->_employee_allowed());
        // มุมมองพนักงานทั่วไป (login ปกติ ไม่ใช่ token) — ใช้คุมปุ่ม "ดูรายละเอียด" ให้เป็น popup
        // แบบจำกัดฟิลด์แทนการเด้งไปหน้า /service ซึ่งพนักงานเข้าไม่ได้อยู่แล้ว
        $data['is_employee_view'] = ($this->token_bill_no === null && $this->session->userdata('role') === 'employee');
        $this->load->view('customer_map/public', $data);
    }

    // true = ดูแผนที่ได้ (role อื่นที่ไม่ใช่ employee เข้าได้เสมอ, employee ต้องมี map_access = 1)
    private function _employee_allowed() {
        if ($this->session->userdata('role') !== 'employee') {
            return true;
        }
        $this->load->model('Employee_model');
        return $this->Employee_model->has_map_access($this->session->userdata('user_id'));
    }

    private function _forbidden_json() {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'], JSON_UNESCAPED_UNICODE);
    }

    // API: markers
    public function api_markers() {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->token_invalid) { $this->_forbidden_json(); return; }
        if ($this->token_bill_no === null && !$this->_employee_allowed()) { $this->_forbidden_json(); return; }
        try {

        // โหมด token: บังคับกรองเหลือแค่บิลเดียวตาม token เสมอ ไม่สนใจ filter/q/tech/job_type ที่ส่งมา
        $q        = $this->token_bill_no ? '' : trim($this->input->get('q',        TRUE) ?? '');
        $filter   = $this->token_bill_no ? 'all' : trim($this->input->get('filter',    TRUE) ?? 'all');
        $tech     = $this->token_bill_no ? '' : trim($this->input->get('tech',      TRUE) ?? '');
        $job_type = $this->token_bill_no ? '' : trim($this->input->get('job_type',  TRUE) ?? '');
        $category = $this->token_bill_no ? '' : trim($this->input->get('category',  TRUE) ?? '');
        $debugSample = [];
        $today    = date('Y-m-d');

        // ── รอบระยะเวลาบริการ + หมวดหมู่ (หลัก+ย่อย) ต่อสินค้า ──────────────────────
        // จับคู่ผ่าน bill_no: service_jobs.bill_no (ตัดส่วนต่อท้าย _1,_2 ฯลฯ ออกก่อน) ->
        // tgsmartlife.product_regis.bill_number -> product_regis.product_id -> product
        // (ต้อง product_id ไม่เป็น NULL ด้วย) — ไม่ใช่การจับคู่ผ่านชื่อสินค้า (product_service/
        // regis_name) แบบที่เคยทำผิดไป ไม่เจอ/ไม่มี product_id ใช้รอบ 6 เดือนเดิม ไม่กรองออกจากหมวดหมู่
        $serviceCycleByBillNumber = [];
        $categoryByBillNumber = [];
        $tgDebug = ['connected' => false, 'error' => null, 'rows_fetched' => 0];
        try {
            $tg_db = $this->load->database('tgsmartlife', TRUE);
            $cyc_rows = $tg_db
                ->select('product_regis.bill_number, product.service_cycle_value, product.service_cycle_unit, product_category.name AS category_name, product_subcategory.subcategory_name AS subcategory_name')
                ->join('product', 'product.id = product_regis.product_id', 'inner')
                ->join('product_category', 'product_category.id = product.category', 'left')
                ->join('product_subcategory', 'product_subcategory.id = product.sub_category_id', 'left')
                ->where('product_regis.product_id IS NOT NULL', null, false)
                ->get('product_regis')
                ->result_array();
            $tgDebug['connected'] = true;
            $tgDebug['rows_fetched'] = count($cyc_rows);
            foreach ($cyc_rows as $cr) {
                $bn = trim($cr['bill_number']);
                if ($bn === '') { continue; }
                if ($cr['service_cycle_value'] !== null && $cr['service_cycle_unit'] !== null) {
                    $serviceCycleByBillNumber[$bn] = [
                        'value' => (int) $cr['service_cycle_value'],
                        'unit'  => $cr['service_cycle_unit'],
                    ];
                }
                if (!empty($cr['category_name']) || !empty($cr['subcategory_name'])) {
                    $categoryByBillNumber[$bn] = [
                        'main' => $cr['category_name'] ?: null,
                        'sub'  => $cr['subcategory_name'] ?: null,
                    ];
                }
            }
        } catch (Exception $e) {
            $tgDebug['error'] = $e->getMessage();
            // เชื่อมฐาน tgsmartlife ไม่ได้ (เช่น ตั้งค่า credential ผิดตอน local) — ใช้รอบ 6 เดือนเดิมทุกแถวแทน
            $serviceCycleByRegisName = [];
            $categoryByRegisName = [];
        }

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
        if (!empty($this->token_bill_no)) {
            // โหมด token: จำกัดเหลือแค่บิลนี้บิลเดียว
            // service_jobs.bill_no มักมีต่อท้ายเป็น _1, _2 ฯลฯ (เช่น RT-2606007_1) ในขณะที่
            // product_regis.bill_number (ฝั่ง tgsmartlife) เก็บเป็นเลขบิลเปล่าๆ — เทียบตรงเป๊ะ (=)
            // อย่างเดียวจะหาไม่เจอ แต่ LIKE 'เลขบิล%' เฉยๆ ก็เสี่ยงไปจับบิลอื่นที่ขึ้นต้นด้วยเลขเดียวกัน
            // โดยบังเอิญ (เช่น RT-26060820) จึงเทียบแบบ "ตรงเป๊ะ" หรือ "ตรงเป๊ะ+ตามด้วย _" เท่านั้น
            $where_extra .= " AND (sj2.bill_no = ? OR sj2.bill_no LIKE ?)";
            $binds[] = $this->token_bill_no;
            $binds[] = $this->token_bill_no . '\\_%';
        }

        // subquery 1a: หา job ที่มีพิกัดจริง (close/start lat) ต่อลูกค้า
        $sql_loc_gps = "
            SELECT sj2.customer_name,
                   MAX(sj2.id) AS loc_job_id,
                   'gps' AS loc_source
            FROM service_jobs sj2
            WHERE (
                (sj2.close_lat IS NOT NULL AND sj2.close_lat != '')
                OR (sj2.start_lat IS NOT NULL AND sj2.start_lat != '')
              )
              AND sj2.status NOT IN ('ยกเลิกนัด')
              {$where_extra}
            GROUP BY sj2.customer_name
        ";

        // subquery 1b: หา job ที่มี branch_id ที่มีพิกัด (fallback) ต่อลูกค้าที่ไม่มีพิกัด GPS
        $sql_loc_branch = "
            SELECT sj2.customer_name,
                   MAX(sj2.id) AS loc_job_id,
                   'branch' AS loc_source
            FROM service_jobs sj2
            INNER JOIN branches brb ON brb.id = sj2.branch_id
                AND brb.lat IS NOT NULL AND brb.lng IS NOT NULL
            WHERE sj2.status NOT IN ('ยกเลิกนัด')
              {$where_extra}
            GROUP BY sj2.customer_name
        ";

        // รวม GPS + Branch fallback (UNION แบบ GPS ก่อน)
        $sql_loc = "
            SELECT customer_name, loc_job_id FROM ({$sql_loc_gps}) gps_tbl
            UNION
            SELECT b.customer_name, b.loc_job_id FROM ({$sql_loc_branch}) b
            WHERE b.customer_name NOT IN (SELECT customer_name FROM ({$sql_loc_gps}) gps_tbl2)
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
                br_loc.lat AS branch_lat, br_loc.lng AS branch_lng,
                ls.last_service_date,
                ls.last_service_type,
                la.last_any_type,
                DATEDIFF(CURDATE(), ls.last_service_date) AS days_since
            FROM ({$sql_loc}) loc_tbl
            JOIN service_jobs sj_loc       ON sj_loc.id = loc_tbl.loc_job_id
            LEFT JOIN ({$sql_install}) ins_tbl ON ins_tbl.customer_name = loc_tbl.customer_name
            LEFT JOIN service_jobs sj_install  ON sj_install.id = ins_tbl.install_job_id
            LEFT JOIN branches br_loc              ON br_loc.id = sj_loc.branch_id
            LEFT JOIN ({$sql_last_service}) ls  ON ls.customer_name  = loc_tbl.customer_name
            LEFT JOIN ({$sql_last_any}) la             ON la.customer_name  = loc_tbl.customer_name
            ORDER BY install_date DESC
        ";

        // binds: sql_loc_gps + sql_loc_branch + sql_loc_gps (ใน UNION subquery)
        $query = $this->db->query($sql, array_merge($binds, $binds, $binds));
        $rows  = $query->result_array();

        $markers = [];
        $counts  = ['all' => 0, 'green' => 0, 'yellow' => 0, 'red' => 0, 'overdue' => 0, 'pending' => 0];
        $branch_pin_seq = []; // นับลำดับ record ที่ตกพิกัดสาขาเดียวกัน เพื่อกระจายหมุดไม่ให้ทับกัน

        foreach ($rows as $r) {
            // Priority: close_lat → start_lat → branch lat
            if (!empty($r['close_lat']) && !empty($r['close_lng'])
                && is_numeric(trim($r['close_lat'])) && is_numeric(trim($r['close_lng']))) {
                $lat = (float)trim($r['close_lat']);
                $lng = (float)trim($r['close_lng']);
            } elseif (!empty($r['start_lat']) && !empty($r['start_lng'])
                && is_numeric(trim($r['start_lat'])) && is_numeric(trim($r['start_lng']))) {
                $lat = (float)trim($r['start_lat']);
                $lng = (float)trim($r['start_lng']);
            } elseif (!empty($r['branch_lat']) && !empty($r['branch_lng'])
                && is_numeric($r['branch_lat']) && is_numeric($r['branch_lng'])) {
                $lat = (float)$r['branch_lat'];
                $lng = (float)$r['branch_lng'];

                // กระจายหมุดที่ใช้พิกัดสาขาเดียวกันออกจากกัน (วงก้นหอย golden-angle)
                // ตำแหน่งคงที่ทุกครั้งที่โหลด (deterministic ตามลำดับที่เจอ) ไม่ใช่สุ่มใหม่ทุกครั้ง
                $bkey = $lat . ',' . $lng;
                $idx  = $branch_pin_seq[$bkey] ?? 0;
                $branch_pin_seq[$bkey] = $idx + 1;
                if ($idx > 0) {
                    $angle_rad  = deg2rad($idx * 137.508); // golden angle กระจายสม่ำเสมอแบบดอกทานตะวัน
                    $radius_deg = 0.00015 * sqrt($idx);     // ขยายวงตามลำดับ ~16.7 เมตรต่อ sqrt(idx) (ปรับตัวเลขนี้ได้ถ้าอยากให้ห่าง/ชิดกว่านี้)
                    $lat += $radius_deg * cos($angle_rad);
                    $lng += $radius_deg * sin($angle_rad) / cos(deg2rad($lat));
                }
            } else {
                continue; // ไม่มีพิกัดเลย ข้ามไป
            }

            // ── คำนวณครบกำหนด ──────────────────────────────────────
            // ค่าเริ่มต้นคือรอบเดิม (6 เดือนนับจาก last_service_date) เหมือนที่เคยมีมาตลอด
            // จับคู่ผ่านเลขบิล (ตัดส่วนต่อท้าย _1,_2 ฯลฯ ออกก่อน เพราะฝั่ง service_jobs มักมีต่อท้าย
            // แต่ product_regis.bill_number ฝั่ง tgsmartlife ไม่มี) กับ product_regis ที่มี product_id
            // ผูกไว้แล้วเท่านั้น — ไม่เจอ/ไม่มี product_id ยังใช้รอบ 6 เดือนเดิมเป๊ะๆ ไม่กรองออก
            $intervalStr = '+6 months';
            $billNoBase = preg_replace('/_\d+$/', '', trim((string) ($r['bill_no'] ?? '')));
            $productCat = $categoryByBillNumber[$billNoBase] ?? null;
            $productCategoryMain = $productCat['main'] ?? null;
            $productCategorySub  = $productCat['sub']  ?? null;
            if (count($debugSample) < 10) {
                $debugSample[] = [
                    'bill_no'      => $r['bill_no'] ?? null,
                    'bill_no_base' => $billNoBase,
                    'matched_main' => $productCategoryMain,
                    'matched_sub'  => $productCategorySub,
                ];
            }
            if ($billNoBase !== '' && isset($serviceCycleByBillNumber[$billNoBase])) {
                $cyc      = $serviceCycleByBillNumber[$billNoBase];
                $unitWord = ($cyc['unit'] === 'year') ? 'years' : 'months';
                if ($cyc['value'] > 0) {
                    $intervalStr = '+' . $cyc['value'] . ' ' . $unitWord;
                }
            }

            $base    = $r['last_service_date'] ?? $r['install_date'];
            $ts_now  = strtotime($today);
            $due_next = null;  // วันครบกำหนดถัดไป (Y-m-d)
            $days_to_due = null;

            if ($base) {
                $ts_base = strtotime($base);

                // หา "รอบแรกที่ครบกำหนด" (รอบแรกหลัง last_service ตามรอบของสินค้านั้น หรือ 6 เดือน ถ้าไม่เจอ)
                $ts_first_due = strtotime(date('Y-m-d', $ts_base) . ' ' . $intervalStr);

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
                        $ts_due = strtotime(date('Y-m-d', $ts_due) . ' ' . $intervalStr);
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

            // กรองตามหมวดหมู่สินค้า (จาก tgsmartlife ผ่านการจับคู่ bill_no ด้านบน) — เทียบได้ทั้งหมวดหมู่หลักและย่อย
            if (!empty($category) && $category !== 'all') {
                if ($productCategoryMain !== $category && $productCategorySub !== $category) {
                    continue;
                }
            }

            // นับสถิติหลังกรอง job_type/category แล้ว (ให้แถบสถิติสะท้อนตัวกรองที่เลือกอยู่จริง
            // ตามที่ระบุ) — แต่ยังนับก่อนเช็ค filter สี/สถานะเอง เพราะแถบสถิติคือตัวแบ่งตามสีนั้น
            // อยู่แล้ว ต้องนับครบทุกสีพร้อมกันไว้ ไม่งั้นกดปุ่มสีไหนจะเหลือแต่ปุ่มนั้นตัวเดียวไม่เป็น 0
            $counts['all']++;
            $counts[$marker_status]++;
            if ($r['status'] === 'รอดำเนินการ') {
                $counts['pending']++;
            }

            // กรองตาม filter สี (filter=pending คือกรองตามสถานะงานจริง แยกจากสีวันครบกำหนด)
            if ($filter === 'pending') {
                if ($r['status'] !== 'รอดำเนินการ') continue;
            } elseif ($filter !== 'all' && $marker_status !== $filter) {
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
                'product_category'     => $productCategoryMain,
                'product_subcategory'  => $productCategorySub,
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

        $response = [
            'success' => true,
            'data'    => $markers,
            'counts'  => $counts,
        ];
        // ── ชั่วคราว เพื่อวินิจฉัยปัญหาตัวกรองหมวดหมู่ — ลบออกได้เมื่อยืนยันสาเหตุแล้ว ──
        // เรียกด้วย &debug=1 ต่อท้าย URL api_markers เพื่อดูว่า: เชื่อมฐาน tgsmartlife ได้จริงไหม,
        // ดึงมากี่แถว, ค่า category ที่ server ได้รับจริงคืออะไร, ตัวอย่างการจับคู่ 10 แถวแรก
        if ($this->input->get('debug', TRUE) === '1') {
            $response['_debug'] = [
                'tgsmartlife_connected'   => $tgDebug['connected'],
                'tgsmartlife_error'       => $tgDebug['error'],
                'tgsmartlife_rows_fetched'=> $tgDebug['rows_fetched'],
                'category_received'      => $category,
                'sample_matches'         => $debugSample,
            ];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // API: ประวัติการให้บริการของลูกค้า (ค้นจาก customer_name)
    // โหมด token: จำกัดเหลือแค่ประวัติของ "บิลนี้บิลเดียว" (ไม่ใช่ทั้ง customer_name) กันไม่ให้
    // เห็นบิล/ที่อยู่อื่นของลูกค้าคนเดียวกันที่ไม่เกี่ยวกับบิลที่ค้นหามา
    public function api_history() {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->token_invalid) { $this->_forbidden_json(); return; }

        if ($this->token_bill_no !== null) {
            // เหตุผลเดียวกับ api_markers() — bill_no ฝั่งนี้มักมีต่อท้าย _1, _2 ฯลฯ
            // ใช้ query แบบเดียวกัน (ตรงเป๊ะ หรือ ตรงเป๊ะ+ตามด้วย _) ให้สอดคล้องกัน
            $sql = "SELECT id, bill_no, job_type, install_date, start_time, status, product_service, technician, tech_note
                    FROM service_jobs
                    WHERE bill_no = ? OR bill_no LIKE ?
                    ORDER BY install_date DESC, id DESC";
            $rows = $this->db->query($sql, [$this->token_bill_no, $this->token_bill_no . '\\_%'])->result_array();
            echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!$this->_employee_allowed()) { $this->_forbidden_json(); return; }
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

    // API: ข้อมูลรับประกันสินค้า (จากฐานข้อมูลของเว็บ tgsmartlife) — แทนที่ลิงก์ "รายละเอียด"
    // เดิมที่ชี้ไปหน้า admin (ใช้ไม่ได้กับลูกค้าที่เข้าผ่าน token อยู่แล้ว เพราะต้อง login)
    public function api_warranty_info() {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->token_invalid) { $this->_forbidden_json(); return; }

        $bill_no = $this->token_bill_no;
        if (empty($bill_no)) {
            // staff/admin ที่ login ปกติ เรียกดูโดยระบุ bill_no เองผ่าน query string ได้เช่นกัน
            if (!$this->_employee_allowed()) { $this->_forbidden_json(); return; }
            $bill_no = trim($this->input->get('bill_no', TRUE) ?? '');
        }
        if (empty($bill_no)) {
            echo json_encode(['success'=>false,'message'=>'ไม่ระบุเลขที่บิล'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->load->model('Warranty_model');
        $row = $this->Warranty_model->get_by_bill($bill_no);
        if (!$row) {
            echo json_encode(['success'=>false,'message'=>'ไม่พบข้อมูลการลงทะเบียนรับประกันสำหรับบิลนี้'], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
    }

    // API: รายละเอียดงาน (แบบจำกัดฟิลด์) สำหรับพนักงานทั่วไปเท่านั้น กด "ดูรายละเอียด" บน /map
    // ดึงจากตาราง service_jobs ตัวเดียวกับที่ /service?search= ใช้ (api/service/{id}) แต่ตัดฟิลด์
    // ประเภทงาน, สถานะ, วันที่นัด, เวลา, หมายเหตุช่าง, หมายเหตุบิล และไม่ดึงตำแหน่งที่ช่างบันทึก
    // การเข้างาน (start_lat/start_lng/start_time/start_address) ออกทั้งหมดตามที่ระบุ
    public function api_job_detail($id) {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->token_invalid) { $this->_forbidden_json(); return; }
        // จำกัดเฉพาะ role=employee ที่ login ปกติ (ไม่ใช่ token) และมีสิทธิ์ดูแผนที่อยู่แล้วเท่านั้น
        // admin/superadmin/staff ใช้ปุ่ม "ดูรายละเอียด" แบบเดิม (เด้งไปหน้า /service) ไม่ผ่านจุดนี้
        if ($this->token_bill_no !== null
            || $this->session->userdata('role') !== 'employee'
            || !$this->_employee_allowed()) {
            $this->_forbidden_json(); return;
        }

        $row = $this->db
            ->select('id, bill_no, customer_name, phone, purchase_date, address, location, technician, team, branch, sale_code, product_service, tags')
            ->where('id', (int)$id)
            ->get('service_jobs')
            ->row_array();
        if (!$row) {
            echo json_encode(['success'=>false,'message'=>'ไม่พบข้อมูล'], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
    }

    // API: job_types ที่มีจริงใน last_service ของแต่ละลูกค้า
    public function api_job_types() {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->_employee_allowed()) { $this->_forbidden_json(); return; }
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

    // API: หมวดหมู่สินค้า สำหรับ filter dropdown — เฉพาะหมวดหมู่ที่มีงานบริการจริงอยู่ในระบบ
    // (จับคู่ product_service ที่ใช้จริงกับ tgsmartlife.product.regis_name เหมือน api_markers())
    public function api_categories() {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->_employee_allowed()) { $this->_forbidden_json(); return; }

        $rows = $this->db
            ->distinct()
            ->select('bill_no')
            ->where('bill_no IS NOT NULL', null, false)
            ->where('bill_no !=', '')
            ->get('service_jobs')
            ->result_array();
        // ตัดส่วนต่อท้าย _1,_2 ฯลฯ ออกก่อนเทียบ เหมือนกับใน api_markers()
        $usedBillNumbers = [];
        foreach ($rows as $r) {
            $usedBillNumbers[preg_replace('/_\d+$/', '', trim($r['bill_no']))] = true;
        }

        $mainCategories = [];
        $subCategories  = [];
        try {
            $tg_db = $this->load->database('tgsmartlife', TRUE);
            $cat_rows = $tg_db
                ->select('product_regis.bill_number, product_category.name AS category_name, product_subcategory.subcategory_name AS subcategory_name')
                ->join('product', 'product.id = product_regis.product_id', 'inner')
                ->join('product_category', 'product_category.id = product.category', 'left')
                ->join('product_subcategory', 'product_subcategory.id = product.sub_category_id', 'left')
                ->where('product_regis.product_id IS NOT NULL', null, false)
                ->get('product_regis')
                ->result_array();
            $mainByBillNumber = [];
            $subByBillNumber  = [];
            foreach ($cat_rows as $cr) {
                $bn = trim($cr['bill_number']);
                if (!empty($cr['category_name']))    { $mainByBillNumber[$bn] = $cr['category_name']; }
                if (!empty($cr['subcategory_name']))  { $subByBillNumber[$bn]  = $cr['subcategory_name']; }
            }
            foreach (array_keys($usedBillNumbers) as $bn) {
                if (isset($mainByBillNumber[$bn])) { $mainCategories[$mainByBillNumber[$bn]] = true; }
                if (isset($subByBillNumber[$bn]))  { $subCategories[$subByBillNumber[$bn]]   = true; }
            }
        } catch (Exception $e) {
            $mainCategories = [];
            $subCategories  = [];
        }

        $mainList = array_keys($mainCategories); sort($mainList);
        $subList  = array_keys($subCategories);  sort($subList);
        echo json_encode(['success'=>true,'data'=>['main'=>$mainList,'sub'=>$subList]], JSON_UNESCAPED_UNICODE);
    }

    // API: technician list สำหรับ filter dropdown
    // เฉพาะช่างที่มีอย่างน้อย 1 งานที่หาพิกัดขึ้นแผนที่ได้จริง (GPS หรือ branch fallback)
    // ให้ตรงกับ logic เดียวกับ api_markers() — กันไม่ให้ dropdown มีชื่อช่างที่กดแล้วไม่ขึ้นหมุดเลย
    public function api_techs() {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->_employee_allowed()) { $this->_forbidden_json(); return; }
        $sql = "
            SELECT DISTINCT sj.technician
            FROM service_jobs sj
            LEFT JOIN branches br ON br.id = sj.branch_id
            WHERE sj.technician IS NOT NULL
              AND sj.technician != ''
              AND sj.status NOT IN ('ยกเลิกนัด')
              AND (
                  (sj.close_lat IS NOT NULL AND sj.close_lat != '')
                  OR (sj.start_lat IS NOT NULL AND sj.start_lat != '')
                  OR (br.lat IS NOT NULL AND br.lng IS NOT NULL)
              )
            ORDER BY sj.technician
        ";
        $rows  = $this->db->query($sql)->result_array();
        $techs = array_column($rows, 'technician');
        echo json_encode(['success' => true, 'data' => $techs], JSON_UNESCAPED_UNICODE);
    }
}

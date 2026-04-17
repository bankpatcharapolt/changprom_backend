<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Service extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->session->userdata('logged_in')) redirect('login');
        $this->load->model('Service_model');
        $this->load->helper(['url','form']);
    }

    public function index() {
        $data['title']   = 'รายการงานบริการ';
        $data['page_js'] = ['service'];
        $this->load->view('templates/header', $data);
        $this->load->view('service/index', $data);
        $this->load->view('templates/footer');
    }

    public function import() {
        $data['title']   = 'นำเข้าข้อมูล Excel';
        $data['page_js'] = ['import'];
        $this->load->view('templates/header', $data);
        $this->load->view('service/import', $data);
        $this->load->view('templates/footer');
    }

    public function import_excel() {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_FILES['excel_file']['name'])) {
            echo json_encode(['success'=>false,'message'=>'กรุณาเลือกไฟล์']); return;
        }

        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx','xls','csv'])) {
            echo json_encode(['success'=>false,'message'=>'รองรับเฉพาะ .xlsx, .xls, .csv']); return;
        }
        if ($_FILES['excel_file']['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success'=>false,'message'=>'ไฟล์ขนาดไม่เกิน 10MB']); return;
        }

        if (!is_dir(FCPATH . 'uploads/')) mkdir(FCPATH . 'uploads/', 0755, TRUE);
        $dest = FCPATH . 'uploads/import_' . time() . '.' . $ext;

        if (!move_uploaded_file($_FILES['excel_file']['tmp_name'], $dest)) {
            echo json_encode(['success'=>false,'message'=>'ไม่สามารถอัปโหลดได้']); return;
        }

        if ($ext === 'csv') {
            $rows = $this->_read_csv($dest);
        } else {
            $rows = $this->_read_xlsx_native($dest);
        }

        @unlink($dest);

        if ($rows === false || count($rows) === 0) {
            echo json_encode(['success'=>false,'message'=>'ไม่สามารถอ่านไฟล์ได้ หรือไฟล์ว่างเปล่า']); return;
        }

        $this->_process_and_save($rows);
    }

    // ================================================================
    // อ่าน XLSX bank- 20260323 10.04 
    // ================================================================
    private function _read_xlsx_native($file) {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($file) !== TRUE) return false;

        // อ่าน shared strings
        $strings = [];
        $sstRaw = $zip->getFromName('xl/sharedStrings.xml');
        if ($sstRaw) {
            $sst = @simplexml_load_string($sstRaw, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($sst) {
                foreach ($sst->si as $si) {
                    if (isset($si->t)) {
                        $strings[] = (string)$si->t;
                    } else {
                        $t = '';
                        foreach ($si->r as $r) $t .= (string)$r->t;
                        $strings[] = $t;
                    }
                }
            }
        }

        // อ่าน sheet แรก
        $sheetRaw = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetRaw) return false;

        $sheet = @simplexml_load_string($sheetRaw, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$sheet) return false;

        $result = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowArr = [];
            $maxIdx = 0;
            foreach ($row->c as $cell) {
                $ref = (string)$cell['r'];
                preg_match('/^([A-Z]+)/', $ref, $m);
                $idx = $this->_col_index($m[1]);
                $maxIdx = max($maxIdx, $idx);

                $type = (string)$cell['t'];
                $v    = isset($cell->v) ? (string)$cell->v : '';

                if ($type === 's') {
                    $v = isset($strings[(int)$v]) ? $strings[(int)$v] : '';
                } elseif ($type === 'inlineStr') {
                    $v = isset($cell->is->t) ? (string)$cell->is->t : '';
                }
                $rowArr[$idx] = $v;
            }

            $normalized = [];
            for ($i = 0; $i <= $maxIdx; $i++) {
                $normalized[] = isset($rowArr[$i]) ? $rowArr[$i] : '';
            }
            $result[] = $normalized;
        }

        return $result;
    }

    private function _col_index($col) {
        $n = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $n = $n * 26 + (ord($col[$i]) - 64);
        }
        return $n - 1;
    }

    private function _read_csv($file) {
        $rows = [];
        if (($h = fopen($file, 'r')) === FALSE) return false;
        $bom = fread($h, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($h);
        while (($row = fgetcsv($h, 0, ',')) !== FALSE) {
            $rows[] = array_map('trim', $row);
        }
        fclose($h);
        return $rows ?: false;
    }

    // ================================================================
    // ตรวจ format ของไฟล์จาก header row แก้บัคพี่จ๋อม 20260323
    // - Format ใหม่ (21 cols): ไม่มีคอลัมน์ "บันทึกบัญชี", มี "หมายเหตุ" และ "แท็ก"
    // - Format เก่า (33 cols): มีคอลัมน์ "บันทึกบัญชี", สินค้าหลาย row ต่อบิล
    // ================================================================
    private function _detect_format($headerRow) {
        $line = implode('|', array_map('strval', $headerRow));
        if (mb_strpos($line, 'บันทึกบัญชี') !== false) {
            return 'old'; // 33 cols — แสดงรายละเอียด
        }
        return 'new'; // 21 cols — แสดงข้อมูลพื้นฐาน
    }

    // ================================================================
    // หา row เริ่มต้นข้อมูล และ map ข้อมูลเข้าระบบ แก้บัคพี่จ๋อม 20260323
    // ================================================================
    private function _process_and_save($rows) {
        $dataStart  = 0;
        $headerRow  = [];
        $format     = 'new';

        foreach ($rows as $i => $row) {
            $line = implode('', $row);
            if (mb_strpos($line, 'เลขที่เอกสาร') !== false) {
                $headerRow = $row;
                $format    = $this->_detect_format($row);
                $dataStart = $i + 1;
                break;
            }
            if (mb_strpos($line, 'bill_no') !== false || mb_strpos($line, 'เลขที่บิล') !== false) {
                $headerRow = $row;
                $dataStart = $i + 1;
                break;
            }
        }

        $imported = 0; $updated = 0; $errors = [];

        if ($format === 'old') {
            // Format เก่า: 1 บิลมีหลาย row → ต้อง group ก่อน แล้วค่อย save
            $grouped = $this->_group_old_format($rows, $dataStart);
            foreach ($grouped as $billNo => $job) {
                if (empty($billNo)) continue;
                if (empty($job['job_type'])) $job['job_type'] = 'ติดตั้ง';
                if (empty($job['status']))   $job['status']   = 'รอดำเนินการ';
                $job['created_by'] = $this->session->userdata('user_id');

                $exist = $this->Service_model->get_by_bill($billNo);
                if ($exist) {
                    $this->Service_model->update($exist['id'], $job);
                    $updated++;
                } else {
                    $this->Service_model->create($job);
                    $imported++;
                }
            }
        } else {
            // Format ใหม่: 1 บิล = 1 row
            $seen = [];
            for ($i = $dataStart; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row, function($v){ return $v !== ''; }))) continue;

                $job = $this->_map_row_new($row);

                if (empty($job['bill_no'])) {
                    $errors[] = "แถวที่ " . ($i + 1) . ": ไม่มีเลขที่บิล";
                    continue;
                }
                if (in_array($job['bill_no'], $seen)) continue;
                $seen[] = $job['bill_no'];

                if (empty($job['job_type'])) $job['job_type'] = 'ติดตั้ง';
                if (empty($job['status']))   $job['status']   = 'รอดำเนินการ';
                $job['created_by'] = $this->session->userdata('user_id');

                $exist = $this->Service_model->get_by_bill($job['bill_no']);
                if ($exist) {
                    $this->Service_model->update($exist['id'], $job);
                    $updated++;
                } else {
                    $this->Service_model->create($job);
                    $imported++;
                }
            }
        }

        echo json_encode([
            'success'  => true,
            'imported' => $imported,
            'updated'  => $updated,
            'format'   => $format,
            'errors'   => $errors,
            'message'  => "นำเข้าใหม่ {$imported} | อัปเดต {$updated} รายการ" .
                          ($format === 'old' ? ' (รูปแบบรายละเอียด)' : ' (รูปแบบพื้นฐาน)') .
                          (count($errors) ? " | ข้าม " . count($errors) . " แถว" : ''),
        ], JSON_UNESCAPED_UNICODE);
    }

    // ================================================================
    // Format ใหม่ (21 cols) — 1 row = 1 บิล มี หมายเหตุ และ แท็ก แก้บัคพี่จ๋อม 20260323
    // [0]ลำดับ [1]เลขที่เอกสาร [2]วันที่ออก [5]สถานะ [6]ชื่อลูกค้า
    // [9]ชื่อสินค้า [15]ทั้งหมด [18]หมายเหตุ [20]แท็ก
    // ================================================================
    private function _map_row_new($row) {
        $get = function($idx) use ($row) {
            return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
        };

        $note = $get(18);

        $phone = $this->_extract_phone($note);
        list($saleCode, $team, $branch) = $this->_extract_note_fields($note);

        $amount = (float)str_replace([',', ' '], '', $get(15));

        return [
            'bill_no'         => $get(1),
            'customer_name'   => $get(6),
            'purchase_date'   => $this->_parse_date($get(2)),
            'product_service' => $get(9),
            'bill_note'       => $note,
            'tags'            => $get(20),
            'phone'           => $phone,
            'sale_code'       => $saleCode,
            'team'            => $team,
            'branch'          => $branch,
            'amount'          => $amount,
        ];
    }

    // ================================================================
    // Format เก่า (33 cols) — 1 บิลมีหลาย row (สินค้าหลายชิ้น) แก้บัคพี่จ๋อม 20260323
    // [0]ลำดับ [1]เลขที่เอกสาร [2]วันที่ออก [8]ชื่อลูกค้า
    // [13]ชื่อสินค้า [30]ทั้งหมด — ไม่มีหมายเหตุ/แท็ก
    // Group by bill_no แล้วรวมชื่อสินค้า + ใช้ยอดรวมจาก row แรกของบิลนั้น
    // ================================================================
    private function _group_old_format($rows, $dataStart) {
        $grouped = []; // bill_no => job array
        $order   = []; // เก็บลำดับเพื่อ preserve insertion order

        for ($i = $dataStart; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row, function($v){ return $v !== ''; }))) continue;

            $get = function($idx) use ($row) {
                return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
            };

            $billNo = $get(1);
            if (empty($billNo)) continue;

            $product = $get(13);
            $amount  = trim($get(30));
            $isFirstRow = !isset($grouped[$billNo]); // row แรกของบิลนี้

            if ($isFirstRow) {
                $order[] = $billNo;
                $grouped[$billNo] = [
                    'bill_no'         => $billNo,
                    'customer_name'   => $get(8),
                    'purchase_date'   => $this->_parse_date($get(2)),
                    'product_service' => $product,
                    'bill_note'       => '',   
                    'tags'            => '',
                    'phone'           => '',
                    'sale_code'       => '',
                    'team'            => '',
                    'branch'          => '',
                   
                    'amount'          => ($amount !== '' && $amount !== '0')
                                         ? (float)str_replace([',', ' '], '', $amount)
                                         : 0,
                ];
            } else {
                // row ถัดไปของบิลเดียวกัน: ต่อชื่อสินค้า (ถ้าไม่ซ้ำและไม่ว่าง)
                if ($product !== '' && mb_strpos($grouped[$billNo]['product_service'], $product) === false) {
                    $grouped[$billNo]['product_service'] .= ', ' . $product;
                }
                // ยอดรวม: ใช้ค่าแรกที่ไม่ใช่ 0 (col 30 ว่างใน sub-rows)
                if ($grouped[$billNo]['amount'] == 0 && $amount !== '' && $amount !== '0') {
                    $grouped[$billNo]['amount'] = (float)str_replace([',', ' '], '', $amount);
                }
            }
        }

        // คืนค่าตามลำดับที่พบในไฟล์
        $result = [];
        foreach ($order as $billNo) {
            $result[$billNo] = $grouped[$billNo];
        }
        return $result;
    }

    // ================================================================
    // Helper: ดึงเบอร์โทรจาก note
    // ================================================================
    private function _extract_phone($note) {
        if (!$note) return '';
        // หา pattern "เบอร์โทร..." ก่อน
        if (preg_match('/เบอร์โทร(?:พนักงาน)?\s*:?\s*([\d\s\-]+)/u', $note, $m)) {
            $phone = preg_replace('/[^0-9]/', '', $m[1]);
            if (strlen($phone) >= 9) return $phone;
        }
        // fallback: เบอร์ 10 หลักในข้อความ
        if (preg_match('/(0[689]\d{8})/', preg_replace('/[^0-9]/', '', $note), $m)) {
            return $m[1];
        }
        return '';
    }

    // ================================================================
    // Helper: ดึง saleCode, team, branch จาก note
    // ================================================================
    private function _extract_note_fields($note) {
        $saleCode = '';
        $team     = '';
        $branch   = '';
        if (!$note) return [$saleCode, $team, $branch];

        if (preg_match('/รหัสพนักงาน\s*:?\s*([^\n\r]+)/u', $note, $m)) $saleCode = trim($m[1]);
        if (preg_match('/ทีม\s*:?\s*([^\n\r]+)/u',           $note, $m)) $team     = trim($m[1]);
        if (preg_match('/สาขา\s*:?\s*([^\n\r]+)/u',          $note, $m)) $branch   = trim($m[1]);

        return [$saleCode, $team, $branch];
    }

    private function _parse_date($val) {
        if (!$val) return null;
        // dd/mm/yyyy
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $val, $m)) {
            $yr = (int)$m[3];
            if ($yr > 2400) $yr -= 543; // พ.ศ. → ค.ศ.
            return sprintf('%04d-%02d-%02d', $yr, (int)$m[2], (int)$m[1]);
        }
        // yyyy-mm-dd
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $val)) return $val;
        // Excel serial number
        if (is_numeric($val) && $val > 40000) {
            return date('Y-m-d', ($val - 25569) * 86400);
        }
        return null;
    }
}

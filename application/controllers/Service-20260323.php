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
    // อ่าน XLSX ด้วย ZipArchive + SimpleXML (PHP built-in, ไม่ต้องติดตั้งอะไรเพิ่ม)
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
    // หา row เริ่มต้นข้อมูล และ map ข้อมูลเข้าระบบ
    // ================================================================
    private function _process_and_save($rows) {
        $dataStart = 0;

        foreach ($rows as $i => $row) {
            $line = implode('', $row);
            // receipt_report: header คอลัมน์ที่มีคำว่า "เลขที่เอกสาร"
            if (mb_strpos($line, 'เลขที่เอกสาร') !== false) {
                $dataStart = $i + 1; break;
            }
            // CSV template: header มีคำว่า "bill_no" หรือ "เลขที่บิล"
            if (mb_strpos($line, 'bill_no') !== false || mb_strpos($line, 'เลขที่บิล') !== false) {
                $dataStart = $i + 1; break;
            }
        }

        $imported = 0; $updated = 0; $errors = [];
        $seen = [];

        for ($i = $dataStart; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row, function($v){ return $v !== ''; }))) continue;

            $job = $this->_map_row($row);

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

        echo json_encode([
            'success'  => true,
            'imported' => $imported,
            'updated'  => $updated,
            'errors'   => $errors,
            'message'  => "นำเข้าใหม่ {$imported} | อัปเดต {$updated} รายการ" .
                          (count($errors) ? " | ข้าม " . count($errors) . " แถว" : ''),
        ], JSON_UNESCAPED_UNICODE);
    }

    // Map แถวจาก receipt_report_export (col index 0-based)
    // B=1:เลขที่เอกสาร, C=2:วันที่, I=8:ชื่อลูกค้า, N=13:สินค้า, AH=33:หมายเหตุ, AJ=35:แท็ก, AE=30:ทั้งหมด
    private function _map_row($row) {
        $get = function($idx) use ($row) {
            return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
        };

        $note = $get(33);

        // ดึงเบอร์โทรจากหมายเหตุ
        $phone = '';
        if (preg_match('/เบอร์โทร[ขของ\s]*[:\s]*([\d\s\-]+)/u', $note, $m)) {
            $phone = preg_replace('/[^0-9]/', '', $m[1]);
        }
        if (!$phone && preg_match('/(0[689]\d{8})/', preg_replace('/[^0-9]/','',$note), $m)) {
            $phone = $m[1];
        }

        // ดึง ทีม / รหัสพนักงาน / สาขา จากหมายเหตุ
        $saleCode = '';
        $team     = '';
        $branch   = '';
        if (preg_match('/รหัสพนักงาน\s*:?\s*([^\n\r]+)/u', $note, $m)) $saleCode = trim($m[1]);
        if (preg_match('/ทีม\s*:?\s*([^\n\r]+)/u',           $note, $m)) $team     = trim($m[1]);
        if (preg_match('/สาขา\s*:?\s*([^\n\r]+)/u',          $note, $m)) $branch   = trim($m[1]);

        // แปลงวันที่ dd/mm/yyyy หรือ yyyy-mm-dd
        $purchaseDate = $this->_parse_date($get(2));

        // amount
        $amount = (float)str_replace([',', ' '], '', $get(30));

        return [
            'bill_no'         => $get(1),
            'customer_name'   => $get(8),
            'purchase_date'   => $purchaseDate,
            'product_service' => $get(13),
            'bill_note'       => $note,
            'tags'            => $get(35),
            'phone'           => $phone,
            'sale_code'       => $saleCode,
            'team'            => $team,
            'branch'          => $branch,
            'amount'          => $amount,
        ];
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

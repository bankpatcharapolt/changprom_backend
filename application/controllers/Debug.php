<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Debug extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->session->userdata('logged_in')) redirect('login');
    }

    public function tech_db() {
        header('Content-Type: application/json; charset=utf-8');
        $result = [];

        // ดึง config ของ DB ช่าง
        $this->load->database('default'); // โหลด default ก่อน
        $db_config = $this->load->database('technicians', TRUE, TRUE); // TRUE,TRUE = return object + use config
        
        $result['config_check'] = [
            'hostname' => $db_config->hostname ?? 'N/A',
            'database' => $db_config->database ?? 'N/A',
            'username' => $db_config->username ?? 'N/A',
        ];

        // ทดสอบ connect ด้วย mysqli โดยตรง (ไม่ผ่าน CI เพื่อกัน crash)
        $cfg = $this->_get_db_config('technicians');
        $result['config'] = [
            'host' => $cfg['hostname'],
            'db'   => $cfg['database'],
            'user' => $cfg['username'],
        ];

        // ทดสอบ connect ด้วย mysqli
        $conn = @mysqli_connect($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);

        if (!$conn) {
            $result['connected'] = false;
            $result['error']     = mysqli_connect_error();
            $result['error_no']  = mysqli_connect_errno();
            $result['hint']      = $this->_hint(mysqli_connect_errno());
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            return;
        }

        $result['connected'] = true;
        mysqli_set_charset($conn, 'utf8mb4');

        // ลิสต์ตาราง
        $tables = [];
        $r = mysqli_query($conn, 'SHOW TABLES');
        while ($row = mysqli_fetch_row($r)) $tables[] = $row[0];
        $result['tables'] = $tables;

        if (in_array('register', $tables)) {
            // columns
            $cols = [];
            $r2 = mysqli_query($conn, 'DESCRIBE register');
            while ($row = mysqli_fetch_assoc($r2)) $cols[] = $row['Field'];
            $result['register_columns'] = $cols;

            // count
            $r3 = mysqli_query($conn, 'SELECT COUNT(*) as cnt FROM register');
            $result['total_rows'] = mysqli_fetch_assoc($r3)['cnt'];

            // sample
            $r4 = mysqli_query($conn, 'SELECT * FROM register LIMIT 3');
            $samples = [];
            while ($row = mysqli_fetch_assoc($r4)) $samples[] = $row;
            $result['sample_data'] = $samples;

            $result['status'] = 'OK - พร้อมใช้งาน';
        } else {
            $result['status'] = 'ไม่พบตาราง register';
            $result['hint']   = 'ชื่อตารางจริงอาจต่างกัน ดูจาก tables ข้างบน';
        }

        mysqli_close($conn);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function _get_db_config($group) {
        $this->load->config('database');
        $cfg = $this->config->item($group, 'db');
        if (!$cfg) {
            // โหลดตรงจากไฟล์
            include APPPATH . 'config/database.php';
            $cfg = $db[$group];
        }
        return $cfg;
    }

    private function _hint($errno) {
        switch ($errno) {
            case 1045: return 'username หรือ password ผิด → แก้ใน application/config/database.php';
            case 1049: return 'ไม่พบชื่อ database → ตรวจสอบชื่อ database ใน config';
            case 2002: return 'ต่อ hostname ไม่ได้ → hostname ผิด หรือ MySQL ไม่รัน';
            case 1130: return 'host ไม่ได้รับอนุญาต → shared host อาจบล็อก remote connection';
            default:   return 'error code: ' . $errno;
        }
    }
}

<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Map_token.php
 *
 * สร้าง/ตรวจสอบ token สำหรับให้หน้า /map เข้าดูได้โดยไม่ต้อง login
 * ใช้กับ requirement: embed แผนที่ (อ่านอย่างเดียว, บิลเดียว) ในหน้า
 * tgsmartlife.com/register-product
 *
 * รูปแบบ token: base64url(payload_json) . '.' . base64url(hmac_sha256(payload, secret))
 * payload = { "bill_no": "...", "exp": <unix timestamp> }
 *
 * หมายเหตุ: ไฟล์นี้มีอยู่ "คู่กัน" ในระบบ tgsmartlife (เว็บหลัก) ด้วย โค้ดต้องเหมือนกัน
 * ทุกตัวอักษร และ $secret ต้องเป็นค่าเดียวกันทั้ง 2 ระบบ (ตั้งไว้ที่ config/config.php
 * ค่า $config['map_token_secret']) ไม่งั้นฝั่งหนึ่งสร้าง token แล้วอีกฝั่งตรวจไม่ผ่าน
 */
class Map_token {

    // อายุ token: 6 วัน นับจากตอนออก (ตามที่ยืนยันไว้)
    const TTL_SECONDS = 6 * 24 * 60 * 60;

    private $secret;

    public function __construct() {
        $CI =& get_instance();
        $this->secret = (string) $CI->config->item('map_token_secret');
        if ($this->secret === '') {
            log_message('error', 'Map_token: map_token_secret ยังไม่ถูกตั้งค่าใน config.php');
        }
    }

    /**
     * ออก token ใหม่ ผูกกับเลขบิลเดียว หมดอายุใน 6 วัน
     */
    public function generate($bill_no) {
        $payload = [
            'bill_no' => (string) $bill_no,
            'exp'     => time() + self::TTL_SECONDS,
        ];
        $payload_b64 = $this->_b64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig_b64     = $this->_sign($payload_b64);
        return $payload_b64 . '.' . $sig_b64;
    }

    /**
     * ตรวจ token — คืนค่า bill_no ถ้าถูกต้องและยังไม่หมดอายุ, คืน false ถ้าไม่ผ่าน
     */
    public function verify($token) {
        if (empty($token) || strpos($token, '.') === false) {
            return false;
        }
        list($payload_b64, $sig_b64) = explode('.', $token, 2);

        $expected_sig = $this->_sign($payload_b64);
        // hash_equals กัน timing attack ตอนเทียบลายเซ็น
        if (!hash_equals($expected_sig, $sig_b64)) {
            return false;
        }

        $json = $this->_b64url_decode($payload_b64);
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['bill_no']) || empty($data['exp'])) {
            return false;
        }
        if ((int) $data['exp'] < time()) {
            return false; // หมดอายุ
        }
        return $data['bill_no'];
    }

    private function _sign($payload_b64) {
        return $this->_b64url_encode(hash_hmac('sha256', $payload_b64, $this->secret, true));
    }

    private function _b64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function _b64url_decode($data) {
        $pad = strlen($data) % 4;
        if ($pad > 0) { $data .= str_repeat('=', 4 - $pad); }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

<?php
/**
 * generate_test_token.php — สำหรับทดสอบ local เท่านั้น
 *
 * สร้าง token ทดสอบให้บิลที่ระบุ โดยไม่ต้องผ่านหน้า register-product เลย
 * ใช้เทส /map?token=... ได้ทันทีโดยตรง
 *
 * วิธีใช้ (รันจาก command line ที่เครื่อง local):
 *   php generate_test_token.php RT-2606007
 *
 * แล้วเอา token ที่ได้ไปต่อท้าย URL:
 *   http://localhost/service_management/map?token=<token ที่ได้>
 *
 * หมายเหตุ: ไฟล์นี้ไม่ควรอยู่บน production — เป็นเครื่องมือทดสอบเฉยๆ ลบทิ้งได้หลังทดสอบเสร็จ
 * ค่า secret ด้านล่างต้องตรงกับ $config['map_token_secret'] ใน
 * application/config/config.php ของระบบ service_management เป๊ะๆ
 */

if (php_sapi_name() !== 'cli') {
    die('รันจาก command line เท่านั้น เช่น: php generate_test_token.php RT-2606007');
}

$bill_no = $argv[1] ?? null;
if (!$bill_no) {
    die("ใช้งาน: php generate_test_token.php <เลขที่บิล>\nตัวอย่าง: php generate_test_token.php RT-2606007\n");
}

// ต้องตรงกับ $config['map_token_secret'] ใน application/config/config.php ของทั้ง 2 ระบบเป๊ะ
$secret = '9b5bed1b8011ae01c8638425d8bef98f97319e47d31b520c1fcaa43985cd08fb';
$ttl_seconds = 6 * 24 * 60 * 60; // 6 วัน เท่ากับใน Map_token.php

function b64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$payload = json_encode(['bill_no' => (string) $bill_no, 'exp' => time() + $ttl_seconds], JSON_UNESCAPED_UNICODE);
$payload_b64 = b64url_encode($payload);
$sig_b64 = b64url_encode(hash_hmac('sha256', $payload_b64, $secret, true));
$token = $payload_b64 . '.' . $sig_b64;

echo "Bill number : {$bill_no}\n";
echo "Token       : {$token}\n";
echo "หมดอายุ      : " . date('Y-m-d H:i:s', time() + $ttl_seconds) . "\n\n";
echo "ทดสอบด้วย URL:\n";
echo "http://localhost/service_management/map?token=" . urlencode($token) . "\n";

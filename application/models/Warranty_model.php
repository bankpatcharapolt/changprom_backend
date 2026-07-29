<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Warranty_model.php — ดึงข้อมูลลงทะเบียน/รับประกันสินค้าจากฐานข้อมูลของเว็บ tgsmartlife
 * (คนละฐานข้อมูลกับระบบนี้ — เชื่อมผ่าน DB connection group "tgsmartlife" ใน database.php)
 * ใช้แสดงในปุ่ม "ข้อมูลรับประกันสินค้า" บนแผนที่ ตอนเข้าผ่าน token จากหน้า register-product
 */
class Warranty_model extends CI_Model {

    private $tg_db;

    public function __construct() {
        parent::__construct();
        // TRUE = คืนค่าเป็น object connection แยก ไม่ทับ connection หลัก ($this->db เดิมยังใช้ได้ปกติ)
        $this->tg_db = $this->load->database('tgsmartlife', TRUE);
    }

    // ดึงข้อมูลรับประกันของบิลที่ระบุ (เอารายการล่าสุดถ้ามีมากกว่า 1 แถวต่อบิล)
    public function get_by_bill($bill_no) {
        return $this->tg_db
            ->select(
                'product_regis.id, product_regis.bill_number, product_regis.tel_cus, '
                . 'product_regis.tel_idcart, product_regis.detail, product_regis.link, '
                . 'product_regis.file_path, product_regis.created, product_regis.updated, '
                . 'product.name AS product_name, product.warranty AS product_warranty'
            )
            ->from('product_regis')
            ->join('product', 'product.id = product_regis.product_id', 'left')
            ->where('product_regis.bill_number', $bill_no)
            ->order_by('product_regis.created', 'desc')
            ->get()->row_array();
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Service_model extends CI_Model {
    protected $table = 'service_jobs';

    // สถานะที่ถูกต้อง
    public static $statuses = ['รอดำเนินการ','ยืนยันแล้ว','กำลังดำเนินงาน','เสร็จแล้ว','เลื่อนนัด','ยกเลิกนัด'];
    // ประเภทงานที่ถูกต้อง
    public static $job_types = ['ติดตั้ง','ซ่อม','ล้างเครื่อง','เปลี่ยนไส้กรอง','ส่งสินค้า','นำสินค้ากลับ'];

    public function get_all($search='', $status='') {
        if ($search) {
            $this->db->group_start();
            foreach (['bill_no','customer_name','phone','technician','product_service','tags','job_type','team','branch','sale_code'] as $col)
                $this->db->or_like($col, $search);
            $this->db->group_end();
        }
        if ($status) $this->db->where('status', $status);
        return $this->db->order_by('id','DESC')->get($this->table)->result_array();
    }

    public function get_by_id($id) {
        return $this->db->where('id',$id)->get($this->table)->row_array();
    }

    public function get_by_bill($bill_no) {
        return $this->db->where('bill_no',$bill_no)->get($this->table)->row_array();
    }

    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        return $this->db->where('id',$id)->update($this->table, $data);
    }

    public function delete($id) {
        return $this->db->where('id',$id)->delete($this->table);
    }

    public function datatable($start, $length, $search, $orderField, $orderDir) {
        if ($search) {
            $this->db->group_start();
            foreach (['bill_no','customer_name','phone','technician','product_service','tags','job_type','status','team','branch'] as $col)
                $this->db->or_like($col, $search);
            $this->db->group_end();
        }
        return $this->db->order_by($orderField,$orderDir)->limit($length,$start)->get($this->table)->result_array();
    }

    public function count_all($search='') {
        if ($search) {
            $this->db->group_start();
            foreach (['bill_no','customer_name','phone','technician','product_service','tags','job_type','status','team','branch'] as $col)
                $this->db->or_like($col, $search);
            $this->db->group_end();
        }
        return $this->db->count_all_results($this->table);
    }

    // เช็คชนเวลา: ช่างคนเดียว วันเดียว เวลาซ้อนกัน (ห่างกัน < 2 ชั่วโมง)
    public function check_time_conflict($technician, $install_date, $install_time, $exclude_id=0) {
        if (!$technician || !$install_date || !$install_time) return [];
        $this->db->where('technician', $technician)
                 ->where('install_date', $install_date)
                 ->where('install_time IS NOT NULL')
                 ->where('install_time !=', '')
                 ->where_not_in('status', ['ยกเลิกนัด','เลื่อนนัด']);
        if ($exclude_id) $this->db->where('id !=', $exclude_id);
        $jobs = $this->db->get($this->table)->result_array();
        $conflicts = [];
        $newTime = strtotime($install_date . ' ' . $install_time);
        foreach ($jobs as $j) {
            $existTime = strtotime($install_date . ' ' . $j['install_time']);
            $diff = abs($newTime - $existTime) / 3600;
            if ($diff < 2) $conflicts[] = $j;
        }
        return $conflicts;
    }
}

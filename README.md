# ระบบจัดการงานบริการ
**PHP CodeIgniter 3 + Bootstrap 5 + jQuery DataTables + MySQL**

---

## ขั้นตอนติดตั้ง

### 1. ดาวน์โหลด CodeIgniter 3
ไปที่ https://codeigniter.com/download และดาวน์โหลด CodeIgniter 3.x  
แตกไฟล์แล้ว **copy ไฟล์/โฟลเดอร์ทั้งหมด** ของ CI ลงใน folder `service_management/`  
(โฟลเดอร์ที่ต้อง copy: `system/`, `index.php`, `license.txt` ฯลฯ)

### 2. สร้างฐานข้อมูล
เปิด phpMyAdmin หรือ MySQL client แล้วรัน:
```sql
-- นำเข้าไฟล์
sql/schema.sql
```

### 3. แก้ไข Config

**application/config/database.php**
```php
'hostname' => 'localhost',
'username' => 'root',       // เปลี่ยนตามของคุณ
'password' => '',           // เปลี่ยนตามของคุณ
'database' => 'service_management',
```

**application/config/config.php**
```php
$config['base_url'] = 'http://localhost/service_management/';
// เปลี่ยนให้ตรงกับ URL จริงของคุณ
```

### 4. ติดตั้ง PhpSpreadsheet (สำหรับ Import Excel)
```bash
cd application/third_party
mkdir PhpSpreadsheet && cd PhpSpreadsheet
composer require phpoffice/phpspreadsheet
```

### 5. ตั้งค่า Web Server
วาง folder `service_management/` ไว้ใน:
- XAMPP: `C:/xampp/htdocs/service_management/`
- WAMP: `C:/wamp64/www/service_management/`
- Linux: `/var/www/html/service_management/`

ตรวจสอบว่าเปิดใช้งาน `mod_rewrite` สำหรับ Apache

### 6. เข้าใช้งาน
เปิดเบราว์เซอร์ไปที่: `http://localhost/service_management/`

**ผู้ใช้เริ่มต้น:**
- Username: `admin`
- Password: `password`

---

## โครงสร้างโปรเจกต์
```
service_management/
├── .htaccess
├── README.md
├── sql/
│   └── schema.sql              ← SQL สร้างตาราง + user ตัวอย่าง
├── application/
│   ├── config/
│   │   ├── config.php          ← ตั้งค่าหลัก + CSRF
│   │   ├── database.php        ← การเชื่อมต่อ MySQL
│   │   ├── routes.php          ← กำหนด URL routes
│   │   └── autoload.php        ← โหลด library อัตโนมัติ
│   ├── controllers/
│   │   ├── Auth.php            ← Login / Register / Logout
│   │   ├── Service.php         ← หน้ารายการ + Import Excel
│   │   └── Api.php             ← REST API (GET/POST/PUT/DELETE)
│   ├── models/
│   │   ├── User_model.php
│   │   └── Service_model.php   ← DataTables server-side
│   └── views/
│       ├── auth/
│       │   ├── login.php
│       │   └── register.php
│       ├── service/
│       │   ├── index.php       ← ตาราง DataTable + Modal เพิ่ม/แก้ไข
│       │   └── import.php      ← Drag & Drop import Excel
│       └── templates/
│           ├── header.php
│           └── footer.php
└── assets/
    └── css/style.css
```

---

## REST API Endpoints

| Method | URL | คำอธิบาย |
|--------|-----|----------|
| GET | `/api/service` | ดึงรายการทั้งหมด |
| GET | `/api/service/{id}` | ดึงรายการตาม ID |
| POST | `/api/service` | เพิ่มรายการใหม่ |
| PUT | `/api/service/{id}` | แก้ไขรายการ |
| DELETE | `/api/service/{id}` | ลบรายการ |
| POST | `/api/service/datatable` | DataTables server-side |

**ตัวอย่าง POST body (JSON):**
```json
{
  "job_type": "ติดตั้ง",
  "bill_no": "BILL-2024-001",
  "customer_name": "นายสมชาย ใจดี",
  "purchase_date": "2024-01-15",
  "address": "123 ถ.สุขุมวิท กรุงเทพ",
  "location": "13.7563,100.5018",
  "install_date": "2024-01-20",
  "install_time": "09:00",
  "phone": "081-234-5678",
  "technician": "นายวิชัย",
  "status": "pending",
  "product_service": "เครื่องปรับอากาศ",
  "tags": "VIP,ด่วน"
}
```

---

## Format Excel สำหรับ Import

| คอลัมน์ | ฟิลด์ | หมายเหตุ |
|---------|-------|---------|
| A | ประเภทงาน | |
| B | เลขที่บิล | **จำเป็น** |
| C | ชื่อลูกค้า | |
| D | วันที่ซื้อ | YYYY-MM-DD |
| E | ที่อยู่ | |
| F | Location | lat,lng หรือ URL |
| G | วันที่นัดติดตั้ง | YYYY-MM-DD |
| H | เวลา | HH:MM |
| I | เบอร์โทร | |
| J | ช่างรับผิดชอบ | |
| K | หมายเหตุช่าง | |
| L | สถานะ | pending/confirmed/in_progress/completed/cancelled |
| M | ชื่อสินค้า/บริการ | |
| N | หมายเหตุบิล | |
| O | แท็ก | คั่นด้วยลูกน้ำ |

---

## Libraries ที่ใช้ (CDN - ไม่ต้องติดตั้ง)
- Bootstrap 5.3
- jQuery 3.7
- DataTables 1.13 + Bootstrap5 theme
- Bootstrap Icons 1.11
- SweetAlert2


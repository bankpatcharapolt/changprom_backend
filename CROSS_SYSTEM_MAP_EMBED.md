# Embed แผนที่ (อ่านอย่างเดียว) ในหน้า register-product ของ tgsmartlife.com

งานนี้แตะ 2 ระบบพร้อมกัน: **service_management** (ที่ /map อยู่) และ **tgsmartlife** (เว็บหลัก ที่
/register-product อยู่) — ต้อง deploy ทั้งคู่พร้อมกัน ไม่มี SQL migration ใหม่ทั้ง 2 ฝั่ง

## วิธีทำงานโดยสรุป

1. ลูกค้ากรอกเลขบิล/เบอร์/เลขบัตรที่ `/register-product` กด "ค้นหา"
2. `Main::get_register_product_results()` (tgsmartlife) หาเจอ → ออก **token** เซ็นชื่อ (HMAC-SHA256)
   ผูกกับเลขบิลของผลลัพธ์แรกเท่านั้น หมดอายุใน 6 วัน ส่งกลับมาพร้อมผลค้นหา (`map_token`)
3. หน้าเว็บฝัง `<iframe>` ชี้ไปที่ `{service_management_url}map?token=...`
4. `/map` (service_management) เห็น token → ข้ามการ login ไปเลย โชว์เฉพาะบิลนั้นบิลเดียว
   แบบ readonly (ซ่อนช่องค้นหา/ตัวกรอง/แถบสถานะทั้งหมด) จนกว่า token จะหมดอายุ
5. ปุ่ม "รายละเอียด" ในป็อปอัพประวัติการให้บริการ (เดิมลิงก์ไปหน้า admin ที่ login ไม่ได้อยู่แล้วสำหรับ
   ผู้เข้าชมทางนี้) เปลี่ยนเป็น "ข้อมูลรับประกันสินค้า" ดึงจาก `warawat121_tgsmartlife.product_regis`
   ข้ามฐานข้อมูลโดยตรง (DB connection group ใหม่ใน service_management)

## ขั้นตอน deploy (ทำพร้อมกันทั้ง 2 ระบบ)

### service_management
อัปโหลดไฟล์ทั้งหมดในนี้ทับของเดิม (ไม่มี SQL migration ใหม่)

### tgsmartlife
อัปโหลด 4 ไฟล์นี้ทับของเดิม:
- `application/libraries/Map_token.php` (ใหม่)
- `application/config/config.php` (เพิ่ม `map_token_secret` + `service_management_url`)
- `application/controllers/Main.php` (แก้ `get_register_product_results()` + `register_product()`)
- `application/views/desktop/register_product.php` (เพิ่ม iframe แผนที่)

**สำคัญมาก**: ค่า `$config['map_token_secret']` ในไฟล์ config.php ของทั้ง 2 ระบบ **ต้องเหมือนกันเป๊ะ
ทุกตัวอักษร** (ตอนนี้ตั้งไว้ให้ตรงกันแล้วในไฟล์ที่แนบมา) ถ้าใครไปแก้ค่านี้อีกฝั่งเดียวแล้วอีกฝั่งไม่แก้
ตาม token จะตรวจไม่ผ่านทันที

ถ้า production จริงมี URL ของ service_management ไม่ตรงกับ `https://tgsmartlife.com/service_management/`
ที่ตั้งไว้ใน `$config['service_management_url']` ต้องแก้ค่านี้ในไฟล์ config.php ของ tgsmartlife ให้ตรง

## จุดที่ทดสอบแล้วจริง (ไม่ได้เดา)

- **Token cross-system handoff**: จำลองการออก token จากสำเนาไฟล์ library ฝั่ง tgsmartlife (คนละ
  PHP process จริงๆ) แล้วเอาไปตรวจด้วยสำเนาไฟล์ library ฝั่ง service_management (อีก process หนึ่ง)
  ตรวจผ่านถูกต้อง ยืนยันว่า secret + algorithm ตรงกันจริง
- **Token security**: เทส 7 กรณี (round-trip ปกติ, แก้ payload, แก้ signature, secret ผิด, หมดอายุ,
  token มั่ว/ว่าง/null, บิลต่างกันแยกกันถูก) ผ่านหมด
- **SQL Injection ที่เจอใน `get_register_product_results()`**: เดิม query เอา input ลูกค้ามาต่อ
  string ตรงๆ — ทดสอบแล้วว่า inject ข้อมูลจากตารางอื่นออกมาได้จริง (leak username/password จำลอง)
  หลังแก้เป็น query builder แล้ว payload เดียวกันไม่หลุดข้อมูลอะไรออกมาอีก ส่วนการค้นหาปกติ (เลขบิลจริง)
  ยังได้ผลถูกต้องเหมือนเดิม

## แก้เพิ่ม: บิลมีในระบบแต่แผนที่ไม่ขึ้น (bill_no มีต่อท้าย _1, _2)

**อาการ**: ค้นบิลที่ `/register-product` แล้วเจอข้อมูลสินค้า (product_regis มีจริง) แต่แผนที่ที่ embed มา
ไม่ขึ้นหมุดเลย ทั้งที่ค้นบิลเดียวกันจากหน้า admin (`/customer_map`) เจอหมุดปกติ

**สาเหตุ**: `service_jobs.bill_no` มักเก็บแบบมีต่อท้าย เช่น `RT-2606082_1` (ต่อท้ายด้วย _1, _2 ฯลฯ
ถ้ามีหลายงานต่อบิลเดียว) ในขณะที่ `product_regis.bill_number` (ฝั่ง tgsmartlife) เก็บเป็นเลขบิล
เปล่าๆ ไม่มีต่อท้าย — token ที่ออกจากฝั่ง tgsmartlife จึงเก็บเลขบิลแบบไม่มีต่อท้าย แต่โค้ดเดิมของ
`api_markers()`/`api_history()` เทียบแบบตรงเป๊ะ (`=`) จึงหาไม่เจอทุกกรณีที่ bill_no มีต่อท้าย

**วิธีแก้**: เปลี่ยนเป็นเทียบแบบ "ตรงเป๊ะ หรือ ตรงเป๊ะ+ตามด้วย _" (`bill_no = ? OR bill_no LIKE ?`)
แทนตรงเป๊ะอย่างเดียว — ทดสอบแล้วด้วยข้อมูลจำลอง 4 แบบ (มีต่อท้าย _1 / ตรงเป๊ะไม่มีต่อท้าย / บิลอื่นที่
บังเอิญขึ้นต้นเลขเดียวกัน / บิลไม่เกี่ยวข้องเลย) ผลออกมาถูกต้องครบทั้ง 4 กรณี — เจอเฉพาะ 2 กรณีแรก
ไม่หลุดไปจับบิลอื่นที่ขึ้นต้นเลขเดียวกันโดยบังเอิญ

## สิ่งที่ควรทดสอบเพิ่มเติมก่อนใช้งานจริง (ต้องใช้ browser จริง ผมจำลองในนี้ไม่ได้)

1. เปิด `/register-product` กรอกเลขบิลจริงที่มีพิกัดบนแผนที่ กด "ค้นหา" → ต้องเห็น iframe แผนที่ขึ้นใต้
   แถวปุ่มค้นหา แสดงหมุดเดียว เปิดกล่องข้อมูลให้อัตโนมัติ
2. ในแผนที่ที่ embed มา: ต้องไม่มีช่องค้นหา/ตัวกรอง/แถบสถานะให้เห็นเลย, ปุ่ม "ดูรายละเอียด" ต้องไม่โชว์
   (ลิงก์เดิมใช้ไม่ได้กับโหมดนี้)
3. กด "ประวัติการให้บริการ" → กด "ข้อมูลรับประกันสินค้า" ที่แถวประวัติ → ต้องเห็นข้อมูลจาก
   `product_regis` (ถ้าบิลนั้นไม่มีข้อมูลลงทะเบียนรับประกัน จะขึ้นข้อความแจ้งแทน ไม่ error)
4. ลองแก้ token ในแถบ URL เอง (พิมพ์มั่ว) → ต้องขึ้น "ลิงก์หมดอายุหรือไม่ถูกต้อง" ไม่ redirect ไปหน้า login
5. Login แบบ superadmin/admin/staff ปกติเข้า `/map` หรือ `/customer_map` → ต้องใช้งานได้ปกติเหมือนเดิม
   ทุกอย่าง ไม่ถูกกระทบจากการเปลี่ยนแปลงนี้เลย

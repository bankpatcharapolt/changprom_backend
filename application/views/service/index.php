<?php
$statusList = ['รอดำเนินการ','ยืนยันแล้ว','กำลังดำเนินงาน','เสร็จแล้ว','เลื่อนนัด','ยกเลิกนัด'];
$jobTypes   = ['ติดตั้ง','ซ่อม','ล้างเครื่อง','เปลี่ยนไส้กรอง','ส่งสินค้า','นำสินค้ากลับ'];
?>
<style>
.table th { font-size:.8rem; white-space:nowrap; }
.table td { font-size:.83rem; }
.badge { font-size:.71rem; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="fw-bold mb-1"><i class="bi bi-list-check me-2 text-primary"></i>รายละเอียดงานบริการ</h4>
    <p class="text-muted small mb-0">ค้นหา เพิ่ม แก้ไข จัดการงานทั้งหมด</p>
  </div>
  <button class="btn btn-primary" id="btn-add-new">
    <i class="bi bi-plus-lg me-2"></i>เพิ่มรายการ
  </button>
</div>

<!-- Stats -->
<div class="row g-2 mb-3">
  <?php foreach([
    ['today','primary','bi-calendar-day','วันนี้'],
    ['open','danger','bi-hourglass-split','ยังไม่ปิด'],
    ['pending','warning','bi-clock','รอดำเนินการ'],
    ['done','success','bi-check-circle','เสร็จแล้ว']
  ] as $s): ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 bg-<?= $s[1] ?> bg-opacity-10 text-center py-2">
      <i class="bi <?= $s[2] ?> text-<?= $s[1] ?>"></i>
      <div class="fw-bold fs-5 text-<?= $s[1] ?>" id="stat-<?= $s[0] ?>">-</div>
      <div class="small text-muted"><?= $s[3] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Table -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="serviceTable" class="table table-hover mb-0 align-middle" style="width:100%">
        <thead class="table-light">
          <tr>
            <th>ประเภท</th><th>เลขที่บิล</th><th>ชื่อลูกค้า</th>
            <th>วันที่ซื้อ</th><th>วันที่นัด</th><th>เวลา</th>
            <th>เบอร์โทร</th><th>ช่าง</th><th>สถานะ</th>
            <th>สินค้า/บริการ</th><th>แท็ก</th>
            <th class="text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="modalTitle">
          <i class="bi bi-plus-circle me-2"></i>เพิ่มรายการงาน
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="record_id">

        <div id="conflict-alert" class="alert alert-warning d-none">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <strong>ชนเวลา!</strong> <span id="conflict-msg"></span>
          <br>
          <button class="btn btn-sm btn-warning mt-2" id="btn-force-save">
            <i class="bi bi-check me-1"></i>บันทึกต่อ (ยืนยัน)
          </button>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-medium">ประเภทงาน <span class="text-danger">*</span></label>
            <select id="f_job_type" class="form-select">
              <option value="">-- เลือกประเภทงาน --</option>
              <?php foreach($jobTypes as $t): ?><option><?= $t ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-medium">เลขที่บิล <span class="text-danger">*</span></label>
            <input type="text" id="f_bill_no" class="form-control" placeholder="เลขที่บิล (จำเป็น)">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-medium">ชื่อลูกค้า</label>
            <input type="text" id="f_customer_name" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-medium">วันที่ซื้อ</label>
            <input type="date" id="f_purchase_date" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-medium">เบอร์โทร</label>
            <input type="text" id="f_phone" class="form-control" placeholder="0xx-xxx-xxxx">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">ชื่อสินค้า/บริการ</label>
            <input type="text" id="f_product_service" class="form-control">
          </div>
          <div class="col-md-12">
            <label class="form-label fw-medium">ที่อยู่</label>
            <input type="text" id="f_address" class="form-control">
          </div>
         <div class="input-group">
  <input type="text" class="form-control" id="f_location" placeholder="13.7815445,100.6165399">
  <button class="btn btn-outline-secondary" type="button" onclick="previewLocation()">
    <i class="bi bi-geo-alt"></i> ดู Maps
  </button>
</div>
          <div class="col-md-3">
            <label class="form-label fw-medium">วันที่นัดติดตั้ง</label>
            <input type="date" id="f_install_date" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-medium">เวลา</label>
            <input type="time" id="f_install_time" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-medium">ช่างรับผิดชอบ</label>
            <input type="text" id="f_technician" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-medium">สถานะ <span class="text-danger">*</span></label>
            <select id="f_status" class="form-select">
              <?php foreach($statusList as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-medium">รหัสพนักงานขาย</label>
            <input type="text" id="f_sale_code" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-medium">ทีม</label>
            <input type="text" id="f_team" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-medium">สาขา</label>
            <input type="text" id="f_branch" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">หมายเหตุช่าง</label>
            <textarea id="f_tech_note" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">หมายเหตุบิล</label>
            <textarea id="f_bill_note" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-md-12">
            <label class="form-label fw-medium">แท็ก</label>
            <input type="text" id="f_tags" class="form-control" placeholder="TG01,VIP,ด่วน">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button class="btn btn-primary px-4" id="saveBtn">
          <i class="bi bi-save me-2"></i>บันทึก
        </button>
      </div>
    </div>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-info-circle me-2"></i>รายละเอียด
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewBody"></div>
    </div>
  </div>
</div>

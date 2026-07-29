<style>
.emp-stat-card { border-radius:14px; border:none; transition:.2s; }
.emp-stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.1)!important; }
.table th { font-size:.8rem; white-space:nowrap; }
.table td { font-size:.83rem; }
.badge { font-size:.71rem; }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="fw-bold mb-1"><i class="bi bi-person-badge-fill me-2 text-primary"></i>จัดการพนักงาน</h4>
    <p class="text-muted small mb-0">ข้อมูลพนักงานทั่วไป และสิทธิ์การเข้าดูแผนที่ลูกค้า</p>
  </div>
  <button class="btn btn-primary" id="btn-add-emp">
    <i class="bi bi-plus-lg me-2"></i>เพิ่มพนักงานใหม่
  </button>
</div>

<!-- Stats -->
<div class="row g-2 mb-3">
  <div class="col-6 col-md-3">
    <div class="card emp-stat-card border-0 bg-primary bg-opacity-10 text-center py-2">
      <i class="bi bi-people text-primary"></i>
      <div class="fw-bold fs-5 text-primary" id="stat-emp-total">-</div>
      <div class="small text-muted">พนักงานทั้งหมด</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card emp-stat-card border-0 bg-success bg-opacity-10 text-center py-2">
      <i class="bi bi-person-check text-success"></i>
      <div class="fw-bold fs-5 text-success" id="stat-emp-active">-</div>
      <div class="small text-muted">ใช้งาน</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card emp-stat-card border-0 bg-info bg-opacity-10 text-center py-2">
      <i class="bi bi-pin-map text-info"></i>
      <div class="fw-bold fs-5 text-info" id="stat-emp-map-access">-</div>
      <div class="small text-muted">มีสิทธิ์ดูแผนที่</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card emp-stat-card border-0 bg-warning bg-opacity-10 text-center py-2">
      <i class="bi bi-hourglass-split text-warning"></i>
      <div class="fw-bold fs-5 text-warning" id="stat-emp-pending">-</div>
      <div class="small text-muted">รอการอนุมัติ</div>
    </div>
  </div>
</div>

<!-- DataTable: รายชื่อพนักงาน -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="employeeTable" class="table table-hover mb-0 align-middle" style="width:100%">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>ชื่อ-สกุล</th>
            <th>Username</th>
            <th>อีเมล</th>
            <th>เบอร์โทร</th>
            <th>ตำแหน่ง</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center">สิทธิ์ดูแผนที่</th>
            <th class="text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<?php if (!empty($is_superadmin)): ?>
<!-- คำขออนุมัติสิทธิ์ — เห็นเฉพาะ superadmin -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h5 class="fw-bold mb-0"><i class="bi bi-inbox-fill me-2 text-warning"></i>คำขอสิทธิ์ดูแผนที่ลูกค้า</h5>
    <div class="btn-group btn-group-sm" role="group" id="reqFilterGroup">
      <button type="button" class="btn btn-outline-secondary active" data-status="pending">รอดำเนินการ</button>
      <button type="button" class="btn btn-outline-secondary" data-status="">ทั้งหมด</button>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="requestTable" class="table table-hover mb-0 align-middle" style="width:100%">
        <thead class="table-light">
          <tr>
            <th>พนักงาน</th>
            <th>ผู้ร้องขอ</th>
            <th>วันที่ขอ</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ==================== Modal: Add/Edit ==================== -->
<div class="modal fade" id="empModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="empModalTitle">
          <i class="bi bi-plus-circle me-2"></i>เพิ่มพนักงานใหม่
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="emp_id">
        <div class="row g-3">

          <!-- ชื่อ / สกุล -->
          <div class="col-md-6">
            <label class="form-label fw-medium">ชื่อ <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="ef_first_name" placeholder="ชื่อจริง" maxlength="100">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">นามสกุล <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="ef_last_name" placeholder="นามสกุล" maxlength="100">
          </div>

          <!-- เบอร์โทร / อีเมล -->
          <div class="col-md-6">
            <label class="form-label fw-medium">เบอร์โทร <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="ef_phone" placeholder="08x-xxx-xxxx" maxlength="30">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">อีเมล <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="ef_email" placeholder="example@email.com" maxlength="150">
          </div>

          <!-- ตำแหน่ง / สถานะ -->
          <div class="col-md-8">
            <label class="form-label fw-medium">ตำแหน่ง / หมายเหตุ</label>
            <input type="text" class="form-control" id="ef_position" placeholder="เช่น พนักงานคลังสินค้า" maxlength="150">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-medium">สถานะ</label>
            <select class="form-select" id="ef_active">
              <option value="1">ใช้งาน</option>
              <option value="0">ไม่ใช้งาน</option>
            </select>
          </div>

          <!-- ── Login Account ── -->
          <div class="col-12">
            <hr class="my-1">
            <label class="form-label fw-medium">
              <i class="bi bi-person-lock me-1 text-primary"></i>บัญชีเข้าสู่ระบบ
            </label>
            <div class="p-3 bg-light rounded-3 border">
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small text-muted mb-1">Username <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="ef_username" placeholder="ชื่อผู้ใช้ (a-z, 0-9, . _ -)" maxlength="100" autocomplete="off">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-muted mb-1">
                    Password <span id="ef_password_req" class="text-danger">*</span>
                    <span id="ef_password_hint_label" class="text-muted"></span>
                  </label>
                  <input type="text" class="form-control" id="ef_password" placeholder="อย่างน้อย 6 ตัวอักษร" maxlength="255" autocomplete="new-password">
                </div>
              </div>
            </div>
          </div>

        </div><!-- /row -->
      </div><!-- /modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i>ยกเลิก
        </button>
        <button type="button" class="btn btn-primary px-4" id="empSaveBtn">
          <i class="bi bi-save me-2"></i>บันทึก
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ==================== Modal: View ==================== -->
<div class="modal fade" id="empViewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-person-badge me-2"></i>ข้อมูลพนักงาน
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="empViewBody">
        <!-- inject by employee.js -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

<script>
var IS_SUPERADMIN = <?= !empty($is_superadmin) ? 'true' : 'false' ?>;
</script>

<style>
.vehicle-stat-card { border-radius:14px; border:none; transition:.2s; }
.vehicle-stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.1)!important; }
.table th { font-size:.8rem; white-space:nowrap; }
.table td { font-size:.83rem; }
.badge { font-size:.71rem; }
.plate-badge {
  font-family: 'Courier New', monospace;
  font-weight: 700;
  font-size: .9rem;
  background: #fff;
  color: #1a237e;
  border: 2px solid #1a237e;
  border-radius: 6px;
  padding: 2px 10px;
  letter-spacing: 2px;
}
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="fw-bold mb-1"><i class="bi bi-car-front-fill me-2 text-primary"></i>จัดการยานพาหนะ</h4>
    <p class="text-muted small mb-0">ข้อมูลยานพาหนะทั้งหมดในระบบ</p>
  </div>
  <button class="btn btn-primary" id="btn-add-vehicle">
    <i class="bi bi-plus-lg me-2"></i>เพิ่มยานพาหนะ
  </button>
</div>

<!-- Stats -->
<div class="row g-2 mb-3" id="vehicle-stats">
  <div class="col-6 col-md-3">
    <div class="card vehicle-stat-card border-0 bg-primary bg-opacity-10 text-center py-2">
      <i class="bi bi-car-front text-primary"></i>
      <div class="fw-bold fs-5 text-primary" id="stat-total">-</div>
      <div class="small text-muted">ทั้งหมด</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card vehicle-stat-card border-0 bg-success bg-opacity-10 text-center py-2">
      <i class="bi bi-check-circle text-success"></i>
      <div class="fw-bold fs-5 text-success" id="stat-active">-</div>
      <div class="small text-muted">ใช้งาน</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card vehicle-stat-card border-0 bg-warning bg-opacity-10 text-center py-2">
      <i class="bi bi-car-front text-warning"></i>
      <div class="fw-bold fs-5 text-warning" id="stat-car">-</div>
      <div class="small text-muted">รถยนต์</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card vehicle-stat-card border-0 bg-info bg-opacity-10 text-center py-2">
      <i class="bi bi-bicycle text-info"></i>
      <div class="fw-bold fs-5 text-info" id="stat-moto">-</div>
      <div class="small text-muted">รถจักรยานยนต์</div>
    </div>
  </div>
</div>

<!-- DataTable -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="vehicleTable" class="table table-hover mb-0 align-middle" style="width:100%">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>ประเภท</th>
            <th>ป้ายทะเบียน</th>
            <th>จังหวัด</th>
            <th>ยี่ห้อ/รุ่น</th>
            <th>สี</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- ==================== Modal: Add/Edit ==================== -->
<div class="modal fade" id="vehicleModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="vehicleModalTitle">
          <i class="bi bi-plus-circle me-2"></i>เพิ่มยานพาหนะ
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="vehicle_id">
        <div class="row g-3">

          <!-- ประเภทยานพาหนะ -->
          <div class="col-md-6">
            <label class="form-label fw-medium">ประเภทยานพาหนะ <span class="text-danger">*</span></label>
            <select id="vf_type" class="form-select">
              <option value="">-- เลือกประเภท --</option>
              <option value="รถยนต์">🚗 รถยนต์</option>
              <option value="รถจักรยานยนต์">🏍️ รถจักรยานยนต์</option>
              <option value="รถกระบะ">🛻 รถกระบะ</option>
              <option value="รถตู้">🚐 รถตู้</option>
              <option value="อื่นๆ">🚗 อื่นๆ</option>
            </select>
          </div>

          <!-- สถานะ -->
          <div class="col-md-6">
            <label class="form-label fw-medium">สถานะ</label>
            <select id="vf_active" class="form-select">
              <option value="1">✅ ใช้งาน</option>
              <option value="0">❌ ไม่ใช้งาน</option>
            </select>
          </div>

          <!-- ป้ายทะเบียน -->
          <div class="col-md-6">
            <label class="form-label fw-medium">ป้ายทะเบียน <span class="text-danger">*</span></label>
            <input type="text" id="vf_plate" class="form-control text-uppercase"
                   placeholder="เช่น กข 1234" autocomplete="off"
                   style="font-family:monospace;font-weight:700;font-size:1.1rem;letter-spacing:2px">
          </div>

          <!-- จังหวัด -->
          <div class="col-md-6">
            <label class="form-label fw-medium">จังหวัด</label>
            <input type="text" id="vf_province" class="form-control" placeholder="เช่น กรุงเทพมหานคร">
          </div>

          <!-- ยี่ห้อ -->
          <div class="col-md-6">
            <label class="form-label fw-medium">ยี่ห้อ</label>
            <input type="text" id="vf_brand" class="form-control" placeholder="เช่น Toyota, Honda">
          </div>

          <!-- รุ่น -->
          <div class="col-md-6">
            <label class="form-label fw-medium">รุ่น</label>
            <input type="text" id="vf_model" class="form-control" placeholder="เช่น Vios, Wave 125">
          </div>

          <!-- สี -->
          <div class="col-md-6">
            <label class="form-label fw-medium">สี</label>
            <input type="text" id="vf_color" class="form-control" placeholder="เช่น ขาว, ดำ, แดง">
          </div>

          <!-- หมายเหตุ -->
          <div class="col-12">
            <label class="form-label fw-medium">หมายเหตุ</label>
            <textarea id="vf_note" class="form-control" rows="2" placeholder="หมายเหตุเพิ่มเติม..."></textarea>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i>ยกเลิก
        </button>
        <button class="btn btn-primary" id="vehicleSaveBtn">
          <i class="bi bi-save me-1"></i>บันทึก
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ==================== Modal: Delete confirm ==================== -->
<div class="modal fade" id="vehicleDeleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>ยืนยันการลบ</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-1">ลบยานพาหนะ</p>
        <div class="plate-badge my-2" id="delete-plate-display">-</div>
        <p class="text-muted small mb-0">ข้อมูลจะถูกลบถาวร ไม่สามารถกู้คืนได้</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
        <button class="btn btn-danger btn-sm" id="confirmDeleteBtn">
          <i class="bi bi-trash me-1"></i>ลบเลย
        </button>
      </div>
    </div>
  </div>
</div>


<style>
.tech-stat-card { border-radius:14px; border:none; transition:.2s; }
.tech-stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.1)!important; }
.table th { font-size:.8rem; white-space:nowrap; }
.table td { font-size:.83rem; }
.badge { font-size:.71rem; }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="fw-bold mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>จัดการข้อมูลช่าง</h4>
    <p class="text-muted small mb-0">ข้อมูลช่างทั้งหมดในระบบ</p>
  </div>
  <button class="btn btn-primary" id="btn-add-tech">
    <i class="bi bi-plus-lg me-2"></i>เพิ่มช่างใหม่
  </button>
</div>

<!-- Stats -->
<div class="row g-2 mb-3">
  <div class="col-6 col-md-3">
    <div class="card tech-stat-card border-0 bg-primary bg-opacity-10 text-center py-2">
      <i class="bi bi-people text-primary"></i>
      <div class="fw-bold fs-5 text-primary" id="stat-tech-total">-</div>
      <div class="small text-muted">ช่างทั้งหมด</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card tech-stat-card border-0 bg-success bg-opacity-10 text-center py-2">
      <i class="bi bi-person-check text-success"></i>
      <div class="fw-bold fs-5 text-success" id="stat-tech-active">-</div>
      <div class="small text-muted">ใช้งาน</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card tech-stat-card border-0 bg-secondary bg-opacity-10 text-center py-2">
      <i class="bi bi-person-dash text-secondary"></i>
      <div class="fw-bold fs-5 text-secondary" id="stat-tech-inactive">-</div>
      <div class="small text-muted">ไม่ใช้งาน</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card tech-stat-card border-0 bg-info bg-opacity-10 text-center py-2">
      <i class="bi bi-geo-alt text-info"></i>
      <div class="fw-bold fs-5 text-info" id="stat-tech-location">-</div>
      <div class="small text-muted">มี Location</div>
    </div>
  </div>
</div>

<!-- DataTable -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="technicianTable" class="table table-hover mb-0 align-middle" style="width:100%">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>ชื่อช่าง</th>
            <th>เบอร์โทร</th>
            <th>ที่อยู่</th>
            <th class="text-center">Location</th>
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
<div class="modal fade" id="techModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="techModalTitle">
          <i class="bi bi-plus-circle me-2"></i>เพิ่มช่างใหม่
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="tech_id">
        <div class="row g-3">

          <!-- ชื่อช่าง -->
          <div class="col-md-8">
            <label class="form-label fw-medium">ชื่อช่าง <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="tf_reg_name" placeholder="ชื่อ-นามสกุล">
          </div>

          <!-- สถานะ -->
          <div class="col-md-4">
            <label class="form-label fw-medium">สถานะ</label>
            <select class="form-select" id="tf_active">
              <option value="1">ใช้งาน</option>
              <option value="0">ไม่ใช้งาน</option>
            </select>
          </div>

          <!-- เบอร์โทร -->
          <div class="col-md-6">
            <label class="form-label fw-medium">เบอร์โทร</label>
            <input type="text" class="form-control" id="tf_reg_telephone" placeholder="08x-xxx-xxxx">
          </div>

          <!-- อีเมล -->
          <div class="col-md-6">
            <label class="form-label fw-medium">อีเมล</label>
            <input type="email" class="form-control" id="tf_reg_email" placeholder="example@email.com">
          </div>

          <!-- ที่อยู่ -->
          <div class="col-12">
            <label class="form-label fw-medium">ที่อยู่</label>
            <textarea class="form-control" id="tf_reg_address" rows="2" placeholder="บ้านเลขที่ ถนน ตำบล อำเภอ จังหวัด"></textarea>
          </div>

          <!-- ── Login Account ── -->
          <div class="col-12">
            <hr class="my-1">
            <label class="form-label fw-medium">
              <i class="bi bi-person-lock me-1 text-primary"></i>บัญชีเข้าสู่ระบบ (สำหรับช่าง)
            </label>
            <div class="p-3 bg-light rounded-3 border">
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small text-muted mb-1">Username</label>
                  <input type="text" class="form-control" id="tf_username" placeholder="ชื่อผู้ใช้" autocomplete="off">
                  <div class="form-text" id="tf_username_hint">เว้นว่างไว้ถ้าไม่ต้องการตั้ง</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-muted mb-1">
                    Password <span id="tf_password_hint_label" class="text-muted">(กรณีอยู่ในหน้าแก้ไขเว้นว่าง = ไม่เปลี่ยน)</span>
                  </label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="tf_password" placeholder="รหัสผ่าน" autocomplete="new-password">
                    <!-- <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                      <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button> -->
                  </div>
                </div>
                <div class="col-12" id="tf_account_status_row" style="display:none">
                  <div class="alert alert-info py-1 px-2 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    มีบัญชีอยู่แล้ว: <strong id="tf_account_username_display"></strong>
                    — เว้นว่าง Password ไว้เพื่อคงรหัสเดิม
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Location GPS -->
          <div class="col-12">
            <label class="form-label fw-medium">
              <i class="bi bi-geo-alt me-1 text-primary"></i>Location (GPS)
            </label>
            <div class="p-3 bg-light rounded-3 border">
              <div class="row g-2">
                <div class="col-md-5">
                  <label class="form-label small text-muted mb-1">Latitude</label>
                  <input type="text" class="form-control" id="tf_latitude"
                    placeholder="13.7815445  หรือ paste lat,lng ที่นี่"
                    title="Paste ค่า lat,lng แบบ 13.xxx,100.xxx แล้วระบบจะแยกให้อัตโนมัติ">
                  <div class="form-text">Paste <code>lat,lng</code> แล้วคลิกออกเพื่อแยกอัตโนมัติ</div>
                </div>
                <div class="col-md-5">
                  <label class="form-label small text-muted mb-1">Longitude</label>
                  <input type="text" class="form-control" id="tf_longitude" placeholder="100.6165399">
                </div>
                <!-- <div class="col-md-2 d-flex align-items-end">
                  <button type="button" class="btn btn-outline-primary w-100" onclick="previewTechLocation()" title="เปิด Google Maps">
                    <i class="bi bi-map"></i><span class="d-none d-md-inline ms-1">ดู Maps</span>
                  </button>
                </div> -->
              </div>
            </div>
          </div>

        </div><!-- /row -->
      </div><!-- /modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i>ยกเลิก
        </button>
        <button type="button" class="btn btn-primary px-4" id="techSaveBtn">
          <i class="bi bi-save me-2"></i>บันทึก
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ==================== Modal: View ==================== -->
<div class="modal fade" id="techViewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-person-badge me-2"></i>ข้อมูลช่าง
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="techViewBody">
        <!-- inject by technician.js -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

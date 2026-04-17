<div class="row justify-content-center">
<div class="col-lg-9">
  <div class="d-flex align-items-center mb-4">
    <a href="<?= site_url('service') ?>" class="btn btn-outline-secondary btn-sm me-3">
      <i class="bi bi-arrow-left me-1"></i>กลับ
    </a>
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-excel me-2 text-success"></i>นำเข้าข้อมูลจาก Excel</h4>
      <p class="text-muted small mb-0">รองรับไฟล์ใบเสร็จรับเงินจากระบบ (receipt_report_export...)</p>
    </div>
  </div>

  <!-- info card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-medium">
      <i class="bi bi-info-circle me-2 text-primary"></i>รูปแบบไฟล์ที่รองรับ — ใบเสร็จรับเงิน
    </div>
    <div class="card-body">
      <div class="row g-2 small">
        <div class="col-md-6">
          <div class="fw-medium text-primary mb-1">ข้อมูลที่ดึงจากไฟล์อัตโนมัติ</div>
          <ul class="mb-0 ps-3">
            <li>เลขที่บิล (คอลัมน์ B)</li>
            <li>ชื่อลูกค้า (คอลัมน์ I)</li>
            <li>วันที่ออก (คอลัมน์ C) → วันที่ซื้อ</li>
            <li>ชื่อสินค้า/บริการ (คอลัมน์ N)</li>
            <li>ยอดรวม (คอลัมน์ AE) และแท็ก (คอลัมน์ AJ)</li>
          </ul>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-success mb-1">ดึงจากช่อง "หมายเหตุ" อัตโนมัติ</div>
          <ul class="mb-0 ps-3">
            <li>เบอร์โทรพนักงาน / รหัสพนักงานขาย</li>
            <li>ทีม / สาขา</li>
          </ul>
          <div class="mt-2 text-warning fw-medium small">
            <i class="bi bi-exclamation-triangle me-1"></i>ต้องกรอก ช่าง / วันนัด / ประเภทงาน ภายหลัง
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- rules card -->
  <div class="card border-warning border-2 mb-4">
    <div class="card-header bg-warning bg-opacity-10 fw-medium text-warning">
      <i class="bi bi-shield-check me-2"></i>กฎการนำเข้า
    </div>
    <div class="card-body small">
      <div class="row g-2">
        <div class="col-md-6">
          <div class="d-flex gap-2"><i class="bi bi-check-circle text-success mt-1"></i>
            <span><strong>ทุกบิลต้องมีเลขที่บิล</strong> — แถวที่ไม่มีจะถูกข้าม</span></div>
          <div class="d-flex gap-2 mt-1"><i class="bi bi-check-circle text-success mt-1"></i>
            <span>บิลซ้ำ = <strong>อัปเดต</strong> ข้อมูลที่มีอยู่</span></div>
          <div class="d-flex gap-2 mt-1"><i class="bi bi-check-circle text-success mt-1"></i>
            <span>สินค้าหลายรายการในบิลเดียว = รวมเป็น 1 งาน</span></div>
        </div>
        <div class="col-md-6">
          <div class="d-flex gap-2"><i class="bi bi-arrow-right-circle text-primary mt-1"></i>
            <span>สถานะเริ่มต้น: <span class="badge bg-warning text-dark">รอดำเนินการ</span></span></div>
          <div class="d-flex gap-2 mt-1"><i class="bi bi-arrow-right-circle text-primary mt-1"></i>
            <span>ประเภทงานเริ่มต้น: <span class="badge bg-secondary">ติดตั้ง</span> (แก้ได้ทีหลัง)</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- upload card -->
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-medium">
      <i class="bi bi-upload me-2 text-primary"></i>อัปโหลดไฟล์
    </div>
    <div class="card-body">
      <div id="drop-zone" class="border border-2 rounded-3 p-5 text-center mb-3"
        style="cursor:pointer;border-style:dashed!important;border-color:#ccc!important;">
        <i class="bi bi-cloud-upload fs-1 text-primary d-block mb-2"></i>
        <div class="fw-medium mb-1">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
        <div class="text-muted small">receipt_report_export_*.xlsx (.xlsx, .xls, .csv — ไม่เกิน 10MB)</div>
        <input type="file" id="excel_file" accept=".xlsx,.xls,.csv" class="d-none">
      </div>

      <div id="file-preview" class="d-none alert alert-info d-flex align-items-center justify-content-between mb-3">
        <span><i class="bi bi-file-earmark-excel me-2 text-success"></i><span id="file-name">-</span></span>
        <button class="btn btn-sm btn-outline-secondary" id="clear-btn"><i class="bi bi-x"></i></button>
      </div>

      <div class="d-grid">
        <button class="btn btn-success btn-lg" id="import-btn" disabled>
          <i class="bi bi-cloud-upload me-2"></i>นำเข้าข้อมูล
        </button>
      </div>

      <div id="progress-box" class="mt-3 d-none">
        <div class="progress mb-2" style="height:8px">
          <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
        </div>
        <div class="text-center text-muted small">กำลังประมวลผล กรุณารอสักครู่...</div>
      </div>

      <div id="result-box" class="mt-3 d-none"></div>
    </div>
  </div>
</div>
</div>

<!-- ======= Result Modal ======= -->
<div class="modal fade" id="importResultModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="importResultTitle"></h5>
      </div>
      <div class="modal-body pt-3" id="importResultBody"></div>
    </div>
  </div>
</div>

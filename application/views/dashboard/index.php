<style>
.stat-card { border-radius:14px; border:none; transition:.2s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.1)!important; }
.tech-bar { height:8px; border-radius:4px; }
.badge-status { font-size:.72rem; padding:3px 8px; border-radius:6px; }
.upcoming-item { border-left:3px solid #dee2e6; padding-left:12px; margin-bottom:12px; }
.upcoming-item.pending  { border-color:#f59e0b; }
.upcoming-item.confirmed{ border-color:#3b82f6; }
.upcoming-item.in_progress{ border-color:#8b5cf6; }
.upcoming-item.completed{ border-color:#10b981; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard คุมงาน</h4>
    <p class="text-muted small mb-0" id="last-updated">กำลังโหลด...</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url('dashboard/calendar') ?>" class="btn btn-outline-primary">
      <i class="bi bi-calendar3 me-2"></i>ตารางคิวช่าง
    </a>
    <button class="btn btn-outline-secondary btn-sm" onclick="loadStats()">
      <i class="bi bi-arrow-clockwise me-1"></i>รีเฟรช
    </button>
  </div>
</div>

<!-- KPI Cards Row 1 -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card shadow-sm h-100" style="background:linear-gradient(135deg,#667eea,#764ba2)">
      <div class="card-body text-white">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75">งานวันนี้ทั้งหมด</div>
            <div class="display-6 fw-bold" id="kpi-today">-</div>
          </div>
          <i class="bi bi-calendar-day fs-2 opacity-50"></i>
        </div>
        <div class="small opacity-75 mt-1">วันที่ <span id="kpi-date">-</span></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card shadow-sm h-100" style="background:linear-gradient(135deg,#f093fb,#f5576c)">
      <div class="card-body text-white">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75">งานยังไม่ปิด</div>
            <div class="display-6 fw-bold" id="kpi-open">-</div>
          </div>
          <i class="bi bi-hourglass-split fs-2 opacity-50"></i>
        </div>
        <div class="small opacity-75 mt-1">รอดำเนินการทั้งระบบ</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card shadow-sm h-100" style="background:linear-gradient(135deg,#4facfe,#00f2fe)">
      <div class="card-body text-white">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75">งานสัปดาห์นี้</div>
            <div class="display-6 fw-bold" id="kpi-week">-</div>
          </div>
          <i class="bi bi-calendar-week fs-2 opacity-50"></i>
        </div>
        <div class="small opacity-75 mt-1">จันทร์ - อาทิตย์</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card shadow-sm h-100" style="background:linear-gradient(135deg,#fa709a,#fee140)">
      <div class="card-body text-white">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75">งานเกินกำหนด</div>
            <div class="display-6 fw-bold" id="kpi-overdue">-</div>
          </div>
          <i class="bi bi-exclamation-triangle fs-2 opacity-50"></i>
        </div>
        <div class="small opacity-75 mt-1">ยังไม่ปิดงาน</div>
      </div>
    </div>
  </div>
</div>

<!-- Status breakdown today -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white fw-medium border-0 pb-0">
        <i class="bi bi-pie-chart me-2 text-primary"></i>สถานะงานวันนี้
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:#fff8e7">
          <span><span class="badge me-2" style="background:#f59e0b">●</span>รอดำเนินการ</span>
          <span class="fw-bold" id="s-pending">-</span>
        </div>
        <!-- <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:#eff6ff">
          <span><span class="badge me-2" style="background:#3b82f6">●</span>ยืนยันแล้ว</span>
          <span class="fw-bold" id="s-confirmed">-</span>
        </div> -->
        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:#f5f3ff">
          <span><span class="badge me-2" style="background:#8b5cf6">●</span>กำลังดำเนินการ</span>
          <span class="fw-bold" id="s-inprog">-</span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#f0fdf4">
          <span><span class="badge me-2" style="background:#10b981">●</span>เสร็จสิ้น</span>
          <span class="fw-bold" id="s-done">-</span>
        </div>
        <div class="mt-3">
          <div class="d-flex mb-1 justify-content-between small text-muted">
            <span>ความคืบหน้าวันนี้</span>
            <span id="s-pct">0%</span>
          </div>
          <div class="progress" style="height:10px">
            <div class="progress-bar bg-success" id="s-bar" style="width:0%"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white fw-medium border-0 pb-0 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person-gear me-2 text-warning"></i>โหลดงานช่างวันนี้</span>
        <span class="small text-muted" id="tech-date-label"></span>
      </div>
      <div class="card-body" id="tech-load-body">
        <div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>กำลังโหลด...</div>
      </div>
    </div>
  </div>
</div>

<!-- Chart + Upcoming -->
<div class="row g-3 mb-4">
  <div class="col-md-7">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white fw-medium border-0 pb-0">
        <i class="bi bi-bar-chart me-2 text-info"></i>งาน 6 เดือนย้อนหลัง
      </div>
      <div class="card-body">
        <div style="position:relative;height:220px">
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white fw-medium border-0 pb-0">
        <i class="bi bi-clock-history me-2 text-success"></i>งานใกล้ถึงกำหนด (7 วัน)
      </div>
      <div class="card-body overflow-auto" style="max-height:260px" id="upcoming-body">
        <div class="text-center text-muted py-3">กำลังโหลด...</div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
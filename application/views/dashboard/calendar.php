<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
/* ─── Variables ─────────────────────────────── */
:root {
  --col-wait:    #f59e0b;
  --col-confirm: #3b82f6;
  --col-doing:   #8b5cf6;
  --col-done:    #10b981;
  --col-cancel:  #ef4444;
  --radius: 10px;
}
* { font-family: 'Noto Sans Thai', 'Segoe UI', sans-serif; }

/* ─── Header ────────────────────────────────── */
.cal-header {
  display: flex; justify-content: space-between;
  align-items: flex-start; flex-wrap: wrap;
  gap: 8px; margin-bottom: 1rem;
}
.cal-header h4 { font-size: clamp(.95rem, 4vw, 1.15rem); margin-bottom: 2px; }
.cal-header-actions { display: flex; gap: 6px; flex-wrap: wrap; }

/* ─── Legend ────────────────────────────────── */
.legend-wrap { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-bottom: .7rem; font-size: .78rem; }
.legend-dot { width:10px; height:10px; border-radius:3px; display:inline-block; vertical-align:middle; margin-right:3px; }

/* ─── Filter card ───────────────────────────── */
.filter-card { background:#fff; border-radius:var(--radius); box-shadow:0 1px 6px rgba(0,0,0,.08); padding:10px 14px; margin-bottom:.75rem; }
.filter-card label { font-size:.78rem; margin-bottom:2px; color:#555; }

/* ─── Mobile view toggle pill ───────────────── */
.view-toggle { display:none; gap:4px; margin-bottom:8px; }
.view-toggle button {
  flex:1; font-size:.75rem; padding:6px 4px;
  border-radius:8px; border:1px solid #d1d5db;
  background:#fff; color:#374151; font-weight:600;
  cursor:pointer; transition:all .15s;
}
.view-toggle button.active { background:#3b82f6; color:#fff; border-color:#3b82f6; }
@media (max-width:575px) { .view-toggle { display:flex; } }

/* ─── Calendar wrapper ──────────────────────── */
.cal-wrap { background:#fff; border-radius:var(--radius); box-shadow:0 1px 6px rgba(0,0,0,.08); padding:10px; overflow:hidden; }

/* ─── FullCalendar overrides ────────────────── */
#calendar {
  --fc-border-color: #e5e7eb;
  --fc-today-bg-color: #fffbeb;
  --fc-button-bg-color: #3b82f6;
  --fc-button-border-color: #3b82f6;
  --fc-button-hover-bg-color: #2563eb;
  --fc-button-active-bg-color: #1d4ed8;
}
.fc .fc-toolbar { flex-wrap:wrap; gap:4px; }
.fc .fc-toolbar-title { font-size:clamp(.82rem,3.5vw,1.05rem); font-weight:700; }
.fc .fc-button { font-size:.75rem!important; padding:4px 8px!important; border-radius:7px!important; }
.fc .fc-daygrid-day-number { font-size:.8rem; padding:2px 4px; font-weight:600; color:#374151; }
.fc .fc-col-header-cell-cushion { font-size:.75rem; font-weight:700; color:#6b7280; }

/* event block */
.fc-daygrid-event {
  border-radius:5px!important; border:none!important;
  padding:2px 5px!important; cursor:pointer;
  font-size:.72rem!important; line-height:1.35!important;
}
.fc-event-title { white-space:normal!important; overflow:visible!important; font-size:.72rem!important; }

/* ─── Mobile: dot-only events ───────────────── */
@media (max-width:575px) {
  .fc .fc-daygrid-day-frame { min-height:48px!important; }

  .fc-daygrid-event .fc-event-title,
  .fc-daygrid-event .fc-event-time { display:none!important; }

  .fc-daygrid-event {
    height:7px!important; min-height:7px!important;
    padding:0!important; border-radius:4px!important;
    margin:1px 2px!important; opacity:.9;
  }
  .fc-daygrid-more-link {
    font-size:.68rem!important; background:#f3f4f6;
    border-radius:10px; padding:1px 4px;
    color:#374151; font-weight:700;
  }
  .fc .fc-toolbar-title { font-size:.85rem; }
  .fc .fc-button { font-size:.68rem!important; padding:3px 5px!important; }
  .fc .fc-col-header-cell-cushion { font-size:.68rem!important; }

  /* hide right toolbar buttons on mobile (handled by pill) */
  .fc .fc-toolbar-chunk:last-child { display:none!important; }
}

/* ─── List view ─────────────────────────────── */
.fc-list-event td { font-size:.82rem; }
.fc-list-event-dot { border-color:transparent!important; }
.fc-list-event-title a { color:#111827; font-weight:500; }
.fc-list-day-cushion { background:#f9fafb!important; font-size:.8rem; font-weight:700; }
.fc-list-empty { font-size:.85rem; color:#9ca3af; text-align:center; padding:2rem; }

/* ─── Modal: bottom-sheet on mobile ─────────── */
@media (max-width:575px) {
  .modal { align-items:flex-end!important; }
  .modal-dialog { margin:0!important; max-width:100%!important; }
  .modal-content { border-radius:16px 16px 0 0; }
  .modal-body { max-height:72vh; overflow-y:auto; }
  .form-label { font-size:.8rem; }
  .form-control, .form-select { font-size:.85rem; }
}

/* ─── Tech row (select + free text side-by-side) */
.tech-row { display:flex; gap:6px; flex-wrap:wrap; }
.tech-row select, .tech-row input { flex:1; min-width:130px; }

/* ─── Job result / suggest items ────────────── */
.job-result-item { cursor:pointer; padding:8px 12px; border-bottom:1px solid #f0f0f0; font-size:.83rem; }
.job-result-item:hover { background:#f8f9ff; }
.job-result-item.selected { background:#eff6ff; border-left:3px solid #3b82f6; }
.tech-suggest-item { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:.83rem; }
.tech-suggest-item:hover { background:#f0f6ff; }

/* ─── Disabled job item (มีช่างแล้ว) ────────────── */
.job-result-disabled {
  padding:8px 12px; border-bottom:1px solid #f0f0f0;
  background:#fafafa; cursor:not-allowed;
  opacity:.75; pointer-events:none;
  user-select:none;
}
</style>

<!-- ── Page Header ─────────────────────────────────── -->
<div class="cal-header mb-3">
  <div>
    <h4 class="fw-bold mb-1">
      <i class="bi bi-calendar3 me-2 text-primary"></i>ตารางคิวช่าง
    </h4>
    <p class="text-muted small mb-0">ดูและจัดการคิวงานช่างแบบ Calendar</p>
  </div>
  <div class="cal-header-actions">
    <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-speedometer2 me-1"></i><span class="d-none d-sm-inline">Dashboard</span>
    </a>
    <button class="btn btn-primary btn-sm" onclick="openAssignModal()">
      <i class="bi bi-plus-lg me-1"></i>ลงคิว
    </button>
  </div>
</div>

<!-- ── Legend ──────────────────────────────────────── -->
<div class="legend-wrap">
  <span><span class="legend-dot" style="background:var(--col-wait)"></span>รอดำเนินการ</span>
  <span><span class="legend-dot" style="background:var(--col-confirm)"></span>ยืนยันแล้ว</span>
  <span><span class="legend-dot" style="background:var(--col-doing)"></span>กำลังดำเนินการ</span>
  <span><span class="legend-dot" style="background:var(--col-done)"></span>เสร็จสิ้น</span>
  <span><span class="legend-dot" style="background:var(--col-cancel)"></span>ยกเลิก</span>
</div>

<!-- ── Filter ──────────────────────────────────────── -->
<div class="filter-card">
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-4">
      <label>กรองตามช่าง</label>
      <select id="filter-tech" class="form-select form-select-sm">
        <option value="">-- ช่างทั้งหมด --</option>
      </select>
    </div>
    <div class="col-6 col-md-4">
      <label>กรองตามสถานะ</label>
      <select id="filter-status" class="form-select form-select-sm">
        <option value="">-- ทุกสถานะ --</option>
        <option>รอดำเนินการ</option>
        <option>ยืนยันแล้ว</option>
        <option>กำลังดำเนินงาน</option>
        <option>เสร็จแล้ว</option>
        <option>เลื่อนนัด</option>
        <option>ยกเลิกนัด</option>
      </select>
    </div>
    <div class="col-12 col-md-4">
      <button class="btn btn-outline-primary btn-sm w-100" onclick="refreshCalendar()">
        <i class="bi bi-funnel me-1"></i>ค้นหา
      </button>
    </div>
  </div>
</div>

<!-- ── Mobile view toggle ──────────────────────────── -->
<div class="view-toggle" id="viewToggle">
  <button class="active" onclick="switchView('dayGridMonth',this)">
    <i class="bi bi-calendar-month me-1"></i>เดือน
  </button>
  <button onclick="switchView('listWeek',this)">
    <i class="bi bi-list-ul me-1"></i>รายการ
  </button>
  <button onclick="switchView('timeGridDay',this)">
    <i class="bi bi-calendar-day me-1"></i>วันนี้
  </button>
</div>

<!-- ── Calendar ────────────────────────────────────── -->
<div class="cal-wrap">
  <div id="calendar"></div>
</div>

<!-- ════════════════════════════════════════════════ -->
<!-- Assign Modal                                    -->
<!-- ════════════════════════════════════════════════ -->
<div class="modal fade" id="assignModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title fw-bold" id="assignTitle">
          <i class="bi bi-calendar-plus me-2"></i>ลงคิวงาน
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="assign-job-id">

        <!-- Step 1 -->
        <label class="form-label fw-medium mb-1">1. ค้นหางาน</label>
        <div class="input-group input-group-sm mb-2">
          <input type="text" id="job-search-input" class="form-control"
                 placeholder="เลขบิล / ชื่อลูกค้า / เบอร์โทร">
          <button class="btn btn-outline-primary" onclick="searchJobs()">
            <i class="bi bi-search"></i>
          </button>
        </div>
        <div id="job-results" class="border rounded"
             style="max-height:190px;overflow-y:auto;min-height:48px">
          <div class="text-center text-muted py-3 small">พิมพ์เพื่อค้นหา...</div>
        </div>
        <div id="selected-job-preview" class="d-none mt-2 p-2 rounded"
             style="background:#eff6ff;border:1px solid #bfdbfe">
          <div class="small fw-medium text-primary" id="sel-job-text"></div>
        </div>

        <hr class="my-3">

        <!-- Step 2 -->
        <label class="form-label fw-medium mb-2">2. กำหนดช่างและวันเวลา</label>
        <div class="row g-2">

          <div class="col-12">
            <label class="form-label mb-1">ประเภทงาน <span class="text-danger">*</span></label>
            <select id="assign-job-type" class="form-select form-select-sm">
              <option value="">-- เลือกประเภทงาน --</option>
              <option>ติดตั้ง</option>
              <option>ซ่อม</option>
              <option>ล้างเครื่อง</option>
              <option>เปลี่ยนไส้กรอง</option>
              <option>ส่งสินค้า</option>
              <option>นำสินค้ากลับ</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label mb-1">ช่างรับผิดชอบ</label>
            <div class="tech-row">
              <select id="assign-tech-select" class="form-select form-select-sm">
                <option value="">-- เลือกช่างในระบบ --</option>
                <?php foreach($technicians as $t): ?>
                  <option value="<?= $t['reg_name'] ?>" data-id="<?= $t['id'] ?>">
                    <?= $t['reg_name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="text" id="assign-tech-input" class="form-control form-control-sm"
                     placeholder="ช่างนอกระบบ..." autocomplete="off">
            </div>
            <input type="hidden" id="assign-tech-id">
          </div>

          <div class="col-6">
            <label class="form-label mb-1">ค่าจ้างช่าง (บาท)</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-cash-coin"></i></span>
              <input type="number" id="assign-tech-wage" class="form-control"
                     placeholder="0.00" min="0" step="0.01">
            </div>
          </div>

          <div class="col-6">
            <label class="form-label mb-1">หมายเหตุ</label>
            <input type="text" id="assign-tech-note" class="form-control form-control-sm"
                   placeholder="เช่น ช่างนอก...">
          </div>

          <div class="col-6">
            <label class="form-label mb-1">วันที่นัด</label>
            <input type="date" id="assign-date" class="form-control form-control-sm">
          </div>

          <div class="col-6">
            <label class="form-label mb-1">เวลา</label>
            <input type="time" id="assign-time" class="form-control form-control-sm">
          </div>

        </div>
      </div>
      <div class="modal-footer py-2">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
        <button class="btn btn-primary btn-sm px-4" onclick="saveAssign()">
          <i class="bi bi-save me-1"></i>บันทึกคิว
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════ -->
<!-- Event Detail Modal                              -->
<!-- ════════════════════════════════════════════════ -->
<div class="modal fade" id="eventModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-info-circle me-2"></i>รายละเอียดงาน
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="event-detail-body"></div>
      <div class="modal-footer py-2">
        <button class="btn btn-warning btn-sm" onclick="editEvent()">
          <i class="bi bi-pencil me-1"></i>แก้ไข
        </button>
        <button class="btn btn-danger btn-sm" onclick="deleteEvent()">
          <i class="bi bi-trash me-1"></i>ลบ
        </button>
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

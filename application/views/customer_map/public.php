<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>แผนที่ลูกค้า</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>html,body{margin:0;padding:0;height:100%;font-family:"Sarabun",sans-serif;}#map-page{height:100vh;}</style>
</head>
<body>

<!-- แถบบนสุด: ผู้ใช้ปัจจุบัน + ออกจากระบบ (เดิมหน้านี้ไม่ต้อง login เลยไม่มีแถบนี้ ตอนนี้ต้อง login แล้วจึงต้องมีทางออกจากระบบ)
     โหมด token (embed จาก register-product): ไม่มี session เข้าสู่ระบบเลย จึงโชว์แค่ป้ายชื่อ ไม่มีชื่อผู้ใช้/ปุ่มออกจากระบบ -->
<div class="d-flex justify-content-between align-items-center px-3" style="height:60px;background:#0d6efd;color:#fff;box-sizing:border-box;">
  <div class="fw-bold"><i class="bi bi-pin-map-fill me-2"></i>แผนที่ลูกค้า</div>
  <?php if (empty($token_mode) && empty($token_expired)): ?>
  <div class="d-flex align-items-center gap-3">
    <span class="small"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($this->session->userdata('full_name') ?? '', ENT_QUOTES) ?></span>
    <a href="<?= site_url('logout') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>ออกจากระบบ</a>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($token_expired)): ?>
<!-- token ที่ส่งมาหมดอายุหรือไม่ถูกต้อง (ปกติจะเกิดถ้าลิงก์เก่าเกิน 6 วัน) — ไม่ redirect ไป login
     เพราะผู้เข้าชมทางนี้ไม่มีบัญชีให้ login อยู่แล้ว โชว์ข้อความให้กลับไปค้นหาใหม่แทน -->
<div class="d-flex flex-column align-items-center justify-content-center text-center px-3" style="height:calc(100vh - 60px);">
  <i class="bi bi-exclamation-triangle text-danger" style="font-size:3rem;"></i>
  <h5 class="fw-bold mt-3 mb-1">ลิงก์หมดอายุหรือไม่ถูกต้อง</h5>
  <p class="text-muted mb-0">กรุณากลับไปค้นหาข้อมูลใหม่อีกครั้งจากหน้าลงทะเบียนสินค้า</p>
</div>
<?php else: ?>

<?php if ($show_map ?? true): ?>
<style>
/* ═══════════════════════════════════════════════
   BASE
═══════════════════════════════════════════════ */
#map-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 60px);
  position: relative;
  overflow: hidden;
  overflow: clip; /* รองรับ Android 6+ ไม่ block touch events บน absolute children */
}

/* ═══════════════════════════════════════════════
   STATS BAR — DESKTOP: pill แนวนอน
═══════════════════════════════════════════════ */
#stats-bar {
  display: flex;
  padding: 7px 12px;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  overflow-x: auto;
  flex-shrink: 0;
  align-items: center;
  scrollbar-width: none;
}
#stats-bar::-webkit-scrollbar { display: none; }

/* Desktop: pill แนวนอน (ตัวเลข + label อยู่บรรทัดเดียว) */
.stat-chip {
  display: inline-flex;
  align-items: center;
  padding: 5px 14px;
  border-radius: 20px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: .15s;
  white-space: nowrap;
  flex-shrink: 0;
}
.stat-chip.active { border-color: currentColor; }
.stat-chip .chip-icon { font-size: .9rem; margin-right: 6px; }
.stat-chip .chip-lbl  { font-size: .8rem;  font-weight: 600; }
.stat-chip .chip-num  { font-size: .8rem;  font-weight: 800; }

.stat-chip.sc-all    { background:#f3f4f6; color:#374151; }
.stat-chip.sc-green  { background:#d1fae5; color:#065f46; }
.stat-chip.sc-yellow { background:#fef9c3; color:#713f12; }
.stat-chip.sc-red    { background:#fee2e2; color:#7f1d1d; }
.stat-chip.sc-over   { background:#f3e8ff; color:#4c1d95; }
.stat-chip.sc-pending { background:#dbeafe; color:#1e3a8a; }

/* Mobile: card ใหญ่ 5 คอลัมน์เท่ากัน */
@media (max-width: 640px) {
  #stats-bar {
    padding: 8px 10px;
  }
  #stats-bar .stat-chip { margin-right: 6px; }
  #stats-bar .stat-chip:last-child { margin-right: 0; }
  .stat-chip {
    flex: 1;
    flex-direction: column;
    align-items: center;
    padding: 8px 4px;
    border-radius: 12px;
    min-width: 0;
  }
  .stat-chip .chip-icon { font-size: 1.1rem; margin-bottom: 2px; }
  .stat-chip .chip-num  { font-size: 1.3rem; font-weight: 800; line-height: 1; }
  .stat-chip .chip-lbl  { font-size: .6rem;  font-weight: 600; text-align: center; }
}

/* ═══════════════════════════════════════════════
   TOOLBAR
═══════════════════════════════════════════════ */
#map-toolbar {
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-align: center;
      -ms-flex-align: center;
          align-items: center;
  padding: 7px 12px;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  -ms-flex-negative: 0;
      flex-shrink: 0;
}
#search-box { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; min-width: 0; margin-right: 8px; }
#tech-filter { width: 140px; -ms-flex-negative: 0; flex-shrink: 0; margin-right: 8px; }
#jobtype-filter { width: 180px; -ms-flex-negative: 0; flex-shrink: 0; margin-right: 8px; }
#category-filter { width: 160px; -ms-flex-negative: 0; flex-shrink: 0; margin-right: 8px; }

/* Mobile: ค้นหาบรรทัดแรกเต็มความกว้าง, ช่าง+ประเภทงานแบ่งครึ่งบรรทัดสอง
   (เดิมซ่อน #tech-filter ทิ้งบนมือถือ ทำให้กรองช่างไม่ได้เลย — แก้ด้วยการ wrap แทนการซ่อน) */
@media (max-width: 640px) {
  #map-toolbar { -ms-flex-wrap: wrap; flex-wrap: wrap; }
  #search-box { -webkit-box-flex: 1; -ms-flex: 1 1 100%; flex: 1 1 100%; margin-right: 0; margin-bottom: 8px; }
  #tech-filter, #jobtype-filter, #category-filter {
    display: block;
    width: auto;
    -webkit-box-flex: 1; -ms-flex: 1 1 0; flex: 1 1 0;
    min-width: 0;
  }
}

/* ═══════════════════════════════════════════════
   MAP BODY
═══════════════════════════════════════════════ */
#map-body { flex: 1; display: flex; min-height: 0; }
#map-wrap { flex: 1; position: relative; }
#map      { width: 100%; height: 100%; }

/* ═══════════════════════════════════════════════
   INFO PANEL — DESKTOP (ข้างขวา)
═══════════════════════════════════════════════ */
#info-panel {
  width: 320px;
  flex: 0 0 320px;
  overflow-y: hidden;
  background: #fff;
  border-left: 1px solid #e5e7eb;
  display: none;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
      -ms-flex-direction: column;
          flex-direction: column;
  position: relative;
}
#info-panel.is-open { display: -webkit-box; display: -ms-flexbox; display: flex; }

/* ═══════════════════════════════════════════════
   INFO PANEL — MOBILE (bottom sheet)
═══════════════════════════════════════════════ */
@media (max-width: 640px) {
  #map-body { position: relative; }

  #info-panel {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    width: 100%;
    -ms-flex: none;
        flex: none;
    height: 72vh;
    max-height: 72vh;
    border-left: none;
    border-top: 1px solid #e5e7eb;
    border-radius: 18px 18px 0 0;
    box-shadow: 0 -4px 24px rgba(0,0,0,.15);
    z-index: 10;
    display: none;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
        -ms-flex-direction: column;
            flex-direction: column;
  }
  #info-panel.is-open {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
  }
  #info-panel::before {
    content: '';
    display: block;
    width: 40px; height: 4px;
    background: #d1d5db;
    border-radius: 2px;
    margin: 10px auto 2px;
    -ms-flex-negative: 0;
        flex-shrink: 0;
  }
}

/* ═══════════════════════════════════════════════
   INFO PANEL CONTENT
═══════════════════════════════════════════════ */
.ip-header {
  padding: 10px 16px 8px;
  border-bottom: 1px solid #f3f4f6;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-pack: justify;
      -ms-flex-pack: justify;
          justify-content: space-between;
  -webkit-box-align: start;
      -ms-flex-align: start;
          align-items: flex-start;
  -ms-flex-negative: 0;
      flex-shrink: 0;
  position: relative;
  z-index: 1;
}
.ip-header > div { margin-right: 8px; }
/* ปุ่มปิด — absolute เพื่อไม่ถูก overflow บัง */
#info-close {
  position: absolute;
  top: 10px;
  right: 14px;
  z-index: 100;
  cursor: pointer;
  touch-action: manipulation;
  padding: 8px;
  background: rgba(255,255,255,.9);
  border-radius: 50%;
  border: none;
  -webkit-tap-highlight-color: transparent;
}
.ip-name { font-weight: 700; font-size: 1rem; line-height: 1.3; }
.ip-body { padding: 10px 16px 0; flex: 1; overflow-y: auto; min-height: 0; }
.ip-actions-wrap { padding: 10px 16px 14px; flex-shrink: 0; border-top: 1px solid #f3f4f6; }

.status-pill {
  display: inline-block;
  border-radius: 20px;
  padding: 3px 12px;
  font-size: .75rem;
  font-weight: 700;
  margin-top: 4px;
}
.sp-green   { background:#d1fae5; color:#065f46; }
.sp-yellow  { background:#fef9c3; color:#713f12; }
.sp-red     { background:#fee2e2; color:#7f1d1d; }
.sp-overdue { background:#f3e8ff; color:#4c1d95; }

.ip-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 5px 0;
  border-bottom: 1px solid #f9fafb;
}
.ip-lbl { margin-right: 8px; }
.ip-lbl { font-size: .75rem; color: #6b7280; white-space: nowrap; flex-shrink: 0; }
.ip-val { font-size: .85rem; font-weight: 500; text-align: right; word-break: break-word; }

.days-ok   { color: #059669; font-weight: 700; }
.days-warn { color: #d97706; font-weight: 700; }
.days-due  { color: #dc2626; font-weight: 700; }
.days-over { color: #7c3aed; font-weight: 700; }

.ip-actions { display: flex; margin-bottom: 8px; }
.ip-actions > * + * { margin-left: 8px; }

/* base style ปุ่มทุกตัว */
/* ── Zoom buttons + My Location ────────────────────────── */
#map-wrap { position: relative; }
#zoom-btns {
  position: absolute;
  bottom: 110px;
  right: 10px;
  z-index: 5;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
      -ms-flex-direction: column;
          flex-direction: column;
}
.zoom-btn {
  width: 39px;
  height: 39px;
  margin-bottom: 4px;
  border: none;
  border-radius: 6px;
  background: #fff;
  color: #374151;
  font-size: .75rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 1px 4px rgba(0,0,0,.25);
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-align: center;
      -ms-flex-align: center;
          align-items: center;
  -webkit-box-pack: center;
      -ms-flex-pack: center;
          justify-content: center;
}
.zoom-btn:hover { background: #f3f4f6; }
#btn-my-location {
  width: 39px;
  height: 39px;
  margin-bottom: 8px;
  border: none;
  border-radius: 6px;
  background: #fff;
  color: #2563eb;
  font-size: 1rem;
  cursor: pointer;
  box-shadow: 0 1px 4px rgba(0,0,0,.25);
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-align: center;
      -ms-flex-align: center;
          align-items: center;
  -webkit-box-pack: center;
      -ms-flex-pack: center;
          justify-content: center;
}
#btn-my-location:hover { background: #eff6ff; }
#btn-my-location.locating { color: #2563eb; }
#btn-my-location.locating i {
  display: inline-block;
  -webkit-animation: spin 1s linear infinite;
          animation: spin 1s linear infinite;
}
@-webkit-keyframes spin { to { -webkit-transform: rotate(360deg); transform: rotate(360deg); } }
@keyframes spin        { to { -webkit-transform: rotate(360deg); transform: rotate(360deg); } }
@media (max-width: 640px) {
  /* บน mobile ใช้ position:fixed เพื่อไม่ให้ทะลุออกนอก map */
  #zoom-btns {
    position: fixed;
    bottom: 80px;
    right: 10px;
    z-index: 20;
    -webkit-transition: opacity .2s, visibility .2s;
            transition: opacity .2s, visibility .2s;
  }
  /* ซ่อนปุ่ม zoom เมื่อ info panel เปิดอยู่ */
  #info-panel.is-open ~ * #zoom-btns,
  body.panel-open #zoom-btns {
    opacity: 0;
    visibility: hidden;
  }
}

.ip-btn {
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-align: center;
      -ms-flex-align: center;
          align-items: center;
  -webkit-box-pack: center;
      -ms-flex-pack: center;
          justify-content: center;
  height: 42px;
  border-radius: 10px;
  font-size: .82rem;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  -webkit-box-sizing: border-box;
          box-sizing: border-box;
  white-space: nowrap;
}
.btn-history { background: #7c3aed; color: #fff; width: 100%; margin-bottom: 8px; }
.btn-detail  { background: #2563eb; color: #fff; -webkit-box-flex: 1; -ms-flex: 1; flex: 1; }
.btn-nav     { background: #16a34a; color: #fff; -webkit-box-flex: 1; -ms-flex: 1; flex: 1; }
.btn-call    { background: #f3f4f6; color: #374151; width: 44px; -ms-flex-negative: 0; flex-shrink: 0; }

.hrow {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 14px;
  margin-bottom: 8px;
}
.hrow-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}
.hrow-top > * + * { margin-left: 8px; }
.hrow-bill  { font-weight: 700; font-size: .88rem; }
.hrow-type  { font-size: .72rem; background: #e0e7ff; color: #3730a3; border-radius: 10px; padding: 2px 8px; }
.hrow-date  { font-size: .75rem; color: #6b7280; }
.hrow-prod  { font-size: .8rem; color: #374151; margin-top: 2px; }
.hrow-tech  { font-size: .75rem; color: #6b7280; }
.hrow-tech-row { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; }
.hrow-status { font-size: .72rem; border-radius: 10px; padding: 2px 8px; }
.hs-done    { background: #d1fae5; color: #065f46; }
.hs-pending { background: #fef9c3; color: #713f12; }
.hs-other   { background: #f3f4f6; color: #374151; }

/* รองรับไอคอน Font Awesome ใน Dropdown */
#jobtype-filter, 
#jobtype-filter option {
  font-family: "Font Awesome 6 Free", "Sarabun", sans-serif;
  font-weight: 700; /* สำคัญมาก: Font Awesome แบบ Solid ต้องใช้ weight 900 */
}
</style>

<div id="map-page">

  <?php if (empty($token_mode)): ?>
  <!-- Stats bar -->
  <div id="stats-bar">
    <div class="stat-chip sc-pending" data-f="pending">
     
      <span class="chip-lbl">รอดำเนินการ</span>
      <span class="chip-num" id="c-pending">0</span>
    </div>|
    <div class="stat-chip sc-all active" data-f="all">
   
      <span class="chip-lbl">รวมทุกรายการ</span>
      <span class="chip-num" id="c-all">0</span>
    </div>
    <div class="stat-chip sc-green" data-f="green">
    
      <span class="chip-lbl">ยังไม่ครบกำหนด</span>
      <span class="chip-num" id="c-green">0</span>
    </div>
    <div class="stat-chip sc-yellow" data-f="yellow">
   
      <span class="chip-lbl">ครบ 6 เดือน</span>
      <span class="chip-num" id="c-yellow">0</span>
    </div>
    <div class="stat-chip sc-red" data-f="red">
     
      <span class="chip-lbl">ครบ 1 ปี</span>
      <span class="chip-num" id="c-red">0</span>
    </div>
    <div class="stat-chip sc-over" data-f="overdue">
     
      <span class="chip-lbl">เกิน 1 ปี</span>
      <span class="chip-num" id="c-over">0</span>
    </div>
    
  </div>

  <!-- Toolbar -->
  <div id="map-toolbar">
    <input type="search" id="search-box" class="form-control form-control-sm"
           placeholder="ค้นหาชื่อลูกค้า / ที่อยู่ / เลขบิล">
    <select id="tech-filter" class="form-select form-select-sm">
      <option value="">-- ช่างทั้งหมด --</option>
    </select>
    <select id="jobtype-filter" class="form-select form-select-sm">
      <option value="">-- ประเภทงาน --</option>
    </select>
    <select id="category-filter" class="form-select form-select-sm">
      <option value="">-- หมวดหมู่ทั้งหมด --</option>
    </select>
    <button class="btn btn-sm btn-outline-secondary" style="-ms-flex-negative:0;flex-shrink:0;" id="btn-refresh">
      <i class="bi bi-arrow-clockwise"></i>
    </button>
  </div>
  <?php else: ?>
  <!-- โหมด token: ซ่อนแถบสถานะ + ช่องค้นหา/ตัวกรองทั้งหมด (อ่านอย่างเดียว บิลเดียว ห้ามค้นบิลอื่น) -->
  <div id="stats-bar" style="display:none;">
    <span id="c-pending">0</span><span id="c-all">0</span><span id="c-green">0</span>
    <span id="c-yellow">0</span><span id="c-red">0</span><span id="c-over">0</span>
  </div>
  <div id="map-toolbar" style="display:none;">
    <input type="search" id="search-box" style="display:none;">
    <select id="tech-filter" style="display:none;"><option value=""></option></select>
    <select id="jobtype-filter" style="display:none;"><option value=""></option></select>
    <select id="category-filter" style="display:none;"><option value=""></option></select>
    <button id="btn-refresh" style="display:none;"></button>
  </div>
  <?php endif; ?>

  <!-- Map + Panel -->
  <div id="map-body">
    <div id="map-wrap">
      <div id="map"></div>
      <!-- Loading toast สำหรับ geolocation -->
      <div id="loc-toast" style="display:none;position:absolute;top:10px;left:50%;-webkit-transform:translateX(-50%);transform:translateX(-50%);background:rgba(0,0,0,.72);color:#fff;padding:8px 18px;border-radius:20px;font-size:.8rem;z-index:20;white-space:nowrap;pointer-events:none;">
        <span style="display:inline-block;-webkit-animation:spin 1s linear infinite;animation:spin 1s linear infinite;margin-right:6px;">⟳</span>กำลังดึงตำแหน่ง...
      </div>
      <!-- ปุ่ม Zoom Level -->
      <div id="zoom-btns">
        <button id="btn-my-location" title="ตำแหน่งปัจจุบัน">
          <i class="bi bi-crosshair"></i>
        </button>
        <button class="zoom-btn" data-zoom="10" title="จังหวัด">จังหวัด</button>
        <button class="zoom-btn" data-zoom="13" title="อำเภอ">อำเภอ</button>
        <button class="zoom-btn" data-zoom="15" title="ตำบล">ตำบล</button>
      </div>
    </div>

    <div id="info-panel">
      <!-- ปุ่มปิด absolute ไม่ถูก overflow บัง -->
      <button id="info-close" aria-label="ปิด">✕</button>
      <div class="ip-header">
        <div>
          <div class="ip-name" id="info-name">-</div>
          <div id="info-status-pill"></div>
        </div>
      </div>
      <div class="ip-body">
        <div class="ip-row"><span class="ip-lbl">เลขที่บิล</span>      <span class="ip-val" id="info-bill">-</span></div>
        <div class="ip-row"><span class="ip-lbl">เบอร์โทร</span>       <span class="ip-val" id="info-phone">-</span></div>
        <div class="ip-row"><span class="ip-lbl">สินค้า</span>         <span class="ip-val" id="info-product">-</span></div>
        <div class="ip-row"><span class="ip-lbl">ติดตั้งวันที่</span>  <span class="ip-val" id="info-install">-</span></div>
        <div class="ip-row"><span class="ip-lbl">บริการล่าสุด</span>   <span class="ip-val" id="info-last-svc">-</span></div>
        <div class="ip-row"><span class="ip-lbl">ครบกำหนดถัดไป</span><span class="ip-val" id="info-due1y">-</span></div>
        <div class="ip-row"><span class="ip-lbl">เวลาที่เหลือ</span>   <span class="ip-val" id="info-days-left">-</span></div>
        <div class="ip-row"><span class="ip-lbl">ช่าง</span>           <span class="ip-val" id="info-tech">-</span></div>
        <div class="ip-row"><span class="ip-lbl">ที่อยู่</span>        <span class="ip-val" id="info-address">-</span></div>
      </div>

      <!-- ปุ่มติดล่างเสมอ -->
      <div class="ip-actions-wrap">
        <!-- ปุ่มแถว 1: ประวัติ (เต็มความกว้าง) -->
        <button id="btn-history" class="ip-btn btn-history">
          <i class="bi bi-clock-history" style="margin-right:6px;"></i>ประวัติการให้บริการ
        </button>
        <!-- ปุ่มแถว 2: ดูรายละเอียด + นำทาง + โทร -->
        <div class="ip-actions">
          <a id="btn-detail"   href="#" class="ip-btn btn-detail">
            <i class="bi bi-list-ul" style="margin-right:4px;"></i>ดูรายละเอียด
          </a>
          <a id="btn-navigate" href="#" target="_blank" rel="noopener" class="ip-btn btn-nav">
            <i class="bi bi-navigation-fill" style="margin-right:4px;"></i>นำทาง
          </a>
          <a id="btn-call" href="#" class="ip-btn btn-call">
            <i class="bi bi-telephone-fill"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var API_MARKERS = '<?= site_url("map/api_markers") ?>';
var API_TECHS     = '<?= site_url("map/api_techs") ?>';
var API_HISTORY   = '<?= site_url("map/api_history") ?>';
var API_JOB_TYPES   = '<?= site_url("map/api_job_types") ?>';
var API_CATEGORIES  = '<?= site_url("map/api_categories") ?>';
var API_WARRANTY  = '<?= site_url("map/api_warranty_info") ?>';
var API_JOB_DETAIL = '<?= site_url("map/api_job_detail") ?>';
var CHANGPROM_URL   = '<?= "https://changprom.tgsmartlife.com" . "/queue/detail/" ?>';
var SERVICE_URL = '<?= site_url("service") ?>';
var GMAPS_KEY   = '<?= htmlspecialchars($gmaps_key ?? '', ENT_QUOTES) ?>';
// โหมด token: embed แบบไม่ login จากหน้า register-product ของเว็บ tgsmartlife (อ่านอย่างเดียว บิลเดียว)
var TOKEN_MODE = <?= !empty($token_mode) ? 'true' : 'false' ?>;
var TOKEN      = '<?= addslashes($token ?? '') ?>';
var TOKEN_BILL_NO = '<?= addslashes($token_bill_no ?? '') ?>';
// มุมมองพนักงานทั่วไป (login ปกติ ไม่ใช่ token) — คุมปุ่ม "ดูรายละเอียด" ให้เป็น popup จำกัดฟิลด์
var IS_EMPLOYEE_VIEW = <?= !empty($is_employee_view) ? 'true' : 'false' ?>;
function withToken(url) {
  if (!TOKEN_MODE || !TOKEN) return url;
  return url + (url.indexOf('?') === -1 ? '?' : '&') + 'token=' + encodeURIComponent(TOKEN);
}
//var GMAPS_KEY   = 'AIzaSyBiDeosZazrjT1PMnhs7TuKOpjJFDoGUJg';// prod
function makeMarkerIcon(status, lastServiceType) {
  var colors = {
    green:   { pin:'#16a34a', border:'#14532d' },
    yellow:  { pin:'#eab308', border:'#92400e' },
    red:     { pin:'#dc2626', border:'#7f1d1d' },
    overdue: { pin:'#7c3aed', border:'#4c1d95' },
  };
  var c = colors[status] || colors.green;

  // SVG Path สำหรับไอคอนทั้ง 6 ประเภทงาน
  var iconWrench = 'M10.9 2.1c-1.2.3-2.2 1-2.9 2L9.4 5.5 8 6.9 6.6 5.5C5.9 6.3 5.5 7.3 5.5 8.4c0 1.3.5 2.5 1.4 3.4l.6.6-5.9 5.9c-.6.6-.6 1.5 0 2.1.6.6 1.5.6 2.1 0l5.9-5.9.6.6c.9.9 2.1 1.4 3.4 1.4 1.1 0 2.1-.4 2.9-1.1L15.1 14l-1.4-1.4 1.4-1.4 1.4 1.4.4-.4c.5-.8.8-1.7.8-2.7 0-1.3-.5-2.5-1.4-3.4l-2.6 2.6-1.4-1.4 2.6-2.6c-.9-.6-2-.9-3.1-.6z'; // ติดตั้ง
  var iconFilter = 'M1.5 1.5h17l-6.5 8v7l-4-2V9.5z'; // เปลี่ยนไส้กรอง
  var iconTools  = 'M12.9 2c-1 0-2 .4-2.8 1.1L11.5 4.5 10 6 8.6 4.6C7.9 5.4 7.5 6.4 7.5 7.5c0 1 .4 2 1.1 2.7l.4.4L3.5 16c-.5.5-.5 1.4 0 1.9s1.4.5 1.9 0l5.5-5.5.4.4c.7.7 1.7 1.1 2.7 1.1s2-.4 2.7-1.1l-2.6-2.6 1.4-1.4 2.6 2.6c.7-.7 1.1-1.7 1.1-2.7 0-2.1-1.7-3.7-3.8-3.7z'; // ซ่อม
  var iconClean  = 'M10 2C10 2 3 10.5 3 14c0 3.9 3.1 7 7 7s7-3.1 7-7C17 10.5 10 2 10 2zm0 12c-1.7 0-3-1.3-3-3 0-.6.4-1 1-1s1 .4 1 1c0 .6.4 1 1 1s1 .4 1 1-.4 1-1 1z'; // ล้างเครื่อง
  var iconBox    = 'M10 1.5L2 6v9l8 4.5 8-4.5V6l-8-4.5zM10 3.3l5.8 3.2-5.8 3.3-5.8-3.3L10 3.3zM3.5 7.8l5.7 3.3v6.3l-5.7-3.2V7.8zm13 0v6.4l-5.8 3.2v-6.3l5.8-3.3z'; // ส่งสินค้า
  var iconReturn = 'M8 3v3.5C13.5 6.5 18 10.5 18 16c0 1-.2 2-.5 2.8-.3.7-1.3.8-1.7.2-.5-.7-.4-1.7-.3-2.5 0-4-3-7.5-7.5-7.5V13L2 8l6-5z'; // นำสินค้ากลับ

  var paths = {
    'ติดตั้ง':        iconWrench,
    'เปลี่ยนไส้กรอง': iconFilter,
    'ซ่อม':           iconTools,
    'ล้างเครื่อง':    iconClean,
    'ส่งสินค้า':      iconBox,
    'นำสินค้ากลับ':   iconReturn
  };

  // 1. แปลงเป็น String และตัดช่องว่าง (Space) หน้า-หลังทิ้ง ป้องกันปัญหา string ไม่ตรง
  var cleanType = (lastServiceType || '').toString().trim();

  // 2. ถ้าหาใน paths เจอ ให้ใช้ไอคอนนั้น ถ้าหาไม่เจอจริงๆ ถึงจะใช้ iconFilter เป็นค่าเริ่มต้น
  var iconPath = paths[cleanType] || iconFilter;

  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="50" viewBox="0 0 36 50">'
    + '<path d="M18 0C8 0 0 8 0 18c0 13 18 32 18 32S36 31 36 18C36 8 28 0 18 0z" fill="' + c.pin + '" stroke="' + c.border + '" stroke-width="1.5"/>'
    + '<g transform="translate(9,8) scale(0.9)">'
    + '<path d="' + iconPath + '" fill="#ffffff"/>'
    + '</g>'
    + '</svg>';

  return {
    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
    scaledSize: new google.maps.Size(36, 50),
    anchor:     new google.maps.Point(18, 50),
  };
}
// ฟังก์ชันดึงรหัส Unicode ของ Font Awesome 6 (Solid) ตามประเภทงาน


var map, markers = [], currentFilter = 'all', searchTimeout;
// ฟังก์ชันดึงรหัส Unicode ของ Font Awesome 6 (Solid) ตามประเภทงาน
function getJobTypeIcon(jobType) {
  var icons = {
    'ติดตั้ง':        '\uf0ad', // fa-wrench (ประแจ)
    'เปลี่ยนไส้กรอง': '\uf0b0', // fa-filter (กรอง)
    'ซ่อม':           '\uf7d9', // fa-tools / fa-screwdriver-wrench (เครื่องมือซ่อม)
    'ล้างเครื่อง':    '\uf51a', // fa-broom (ไม้กวาด/ทำความสะอาด)
    'ส่งสินค้า':      '\uf466', // fa-box (กล่องพัสดุ)
    'นำสินค้ากลับ':   '\uf3e5', // fa-rotate-left (นำกลับ/เทิร์น)
  };
  return icons[jobType] || '\uf013'; // default เป็นรูปฟันเฟือง fa-gear (\uf013)
}
function initMap() {
  map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: 13.7563, lng: 100.5018 },
    zoom: 11,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: false,
    zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_CENTER },
  });
  if (!TOKEN_MODE) {
    loadTechs();
    loadJobTypes();
    loadCategories();
  }
  loadMarkers();

  // auto-show ตำแหน่ง user หลัง map init เสร็จ (ข้ามในโหมด token เพราะ map จะ fit ไปที่หมุดบิลนั้นให้เองอยู่แล้ว
  // ไม่ควรขอสิทธิ์ตำแหน่งจากลูกค้าที่เข้ามาดูแค่บิลเดียวแบบไม่ login)
  // goToLocation เป็น global function อยู่ใน scope เดียวกัน เรียกได้ทันที
  if (!TOKEN_MODE) {
    goToLocation(10); // เปิดมาใหม่ zoom ระดับจังหวัด
  }
}

function loadTechs() {
  fetch(API_TECHS).then(function(r){ return r.json(); }).then(function(res) {
    if (!res.success) return;
    var sel = document.getElementById('tech-filter');
    res.data.forEach(function(t) {
      var o = document.createElement('option');
      o.value = t; o.textContent = t; sel.appendChild(o);
    });
  });
}
function loadJobTypes() {
  // ก๊อปปี้ชุดนี้ไปเป๊ะๆ ครับ ผมใส่รหัสบังคับสีให้แล้ว และเปลี่ยนรูปลูกศรเทิร์นกลับที่ชอบมีปัญหาเป็นรูปอื่น
  var typeIcon = {
    'ติดตั้ง':        '🔧 ติดตั้ง',
    'เปลี่ยนไส้กรอง': '💧 เปลี่ยนไส้กรอง',
    'ซ่อม':           '🛠️ ซ่อม',       // เพิ่มรหัสบังคับสีให้ไม่ดำบน Windows
    'ล้างเครื่อง':    '🧹 ล้างเครื่อง',
    'ส่งสินค้า':      '📦 ส่งสินค้า',
    'นำสินค้ากลับ':   '🔄 นำสินค้ากลับ', // เปลี่ยนมาใช้อันนี้ Windows จะเห็นเป็นสีชัวร์ๆ
  };

  fetch(API_JOB_TYPES).then(function(r){ return r.json(); }).then(function(res) {
    if (!res.success) return;
    var sel = document.getElementById('jobtype-filter');
    res.data.forEach(function(t) {
      var o = document.createElement('option');
      o.value = t;
      o.textContent = typeIcon[t] || t;
      sel.appendChild(o);
    });
  });
}
function loadCategories() {
  fetch(API_CATEGORIES).then(function(r){ return r.json(); }).then(function(res) {
    if (!res.success) return;
    var sel = document.getElementById('category-filter');
    if (res.data.main && res.data.main.length) {
      var gMain = document.createElement('optgroup');
      gMain.label = 'หมวดหมู่หลัก';
      res.data.main.forEach(function(c) {
        var o = document.createElement('option');
        o.value = c; o.textContent = c; gMain.appendChild(o);
      });
      sel.appendChild(gMain);
    }
    if (res.data.sub && res.data.sub.length) {
      var gSub = document.createElement('optgroup');
      gSub.label = 'หมวดหมู่ย่อย';
      res.data.sub.forEach(function(c) {
        var o = document.createElement('option');
        o.value = c; o.textContent = c; gSub.appendChild(o);
      });
      sel.appendChild(gSub);
    }
  });
}
function loadMarkers() {
  var q    = document.getElementById('search-box').value.trim();
  var tech = document.getElementById('tech-filter').value;
  var jobType = document.getElementById('jobtype-filter').value;
  var category = document.getElementById('category-filter').value;
  var url  = withToken(API_MARKERS + '?filter=' + currentFilter
           + '&q=' + encodeURIComponent(q)
           + '&tech=' + encodeURIComponent(tech)
           + '&job_type=' + encodeURIComponent(jobType)
           + '&category=' + encodeURIComponent(category));

  markers.forEach(function(m){ m.setMap(null); });
  markers = [];
  document.getElementById('info-panel').classList.remove('is-open');
  document.body.classList.remove('panel-open');

  fetch(url).then(function(r){ return r.json(); }).then(function(res) {
    if (!res.success) return;
    var cnt = res.counts;
    document.getElementById('c-all').textContent    = cnt.all     || 0;
    document.getElementById('c-green').textContent  = cnt.green   || 0;
    document.getElementById('c-yellow').textContent = cnt.yellow  || 0;
    document.getElementById('c-red').textContent    = cnt.red     || 0;
    document.getElementById('c-over').textContent   = cnt.overdue || 0;
    document.getElementById('c-pending').textContent = cnt.pending || 0;

 res.data.forEach(function(d) {
      var m = new google.maps.Marker({
        position: { lat: d.lat, lng: d.lng },
        map: map,
        // เพิ่ม || d.job_type เพื่อป้องกันเคสที่ last_service_type เป็น null หรือค่าว่าง
        icon: makeMarkerIcon(d.marker_status, d.last_service_type || d.job_type),
        title: d.customer_name,
      });
      m.addListener('click', function() { showPanel(d); });
      markers.push(m);
    });
    if (res.data.length > 0) {
      document.getElementById('map-page').style.display = '';
      var bounds = new google.maps.LatLngBounds();
      res.data.forEach(function(d){ bounds.extend({ lat: d.lat, lng: d.lng }); });
      map.fitBounds(bounds);
      if (res.data.length === 1) map.setZoom(15);
      // โหมด token: มีหมุดเดียวเสมอ เปิดกล่องข้อมูลให้เลย ไม่ต้องรอลูกค้ากดเอง
      if (TOKEN_MODE) showPanel(res.data[0]);
    } else if (TOKEN_MODE) {
      // ค้นบิลนี้เจอฝั่ง tgsmartlife (ถึงมี token ให้) แต่ไม่มีงานบริการที่ตรงกันเลยในระบบนี้
      // หน้านี้เป็น iframe ฝังอยู่ใน register-product — ข้อความ "ไม่พบข้อมูล" ต้องไปโชว์ที่
      // หน้า register-product เท่านั้น ไม่ใช่ในนี้ ที่นี่แค่ซ่อนแผนที่เปล่าแล้วส่งสัญญาณบอกหน้าแม่
      document.getElementById('map-page').style.display = 'none';
      if (window.parent !== window) {
        window.parent.postMessage({ source: 'tg_customer_map', status: 'no_data' }, '*');
      }
    }
  });
}

function showPanel(d) {
  var pillCls = { green:'sp-green', yellow:'sp-yellow', red:'sp-red', overdue:'sp-overdue' };
  document.getElementById('info-status-pill').innerHTML =
    '<span class="status-pill ' + (pillCls[d.marker_status]||'sp-green') + '">' + d.marker_label + '</span>';

  currentHistoryName = d.customer_name || '';
  document.getElementById('info-name').textContent    = d.customer_name   || '-';
  document.getElementById('info-bill').textContent    = d.bill_no         || '-';
  document.getElementById('info-phone').textContent   = d.phone           || '-';
  document.getElementById('info-product').textContent = d.product_service || '-';
  document.getElementById('info-install').textContent = fmtDate(d.install_date);
  document.getElementById('info-last-svc').textContent = d.last_service_date ? fmtDate(d.last_service_date) : 'ยังไม่มีข้อมูล';
  document.getElementById('info-due1y').textContent   = d.due_1y  ? fmtDate(d.due_1y) : '-';
  document.getElementById('info-tech').textContent    = d.technician || '-';
  document.getElementById('info-address').textContent = d.address   || '-';

  var el = document.getElementById('info-days-left');
  if (d.days_to_due !== null && d.days_to_due !== undefined) {
    var cls = d.days_to_due > 60  ? 'days-ok'
            : d.days_to_due > 0   ? 'days-warn'
            : d.days_to_due > -30 ? 'days-due' : 'days-over';
    el.className = 'ip-val ' + cls;
    el.textContent = d.days_to_due > 0
      ? 'อีก ' + d.days_to_due + ' วัน'
      : 'เลยกำหนด ' + Math.abs(d.days_to_due) + ' วัน';
  } else {
    el.className = 'ip-val'; el.textContent = '-';
  }

  var navUrl = d.map_link || ('https://www.google.com/maps?q=' + d.lat + ',' + d.lng);
  var btnNavigate = document.getElementById('btn-navigate');
  var btnDetail   = document.getElementById('btn-detail');
  var btnCall     = document.getElementById('btn-call');

  if (TOKEN_MODE) {
    // เข้าจาก webview (register-product) — ตามที่ระบุ:
    // "ดูรายละเอียด" (เดิมชี้ไปหน้า /service ซึ่งต้อง login ใช้กับลูกค้าที่ไม่ login ไม่ได้) เปลี่ยนเป็น
    // "ข้อมูลรับประกันสินค้า" แทน, "นำทาง" เปลี่ยนเป็นเพิ่มเพื่อน LINE, เบอร์โทรใช้เบอร์ Service กลาง
    btnDetail.style.display = '';
    btnDetail.innerHTML = 'เช็คประกัน';
    btnDetail.href = '#';
    btnDetail.onclick = function (e) { e.preventDefault(); openWarrantyModal(); };

    btnNavigate.innerHTML = 'ติดต่อ LINE';
    btnNavigate.href = 'https://line.me/R/ti/p/@tgsmartlife';

    btnCall.href = 'tel:0655588553';
  } else if (IS_EMPLOYEE_VIEW) {
    // มุมมองพนักงานทั่วไป (login ปกติ role=employee, ไม่ใช่ token) — "นำทาง"/เบอร์โทร เหมือนเดิมทุกอย่าง
    // มีแค่ "ดูรายละเอียด" เปลี่ยนเป็น popup แบบจำกัดฟิลด์ แทนการเด้งไปหน้า /service ซึ่งพนักงานเข้าไม่ได้
    btnDetail.style.display = '';
    btnDetail.innerHTML = '<i class="bi bi-list-ul" style="margin-right:4px;"></i>ดูรายละเอียด';
    btnDetail.href = '#';
    btnDetail.onclick = function (e) { e.preventDefault(); openJobDetailModal(d.id); };

    btnNavigate.innerHTML = '<i class="bi bi-navigation-fill" style="margin-right:4px;"></i>นำทาง';
    btnNavigate.href = navUrl;

    btnCall.href = d.phone ? 'tel:' + d.phone.replace(/[^0-9+]/g,'') : '#';
  } else {
    // login ปกติ (เข้าตรงจาก /map หรือ /customer_map) — พฤติกรรมเดิมทุกอย่าง ไม่เปลี่ยนแปลง
    btnDetail.style.display = '';
    btnDetail.innerHTML = '<i class="bi bi-list-ul" style="margin-right:4px;"></i>ดูรายละเอียด';
    btnDetail.href = SERVICE_URL + '?search=' + encodeURIComponent(d.bill_no);
    btnDetail.onclick = null;

    btnNavigate.innerHTML = '<i class="bi bi-navigation-fill" style="margin-right:4px;"></i>นำทาง';
    btnNavigate.href = navUrl;

    btnCall.href = d.phone ? 'tel:' + d.phone.replace(/[^0-9+]/g,'') : '#';
  }

  document.getElementById('info-panel').classList.add('is-open');
  if (window.innerWidth <= 640) document.body.classList.add('panel-open');
  if (window.innerWidth <= 640) map.panTo({ lat: d.lat, lng: d.lng });
}

function fmtDate(str) {
  if (!str) return '-';
  var d = new Date(str); if (isNaN(d)) return str;
  var th = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
  return d.getDate() + ' ' + th[d.getMonth()] + ' ' + (d.getFullYear() + 543);
}

document.getElementById('info-close').addEventListener('click', function() {
  document.getElementById('info-panel').classList.remove('is-open');
  document.body.classList.remove('panel-open');
});
document.getElementById('search-box').addEventListener('input', function() {
  clearTimeout(searchTimeout); searchTimeout = setTimeout(loadMarkers, 400);
});
document.getElementById('tech-filter').addEventListener('change', loadMarkers);
document.getElementById('jobtype-filter').addEventListener('change', function() {
  loadMarkers();
  goToLocation(10);
});
document.getElementById('category-filter').addEventListener('change', function() {
  loadMarkers();
  goToLocation(10);
});
document.getElementById('btn-refresh').addEventListener('click', loadMarkers);

// ── My Location helpers (global scope เพื่อให้ initMap เรียกได้) ──
function showLocLoading() {
  var t = document.getElementById('loc-toast');
  var b = document.getElementById('btn-my-location');
  if (t) t.style.display = 'block';
  if (b) b.classList.add('locating');
}
function hideLocLoading() {
  var t = document.getElementById('loc-toast');
  var b = document.getElementById('btn-my-location');
  if (t) t.style.display = 'none';
  if (b) b.classList.remove('locating');
}
function goToLocation(zoomLevel) {
  if (!navigator.geolocation) { alert('Browser ไม่รองรับ Geolocation'); return; }
  showLocLoading();
  navigator.geolocation.getCurrentPosition(
    function(pos) {
      hideLocLoading();
      if (!map) return;
      var latlng = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      map.panTo(latlng);
      map.setZoom(zoomLevel || 14);
      if (window._myLocMarker) window._myLocMarker.setMap(null);
      window._myLocMarker = new google.maps.Marker({
        position: latlng,
        map: map,
        title: 'ตำแหน่งของคุณ',
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          scale: 10,
          fillColor: '#2563eb',
          fillOpacity: 1,
          strokeColor: '#fff',
          strokeWeight: 3,
        }
      });
    },
    function(err) {
      hideLocLoading();
      if (err.code !== 1) alert('ไม่สามารถดึงตำแหน่งได้: ' + err.message);
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

// ── Zoom buttons + My Location button event ────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.zoom-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (map) map.setZoom(parseInt(btn.dataset.zoom));
    });
  });

  var locBtn = document.getElementById('btn-my-location');
  if (locBtn) {
    locBtn.addEventListener('click', goToLocation);
  }
});

document.querySelectorAll('.stat-chip').forEach(function(chip) {
  chip.addEventListener('click', function() {
    document.querySelectorAll('.stat-chip').forEach(function(c){ c.classList.remove('active'); });
    chip.classList.add('active');
    currentFilter = chip.dataset.f;
    loadMarkers();
  });
});

/* ── History Modal ──────────────────────────────────────── */
var currentHistoryName = '';

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('btn-history').addEventListener('click', function() {
    if (!currentHistoryName) return;
    openHistoryModal(currentHistoryName);
  });
});

function openHistoryModal(name) {
  currentHistoryName = name;
  document.getElementById('hm-name').textContent = name;
  document.getElementById('hm-list').innerHTML = '';
  document.getElementById('hm-loading').style.display = 'block';
  new bootstrap.Modal(document.getElementById('historyModal')).show();

  fetch(withToken(API_HISTORY + '?name=' + encodeURIComponent(name)))
    .then(function(r) { return r.json(); })
    .then(function(res) {
      document.getElementById('hm-loading').style.display = 'none';
      if (!res.success || !res.data.length) {
        document.getElementById('hm-list').innerHTML = '<div class="text-center text-muted py-3 small">ไม่พบประวัติการให้บริการ</div>';
        return;
      }
      var html = '';
      res.data.forEach(function(r) {
        var statusCls = r.status === 'เสร็จแล้ว' ? 'hs-done'
                      : (r.status === 'รอดำเนินการ' || r.status === 'ยืนยันแล้ว') ? 'hs-pending' : 'hs-other';
        var dateStr = r.start_time ? r.start_time.substr(0,10) : (r.install_date || '-');
        var detailUrl = CHANGPROM_URL + r.id;
        if (TOKEN_MODE) {
          detailUrl += '?bill_number=' + encodeURIComponent(TOKEN_BILL_NO) + '&from=register-product';
        }else{
          detailUrl += '?from=map';
        }
        var detailBtn = '<a href="' + detailUrl + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;padding:3px 10px;flex-shrink:0;"><i class="bi bi-box-arrow-up-right me-1"></i>รายละเอียด</a>';
        html += '<div class="hrow">'
          + '<div class="hrow-top">'
          + '<span class="hrow-bill">' + (r.bill_no || '#'+r.id) + '</span>'
          + '<span class="d-flex" style="gap:4px;"><span class="hrow-type">' + (r.job_type || '-') + '</span><span class="hrow-status ' + statusCls + '">' + (r.status || '-') + '</span></span>'
          + '</div>'
          + '<div class="hrow-date"><i class="bi bi-calendar3 me-1"></i>' + dateStr + '</div>'
          + (r.product_service ? '<div class="hrow-prod">' + r.product_service + '</div>' : '')
          + '<div class="hrow-tech-row">'
          + (r.technician ? '<span class="hrow-tech"><i class="bi bi-tools me-1"></i>' + r.technician + '</span>' : '<span></span>')
          + detailBtn
          + '</div>'
          + '</div>';
      });
      document.getElementById('hm-list').innerHTML = html;
    })
    .catch(function() {
      document.getElementById('hm-loading').style.display = 'none';
      document.getElementById('hm-list').innerHTML = '<div class="text-center text-danger py-3 small">โหลดข้อมูลไม่สำเร็จ</div>';
    });
}

/* ── รายละเอียดงาน (มุมมองพนักงานทั่วไป) — จำกัดฟิลด์ตามที่ระบุ ────────── */
function openJobDetailModal(id) {
  document.getElementById('jd-body').innerHTML = '';
  document.getElementById('jd-loading').style.display = 'block';
  new bootstrap.Modal(document.getElementById('jobDetailModal')).show();

  fetch(API_JOB_DETAIL + '/' + id)
    .then(function (r) { return r.json(); })
    .then(function (res) {
      document.getElementById('jd-loading').style.display = 'none';
      if (!res.success) {
        document.getElementById('jd-body').innerHTML = '<div class="text-center text-muted py-3 small">' + (res.message || 'ไม่พบข้อมูล') + '</div>';
        return;
      }
      var d = res.data;
      var mapsUrl = '';
      if (d.location) {
        var raw = ('' + d.location).trim();
        mapsUrl = /^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/.test(raw) ? ('https://www.google.com/maps?q=' + raw) : raw;
      }
      var loc = mapsUrl
        ? '<a href="' + mapsUrl + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt me-1"></i>เปิด Maps</a>'
        : '-';
      var html = wCol('เลขที่บิล', d.bill_no)
        + wCol('ชื่อลูกค้า', d.customer_name)
        + wCol('เบอร์โทร', d.phone)
        + wCol('วันที่ซื้อ', d.purchase_date ? d.purchase_date.substr(0, 10) : '')
        + wCol('ที่อยู่', d.address)
        + '<div class="mb-2"><div class="small text-muted">Location</div>' + loc + '</div>'
        + wCol('ช่าง', d.technician)
        + wCol('ทีม', d.team)
        + wCol('สาขา', d.branch)
        + wCol('รหัสพนักงาน', d.sale_code)
        + wCol('สินค้า/บริการ', d.product_service)
        + wCol('แท็ก', d.tags);
      document.getElementById('jd-body').innerHTML = html;
    })
    .catch(function () {
      document.getElementById('jd-loading').style.display = 'none';
      document.getElementById('jd-body').innerHTML = '<div class="text-center text-danger py-3 small">โหลดข้อมูลไม่สำเร็จ</div>';
    });
}

/* ── ข้อมูลรับประกันสินค้า (โหมด token) ─────────────────── */
function wCol(label, val) {
  return '<div class="mb-2"><div class="small text-muted">' + label + '</div><div class="fw-medium">' + (val || '-') + '</div></div>';
}
function openWarrantyModal() {
  document.getElementById('wm-body').innerHTML = '';
  document.getElementById('wm-loading').style.display = 'block';
  new bootstrap.Modal(document.getElementById('warrantyModal')).show();

  fetch(withToken(API_WARRANTY))
    .then(function(r) { return r.json(); })
    .then(function(res) {
      document.getElementById('wm-loading').style.display = 'none';
      if (!res.success) {
        document.getElementById('wm-body').innerHTML = '<div class="text-center text-muted py-3 small">' + (res.message || 'ไม่พบข้อมูลการลงทะเบียนรับประกัน') + '</div>';
        return;
      }
      var d = res.data;
      var html = wCol('สินค้า', d.product_name)
        + wCol('เลขที่บิล', d.bill_number)
        + wCol('เบอร์โทรที่ลงทะเบียน', d.tel_cus)
        + (d.tel_idcart ? wCol('เลขบัตรประชาชน', d.tel_idcart) : '')
        + (d.product_warranty ? wCol('เงื่อนไขการรับประกัน', d.product_warranty) : '')
        + (d.detail ? wCol('รายละเอียดเพิ่มเติม', d.detail) : '')
        + (d.link ? '<div class="mb-2"><a href="' + d.link + '" target="_blank" rel="noopener">คู่มือการใช้งาน</a></div>' : '')
        + (d.file_path ? '<div class="mb-2"><a href="' + d.file_path + '" target="_blank" rel="noopener">คู่มือการใช้งาน (ไฟล์แนบ)</a></div>' : '')
        + (d.created ? wCol('วันที่ลงทะเบียน', d.created.substr(0, 10)) : '')
        + (d.updated ? wCol('แก้ไขล่าสุด', d.updated.substr(0, 10)) : '');
      document.getElementById('wm-body').innerHTML = html;
    })
    .catch(function() {
      document.getElementById('wm-loading').style.display = 'none';
      document.getElementById('wm-body').innerHTML = '<div class="text-center text-danger py-3 small">โหลดข้อมูลไม่สำเร็จ</div>';
    });
}

/* ── เก็บ customer_name ตอน showPanel ─────────────────── */

// โหลด Font Awesome 6 CDN
(function() {
  var fa = document.createElement('link');
  fa.rel  = 'stylesheet';
  fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';
  document.head.appendChild(fa);
})();

(function() {
  var s = document.createElement('script');
  var k = GMAPS_KEY ? ('key=' + GMAPS_KEY + '&') : '';
  s.src = 'https://maps.googleapis.com/maps/api/js?' + k + 'callback=initMap&loading=async&language=th&region=TH';
  s.async = true; s.defer = true;
  document.head.appendChild(s);
})();

// scroll ลงล่างสุดเมื่อเปิดบน mobile
if (window.innerWidth <= 640) {
  window.addEventListener('load', function() {
    setTimeout(function() {
      window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }, 300);
  });
}
</script>

<!-- ── Bootstrap History Modal ───────────────────────────── -->
<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">ประวัติการให้บริการ</h5>
          <small id="hm-name" class="text-muted"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="hm-loading" class="text-center py-4 text-muted small">
          <div class="spinner-border spinner-border-sm me-2"></div>กำลังโหลด...
        </div>
        <div id="hm-list"></div>
      </div>
    </div>
  </div>
</div>

<!-- ── รายละเอียดงาน (มุมมองพนักงานทั่วไปเท่านั้น) — แบบจำกัดฟิลด์ ไม่รวมประเภทงาน/สถานะ/
     วันที่นัด/เวลา/หมายเหตุช่าง/หมายเหตุบิล และไม่รวมตำแหน่งที่ช่างบันทึกการเข้างาน ──────── -->
<div class="modal fade" id="jobDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0"><i class="bi bi-info-circle me-2"></i>รายละเอียด</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="jd-loading" class="text-center py-4 text-muted small">
          <div class="spinner-border spinner-border-sm me-2"></div>กำลังโหลด...
        </div>
        <div id="jd-body"></div>
      </div>
    </div>
  </div>
</div>

<!-- ── ข้อมูลรับประกันสินค้า (โหมด token เท่านั้น) — แทนที่ลิงก์ "รายละเอียด" เดิม
     ที่ชี้ไปหน้า admin ซึ่งใช้ไม่ได้กับลูกค้าที่ไม่ได้ login อยู่แล้ว ──────────────── -->
<div class="modal fade" id="warrantyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0"><i class="bi bi-shield-check me-2 text-success"></i>ข้อมูลรับประกันสินค้า</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="wm-loading" class="text-center py-4 text-muted small">
          <div class="spinner-border spinner-border-sm me-2"></div>กำลังโหลด...
        </div>
        <div id="wm-body"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php else: ?>

<!-- employee ที่ login แล้วแต่ยังไม่ได้รับสิทธิ์ดูแผนที่ (map_access = 0) -->
<div class="d-flex flex-column align-items-center justify-content-center text-center px-3" style="height:calc(100vh - 60px);">
  <i class="bi bi-hourglass-split text-warning" style="font-size:3rem;"></i>
  <h5 class="fw-bold mt-3 mb-1">รอการอนุมัติสิทธิ์เข้าถึงแผนที่ลูกค้า</h5>
  <p class="text-muted mb-0">บัญชีของคุณยังไม่ได้รับสิทธิ์ดูแผนที่ลูกค้า กรุณาติดต่อผู้ดูแลระบบ</p>
</div>
<?php endif; ?>
<?php endif; /* ปิด token_expired if/else */ ?>
</body>
</html>

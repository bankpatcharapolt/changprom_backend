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
  display: flex;
  align-items: center;
  padding: 7px 12px;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}
#search-box { flex: 1; min-width: 0; margin-right: 8px; }
#tech-filter { max-width: 150px; margin-right: 8px; }

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
  #tech-filter { display: none; }
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
.hrow-tech  { font-size: .75rem; color: #6b7280; margin-top: 2px; }
.hrow-status { font-size: .72rem; border-radius: 10px; padding: 2px 8px; }
.hs-done    { background: #d1fae5; color: #065f46; }
.hs-pending { background: #fef9c3; color: #713f12; }
.hs-other   { background: #f3f4f6; color: #374151; }
</style>

<div id="map-page">

  <!-- Stats bar -->
  <div id="stats-bar">
    <div class="stat-chip sc-all active" data-f="all">
   
      <span class="chip-lbl">ทั้งหมด</span>
      <span class="chip-num" id="c-all">0</span>
    </div>
    <div class="stat-chip sc-green" data-f="green">
    
      <span class="chip-lbl">ติดตั้งแล้ว</span>
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
    <button class="btn btn-sm btn-outline-secondary flex-shrink-0" id="btn-refresh">
      <i class="bi bi-arrow-clockwise"></i>
    </button>
  </div>

  <!-- Map + Panel -->
  <div id="map-body">
    <div id="map-wrap"><div id="map"></div></div>

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
var API_MARKERS = '<?= site_url("customer_map/api_markers") ?>';
var API_TECHS    = '<?= site_url("customer_map/api_techs") ?>';
var API_HISTORY  = '<?= site_url("customer_map/api_history") ?>';
var SERVICE_URL = '<?= site_url("service") ?>';
 var GMAPS_KEY   = '';
//var GMAPS_KEY   = 'AIzaSyBiDeosZazrjT1PMnhs7TuKOpjJFDoGUJg';// prod

function makeMarkerIcon(status) {
  var colors = {
    green:   { pin:'#16a34a', border:'#14532d' },
    yellow:  { pin:'#eab308', border:'#92400e' },
    red:     { pin:'#dc2626', border:'#7f1d1d' },
    overdue: { pin:'#7c3aed', border:'#4c1d95' },
  };
  var c = colors[status] || colors.green;
  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="48" viewBox="0 0 36 48">'
    + '<path d="M18 0C8 0 0 8 0 18c0 13 18 30 18 30S36 31 36 18C36 8 28 0 18 0z" fill="' + c.pin + '" stroke="' + c.border + '" stroke-width="1.5"/>'
    + '<circle cx="18" cy="18" r="7" fill="#fff" opacity=".35"/>'
    + '</svg>';
  return {
    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
    scaledSize: new google.maps.Size(36, 48),
    anchor:     new google.maps.Point(18, 48),
  };
}

var map, markers = [], currentFilter = 'all', searchTimeout;

function initMap() {
  map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: 13.7563, lng: 100.5018 },
    zoom: 11,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: false,
    zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_CENTER },
  });
  loadTechs();
  loadMarkers();
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

function loadMarkers() {
  var q    = document.getElementById('search-box').value.trim();
  var tech = document.getElementById('tech-filter').value;
  var url  = API_MARKERS + '?filter=' + currentFilter
           + '&q=' + encodeURIComponent(q)
           + '&tech=' + encodeURIComponent(tech);

  markers.forEach(function(m){ m.setMap(null); });
  markers = [];
  document.getElementById('info-panel').classList.remove('is-open');

  fetch(url).then(function(r){ return r.json(); }).then(function(res) {
    if (!res.success) return;
    var cnt = res.counts;
    document.getElementById('c-all').textContent    = cnt.all     || 0;
    document.getElementById('c-green').textContent  = cnt.green   || 0;
    document.getElementById('c-yellow').textContent = cnt.yellow  || 0;
    document.getElementById('c-red').textContent    = cnt.red     || 0;
    document.getElementById('c-over').textContent   = cnt.overdue || 0;

    res.data.forEach(function(d) {
      var m = new google.maps.Marker({
        position: { lat: d.lat, lng: d.lng },
        map: map,
        icon: makeMarkerIcon(d.marker_status),
        title: d.customer_name,
      });
      m.addListener('click', function() { showPanel(d); });
      markers.push(m);
    });

    if (res.data.length > 0) {
      var bounds = new google.maps.LatLngBounds();
      res.data.forEach(function(d){ bounds.extend({ lat: d.lat, lng: d.lng }); });
      map.fitBounds(bounds);
      if (res.data.length === 1) map.setZoom(15);
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
  document.getElementById('btn-navigate').href = navUrl;
  document.getElementById('btn-detail').href   = SERVICE_URL + '?search=' + encodeURIComponent(d.bill_no);
  document.getElementById('btn-call').href     = d.phone ? 'tel:' + d.phone.replace(/[^0-9+]/g,'') : '#';

  document.getElementById('info-panel').classList.add('is-open');
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
});
document.getElementById('search-box').addEventListener('input', function() {
  clearTimeout(searchTimeout); searchTimeout = setTimeout(loadMarkers, 400);
});
document.getElementById('tech-filter').addEventListener('change', loadMarkers);
document.getElementById('btn-refresh').addEventListener('click', loadMarkers);

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

  fetch(API_HISTORY + '?name=' + encodeURIComponent(name))
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
        html += '<div class="hrow">'
          + '<div class="hrow-top">'
          + '<span class="hrow-bill">' + (r.bill_no || '#'+r.id) + '</span>'
          + '<span class="d-flex gap-1"><span class="hrow-type">' + (r.job_type || '-') + '</span><span class="hrow-status ' + statusCls + '">' + (r.status || '-') + '</span></span>'
          + '</div>'
          + '<div class="hrow-date"><i class="bi bi-calendar3 me-1"></i>' + dateStr + '</div>'
          + (r.product_service ? '<div class="hrow-prod">' + r.product_service + '</div>' : '')
          + (r.technician ? '<div class="hrow-tech"><i class="bi bi-tools me-1"></i>' + r.technician + '</div>' : '')
          + '</div>';
      });
      document.getElementById('hm-list').innerHTML = html;
    })
    .catch(function() {
      document.getElementById('hm-loading').style.display = 'none';
      document.getElementById('hm-list').innerHTML = '<div class="text-center text-danger py-3 small">โหลดข้อมูลไม่สำเร็จ</div>';
    });
}

/* ── เก็บ customer_name ตอน showPanel ─────────────────── */

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

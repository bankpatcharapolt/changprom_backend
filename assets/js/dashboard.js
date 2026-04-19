$(document).ready(function() {
// BASE is set in header
const statusTH = {pending:'รอดำเนินการ',confirmed:'ยืนยันแล้ว',in_progress:'กำลังดำเนินการ',completed:'เสร็จสิ้น',cancelled:'ยกเลิก'};
const statusColor = {pending:'warning',confirmed:'primary',in_progress:'purple',completed:'success',cancelled:'danger'};
let monthlyChart = null;

function loadStats() {
  document.getElementById('last-updated').textContent = 'กำลังโหลด...';
  $.get(BASE + 'dashboard/api_stats', function(r) {
    if (!r.success) return;
    var d = r.data;
    document.getElementById('kpi-today').textContent = d.today_total;
    document.getElementById('kpi-open').textContent  = d.open_jobs;
    document.getElementById('kpi-week').textContent  = d.this_week;
    document.getElementById('kpi-overdue').textContent = d.overdue;
    document.getElementById('kpi-date').textContent  = d.today;
    document.getElementById('s-pending').textContent   = d.today_pending;
    // document.getElementById('s-confirmed').textContent = d.today_confirmed;
    document.getElementById('s-inprog').textContent    = d.today_inprog;
    document.getElementById('s-done').textContent      = d.today_done;
    var pct = d.today_total > 0 ? Math.round(d.today_done / d.today_total * 100) : 0;
    document.getElementById('s-pct').textContent = pct + '%';
    document.getElementById('s-bar').style.width = pct + '%';
    document.getElementById('last-updated').textContent = 'อัปเดตล่าสุด: ' + new Date().toLocaleTimeString('th-TH');
    document.getElementById('tech-date-label').textContent = d.today;

    // Tech load
    renderTechLoad(d.tech_load);

    // Monthly chart
    var labels = d.monthly.map(m => {
      var parts = m.month.split('-');
      var months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
      return months[parseInt(parts[1])] + ' ' + (parseInt(parts[0])+543);
    });
    var counts = d.monthly.map(m => m.count);
    if (monthlyChart) monthlyChart.destroy();
    monthlyChart = new Chart(document.getElementById('monthlyChart'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'จำนวนงาน',
          data: counts,
          backgroundColor: 'rgba(99,102,241,0.7)',
          borderColor: '#6366f1',
          borderWidth: 1.5,
          borderRadius: 6,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 } },
          x: { grid: { display: false } }
        }
      }
    });

    // Upcoming
    var upHtml = '';
    if (d.upcoming.length === 0) {
      upHtml = '<div class="text-muted text-center py-3">ไม่มีงานใน 7 วันข้างหน้า</div>';
    } else {
      d.upcoming.forEach(function(j) {
        var dateStr = j.install_date || '';
        var timeStr = j.install_time ? j.install_time.substr(0,5) : '';
        upHtml += `<div class="upcoming-item ${j.status}">
          <div class="d-flex justify-content-between">
            <span class="fw-medium small">${j.bill_no||'#'+j.id} — ${j.customer_name||'-'}</span>
            <span class="badge bg-secondary" style="font-size:.68rem">${statusTH[j.status]||j.status}</span>
          </div>
          <div class="text-muted" style="font-size:.78rem">
            <i class="bi bi-calendar2 me-1"></i>${dateStr} ${timeStr}
            ${j.technician ? '<i class="bi bi-person me-1 ms-2"></i>'+j.technician : ''}
          </div>
        </div>`;
      });
    }
    document.getElementById('upcoming-body').innerHTML = upHtml;
  });
}

function renderTechLoad(techs) {
  if (!techs || techs.length === 0) {
    document.getElementById('tech-load-body').innerHTML = '<div class="text-muted text-center py-3"><i class="bi bi-person-x fs-3 d-block mb-2"></i>ไม่มีช่างในวันนี้</div>';
    return;
  }
  var maxJobs = Math.max(...techs.map(t => parseInt(t.job_count)));
  var html = '';
  techs.forEach(function(t) {
    var cnt = parseInt(t.job_count);
    var done = parseInt(t.done);
    var open = parseInt(t.open);
    var pct = maxJobs > 0 ? Math.round(cnt/maxJobs*100) : 0;
    var barColor = cnt >= 4 ? '#ef4444' : cnt <= 2 ? '#f59e0b' : '#10b981';
    var badgeClass = cnt >= 4 ? 'danger' : cnt <= 2 ? 'warning text-dark' : 'success';
    var alert = cnt >= 4 ? '<span class="badge bg-danger ms-1" style="font-size:.65rem">เกิน 4</span>' :
                cnt <= 2 ? '<span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">น้อยกว่า 3</span>' : '';
    html += `<div class="mb-3">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="fw-medium">${t.technician}${alert}</span>
        <div>
          <span class="badge bg-${badgeClass} me-1">${cnt} งาน</span>
          <span class="badge bg-success me-1">${done} เสร็จ</span>
          <span class="badge bg-secondary">${open} ค้าง</span>
        </div>
      </div>
      <div class="progress tech-bar">
        <div class="progress-bar" style="width:${pct}%;background:${barColor}"></div>
      </div>
    </div>`;
  });
  document.getElementById('tech-load-body').innerHTML = html;
}

loadStats();
setInterval(loadStats, 60000); // auto refresh ทุก 1 นาที
});

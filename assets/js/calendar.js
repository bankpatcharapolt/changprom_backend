
var calendar      = null;
var currentEvent  = null;
var assignModal   = null;   // singleton
var eventModal    = null;   // singleton

function getAssignModal() {
  if (!assignModal) assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
  return assignModal;
}
function getEventModal() {
  if (!eventModal) eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
  return eventModal;
}

var statusColorMap = {
  'รอดำเนินการ'    : '#f59e0b',
  'ยืนยันแล้ว'     : '#3b82f6',
  'กำลังดำเนินงาน' : '#8b5cf6',
  'เสร็จแล้ว'      : '#10b981',
  'เลื่อนนัด'      : '#6b7280',
  'ยกเลิกนัด'      : '#ef4444',
};

// ── ตรวจสอบ mobile ──────────────────────────────────────────
function isMobile() { return window.innerWidth < 576; }

function getInitialView() {
  return isMobile() ? 'listWeek' : 'dayGridMonth';
}


// function loadTechnicians() {
//   $.get(BASE + 'dashboard/api_technicians', function(r) {
//     var data = r.data || r;
//     var opts = '<option value="">-- ช่างทั้งหมด --</option>';
//     data.forEach(function(t) {
//       opts += '<option value="' + t.reg_name + '">' + t.reg_name + '</option>';
//     });
//     $('#filter-tech').html(opts);
//   });
// }
function loadTechnicians() {
  $.get(BASE + 'dashboard/api_technicians', function(r) {
    var data = r.data || r;
    var opts = '<option value="">-- ช่างทั้งหมด --</option>';
    data.forEach(function(t) {
      opts += '<option value="' + t.reg_name + '">' + t.reg_name + '</option>';
    });

    var el = document.getElementById('filter-tech');


    if (el.tomselect) {
      el.tomselect.destroy();
    }

    $('#filter-tech').html(opts);

    new TomSelect('#filter-tech', {
      placeholder: '-- ช่างทั้งหมด --',
      allowEmptyOption: true,
      create: false,
      sortField: { field: 'text', direction: 'asc' }
    });
  });
}

// ── Init FullCalendar ────────────────────────────────────────
function initCalendar() {
  var calEl = document.getElementById('calendar');
  if (!calEl || typeof FullCalendar === 'undefined') {
    setTimeout(initCalendar, 200);
    return;
  }

  calendar = new FullCalendar.Calendar(calEl, {
    locale       : 'th',
    initialView  : getInitialView(),
    height       : 'auto',
    dayMaxEvents : isMobile() ? 3 : 4,
    displayEventTime: true,
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    views: {
      dayGridMonth: { displayEventTime: true },
      timeGridWeek: { displayEventTime: false },
      listWeek:     { displayEventTime: true },
    },

    headerToolbar: {
      left  : 'prev,next today',
      center: 'title',
     
      right : isMobile() ? '' : 'dayGridMonth,timeGridWeek,listWeek'
    },

    buttonText: {
      today: 'วันนี้', month: 'เดือน',
      week : 'สัปดาห์', day: 'วัน', list: 'รายการ'
    },

    // ── ดึง events ──────────────────────────────────────────
    events: function(info, successCb, failureCb) {
      var tech   = $('#filter-tech').val();
      var status = $('#filter-status').val();
      $.get(BASE + 'dashboard/api_calendar_events', {
        start: info.startStr, end: info.endStr
      }, function(events) {
        if (!Array.isArray(events)) events = [];
        if (tech)   events = events.filter(function(e){ return e.extendedProps.technician === tech; });
        if (status) events = events.filter(function(e){ return e.extendedProps.status === status; });
        successCb(events);
      }).fail(function(){ failureCb(); });
    },

 
    eventClick: function(info) { showEventDetail(info.event); },

    // ── คลิกวัน ─────────────────────────────────────────────
    dateClick: function(info) { window.openAssignModal(info.dateStr); },

    // ── tooltip ─────────────────────────────────────────────
    eventDidMount: function(info) {
      var p = info.event.extendedProps;
      info.el.title = [
        'บิล: '    + (p.bill_no       || '-'),
        'ลูกค้า: ' + (p.customer_name || '-'),
        'ช่าง: '   + (p.technician    || '-'),
        'สถานะ: '  + (p.status        || '-'),
      ].join('\n');
    },

    // ── auto switch view เมื่อหมุนจอ ─────────────────────────
    windowResize: function() {
      var targetView = isMobile() ? 'listWeek' : 'dayGridMonth';
      if (calendar.view.type !== targetView) {
        calendar.changeView(targetView);
        // sync pill
        document.querySelectorAll('#viewToggle button').forEach(function(b) {
          b.classList.toggle('active', b.getAttribute('onclick').indexOf(targetView) > -1);
        });
      }
    }
  });

  calendar.render();
}

// ── สลับ view จาก pill (มือถือ) ─────────────────────────────
window.switchView = function(viewName, btn) {
  if (calendar) calendar.changeView(viewName);
  document.querySelectorAll('#viewToggle button').forEach(function(b) {
    b.classList.remove('active');
  });
  if (btn) btn.classList.add('active');
};

// ── Refresh ──────────────────────────────────────────────────
window.refreshCalendar = function() {
  if (calendar) calendar.refetchEvents();
};

// ── เปิด modal ลงคิว ─────────────────────────────────────────
window.openAssignModal = function(date, time) {
  $('#assign-job-id').val('');
  $('#job-search-input').val('');
  $('#job-results').html('<div class="text-center text-muted py-3 small">พิมพ์เพื่อค้นหา...</div>');
  $('#selected-job-preview').addClass('d-none');
  var _ts = document.getElementById('assign-tech-select') && document.getElementById('assign-tech-select').tomselect;
  if (_ts) { _ts.clear(true); } else { $('#assign-tech-select').prop('selectedIndex', 0); }
  $('#assign-tech-input').val('');
  $('#assign-tech-id').val('');
  $('#assign-zone').val('');
  $('#assign-map-link').val('');
  if (typeof $ !== 'undefined') $('#btn-open-map').hide();
  $('#assign-job-type').val('');
  $('#assign-tech-wage').val('');
  $('#assign-tech-note').val('');
  $('#assign-date').val(date || '');
  $('#assign-time').val(time || '');
  $('#assignTitle').html('<i class="bi bi-calendar-plus me-2"></i>ลงคิวงาน');
  $('#step1').show(); // ✅ แน่ใจว่า step1 โชว์เสมอตอนลงคิวใหม่
  getAssignModal().show();
};

// ── ค้นหางาน ─────────────────────────────────────────────────
window.searchJobs = function() {
  var q = $('#job-search-input').val();
  $('#job-results').html('<div class="text-center py-2"><div class="spinner-border spinner-border-sm"></div></div>');
  $.get(BASE + 'dashboard/api_search_jobs', { q: q }, function(r) {
    if (!r.success || !r.data.length) {
      $('#job-results').html('<div class="text-center text-muted py-3 small">ไม่พบข้อมูล</div>');
      return;
    }
    var html = '';
    r.data.forEach(function(j) {
      var disabled = j.has_technician;
      if (disabled) {
        // รายการที่มีช่างแล้ว — disable ไม่ให้คลิก
        html += '<div class="job-result-item job-result-disabled">'
          + '<div class="d-flex justify-content-between align-items-start">'
          + '<div>'
          + '<div class="fw-medium small text-muted">' + (j.bill_no || '#' + j.id) + ' — ' + (j.customer_name || '-') + '</div>'
          + '<div style="font-size:.75rem;color:#9ca3af">'
          + (j.job_type ? '<span class="badge bg-secondary me-1 opacity-50">' + j.job_type + '</span>' : '')
          + (j.product_service || '') + ' | ' + (j.phone || '')
          + '</div>'
          + '</div>'
          + '<span class="badge ms-2 flex-shrink-0" style="background:#f3f4f6;color:#6b7280;font-size:.7rem;white-space:nowrap">'
          + '<i class="bi bi-person-check me-1"></i>มีช่างแล้ว: ' + j.technician
          + '</span>'
          + '</div></div>';
      } else {
        // รายการปกติ — คลิกได้
        html += '<div class="job-result-item"'
          + ' onclick="window.selectJob('
          + j.id
          + ',\'' + esc(j.bill_no) + '\''
          + ',\'' + esc(j.customer_name) + '\''
          + ',\'' + (j.install_date || '') + '\''
          + ',\'' + (j.install_time ? j.install_time.substr(0,5) : '') + '\''
          + ',\'' + esc(j.technician) + '\''
          + ',\'' + esc(j.job_type) + '\''
          + ')">'
          + '<div class="fw-medium small">' + (j.bill_no || '#' + j.id) + ' — ' + (j.customer_name || '-') + '</div>'
          + '<div class="text-muted" style="font-size:.75rem">'
          + (j.job_type ? '<span class="badge bg-secondary me-1">' + j.job_type + '</span>' : '')
          + (j.product_service || '') + ' | ' + (j.phone || '') + ' | ช่าง: ' + (j.technician || '-')
          + '</div></div>';
      }
    });
    $('#job-results').html(html);
  });
};

// ── เลือกงาน ─────────────────────────────────────────────────
window.selectJob = function(id, bill, name, date, time, tech, jobType) {
  $('#assign-job-id').val(id);
  $('.job-result-item').removeClass('selected');
  event.currentTarget.classList.add('selected');
  $('#sel-job-text').text(bill + ' — ' + name);
  $('#selected-job-preview').removeClass('d-none');
  if (date)    $('#assign-date').val(date);
  if (time)    $('#assign-time').val(time);
  if (tech)    { $('#assign-tech-select').val(tech); $('#assign-tech-input').val(tech); }
  if (jobType) $('#assign-job-type').val(jobType);
};

// ── บันทึกคิว ─────────────────────────────────────────────────
window.saveAssign = function(force) {
  var id      = $('#assign-job-id').val();
  var jobType = $('#assign-job-type').val();
  var techId  = $('#assign-tech-id').val();
  var tech    = $('#assign-tech-select').val() || $('#assign-tech-input').val().trim();
  var date    = $('#assign-date').val();
  var time    = $('#assign-time').val();
  var wage    = $('#assign-tech-wage').val();
  var note    = $('#assign-tech-note').val().trim();
  var zone    = $('#assign-zone').val().trim();
  var mapLink = $('#assign-map-link').val().trim();

  if (!id)      { Swal.fire('แจ้งเตือน', 'กรุณาเลือกงานก่อน', 'warning'); return; }
  if (!jobType) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกประเภทงาน', 'warning'); return; }
  if (!tech)    { Swal.fire('แจ้งเตือน', 'กรุณาระบุชื่อช่าง', 'warning'); return; }
  if (!date)    { Swal.fire('แจ้งเตือน', 'กรุณาเลือกวันที่', 'warning'); return; }

  $.ajax({
    url: BASE + 'dashboard/api_assign', method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
      job_id:        id,
      job_type:      jobType,
      technician:    tech,
      technician_id: techId ? parseInt(techId) : null,
      install_date:  date,
      install_time:  time,
      tech_wage:     wage ? parseFloat(wage) : null,
      tech_note:     note || null,
      tech_zone:     zone || null,
      map_link:      mapLink || null,
      force:         force || false,
    }),
    success: function(r) {
      if (r.success) {
        var _el = document.getElementById('assignModal');
        _el.addEventListener('hidden.bs.modal', function _ref() {
          _el.removeEventListener('hidden.bs.modal', _ref);
          if (calendar) calendar.refetchEvents();
        });
        getAssignModal().hide();
            Swal.fire({ icon:'success', title:'บันทึกคิวสำเร็จ', timer:1500, showConfirmButton:false })
          .then(function() { window.location.reload(); });

      } else if (r.duplicate) {
        Swal.fire({
          icon: 'warning', title: 'พบงานซ้ำ!', text: r.message,
          showCancelButton: true, confirmButtonColor: '#f59e0b',
          confirmButtonText: 'บันทึกต่อ (ยืนยัน)', cancelButtonText: 'ยกเลิก',
        }).then(function(res) { if (res.isConfirmed) window.saveAssign(true); });
      } else {
        Swal.fire('ข้อผิดพลาด', r.message, 'error');
      }
    },
    error: function() { Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อได้', 'error'); }
  });
};


window.editEvent = function() {
  if (!currentEvent) return;
  var p  = currentEvent.extendedProps;
  var id = currentEvent.id; 

  getEventModal().hide();

  setTimeout(function() {

    $('#assign-job-id').val(id);

    // ── fill ข้อมูลคิว ──
    $('#assign-job-type').val(p.job_type      || '');
    // TomSelect: set value programmatically
    var _ts = document.getElementById('assign-tech-select').tomselect;
    if (_ts) {
      _ts.clear(true);
      if (p.technician) _ts.setValue(p.technician);
    } else {
      $('#assign-tech-select').val(p.technician || '');
    }
    $('#assign-tech-input').val(p.technician  || '');
    $('#assign-tech-id').val(p.technician_id  || '');
    $('#assign-zone').val(p.tech_zone         || '');
    var mapLink = p.map_link || '';
    $('#assign-map-link').val(mapLink);
    if (mapLink) { $('#btn-open-map').show(); } else { $('#btn-open-map').hide(); }
    $('#assign-tech-wage').val(p.tech_wage    || '');
    $('#assign-tech-note').val(p.tech_note    || '');
    $('#assign-date').val(p.install_date      || '');
    $('#assign-time').val(p.install_time ? p.install_time.substr(0, 5) : '');

    // ── ชื่อ modal ──
    $('#assignTitle').html('<i class="bi bi-pencil-square me-2"></i>แก้ไขคิว');

    // ── แสดง preview บิล ──
    $('#sel-job-text').text((p.bill_no || '#' + id) + ' — ' + (p.customer_name || ''));
    $('#selected-job-preview').removeClass('d-none');

    getAssignModal().show();

    // ── ล็อค step 1 หลัง modal แสดง: disable input + ซ่อน search box ──
    document.getElementById('assignModal').addEventListener('shown.bs.modal', function lockStep1() {
      $('#step1 input, #step1 button').prop('disabled', true);
      $('#job-results').hide();
      $('#job-search-input').attr('placeholder', '— ไม่สามารถเปลี่ยนบิลได้ในโหมดแก้ไข —');
      // ลบ listener ทิ้งหลังทำงานครั้งแรก
      document.getElementById('assignModal').removeEventListener('shown.bs.modal', lockStep1);
    }, { once: true });
  }, 400);
};
new TomSelect('#filter-tech', {
  placeholder: '-- ช่างทั้งหมด --',
  allowEmptyOption: true,
  create: false,
  sortField: { field: 'text', direction: 'asc' }
});
// ── ลบคิว ────────────────────────────────────────────────────
window.deleteEvent = function() {
  if (!currentEvent) return;
  var id = currentEvent.id;
  getEventModal().hide();
  Swal.fire({
    title: 'ลบคิวงานนี้?', text: 'วันที่และช่างจะถูกล้างออก', icon: 'warning',
    showCancelButton: true, confirmButtonColor: '#ef4444',
    confirmButtonText: 'ลบคิว', cancelButtonText: 'ยกเลิก'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    $.ajax({
      url: BASE + 'dashboard/api_assign', method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ job_id: id, technician: '', install_date: null, install_time: null }),
      success: function(res) {
        if (res.success) {
          if (calendar) calendar.refetchEvents();
        

              Swal.fire({ icon:'success', title:'ลบคิวสำเร็จ', timer:1500, showConfirmButton:false })
          .then(function() { window.location.reload(); });
        }
      }
    });
  });
};

// ── แสดง event detail ────────────────────────────────────────
function showEventDetail(event) {
  currentEvent = event;
  var p     = event.extendedProps;
  var color = statusColorMap[p.status] || '#6b7280';

  var techPhone = p.tech_phone
    ? '<a href="tel:' + p.tech_phone + '" class="text-decoration-none"><i class="bi bi-telephone me-1"></i>' + p.tech_phone + '</a>'
    : '-';

  var wageHtml = p.tech_wage && p.tech_wage > 0
    ? '<span class="text-danger fw-bold">฿' + parseFloat(p.tech_wage).toLocaleString('th-TH', {minimumFractionDigits:2}) + '</span>'
    : '-';

  $('#event-detail-body').html(
    // ── แถบสถานะ top ──
    '<div class="d-flex align-items-center gap-2 mb-3 p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">'
    + '<span class="badge fs-6 px-3 py-2" style="background:' + color + '">' + (p.status || '-') + '</span>'
    + '<span class="fw-bold fs-5">' + (p.bill_no || '-') + '</span>'
    + (p.job_type ? '<span class="badge bg-secondary">' + p.job_type + '</span>' : '')
    + '</div>'

    // ── ข้อมูลลูกค้า ──
    + '<div class="mb-3">'
    + '<div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.05em"><i class="bi bi-person me-1"></i>ข้อมูลลูกค้า</div>'
    + '<div class="row g-2">'
    + row3('ชื่อลูกค้า',    p.customer_name)
    + row3('เบอร์ลูกค้า',  p.phone || '-')
    + row3('สินค้า/บริการ', p.product_service)
    + '</div></div>'

    // ── ข้อมูลนัดหมาย ──
    + '<div class="mb-3">'
    + '<div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.05em"><i class="bi bi-calendar-event me-1"></i>นัดหมาย</div>'
    + '<div class="row g-2">'
    + row3('วันที่นัด',   p.install_date || '-')
    + row3('เวลา',        p.install_time ? p.install_time.substr(0,5) : '-')
    + row3('ประเภทงาน',  p.job_type || '-')
    + '</div></div>'

    // ── ข้อมูลช่าง ──
    + '<div class="mb-1">'
    + '<div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.05em"><i class="bi bi-tools me-1"></i>ช่าง</div>'
    + '<div class="row g-2">'
    + row3('ชื่อช่าง',      p.technician || '-')
    + row3('เบอร์ช่าง',     techPhone)
    + row3('ค่าจ้างช่าง',  wageHtml)
    + row3('Zone', p.tech_zone || '-')
    + (p.tech_note ? '<div class="col-12"><div class="small text-muted">หมายเหตุช่าง</div><div class="fw-medium" style="font-size:.85rem">' + p.tech_note + '</div></div>' : '')
    + '<div class="col-12"><div class="small text-muted">Google Maps</div>'
    + (p.map_link
        ? '<a href="' + p.map_link + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger mt-1"><i class="bi bi-geo-alt-fill me-1"></i>เปิด Google Maps</a>'
        : '<div class="fw-medium" style="font-size:.85rem">-</div>')
    + '</div>'
    + '</div></div>'
  );
  getEventModal().show();
}

function row3(label, val) {
  return '<div class="col-12 col-sm-4"><div class="small text-muted">' + label + '</div>'
       + '<div class="fw-medium" style="font-size:.85rem">' + (val || '-') + '</div></div>';
}
function row(label, val) {
  return '<div class="col-6"><div class="small text-muted">' + label + '</div>'
       + '<div class="fw-medium" style="font-size:.85rem">' + (val || '-') + '</div></div>';
}
function esc(s) { return (s || '').replace(/'/g, "\\'"); }

// ── Document ready ────────────────────────────────────────────
$(document).ready(function() {
  loadTechnicians();
  initCalendar();

  // ── TomSelect สำหรับ assign-tech-select (ค้นหาชื่อช่างได้) ────
  new TomSelect('#assign-tech-select', {
    placeholder: '-- เลือกหรือพิมพ์ค้นหาช่าง --',
    allowEmptyOption: true,
    maxOptions: 300,
    onChange: function(val) {
      if (!val) {
        $('#assign-tech-input').val('');
        $('#assign-tech-id').val('');
        return;
      }
      // ดึง data-id จาก option เดิม
      var opt = document.querySelector('#assign-tech-select option[value="' + val.replace(/"/g, '\"') + '"]');
      $('#assign-tech-input').val('');
      $('#assign-tech-id').val(opt ? (opt.dataset.id || '') : '');
    }
  });

  // sync free-text input (ช่างนอกระบบ) → clear TomSelect
  $('#assign-tech-input').on('input', function() {
    if ($(this).val().trim()) {
      var ts = document.getElementById('assign-tech-select').tomselect;
      if (ts) ts.clear(true);
      $('#assign-tech-id').val('');
    }
  });

  // ── Google Maps link preview ──────────────────────────────────
  function updateMapBtn() {
    var v = $('#assign-map-link').val().trim();
    if (v) { $('#btn-open-map').show(); } else { $('#btn-open-map').hide(); }
  }
  $('#assign-map-link').on('input paste change', function() {
    setTimeout(updateMapBtn, 50); // รอให้ paste เสร็จก่อน
  });
  $('#btn-open-map').on('click', function() {
    var url = $('#assign-map-link').val().trim();
    if (url) window.open(url, '_blank', 'noopener');
  });

  // ── unlock step1 ทุกครั้งที่ modal ปิด ──
  document.getElementById('assignModal').addEventListener('hidden.bs.modal', function() {
    $('#step1 input, #step1 button').prop('disabled', false);
    $('#job-results').show();
    $('#job-search-input').attr('placeholder', 'เลขบิล / ชื่อลูกค้า / เบอร์โทร');
    $('#step1').show();
  });

  // auto search on type
  var searchTimeout;
  $('#job-search-input').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(window.searchJobs, 300);
  });
});

// ============================================================
// service.js — รายการงานบริการ
// ฟังก์ชันที่เรียกจาก onclick ใน DataTable render ต้องเป็น window.xxx
// ============================================================

var statusColor = {
  'รอดำเนินการ'   : 'warning text-dark',
  'ยืนยันแล้ว'    : 'primary',
  'กำลังดำเนินงาน': 'info text-dark',
  'เสร็จแล้ว'     : 'success',
  'เลื่อนนัด'     : 'secondary',
  'ยกเลิกนัด'     : 'danger'
};

var fields = [
  'job_type','bill_no','customer_name','purchase_date','address','location',
  'install_date','install_time','phone','technician','tech_note','status',
  'product_service','bill_note','tags','sale_code','team','branch'
];

var serviceTable = null;

function statusBadge(s) {
  var cls = statusColor[s] || 'secondary';
  return '<span class="badge bg-' + cls + '">' + (s||'-') + '</span>';
}

// ---- Stats ----
function loadStats() {
  $.get(BASE + 'api/service', function(r) {
    if (!r.success) return;
    var d = r.data;
    var today = new Date().toISOString().split('T')[0];
    $('#stat-today').text(d.filter(function(x){ return x.install_date && x.install_date.substr(0,10) === today; }).length);
    $('#stat-open').text(d.filter(function(x){ return ['เสร็จแล้ว','ยกเลิกนัด'].indexOf(x.status) === -1; }).length);
    $('#stat-pending').text(d.filter(function(x){ return x.status === 'รอดำเนินการ'; }).length);
    $('#stat-done').text(d.filter(function(x){ return x.status === 'เสร็จแล้ว'; }).length);
  });
}

// ---- Global functions (เรียกจาก onclick ใน DataTable render ได้) ----

window.viewRecord = function(id) {
  $.get(BASE + 'api/service/' + id, function(r) {
    if (!r.success) return;
    var d = r.data;
  var mapsUrl = '';
if (d.location) {
  var raw = d.location.trim();
  // ถ้าเป็น lat,lng เช่น "13.7815445,100.6165399"
  if (/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/.test(raw)) {
    mapsUrl = 'https://www.google.com/maps?q=' + raw;
  } else {
    // ถ้าเป็น URL เต็ม (https://maps.google.com/...) ใช้เลย
    mapsUrl = raw;
  }
}
var loc = mapsUrl
  ? '<a href="' + mapsUrl + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt me-1"></i>เปิด Maps</a>'
  : '-';
    // ── สร้าง checkin section ──────────────────────────────
    var checkinHtml = '';
    if (d.checkins && d.checkins.length > 0) {
      checkinHtml += '<hr class="my-3">'
        + '<div class="d-flex align-items-center gap-2 mb-2">'
        + '<i class="bi bi-pin-map-fill text-success"></i>'
        + '<span class="fw-bold">ตำแหน่งที่ช่างบันทึกการเข้างาน</span>'
        + '<span class="badge bg-success">' + d.checkins.length + ' รายการ</span>'
        + '</div>';

      d.checkins.forEach(function(c, i) {
        var mapsUrl = (c.start_lat && c.start_lng)
          ? 'https://www.google.com/maps?q=' + c.start_lat + ',' + c.start_lng
          : null;
        var startTime = c.start_time ? c.start_time.substr(0,16).replace('T',' ') : '-';

        checkinHtml += '<div class="border rounded p-3 mb-2" style="background:#f0fdf4;border-color:#bbf7d0!important">'
          + '<div class="d-flex align-items-center gap-2 mb-2">'
          + '<i class="bi bi-clock-history text-success"></i>'
          + '<span class="fw-bold small text-success">บันทึกเข้างาน</span>'
          + '<span class="ms-auto text-dark fw-medium small">' + startTime + '</span>'
          + '</div>'
          + (c.start_address
              ? '<div class="d-flex align-items-start gap-2 mb-2">'
                + '<i class="bi bi-geo-alt-fill text-danger mt-1" style="font-size:.85rem"></i>'
                + '<span class="small text-dark">' + c.start_address + '</span>'
                + '</div>'
              : '')
          + (mapsUrl
              ? '<a href="' + mapsUrl + '" target="_blank" class="btn btn-sm btn-outline-success" style="font-size:.78rem">'
                + '<i class="bi bi-map me-1"></i>เปิดแผนที่</a>'
              : '<span class="small text-muted">ไม่มีข้อมูลพิกัด</span>')
          + '</div>';
      });
    } else {
      checkinHtml = '<hr class="my-3">'
        + '<div class="d-flex align-items-center gap-2 text-muted">'
        + '<i class="bi bi-pin-map"></i>'
        + '<span class="small">ยังไม่มีการบันทึกตำแหน่งเข้างาน</span>'
        + '</div>';
    }

    $('#viewBody').html(
      '<div class="row g-3">'
      + col(4, 'ประเภทงาน',     d.job_type)
      + col(4, 'เลขที่บิล',     d.bill_no)
      + col(4, 'สถานะ',         statusBadge(d.status))
      + col(4, 'ชื่อลูกค้า',   d.customer_name)
      + col(4, 'เบอร์โทร',     d.phone)
      + col(4, 'วันที่ซื้อ',   d.purchase_date ? d.purchase_date.substr(0,10) : '')
      + col(12,'ที่อยู่',       d.address)
      + col(12,'Location',      loc)
      + col(4, 'วันที่นัด',     d.install_date ? d.install_date.substr(0,10) : '')
      + col(4, 'เวลา',          d.install_time ? d.install_time.substr(0,5) : '')
      + col(4, 'ช่าง',          d.technician)
      + col(4, 'ทีม',           d.team)
      + col(4, 'สาขา',          d.branch)
      + col(4, 'รหัสพนักงาน',  d.sale_code)
      + col(6, 'สินค้า/บริการ', d.product_service)
      + col(6, 'แท็ก',          tagsHtml(d.tags))
      + col(6, 'หมายเหตุช่าง', '<span style="white-space:pre-wrap">' + esc(d.tech_note) + '</span>')
      + col(6, 'หมายเหตุบิล',  '<span style="white-space:pre-wrap">' + esc(d.bill_note) + '</span>')
      + '</div>'
      + checkinHtml
    );
    new bootstrap.Modal(document.getElementById('viewModal')).show();
  });
};
window.previewLocation = function() {
  var raw = $('#f_location').val().trim();
  if (!raw) return;
  var url = /^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/.test(raw)
    ? 'https://www.google.com/maps?q=' + raw
    : raw;
  window.open(url, '_blank');
};
window.editRecord = function(id) {
  $.get(BASE + 'api/service/' + id, function(r) {
    if (!r.success) return Swal.fire('ข้อผิดพลาด', r.message, 'error');
    var d = r.data;
    $('#record_id').val(d.id);
    $('#conflict-alert').addClass('d-none');
    fields.forEach(function(f) {
      var v = d[f] || '';
      if (f === 'purchase_date' || f === 'install_date') v = v ? v.substr(0,10) : '';
      if (f === 'install_time') v = v ? v.substr(0,5) : '';
      $('#f_' + f).val(v);
    });
    $('#modalTitle').html('<i class="bi bi-pencil-square me-2"></i>แก้ไขรายการ #' + d.id);
    new bootstrap.Modal(document.getElementById('serviceModal')).show();
  });
};

window.deleteRecord = function(id) {
  Swal.fire({
    title: 'ยืนยันการลบ?', text: 'ไม่สามารถกู้คืนได้', icon: 'warning',
    showCancelButton: true, confirmButtonColor: '#d33',
    confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก'
  }).then(function(res) {
    if (!res.isConfirmed) return;
    $.ajax({
      url: BASE + 'api/service/' + id, method: 'DELETE',
      success: function(r) {
        if (r.success) {
          serviceTable.ajax.reload();
          loadStats();
          Swal.fire({ icon:'success', title:'ลบสำเร็จ', timer:1200, showConfirmButton:false });
        } else {
          Swal.fire('ข้อผิดพลาด', r.message, 'error');
        }
      }
    });
  });
};

function openAddModal() {
  $('#modalTitle').html('<i class="bi bi-plus-circle me-2"></i>เพิ่มรายการงาน');
  $('#record_id').val('');
  $('#conflict-alert').addClass('d-none');
  fields.forEach(function(f) {
    var el = $('#f_' + f);
    el.is('select') ? el.val(f === 'status' ? 'รอดำเนินการ' : '') : el.val('');
  });
  new bootstrap.Modal(document.getElementById('serviceModal')).show();
}

function saveRecord(force) {
  var id    = $('#record_id').val();
  var bill  = $('#f_bill_no').val().trim();
  var jtype = $('#f_job_type').val();
  if (!bill)  return Swal.fire('แจ้งเตือน', 'กรุณาระบุเลขที่บิล', 'warning');
  if (!jtype) return Swal.fire('แจ้งเตือน', 'กรุณาเลือกประเภทงาน', 'warning');

  var data = { force: force || false };
  fields.forEach(function(f) { data[f] = $('#f_' + f).val(); });

  var url    = id ? BASE + 'api/service/' + id : BASE + 'api/service';
  var method = id ? 'PUT' : 'POST';

  $('#saveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>บันทึก...');
  $('#conflict-alert').addClass('d-none');

  $.ajax({
    url: url, method: method, contentType: 'application/json', data: JSON.stringify(data),
    success: function(r) {
      if (r.success) {
        bootstrap.Modal.getInstance(document.getElementById('serviceModal')).hide();
        serviceTable.ajax.reload();
        loadStats();
        Swal.fire({ icon:'success', title:'สำเร็จ', text:r.message, timer:1500, showConfirmButton:false });
      } else if (r.conflict) {
        $('#conflict-msg').text(r.message);
        $('#conflict-alert').removeClass('d-none');
        document.getElementById('conflict-alert').scrollIntoView({ behavior:'smooth' });
      } else {
        Swal.fire('ข้อผิดพลาด', r.message, 'error');
      }
    },
    error: function() { Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อได้', 'error'); },
    complete: function() {
      $('#saveBtn').prop('disabled', false).html('<i class="bi bi-save me-2"></i>บันทึก');
    }
  });
}

// ---- Helper ----
function col(size, label, val) {
  return '<div class="col-md-' + size + '">'
    + '<div class="small text-muted">' + label + '</div>'
    + '<div class="fw-medium">' + (val || '-') + '</div>'
    + '</div>';
}
function tagsHtml(tags) {
  if (!tags) return '-';
  return tags.split(',').map(function(t){ return '<span class="badge bg-secondary me-1">' + t.trim() + '</span>'; }).join('');
}
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ---- Init ----
$(document).ready(function() {
  loadStats();

  serviceTable = $('#serviceTable').DataTable({
    processing: true, serverSide: true,
    ajax: { url: BASE + 'api/service/datatable', type: 'POST' },
    columns: [
      { data:'job_type', defaultContent:'-' },
      { data:'bill_no', defaultContent:'-' },
      { data:'customer_name', defaultContent:'-' },
      { data:'purchase_date', defaultContent:'-', render: function(d){ return d ? d.substr(0,10) : '-'; } },
      { data:'install_date', defaultContent:'-', render: function(d){ return d ? d.substr(0,10) : '-'; } },
      { data:'install_time', defaultContent:'-', render: function(d){ return d ? d.substr(0,5) : '-'; } },
      { data:'phone', defaultContent:'-' },
      { data:'technician', defaultContent:'-' },
      { data:'status', render: statusBadge },
      { data:'product_service', defaultContent:'-', render: function(d){ return d && d.length > 30 ? d.substr(0,30) + '…' : (d||'-'); } },
      { data:'tags', defaultContent:'-', render: tagsHtml },
      { data:'id', orderable:false, className:'text-center', render: function(id) {
        return '<div class="btn-group btn-group-sm">'
          + '<button class="btn btn-outline-info btn-sm" onclick="viewRecord(' + id + ')"><i class="bi bi-eye"></i></button>'
          + '<button class="btn btn-outline-warning btn-sm" onclick="editRecord(' + id + ')"><i class="bi bi-pencil"></i></button>'
          + '<button class="btn btn-outline-danger btn-sm" onclick="deleteRecord(' + id + ')"><i class="bi bi-trash"></i></button>'
          + '</div>';
      }}
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/th.json' },
    pageLength: 25, order: [[0,'desc']],
    dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
  });

  // ปุ่มเพิ่มรายการ
  $('#btn-add-new').on('click', openAddModal);

  // Autocomplete ช่อง f_technician
  var techTimer;
  $('#f_technician').on('input', function() {
    var q = $(this).val().trim();
    if (q.length < 1) { $('#f-tech-suggest').hide(); return; }
    clearTimeout(techTimer);
    techTimer = setTimeout(function() {
      $.get(BASE + 'dashboard/api_technicians_search', { q: q }, function(r) {
        if (!r.success || !r.data.length) { $('#f-tech-suggest').hide(); return; }
        var html = r.data.map(function(t) {
          return '<div class="tech-suggest-item" data-name="' + t.reg_name + '">'
            + '<span class="fw-medium">' + t.reg_name + '</span>'
            + (t.reg_telephone ? '<span class="text-muted ms-2 small">' + t.reg_telephone + '</span>' : '')
            + '</div>';
        }).join('');
        $('#f-tech-suggest').html(html).show();
      });
    }, 250);
  });

  $(document).on('click', '.tech-suggest-item', function() {
    var name = $(this).data('name');
    $('#f_technician').val(name);
    $('#f-tech-suggest').hide();
  });

  $(document).on('click', function(e) {
    if (!$(e.target).closest('#f_technician, #f-tech-suggest').length) {
      $('#f-tech-suggest').hide();
    }
  });

  // ปุ่มบันทึกใน modal
  $('#saveBtn').on('click', function() { saveRecord(false); });

  // ปุ่มยืนยันบันทึกเมื่อชนเวลา
  $('#btn-force-save').on('click', function() { saveRecord(true); });
});

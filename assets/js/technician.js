// ============================================================
// technician.js — จัดการข้อมูลช่าง
// ฟังก์ชันที่เรียกจาก onclick ใน DataTable render ต้องเป็น window.xxx
// ============================================================

var technicianTable = null;

var statusLabel = {
  1: 'ใช้งาน',
  0: 'ไม่ใช้งาน'
};

var statusColor = {
  1: 'success',
  0: 'secondary'
};

function statusBadgeTech(s) {
  var val = parseInt(s);
  var cls = statusColor[val] !== undefined ? statusColor[val] : 'secondary';
  var lbl = statusLabel[val] !== undefined ? statusLabel[val] : '-';
  return '<span class="badge bg-' + cls + '">' + lbl + '</span>';
}

function mapsUrl(lat, lng) {
  var la = lat ? String(lat).trim().replace(/,/g, '') : '';
  var ln = lng ? String(lng).trim().replace(/,/g, '') : '';
  if (!la || !ln) return '';
  return 'https://www.google.com/maps?q=' + la + ',' + ln;
}
// ---- Stats ----
function loadTechStats() {
  $.get(BASE + 'api/technician', function(r) {
    if (!r.success) return;
    var d = r.data;
    $('#stat-tech-total').text(d.length);
    $('#stat-tech-active').text(d.filter(function(x){ return parseInt(x.active) === 1; }).length);
    $('#stat-tech-inactive').text(d.filter(function(x){ return parseInt(x.active) === 0; }).length);
    $('#stat-tech-location').text(d.filter(function(x){ return x.latitude && x.longitude; }).length);
  });
}

// ---- Global functions ----

window.viewTech = function(id) {
  $.get(BASE + 'api/technician/' + id, function(r) {
    if (!r.success) return;
    var d = r.data;
    var url = mapsUrl(d.latitude, d.longitude);
    var locHtml = url
      ? '<a href="' + url + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt me-1"></i>เปิด Maps</a>'
        + '<span class="text-muted ms-2 small">' + d.latitude + ', ' + d.longitude + '</span>'
      : '<span class="text-muted">-</span>';

    $('#techViewBody').html(
      '<div class="row g-3">'
      + techCol(6, 'ชื่อช่าง',   d.reg_name)
      + techCol(6, 'สถานะ',      statusBadgeTech(d.active))
      + techCol(12,'ที่อยู่',    d.reg_address)
      + techCol(6, 'เบอร์โทร',   d.reg_telephone
          ? '<a href="tel:' + d.reg_telephone + '">' + d.reg_telephone + '</a>'
          : '-')
      + techCol(6, 'อีเมล',      d.reg_email || '-')
      + '<div class="col-12"><div class="small text-muted mb-1">Location</div><div class="fw-medium">' + locHtml + '</div></div>'
      + techCol(6, 'วันที่สร้าง', d.created ? d.created.substr(0,10) : '-')
      + techCol(6, 'อัปเดตล่าสุด', d.updated ? d.updated.substr(0,10) : '-')
      + '</div>'
    );
    new bootstrap.Modal(document.getElementById('techViewModal')).show();
  });
};

window.editTech = function(id) {
  $.get(BASE + 'api/technician/' + id, function(r) {
    if (!r.success) return Swal.fire('ข้อผิดพลาด', r.message, 'error');
    var d = r.data;
    $('#tech_id').val(d.id);
    $('#tf_reg_name').val(d.reg_name || '');
    $('#tf_reg_address').val(d.reg_address || '');
    $('#tf_reg_telephone').val(d.reg_telephone || '');
    $('#tf_reg_email').val(d.reg_email || '');
    $('#tf_latitude').val(d.latitude || '');
    $('#tf_longitude').val(d.longitude || '');
    $('#tf_active').val(d.active !== undefined ? d.active : 1);
    $('#techModalTitle').html('<i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลช่าง #' + d.id);
    new bootstrap.Modal(document.getElementById('techModal')).show();
  });
};

window.deleteTech = function(id) {
  Swal.fire({
    title: 'ยืนยันการลบ?',
    text: 'ไม่สามารถกู้คืนได้',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก'
  }).then(function(res) {
    if (!res.isConfirmed) return;
    $.ajax({
      url: BASE + 'api/technician/' + id,
      method: 'DELETE',
      success: function(r) {
        if (r.success) {
          technicianTable.ajax.reload();
          loadTechStats();
          Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire('ข้อผิดพลาด', r.message, 'error');
        }
      }
    });
  });
};

// เปิด Maps จาก input ใน modal
window.previewTechLocation = function() {
  var lat = $('#tf_latitude').val().trim();
  var lng = $('#tf_longitude').val().trim();
  if (!lat || !lng) return Swal.fire('แจ้งเตือน', 'กรุณาระบุ Latitude และ Longitude', 'warning');
  window.open(mapsUrl(lat, lng), '_blank');
};

// paste lat,lng รูปแบบ "13.xxx,100.xxx" ใส่ใน latitude แล้วแยกอัตโนมัติ
window.parseTechLatLng = function() {
  var val = $('#tf_latitude').val().trim();
  var match = val.match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);
  if (match) {
    $('#tf_latitude').val(match[1]);
    $('#tf_longitude').val(match[2]);
  }
};

function openAddTechModal() {
  $('#tech_id').val('');
  $('#techModalTitle').html('<i class="bi bi-plus-circle me-2"></i>เพิ่มช่างใหม่');
  $('#tf_reg_name, #tf_reg_address, #tf_reg_telephone, #tf_reg_email, #tf_latitude, #tf_longitude').val('');
  $('#tf_active').val(1);
  new bootstrap.Modal(document.getElementById('techModal')).show();
}

function saveTech() {
  var id   = $('#tech_id').val();
  var name = $('#tf_reg_name').val().trim();
  if (!name) return Swal.fire('แจ้งเตือน', 'กรุณาระบุชื่อช่าง', 'warning');

  var data = {
    reg_name:      name,
    reg_address:   $('#tf_reg_address').val(),
    reg_telephone: $('#tf_reg_telephone').val(),
    reg_email:     $('#tf_reg_email').val(),
    latitude:      $('#tf_latitude').val(),
    longitude:     $('#tf_longitude').val(),
    active:        $('#tf_active').val()
  };

  var url    = id ? BASE + 'api/technician/' + id : BASE + 'api/technician';
  var method = id ? 'PUT' : 'POST';

  $('#techSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>บันทึก...');

  $.ajax({
    url: url, method: method, contentType: 'application/json', data: JSON.stringify(data),
    success: function(r) {
      if (r.success) {
        bootstrap.Modal.getInstance(document.getElementById('techModal')).hide();
        technicianTable.ajax.reload();
        loadTechStats();
        Swal.fire({ icon: 'success', title: 'สำเร็จ', text: r.message, timer: 1500, showConfirmButton: false });
      } else {
        Swal.fire('ข้อผิดพลาด', r.message, 'error');
      }
    },
    error: function() { Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อได้', 'error'); },
    complete: function() {
      $('#techSaveBtn').prop('disabled', false).html('<i class="bi bi-save me-2"></i>บันทึก');
    }
  });
}

// ---- Helper ----
function techCol(size, label, val) {
  return '<div class="col-md-' + size + '">'
    + '<div class="small text-muted">' + label + '</div>'
    + '<div class="fw-medium">' + (val || '-') + '</div>'
    + '</div>';
}

// ---- Init ----
$(document).ready(function() {
  loadTechStats();

  technicianTable = $('#technicianTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: BASE + 'api/technician/datatable', type: 'POST' },
    columns: [
      { data: 'id', defaultContent: '-' },
      { data: 'reg_name', defaultContent: '-' },
      { data: 'reg_telephone', defaultContent: '-' },
      { data: 'reg_address', defaultContent: '-', render: function(d) {
        return d && d.length > 40 ? d.substr(0, 40) + '…' : (d || '-');
      }},
      { data: 'latitude', defaultContent: '-', orderable: false, className: 'text-center',
        render: function(d, type, row) {
          var url = mapsUrl(row.latitude, row.longitude);
          return url
            ? '<a href="' + url + '" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-geo-alt"></i></a>'
            : '<span class="text-muted small">-</span>';
        }
      },
      { data: 'active', defaultContent: '-', className: 'text-center', render: statusBadgeTech },
      { data: 'id', orderable: false, className: 'text-center', render: function(id) {
        return '<div class="btn-group btn-group-sm">'
          + '<button class="btn btn-outline-info btn-sm" onclick="viewTech(' + id + ')"><i class="bi bi-eye"></i></button>'
          + '<button class="btn btn-outline-warning btn-sm" onclick="editTech(' + id + ')"><i class="bi bi-pencil"></i></button>'
          + '<button class="btn btn-outline-danger btn-sm" onclick="deleteTech(' + id + ')"><i class="bi bi-trash"></i></button>'
          + '</div>';
      }}
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/th.json' },
    pageLength: 25,
    order: [[1, 'asc']],
    dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
  });

  $('#btn-add-tech').on('click', openAddTechModal);
  $('#techSaveBtn').on('click', saveTech);

  // Auto-parse lat,lng เมื่อ paste ค่าแบบ "13.xxx,100.xxx" ลงใน latitude
  $('#tf_latitude').on('blur', parseTechLatLng);
});

// ============================================================
// employee.js — จัดการพนักงาน + สิทธิ์ดูแผนที่ลูกค้า
// ฟังก์ชันที่เรียกจาก onclick ใน DataTable render ต้องเป็น window.xxx
// ============================================================

var employeeTable = null;
var requestTable  = null;

var statusLabel = { 1: 'ใช้งาน', 0: 'ไม่ใช้งาน' };
var statusColor = { 1: 'success', 0: 'secondary' };

var reqStatusLabel = { pending: 'รอดำเนินการ', approved: 'อนุมัติแล้ว', rejected: 'ปฏิเสธแล้ว' };
var reqStatusColor = { pending: 'warning', approved: 'success', rejected: 'danger' };

function statusBadgeEmp(s) {
  var val = parseInt(s);
  var cls = statusColor[val] !== undefined ? statusColor[val] : 'secondary';
  var lbl = statusLabel[val] !== undefined ? statusLabel[val] : '-';
  return '<span class="badge bg-' + cls + '">' + lbl + '</span>';
}

// jQuery ส่งทุก response ที่ status ไม่ใช่ 2xx (เช่น 422, 409, 403, 404, 500) เข้า error:
// เสมอ ไม่ว่า body จะเป็น JSON ที่มีข้อความ error ชัดเจนแค่ไหนก็ตาม — ถ้าไม่ดึง message
// จาก responseJSON ตรงๆ ผู้ใช้จะเห็นแต่ข้อความทั่วไปแทนสาเหตุจริง (เช่น "username ซ้ำ")
function ajaxErrMsg(jqXHR, fallback) {
  if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.message) {
    return jqXHR.responseJSON.message;
  }
  return fallback || 'ไม่สามารถเชื่อมต่อได้ กรุณาลองใหม่อีกครั้ง';
}

// ---- Map access column (ต่างกันตาม role: superadmin เห็นปุ่ม "ให้สิทธิ์"/"ยกเลิก", admin เห็นปุ่ม "ขอสิทธิ์") ----
function mapAccessCell(row) {
  if (parseInt(row.map_access) === 1) {
    var revokeBtn = IS_SUPERADMIN
      ? ' <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="revokeAccess(' + row.id + ')" title="ยกเลิกสิทธิ์"><i class="bi bi-x-lg"></i></button>'
      : '';
    return '<span class="badge bg-success">มีสิทธิ์</span>' + revokeBtn;
  }
  if (parseInt(row.has_pending_request) > 0) {
    return '<span class="badge bg-warning text-dark">รอการอนุมัติ</span>';
  }
  if (IS_SUPERADMIN) {
    return '<button class="btn btn-sm btn-outline-success" onclick="grantAccess(' + row.id + ')"><i class="bi bi-check-lg me-1"></i>ให้สิทธิ์</button>';
  }
  return '<button class="btn btn-sm btn-outline-primary" onclick="requestAccess(' + row.id + ')"><i class="bi bi-send me-1"></i>ขอสิทธิ์</button>';
}

// ---- Stats ----
function loadEmpStats() {
  $.get(BASE + 'api/employee', function (r) {
    if (!r.success) return;
    var d = r.data;
    $('#stat-emp-total').text(d.length);
    $('#stat-emp-active').text(d.filter(function (x) { return parseInt(x.active) === 1; }).length);
    $('#stat-emp-map-access').text(d.filter(function (x) { return parseInt(x.map_access) === 1; }).length);
    $('#stat-emp-pending').text(d.filter(function (x) { return parseInt(x.has_pending_request) > 0; }).length);
  });
}

// ---- View / Edit / Delete ----
window.viewEmp = function (id) {
  $.get(BASE + 'api/employee/get/' + id, function (r) {
    if (!r.success) return Swal.fire('ข้อผิดพลาด', r.message, 'error');
    var d = r.data;
    $('#empViewBody').html(
      '<div class="row g-3">'
      + empCol(6, 'ชื่อ-สกุล', (d.first_name || '') + ' ' + (d.last_name || ''))
      + empCol(6, 'Username', d.username)
      + empCol(6, 'เบอร์โทร', d.phone ? '<a href="tel:' + d.phone + '">' + d.phone + '</a>' : '-')
      + empCol(6, 'อีเมล', d.email || '-')
      + empCol(6, 'ตำแหน่ง', d.position || '-')
      + empCol(6, 'สถานะ', statusBadgeEmp(d.active))
      + empCol(6, 'วันที่สร้าง', d.created_at ? d.created_at.substr(0, 10) : '-')
      + '<div class="col-12"><hr class="my-2">'
      + '<div class="small text-muted mb-1">บัญชีเข้าสู่ระบบ</div>'
      + '<div class="d-flex align-items-center gap-2">'
      + '<i class="bi bi-shield-lock text-success"></i>'
      + '<span class="small">ตั้งรหัสผ่านไว้แล้ว (เข้ารหัสแบบทางเดียว ไม่สามารถแสดงค่าจริงได้ — เหมือนบัญชีอื่นทุกบัญชีในระบบนี้)</span>'
      + '</div>'
      + '<button type="button" class="btn btn-sm btn-outline-primary mt-2" '
      + 'onclick="bootstrap.Modal.getInstance(document.getElementById(\'empViewModal\')).hide(); editEmp(' + d.id + ');">'
      + '<i class="bi bi-key me-1"></i>ตั้งรหัสผ่านใหม่</button>'
      + '</div>'
      + '</div>'
    );
    new bootstrap.Modal(document.getElementById('empViewModal')).show();
  });
};

window.editEmp = function (id) {
  $.get(BASE + 'api/employee/get/' + id, function (r) {
    if (!r.success) return Swal.fire('ข้อผิดพลาด', r.message, 'error');
    var d = r.data;
    $('#emp_id').val(d.id);
    $('#ef_first_name').val(d.first_name || '');
    $('#ef_last_name').val(d.last_name || '');
    $('#ef_phone').val(d.phone || '');
    $('#ef_email').val(d.email || '');
    $('#ef_position').val(d.position || '');
    $('#ef_active').val(d.active !== undefined ? d.active : 1);
    $('#ef_username').val(d.username || '');
    $('#ef_password').val('');
    $('#ef_password_req').hide();
    $('#ef_password_hint_label').text('(เว้นว่าง = ไม่เปลี่ยนรหัสผ่าน)');
    $('#empModalTitle').html('<i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลพนักงาน #' + d.id);
    new bootstrap.Modal(document.getElementById('empModal')).show();
  });
};

window.deleteEmp = function (id) {
  Swal.fire({
    title: 'ยืนยันการลบ?',
    text: 'ไม่สามารถกู้คืนได้ (ประวัติคำขอสิทธิ์ของพนักงานคนนี้จะถูกลบไปด้วย)',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก'
  }).then(function (res) {
    if (!res.isConfirmed) return;
    $.ajax({
      url: BASE + 'api/employee/delete/' + id,
      method: 'POST',
      success: function (r) {
        if (r.success) {
          employeeTable.ajax.reload();
          loadEmpStats();
          Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire('ข้อผิดพลาด', r.message, 'error');
        }
      },
      error: function (jqXHR) { Swal.fire('ข้อผิดพลาด', ajaxErrMsg(jqXHR), 'error'); }
    });
  });
};

// ---- สิทธิ์ดูแผนที่: ขอสิทธิ์ (admin) / ให้สิทธิ์ตรง+ยกเลิก (superadmin) ----
window.requestAccess = function (employeeId) {
  Swal.fire({
    title: 'ส่งคำขอสิทธิ์ดูแผนที่?',
    text: 'คำขอจะถูกส่งให้ superadmin เป็นผู้อนุมัติ',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'ส่งคำขอ',
    cancelButtonText: 'ยกเลิก'
  }).then(function (res) {
    if (!res.isConfirmed) return;
    $.ajax({
      url: BASE + 'api/employee/request_access', method: 'POST', contentType: 'application/json',
      data: JSON.stringify({ employee_id: employeeId }),
      success: function (r) {
        if (r.success) {
          employeeTable.ajax.reload();
          loadEmpStats();
          Swal.fire({ icon: 'success', title: 'สำเร็จ', text: r.message, timer: 1500, showConfirmButton: false });
        } else {
          Swal.fire('ข้อผิดพลาด', r.message, 'error');
        }
      },
      error: function (jqXHR) { Swal.fire('ข้อผิดพลาด', ajaxErrMsg(jqXHR), 'error'); }
    });
  });
};

window.grantAccess = function (employeeId) {
  Swal.fire({
    title: 'ให้สิทธิ์ดูแผนที่ลูกค้า?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'ให้สิทธิ์',
    cancelButtonText: 'ยกเลิก'
  }).then(function (res) {
    if (!res.isConfirmed) return;
    $.ajax({
      url: BASE + 'api/employee/grant/' + employeeId, method: 'POST',
      success: function (r) {
        if (r.success) {
          employeeTable.ajax.reload();
          loadEmpStats();
          Swal.fire({ icon: 'success', title: 'สำเร็จ', text: r.message, timer: 1500, showConfirmButton: false });
        } else {
          Swal.fire('ข้อผิดพลาด', r.message, 'error');
        }
      },
      error: function (jqXHR) { Swal.fire('ข้อผิดพลาด', ajaxErrMsg(jqXHR), 'error'); }
    });
  });
};

window.revokeAccess = function (employeeId) {
  Swal.fire({
    title: 'ยกเลิกสิทธิ์ดูแผนที่?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ยกเลิกสิทธิ์',
    cancelButtonText: 'ปิด'
  }).then(function (res) {
    if (!res.isConfirmed) return;
    $.ajax({
      url: BASE + 'api/employee/revoke/' + employeeId, method: 'POST',
      success: function (r) {
        if (r.success) {
          employeeTable.ajax.reload();
          loadEmpStats();
          Swal.fire({ icon: 'success', title: 'ยกเลิกสิทธิ์แล้ว', timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire('ข้อผิดพลาด', r.message, 'error');
        }
      },
      error: function (jqXHR) { Swal.fire('ข้อผิดพลาด', ajaxErrMsg(jqXHR), 'error'); }
    });
  });
};

// ---- คำขออนุมัติ (superadmin) ----
function loadRequests(status) {
  if (!requestTable) return;
  requestTable.ajax.url(BASE + 'api/employee/requests' + (status ? '?status=' + status : '')).load();
}

window.approveRequest = function (id) {
  Swal.fire({
    title: 'อนุมัติสิทธิ์ดูแผนที่?', icon: 'question', showCancelButton: true,
    confirmButtonText: 'อนุมัติ', cancelButtonText: 'ยกเลิก'
  }).then(function (res) {
    if (!res.isConfirmed) return;
    $.ajax({
      url: BASE + 'api/employee/requests/approve/' + id, method: 'POST',
      success: function (r) {
        if (r.success) {
          requestTable.ajax.reload();
          employeeTable.ajax.reload();
          loadEmpStats();
          Swal.fire({ icon: 'success', title: 'อนุมัติแล้ว', timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire('ข้อผิดพลาด', r.message, 'error');
        }
      },
      error: function (jqXHR) { Swal.fire('ข้อผิดพลาด', ajaxErrMsg(jqXHR), 'error'); }
    });
  });
};

window.rejectRequest = function (id) {
  Swal.fire({
    title: 'ปฏิเสธคำขอนี้?', icon: 'warning', showCancelButton: true,
    confirmButtonColor: '#d33', confirmButtonText: 'ปฏิเสธ', cancelButtonText: 'ยกเลิก'
  }).then(function (res) {
    if (!res.isConfirmed) return;
    $.ajax({
      url: BASE + 'api/employee/requests/reject/' + id, method: 'POST', contentType: 'application/json',
      data: JSON.stringify({}),
      success: function (r) {
        if (r.success) {
          requestTable.ajax.reload();
          employeeTable.ajax.reload();
          loadEmpStats();
          Swal.fire({ icon: 'success', title: 'ปฏิเสธคำขอแล้ว', timer: 1200, showConfirmButton: false });
        } else {
          Swal.fire('ข้อผิดพลาด', r.message, 'error');
        }
      },
      error: function (jqXHR) { Swal.fire('ข้อผิดพลาด', ajaxErrMsg(jqXHR), 'error'); }
    });
  });
};

// ---- Add / Save ----
function openAddEmpModal() {
  $('#emp_id').val('');
  $('#empModalTitle').html('<i class="bi bi-plus-circle me-2"></i>เพิ่มพนักงานใหม่');
  $('#ef_first_name, #ef_last_name, #ef_phone, #ef_email, #ef_position, #ef_username, #ef_password').val('');
  $('#ef_active').val(1);
  $('#ef_password_req').show();
  $('#ef_password_hint_label').text('');
  new bootstrap.Modal(document.getElementById('empModal')).show();
}

function saveEmp() {
  var id        = $('#emp_id').val();
  var first     = $('#ef_first_name').val().trim();
  var last      = $('#ef_last_name').val().trim();
  var username  = $('#ef_username').val().trim();
  var password  = $('#ef_password').val().trim();
  var phone     = $('#ef_phone').val().trim();
  var email     = $('#ef_email').val().trim();
  var position  = $('#ef_position').val().trim();

  if (!first)                    return Swal.fire('แจ้งเตือน', 'กรุณาระบุชื่อ', 'warning');
  if (first.length > 100)        return Swal.fire('แจ้งเตือน', 'ชื่อยาวเกินไป (ไม่เกิน 100 ตัวอักษร)', 'warning');
  if (!last)                     return Swal.fire('แจ้งเตือน', 'กรุณาระบุนามสกุล', 'warning');
  if (last.length > 100)         return Swal.fire('แจ้งเตือน', 'นามสกุลยาวเกินไป (ไม่เกิน 100 ตัวอักษร)', 'warning');

  if (!username)                 return Swal.fire('แจ้งเตือน', 'กรุณาระบุ Username', 'warning');
  if (!/^[A-Za-z0-9_.\-]{3,100}$/.test(username)) {
    return Swal.fire('แจ้งเตือน', 'Username ต้องมีอย่างน้อย 3 ตัวอักษร ใช้ได้เฉพาะ A-Z, a-z, 0-9, _ . - เท่านั้น (ห้ามเว้นวรรค)', 'warning');
  }

  if (!id && !password)          return Swal.fire('แจ้งเตือน', 'กรุณาระบุ Password', 'warning');
  if (password && password.length < 6) return Swal.fire('แจ้งเตือน', 'Password ต้องมีอย่างน้อย 6 ตัวอักษร', 'warning');

  if (!phone)                    return Swal.fire('แจ้งเตือน', 'กรุณาระบุเบอร์โทร', 'warning');
  var phoneDigits = phone.replace(/[^0-9]/g, '');
  if (!/^0\d{8,9}$/.test(phoneDigits)) {
    return Swal.fire('แจ้งเตือน', 'เบอร์โทรไม่ถูกต้อง (ต้องขึ้นต้นด้วย 0 และเป็นตัวเลข 9-10 หลัก)', 'warning');
  }

  if (!email)                    return Swal.fire('แจ้งเตือน', 'กรุณาระบุอีเมล', 'warning');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return Swal.fire('แจ้งเตือน', 'รูปแบบอีเมลไม่ถูกต้อง', 'warning');
  }

  if (position.length > 150)     return Swal.fire('แจ้งเตือน', 'ตำแหน่งยาวเกินไป (ไม่เกิน 150 ตัวอักษร)', 'warning');

  var data = {
    first_name: first,
    last_name:  last,
    phone:      phone,
    email:      email,
    position:   position,
    active:     $('#ef_active').val(),
    username:   username,
    password:   password
  };

  var url = id ? BASE + 'api/employee/update/' + id : BASE + 'api/employee/create';

  $('#empSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>บันทึก...');

  $.ajax({
    url: url, method: 'POST', contentType: 'application/json', data: JSON.stringify(data),
    success: function (r) {
      if (r.success) {
        bootstrap.Modal.getInstance(document.getElementById('empModal')).hide();
        employeeTable.ajax.reload();
        loadEmpStats();
        Swal.fire({ icon: 'success', title: 'สำเร็จ', text: r.message, timer: 1500, showConfirmButton: false });
      } else {
        Swal.fire('ข้อผิดพลาด', r.message, 'error');
      }
    },
    error: function (jqXHR) { Swal.fire('ข้อผิดพลาด', ajaxErrMsg(jqXHR), 'error'); },
    complete: function () {
      $('#empSaveBtn').prop('disabled', false).html('<i class="bi bi-save me-2"></i>บันทึก');
    }
  });
}

// ---- Helper ----
function empCol(size, label, val) {
  return '<div class="col-md-' + size + '">'
    + '<div class="small text-muted">' + label + '</div>'
    + '<div class="fw-medium">' + (val || '-') + '</div>'
    + '</div>';
}

// ---- Init ----
$(document).ready(function () {
  loadEmpStats();

  employeeTable = $('#employeeTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: BASE + 'api/employee/datatable', type: 'POST' },
    columns: [
      { data: 'id', defaultContent: '-' },
      { data: null, render: function (row) { return ((row.first_name || '') + ' ' + (row.last_name || '')).trim() || '-'; } },
      { data: 'username', defaultContent: '-' },
      { data: 'email', defaultContent: '-' },
      { data: 'phone', defaultContent: '-' },
      { data: 'position', defaultContent: '-' },
      { data: 'active', defaultContent: '-', className: 'text-center', render: statusBadgeEmp },
      { data: null, orderable: false, className: 'text-center', render: mapAccessCell },
      {
        data: 'id', orderable: false, className: 'text-center', render: function (id) {
          return '<div class="btn-group btn-group-sm">'
            + '<button class="btn btn-outline-info btn-sm" onclick="viewEmp(' + id + ')"><i class="bi bi-eye"></i></button>'
            + '<button class="btn btn-outline-warning btn-sm" onclick="editEmp(' + id + ')"><i class="bi bi-pencil"></i></button>'
            + '<button class="btn btn-outline-danger btn-sm" onclick="deleteEmp(' + id + ')"><i class="bi bi-trash"></i></button>'
            + '</div>';
        }
      }
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/th.json' },
    pageLength: 25,
    order: [[1, 'asc']],
    dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
  });

  $('#btn-add-emp').on('click', openAddEmpModal);
  $('#empSaveBtn').on('click', saveEmp);

  if (IS_SUPERADMIN) {
    requestTable = $('#requestTable').DataTable({
      processing: true,
      serverSide: false,
      ajax: { url: BASE + 'api/employee/requests?status=pending', dataSrc: 'data' },
      columns: [
        { data: null, render: function (row) { return ((row.first_name || '') + ' ' + (row.last_name || '')).trim() || (row.username || '-'); } },
        { data: 'requested_by', defaultContent: '-' },
        { data: 'requested_at', defaultContent: '-', render: function (d) { return d ? d.substr(0, 16).replace('T', ' ') : '-'; } },
        {
          data: 'status', className: 'text-center', render: function (s) {
            var cls = reqStatusColor[s] || 'secondary';
            var lbl = reqStatusLabel[s] || s;
            return '<span class="badge bg-' + cls + '">' + lbl + '</span>';
          }
        },
        {
          data: null, orderable: false, className: 'text-center', render: function (row) {
            if (row.status !== 'pending') return '<span class="text-muted small">-</span>';
            return '<div class="btn-group btn-group-sm">'
              + '<button class="btn btn-outline-success btn-sm" onclick="approveRequest(' + row.id + ')"><i class="bi bi-check-lg"></i></button>'
              + '<button class="btn btn-outline-danger btn-sm" onclick="rejectRequest(' + row.id + ')"><i class="bi bi-x-lg"></i></button>'
              + '</div>';
          }
        }
      ],
      language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/th.json' },
      pageLength: 10,
      order: [[2, 'desc']],
      dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
    });

    $('#reqFilterGroup button').on('click', function () {
      $('#reqFilterGroup button').removeClass('active');
      $(this).addClass('active');
      loadRequests($(this).data('status'));
    });
  }
});

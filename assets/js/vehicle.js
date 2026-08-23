/* vehicle.js — จัดการยานพาหนะ */
var vehicleTable = null;
var deleteId     = null;

// ── กัน HTML/attribute injection ตอนแสดง map_link ──────────────
function escAttr(s) {
  return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                  .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function normalizeLinkHref(v) {
  if (!v) return null;
  var s = String(v).trim();
  if (!s) return null;
  if (/^https?:\/\//i.test(s)) {
    try { return new URL(s).href; } catch (e) { return null; }
  }
  try { return new URL('https://' + s).href; } catch (e) { return null; }
}

// ── type icon ──────────────────────────────────────────────────
function typeIcon(type) {
  var icons = {
    'รถยนต์':          '🚗',
    'รถจักรยานยนต์':  '🏍️',
    'รถกระบะ':         '🛻',
    'รถตู้':            '🚐',
    'อื่นๆ':            '🚗',
  };
  return (icons[type] || '🚗') + ' ' + (type || '-');
}

// ── load & render table ────────────────────────────────────────
function loadVehicles() {
  $.get(BASE + 'api/vehicle', function(r) {
    if (!r.success) return;
    var rows = r.data;

    // stats
    var total  = rows.length;
    var active = rows.filter(function(v){ return v.active == 1; }).length;
    var cars   = rows.filter(function(v){ return v.vehicle_type === 'รถยนต์' || v.vehicle_type === 'รถกระบะ' || v.vehicle_type === 'รถตู้'; }).length;
    var moto   = rows.filter(function(v){ return v.vehicle_type === 'รถจักรยานยนต์'; }).length;
    $('#stat-total').text(total);
    $('#stat-active').text(active);
    $('#stat-car').text(cars);
    $('#stat-moto').text(moto);

    // render table
    if (vehicleTable) {
      vehicleTable.clear();
      vehicleTable.rows.add(rows);
      vehicleTable.draw();
    }
  });
}

// ── reset form ─────────────────────────────────────────────────
function resetForm() {
  $('#vehicle_id').val('');
  $('#vf_type').val('');
  $('#vf_plate').val('');
  $('#vf_province').val('');
  $('#vf_brand').val('');
  $('#vf_model').val('');
  $('#vf_color').val('');
  $('#vf_map_link').val('');
  $('#vf_note').val('');
  $('#vf_active').val('1');
  $('#vehicleModalTitle').html('<i class="bi bi-plus-circle me-2"></i>เพิ่มยานพาหนะ');
}

// ── edit ───────────────────────────────────────────────────────
window.editVehicle = function(id) {
  $.get(BASE + 'api/vehicle/' + id, function(r) {
    if (!r.success) return;
    var d = r.data;
    $('#vehicle_id').val(d.id);
    $('#vf_type').val(d.vehicle_type || '');
    $('#vf_plate').val(d.license_plate || '');
    $('#vf_province').val(d.province || '');
    $('#vf_brand').val(d.brand || '');
    $('#vf_model').val(d.model || '');
    $('#vf_color').val(d.color || '');
    $('#vf_map_link').val(d.map_link || '');
    $('#vf_note').val(d.note || '');
    $('#vf_active').val(d.active != null ? d.active : 1);
    $('#vehicleModalTitle').html('<i class="bi bi-pencil-square me-2"></i>แก้ไขยานพาหนะ #' + d.id);
    new bootstrap.Modal(document.getElementById('vehicleModal')).show();
  });
};

// ── delete prompt ──────────────────────────────────────────────
window.promptDeleteVehicle = function(id, plate) {
  deleteId = id;
  $('#delete-plate-display').text(plate);
  new bootstrap.Modal(document.getElementById('vehicleDeleteModal')).show();
};

// ── save ───────────────────────────────────────────────────────
function saveVehicle() {
  var id      = $('#vehicle_id').val();
  var payload = {
    vehicle_type:      $('#vf_type').val(),
    license_plate:     $('#vf_plate').val().trim().toUpperCase(),
    province:          $('#vf_province').val().trim(),
    brand:             $('#vf_brand').val().trim(),
    model:             $('#vf_model').val().trim(),
    color:             $('#vf_color').val().trim(),
    map_link:          $('#vf_map_link').val().trim(),
    note:              $('#vf_note').val().trim(),
    active:            parseInt($('#vf_active').val()),
  };

  if (!payload.vehicle_type) { Swal.fire('แจ้งเตือน','กรุณาเลือกประเภทยานพาหนะ','warning'); return; }
  if (!payload.license_plate) { Swal.fire('แจ้งเตือน','กรุณาระบุป้ายทะเบียน','warning'); return; }

  var url    = BASE + 'api/vehicle' + (id ? '/' + id : '');
  var method = id ? 'PUT' : 'POST';

  $.ajax({
    url: url, method: method,
    contentType: 'application/json',
    data: JSON.stringify(payload),
    success: function(r) {
      if (!r.success) { Swal.fire('ผิดพลาด', r.message, 'error'); return; }
      bootstrap.Modal.getInstance(document.getElementById('vehicleModal')).hide();
      Swal.fire({ icon:'success', title: r.message, timer:1500, showConfirmButton:false });
      loadVehicles();
    },
    error: function(xhr) {
      var msg = xhr.responseJSON ? xhr.responseJSON.message : 'เกิดข้อผิดพลาด';
      Swal.fire('ผิดพลาด', msg, 'error');
    }
  });
}

// ── init ───────────────────────────────────────────────────────
$(function() {
  vehicleTable = $('#vehicleTable').DataTable({
    data: [],
    columns: [
      { data: 'id', width: '50px' },
      { data: 'vehicle_type', render: function(v){ return typeIcon(v); } },
      { data: 'license_plate', render: function(v){
          return '<span class="plate-badge">' + (v||'-') + '</span>';
        }
      },
      { data: 'province',  defaultContent: '-' },
      { data: null, render: function(d){
          var parts = [d.brand, d.model].filter(Boolean);
          return parts.length ? parts.join(' ') : '-';
        }
      },
      { data: 'color', defaultContent: '-' },
      { data: 'map_link', render: function(v){
          if (!v) return '-';
          var safe = escAttr(v);
          var body = '<span class="text-truncate d-inline-block align-middle" style="max-width:200px">' + safe + '</span>';
          var href = normalizeLinkHref(v);
          if (href) {
            return '<a href="' + escAttr(href) + '" target="_blank" rel="noopener" class="text-decoration-none" title="' + safe + '">'
                 + '<i class="bi bi-geo-alt-fill text-danger me-1"></i>' + body + '</a>';
          }
          return '<span title="' + safe + '">' + body + '</span>';
        }
      },
      { data: 'active', className: 'text-center', render: function(v){
          return v == 1
            ? '<span class="badge bg-success">ใช้งาน</span>'
            : '<span class="badge bg-secondary">ไม่ใช้งาน</span>';
        }
      },
      { data: null, className: 'text-center', orderable: false, render: function(d){
          return '<button class="btn btn-sm btn-outline-warning me-1" onclick="editVehicle(' + d.id + ')" title="แก้ไข"><i class="bi bi-pencil"></i></button>'
               + '<button class="btn btn-sm btn-outline-danger" onclick="promptDeleteVehicle(' + d.id + ',\'' + (d.license_plate||'').replace(/'/g,"\\'") + '\')" title="ลบ"><i class="bi bi-trash"></i></button>';
        }
      },
    ],
    language: {
      search: 'ค้นหา:', lengthMenu: 'แสดง _MENU_ แถว',
      info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
      infoEmpty: 'ไม่มีข้อมูล', zeroRecords: 'ไม่พบข้อมูล',
      paginate: { first:'แรก', last:'สุดท้าย', next:'ถัดไป', previous:'ก่อนหน้า' }
    },
    order: [[0,'desc']],
    pageLength: 25,
  });

  loadVehicles();

  // auto uppercase plate input
  $('#vf_plate').on('input', function(){
    var pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

  $('#btn-add-vehicle').on('click', function() {
    resetForm();
    new bootstrap.Modal(document.getElementById('vehicleModal')).show();
  });

  $('#vehicleSaveBtn').on('click', saveVehicle);

  $('#confirmDeleteBtn').on('click', function() {
    if (!deleteId) return;
    $.ajax({
      url: BASE + 'api/vehicle/' + deleteId, method: 'DELETE',
      success: function(r) {
        bootstrap.Modal.getInstance(document.getElementById('vehicleDeleteModal')).hide();
        Swal.fire({ icon:'success', title: r.message, timer:1500, showConfirmButton:false });
        loadVehicles();
      },
      error: function(xhr) {
        var msg = xhr.responseJSON ? xhr.responseJSON.message : 'เกิดข้อผิดพลาด';
        Swal.fire('ผิดพลาด', msg, 'error');
      }
    });
  });
});

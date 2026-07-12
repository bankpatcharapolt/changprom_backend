<style>
.table th { font-size:.8rem; white-space:nowrap; }
.table td { font-size:.83rem; vertical-align:middle; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>จัดการสาขา</h4>
    <p class="text-muted small mb-0">เพิ่ม แก้ไข ลบ ข้อมูลสาขา</p>
  </div>
  <button class="btn btn-primary" id="btn-add"><i class="bi bi-plus-lg me-1"></i>เพิ่มสาขา</button>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="branchTable" class="table table-hover mb-0 align-middle" style="width:100%">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>ชื่อสาขา</th>
            <th>ที่อยู่</th>
            <th>เบอร์โทร</th>
            <th>Lat, Lng</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Add/Edit -->
<div class="modal fade" id="branchModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="branchModalTitle"><i class="bi bi-building me-2"></i>เพิ่มสาขา</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="branch_id">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-medium">ชื่อสาขา <span class="text-danger">*</span></label>
            <input type="text" id="bf_name" class="form-control" placeholder="เช่น Big C สุขวัสดิ์">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">Latitude</label>
            <input type="number" id="bf_lat" class="form-control" placeholder="เช่น 13.756331" step="any">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">Longitude</label>
            <input type="number" id="bf_lng" class="form-control" placeholder="เช่น 100.501762" step="any">
          </div>
          <div class="col-12">
            <label class="form-label fw-medium">ที่อยู่</label>
            <textarea id="bf_address" class="form-control" rows="2" placeholder="ที่อยู่สาขา..."></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">เบอร์โทร</label>
            <input type="text" id="bf_phone" class="form-control" placeholder="02-xxx-xxxx">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-medium">สถานะ</label>
            <select id="bf_active" class="form-select">
              <option value="1">✅ ใช้งาน</option>
              <option value="0">❌ ไม่ใช้งาน</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>ยกเลิก</button>
        <button class="btn btn-primary" id="btn-save"><i class="bi bi-save me-1"></i>บันทึก</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Delete -->
<div class="modal fade" id="branchDeleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>ยืนยันการลบ</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-1">ลบสาขา</p>
        <p class="fw-bold" id="delete-name-display">-</p>
        <p class="text-muted small">ข้อมูลจะถูกลบถาวร</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
        <button class="btn btn-danger btn-sm" id="btn-confirm-delete"><i class="bi bi-trash me-1"></i>ลบเลย</button>
      </div>
    </div>
  </div>
</div>

<script>
var BASE_URL = '<?= site_url() ?>';
var API      = BASE_URL + 'api/branch';
var API_GET  = BASE_URL + 'api/branch/get/';
var API_UPD  = BASE_URL + 'api/branch/update/';
var API_DEL  = BASE_URL + 'api/branch/delete/';
var API_NEW  = BASE_URL + 'api/branch/create';
var table, deleteId;

function loadBranches() {
  $.get(API, function(r) {
    if (!r.success) return;
    table.clear().rows.add(r.data).draw();
  });
}

function resetForm() {
  $('#branch_id,#bf_name,#bf_lat,#bf_lng,#bf_address,#bf_phone').val('');
  $('#bf_active').val('1');
  $('#branchModalTitle').html('<i class="bi bi-plus-circle me-2"></i>เพิ่มสาขา');
}

window.editBranch = function(id) {
  $.get(API_GET + id, function(r) {
    if (!r.success) return;
    var d = r.data;
    $('#branch_id').val(d.id);
    $('#bf_name').val(d.name || '');
    $('#bf_lat').val(d.lat || '');
    $('#bf_lng').val(d.lng || '');
    $('#bf_address').val(d.address || '');
    $('#bf_phone').val(d.phone || '');
    $('#bf_active').val(d.active != null ? d.active : 1);
    $('#branchModalTitle').html('<i class="bi bi-pencil-square me-2"></i>แก้ไขสาขา');
    new bootstrap.Modal(document.getElementById('branchModal')).show();
  });
};

window.deleteBranch = function(id, name) {
  deleteId = id;
  $('#delete-name-display').text(name);
  new bootstrap.Modal(document.getElementById('branchDeleteModal')).show();
};

$(function() {
  table = $('#branchTable').DataTable({
    data: [],
    columns: [
      { data: 'id', width: '50px' },
      { data: 'name' },
      { data: 'address', defaultContent: '-', render: function(v){ return v ? (v.length > 40 ? v.substr(0,40)+'…' : v) : '-'; } },
      { data: 'phone', defaultContent: '-' },
      { data: null, render: function(d){
          if (!d.lat || !d.lng) return '<span class="text-muted">-</span>';
          return '<small class="text-muted">' + parseFloat(d.lat).toFixed(6) + ', ' + parseFloat(d.lng).toFixed(6) + '</small>';
        }
      },
      { data: 'active', className:'text-center', render: function(v){
          return v == 1 ? '<span class="badge bg-success">ใช้งาน</span>' : '<span class="badge bg-secondary">ไม่ใช้งาน</span>';
        }
      },
      { data: null, className:'text-center', orderable:false, render: function(d){
          return '<button class="btn btn-sm btn-outline-warning me-1" onclick="editBranch(' + d.id + ')"><i class="bi bi-pencil"></i></button>'
               + '<button class="btn btn-sm btn-outline-danger" onclick="deleteBranch(' + d.id + ',\'' + (d.name||'').replace(/'/g,"\\'") + '\')"><i class="bi bi-trash"></i></button>';
        }
      },
    ],
    language: {
      search: 'ค้นหา:', lengthMenu: 'แสดง _MENU_ แถว',
      info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
      infoEmpty: 'ไม่มีข้อมูล', zeroRecords: 'ไม่พบข้อมูล',
      paginate: { first:'แรก', last:'สุดท้าย', next:'ถัดไป', previous:'ก่อนหน้า' }
    },
    order: [[0,'desc']], pageLength: 25,
  });

  loadBranches();

  $('#btn-add').on('click', function() {
    resetForm();
    new bootstrap.Modal(document.getElementById('branchModal')).show();
  });

  $('#btn-save').on('click', function() {
    var id   = $('#branch_id').val();
    var name = $('#bf_name').val().trim();
    if (!name) { Swal.fire('แจ้งเตือน','กรุณาระบุชื่อสาขา','warning'); return; }
    var payload = {
      name:    name,
      lat:     $('#bf_lat').val()     || null,
      lng:     $('#bf_lng').val()     || null,
      address: $('#bf_address').val() || null,
      phone:   $('#bf_phone').val()   || null,
      active:  parseInt($('#bf_active').val()),
    };
    var url    = id ? API_UPD + id : API_NEW;
    var method = 'POST';
    $.ajax({ url:url, method:method, contentType:'application/json', data:JSON.stringify(payload),
      success: function(r) {
        if (!r.success) { Swal.fire('ผิดพลาด', r.message, 'error'); return; }
        bootstrap.Modal.getInstance(document.getElementById('branchModal')).hide();
        Swal.fire({ icon:'success', title:r.message, timer:1500, showConfirmButton:false });
        loadBranches();
      },
      error: function(xhr) {
        Swal.fire('ผิดพลาด', xhr.responseJSON ? xhr.responseJSON.message : 'เกิดข้อผิดพลาด', 'error');
      }
    });
  });

  $('#btn-confirm-delete').on('click', function() {
    if (!deleteId) return;
    $.ajax({ url: API_DEL + deleteId, method:'POST',
      success: function(r) {
        bootstrap.Modal.getInstance(document.getElementById('branchDeleteModal')).hide();
        Swal.fire({ icon:'success', title:r.message, timer:1500, showConfirmButton:false });
        loadBranches();
      }
    });
  });
});
</script>

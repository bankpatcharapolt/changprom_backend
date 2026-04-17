$(document).ready(function () {
  var selectedFile = null;

  var dropZone    = document.getElementById('drop-zone');
  var fileInput   = document.getElementById('excel_file');
  var importBtn   = document.getElementById('import-btn');
  var clearBtn    = document.getElementById('clear-btn');
  var preview     = document.getElementById('file-preview');
  var fileName    = document.getElementById('file-name');
  var progressBox = document.getElementById('progress-box');
  var resultBox   = document.getElementById('result-box');

  dropZone.addEventListener('click', function () { fileInput.click(); });
  dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.style.background = '#f0f4ff'; });
  dropZone.addEventListener('dragleave', function () { dropZone.style.background = ''; });
  dropZone.addEventListener('drop', function (e) {
    e.preventDefault(); dropZone.style.background = '';
    if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
  });
  fileInput.addEventListener('change', function () { if (fileInput.files.length) setFile(fileInput.files[0]); });
  clearBtn.addEventListener('click', clearFile);
  importBtn.addEventListener('click', doImport);

  function setFile(file) {
    var ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls', 'csv'].includes(ext)) {
      Swal.fire('ไฟล์ไม่ถูกต้อง', 'รองรับเฉพาะ .xlsx, .xls, .csv เท่านั้น', 'warning'); return;
    }
    selectedFile = file;
    fileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    preview.classList.remove('d-none');
    importBtn.disabled = false;
    resultBox.classList.add('d-none');
  }

  function clearFile() {
    selectedFile = null; fileInput.value = '';
    preview.classList.add('d-none');
    importBtn.disabled = true;
    resultBox.classList.add('d-none');
  }

  function doImport() {
    if (!selectedFile) return;
    var fd = new FormData();
    fd.append('excel_file', selectedFile);

    importBtn.disabled = true;
    progressBox.classList.remove('d-none');
    resultBox.classList.add('d-none');

    $.ajax({
      url: BASE + 'service/import_excel',
      method: 'POST', data: fd, processData: false, contentType: false,
      success: function (r) {
        progressBox.classList.add('d-none');
        if (r.success) {
          showSuccessModal(r);
          clearFile();
        } else {
          showErrorModal(r.message);
          importBtn.disabled = false;
        }
      },
      error: function (xhr) {
        progressBox.classList.add('d-none');
        var msg = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
        try { var j = JSON.parse(xhr.responseText); if (j.message) msg = j.message; } catch(e) {}
        showErrorModal(msg);
        importBtn.disabled = false;
      }
    });
  }

  // ---- Modal สำเร็จ ----
  function showSuccessModal(r) {
    var errRows = '';
    if (r.errors && r.errors.length) {
      errRows = r.errors.map(function (e) {
        return '<li class="text-danger small">' + e + '</li>';
      }).join('');
    }

    var html = '<div class="text-center mb-4">'
      + '<div style="width:64px;height:64px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">'
      + '<i class="bi bi-check-lg text-success fs-2"></i></div>'
      + '<h5 class="fw-bold text-success">นำเข้าสำเร็จ!</h5>'
      + '<p class="text-muted small mb-0">' + selectedFile.name + '</p>'
      + '</div>'

      + '<div class="row g-3 mb-3">'
      + '<div class="col-4 text-center">'
      + '<div style="background:#f0fdf4;border-radius:12px;padding:16px 8px">'
      + '<div class="fw-bold fs-3 text-success">' + (r.imported || 0) + '</div>'
      + '<div class="small text-muted">รายการใหม่</div>'
      + '</div></div>'

      + '<div class="col-4 text-center">'
      + '<div style="background:#eff6ff;border-radius:12px;padding:16px 8px">'
      + '<div class="fw-bold fs-3 text-primary">' + (r.updated || 0) + '</div>'
      + '<div class="small text-muted">อัปเดต</div>'
      + '</div></div>'

      + '<div class="col-4 text-center">'
      + '<div style="background:#fef9c3;border-radius:12px;padding:16px 8px">'
      + '<div class="fw-bold fs-3 text-warning">' + ((r.errors && r.errors.length) || 0) + '</div>'
      + '<div class="small text-muted">ข้าม/แจ้งเตือน</div>'
      + '</div></div>'
      + '</div>';

    if (errRows) {
      html += '<div class="mb-3">'
        + '<div class="fw-medium small text-warning mb-1"><i class="bi bi-exclamation-triangle me-1"></i>แถวที่มีปัญหา</div>'
        + '<div style="max-height:140px;overflow-y:auto;background:#fff8f0;border-radius:8px;padding:10px 14px">'
        + '<ul class="mb-0 ps-3">' + errRows + '</ul>'
        + '</div></div>';
    }

    html += '<div class="d-flex gap-2 justify-content-end">'
      + '<button class="btn btn-outline-secondary" onclick="bootstrap.Modal.getInstance(document.getElementById(\'importResultModal\')).hide()">ปิด</button>'
      + '<a href="' + BASE + 'service" class="btn btn-success"><i class="bi bi-list-ul me-2"></i>ดูรายการงาน</a>'
      + '</div>';

    document.getElementById('importResultBody').innerHTML = html;
    document.getElementById('importResultTitle').innerHTML
      = '<i class="bi bi-check-circle-fill text-success me-2"></i>ผลการนำเข้าข้อมูล';
    new bootstrap.Modal(document.getElementById('importResultModal')).show();
  }

  // ---- Modal ไม่สำเร็จ ----
  function showErrorModal(msg) {
    var html = '<div class="text-center mb-4">'
      + '<div style="width:64px;height:64px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">'
      + '<i class="bi bi-x-lg text-danger fs-2"></i></div>'
      + '<h5 class="fw-bold text-danger">นำเข้าไม่สำเร็จ</h5>'
      + '</div>'
      + '<div style="background:#fff1f2;border-left:4px solid #ef4444;border-radius:4px;padding:14px 16px;margin-bottom:20px">'
      + '<div class="fw-medium text-danger mb-1"><i class="bi bi-exclamation-circle me-2"></i>สาเหตุ</div>'
      + '<div class="small text-danger">' + msg + '</div>'
      + '</div>'
      + '<div class="small text-muted mb-4">แนะนำให้ตรวจสอบ:'
      + '<ul class="mt-1 mb-0">'
      + '<li>ไฟล์เป็นรูปแบบ <strong>receipt_report_export</strong> ที่ถูกต้อง</li>'
      + '<li>ไม่มีการเปิดไฟล์ค้างอยู่ใน Excel</li>'
      + '<li>ไฟล์ไม่ได้ถูกป้องกันด้วยรหัสผ่าน</li>'
      + '</ul></div>'
      + '<div class="d-flex justify-content-end">'
      + '<button class="btn btn-outline-secondary" onclick="bootstrap.Modal.getInstance(document.getElementById(\'importResultModal\')).hide()">ปิด</button>'
      + '</div>';

    document.getElementById('importResultBody').innerHTML = html;
    document.getElementById('importResultTitle').innerHTML
      = '<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>นำเข้าไม่สำเร็จ';
    new bootstrap.Modal(document.getElementById('importResultModal')).show();
  }
});

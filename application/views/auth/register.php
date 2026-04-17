<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ลงทะเบียน - ระบบจัดการงานบริการ</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
  .card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
  .card-header { background: #fff; border-radius: 16px 16px 0 0 !important; border-bottom: 1px solid #f0f0f0; }
  .btn-success { background: linear-gradient(135deg, #11998e, #38ef7d); border: none; }
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-danger mb-3"><?= $this->session->flashdata('error') ?></div>
      <?php endif; ?>
      <div class="card">
        <div class="card-header text-center py-4">
          <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>ลงทะเบียนใช้งาน</h4>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="<?= site_url('register') ?>">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <div class="mb-3">
              <label class="form-label fw-medium">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
              <input type="text" name="full_name" class="form-control" required placeholder="ชื่อ-นามสกุลของคุณ">
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control" required placeholder="username">
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">อีเมล <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required placeholder="email@example.com">
            </div>
            <div class="mb-4">
              <label class="form-label fw-medium">รหัสผ่าน <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control" required placeholder="อย่างน้อย 6 ตัวอักษร" minlength="6">
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 fw-medium">
              <i class="bi bi-person-check me-2"></i>ลงทะเบียน
            </button>
          </form>
          <hr>
          <p class="text-center mb-0 small text-muted">
            มีบัญชีแล้ว? <a href="<?= site_url('login') ?>" class="text-decoration-none fw-medium">เข้าสู่ระบบ</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

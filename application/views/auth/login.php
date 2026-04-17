<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ - ระบบจัดการงานบริการ</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
  .card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
  .card-header { background: #fff; border-radius: 16px 16px 0 0 !important; border-bottom: 1px solid #f0f0f0; }
  .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); border: none; }
  .brand-icon { width: 56px; height: 56px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-danger mb-3">
        <i class="bi bi-exclamation-triangle me-2"></i><?= $this->session->flashdata('error') ?>
      </div>
      <?php endif; ?>
      <?php if ($this->session->flashdata('success')): ?>
      <div class="alert alert-success mb-3">
        <i class="bi bi-check-circle me-2"></i><?= $this->session->flashdata('success') ?>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header text-center py-4">
          <div class="brand-icon">
            <i class="bi bi-tools text-white fs-4"></i>
          </div>
          <h4 class="mb-0 fw-bold">ระบบจัดการงานบริการ</h4>
          <p class="text-muted small mb-0">กรุณาเข้าสู่ระบบเพื่อดำเนินการ</p>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="<?= site_url('login') ?>">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <div class="mb-3">
              <label class="form-label fw-medium">ชื่อผู้ใช้ / อีเมล</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="กรอกชื่อผู้ใช้" required autofocus>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-medium">รหัสผ่าน</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">
              <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
            </button>
          </form>
          <hr>
          <p class="text-center mb-0 small text-muted">
            ยังไม่มีบัญชี? <a href="<?= site_url('register') ?>" class="text-decoration-none fw-medium">ลงทะเบียน</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

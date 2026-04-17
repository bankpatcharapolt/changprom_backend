<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? $title . ' - ' : '' ?>ระบบจัดการงานบริการ</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* { font-family: 'Noto Sans Thai', 'Segoe UI', sans-serif; }

</style>
<!-- Global JS variables -->
<script>
var BASE = '<?= site_url() ?>';
var ASSET = '<?= base_url() ?>';
</script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= site_url('dashboard') ?>">
      <i class="bi bi-tools me-2"></i>ระบบงานบริการ
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?= uri_string()=='dashboard'?'active':'' ?>" href="<?= site_url('dashboard') ?>">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= uri_string()=='dashboard/calendar'?'active':'' ?>" href="<?= site_url('dashboard/calendar') ?>">
            <i class="bi bi-calendar3 me-1"></i>ตารางคิวช่าง
          </a>
        </li>
          
        <li class="nav-item">
          <a class="nav-link <?= strpos(uri_string(),'technician')!==false?'active':'' ?>" href="<?= site_url('technician') ?>">
            <i class="bi bi-people me-1"></i>จัดการช่าง
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= uri_string()=='service'?'active':'' ?>" href="<?= site_url('service') ?>">
            <i class="bi bi-list-ul me-1"></i>รายการงาน
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= strpos(uri_string(),'import')!==false?'active':'' ?>" href="<?= site_url('service/import') ?>">
            <i class="bi bi-file-earmark-excel me-1"></i>นำเข้า Excel
          </a>
        </li>
     
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small"><i class="bi bi-person-circle me-1"></i><?= $this->session->userdata('full_name') ?></span>
        <a href="<?= site_url('logout') ?>" class="btn btn-outline-light btn-sm">
          <i class="bi bi-box-arrow-right me-1"></i>ออกจากระบบ
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="bi bi-check-circle me-2"></i><?= $this->session->flashdata('success') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
  <i class="bi bi-exclamation-triangle me-2"></i><?= $this->session->flashdata('error') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

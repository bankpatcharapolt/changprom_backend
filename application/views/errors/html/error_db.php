<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title><?php echo $heading; ?></title>
<style>
body { background:#fafafa; font-family:'Segoe UI',sans-serif; margin:40px; color:#333; }
.box { max-width:700px; margin:0 auto; background:#fff; border:1px solid #e0e0e0; border-radius:8px; padding:32px; }
h1 { color:#8e44ad; font-size:22px; margin-top:0; }
pre { background:#f5f5f5; padding:16px; border-radius:4px; overflow:auto; font-size:13px; }
</style>
</head>
<body>
<div class="box">
  <h1><?php echo $heading; ?></h1>
  <?php echo $message; ?>
</div>
</body>
</html>

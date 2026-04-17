<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>404 - ไม่พบหน้าที่ต้องการ</title>
<style>
body { background:#fafafa; font-family:'Segoe UI',sans-serif; margin:0; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.box { text-align:center; }
h1 { font-size:80px; margin:0; color:#3498db; }
p { color:#666; font-size:18px; }
a { color:#3498db; text-decoration:none; }
</style>
</head>
<body>
<div class="box">
  <h1>404</h1>
  <p><?php echo $heading; ?></p>
  <?php echo $message; ?>
  <p><a href="javascript:history.back()">← กลับหน้าเดิม</a></p>
</div>
</body>
</html>

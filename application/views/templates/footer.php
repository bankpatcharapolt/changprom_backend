</div><!-- end container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($extra_js)): ?>
  <?php foreach((array)$extra_js as $url): ?>
  <script src="<?= $url ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($page_js)): ?>
  <?php foreach((array)$page_js as $js): ?>
  <script src="<?= base_url('assets/js/'.$js.'.js') ?>?v=<?= filemtime(FCPATH.'assets/js/'.$js.'.js') ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>

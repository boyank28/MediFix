<!-- Footer -->
  <footer class="main-footer">
    <div class="pull-right hidden-xs">
      <b>Version</b> 1.0.0
    </div>
    <strong>Copyright &copy; <?= date('Y') ?> MediFix Apps.</strong> M. Wira Satria Buana - 082177846209
  </footer>

</div>

<!-- Modal Tentang -->
<div class="modal fade" id="tentangModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #3c8dbc, #367fa9); color: white;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1;">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">
          <i class="fa fa-heart"></i> Tentang MediFix Apps
        </h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="box box-solid">
              <div class="box-header">
                <h3 class="box-title">Lisensi & Ketentuan</h3>
              </div>
              <div class="box-body">
                <ul style="font-size: 13px;">
                  <li><strong>Aplikasi GRATIS</strong> untuk pengguna SIMRS Khanza</li>
                  <li><strong>DILARANG</strong> diperjualbelikan</li>
                  <li><strong>Open Source</strong> boleh dikembangkan</li>
                  <li><strong>Table</strong>: antrian_wira, loket_admisi_wira</li>
                </ul>
              </div>
            </div>
            
            <div class="box box-success box-solid">
              <div class="box-header">
                <h3 class="box-title">Dukung Pengembangan</h3>
              </div>
              <div class="box-body text-center">
                <p style="font-size: 13px;">Aplikasi <strong>GRATIS</strong> selamanya. Donasi sukarela 🙏</p>
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                  <h4 style="margin: 0; color: #00a65a;"><strong>7134197557</strong></h4>
                  <small>BSI - a.n. M Wira Satria Buana</small>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="box box-primary box-solid">
              <div class="box-header">
                <h3 class="box-title">Fitur Utama</h3>
              </div>
              <div class="box-body">
                <ul style="font-size: 13px;">
                  <li>Anjungan Pasien Mandiri</li>
                  <li>Antrian Admisi dengan Audio</li>
                  <li>Antrian Poliklinik Multi-Dokter</li>
                  <li>Antrian Farmasi</li>
                  <li>Display Real-time</li>
                  <li>Dashboard Monitor</li>
                </ul>
              </div>
            </div>
            
            <div class="box box-warning box-solid">
              <div class="box-header">
                <h3 class="box-title">Developer</h3>
              </div>
              <div class="box-body text-center">
                <p style="margin: 0; font-size: 13px;">
                  <strong>Dikembangkan dengan ❤️ oleh:</strong><br>
                  <span style="font-size: 16px;"><strong>M. Wira Satria Buana, S.Kom</strong></span><br>
                  <i class="fa fa-whatsapp" style="color: #25D366;"></i> <strong>082177846209</strong><br>
                  <small>© 2024 MediFix Apps</small>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- jQuery 3 -->
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/js/adminlte.min.js"></script>

<?php if (isset($extra_js)): ?>
<script>
<?= $extra_js ?>
</script>
<?php endif; ?>

</body>
</html>
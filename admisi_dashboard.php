<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
date_default_timezone_set('Asia/Jakarta');

// Include header
include 'includes/header.php';

// Include sidebar
include 'includes/sidebar.php';
?>
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Dashboard Admisi - MediFix</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <!-- AdminLTE -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/skins/_all-skins.min.css">
  
  <style>
    /* Menu Cards */
    .menu-box {
      background: #fff;
      border-radius: 5px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.12);
      margin-bottom: 20px;
      transition: all 0.3s ease;
      border-top: 3px solid;
      position: relative;
      overflow: hidden;
    }
    
    .menu-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .menu-box-content {
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .menu-icon {
      width: 60px;
      height: 60px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      color: white;
      flex-shrink: 0;
    }
    
    .menu-info {
      flex: 1;
    }
    
    .menu-title {
      font-size: 16px;
      font-weight: 700;
      color: #333;
      margin-bottom: 5px;
    }
    
    .menu-desc {
      font-size: 13px;
      color: #666;
    }
    
    .menu-arrow {
      font-size: 24px;
      color: #ddd;
      transition: all 0.3s ease;
    }
    
    .menu-box:hover .menu-arrow {
      transform: translateX(5px);
    }
    
    /* Menu Colors */
    .menu-panggil {
      border-top-color: #dd4b39;
    }
    .menu-panggil .menu-icon {
      background: #dd4b39;
    }
    
    .menu-display {
      border-top-color: #d73925;
    }
    .menu-display .menu-icon {
      background: #d73925;
    }
    
    .menu-finger {
      border-top-color: #3c8dbc;
    }
    .menu-finger .menu-icon {
      background: #3c8dbc;
    }
    
    .menu-kamar {
      border-top-color: #00c0ef;
    }
    .menu-kamar .menu-icon {
      background: #00c0ef;
    }
    
    .menu-jadwal {
      border-top-color: #605ca8;
    }
    .menu-jadwal .menu-icon {
      background: #605ca8;
    }
    
    .menu-back {
      border-top-color: #999;
    }
    .menu-back .menu-icon {
      background: #999;
    }
    
    /* Welcome Box */
    .welcome-box {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 5px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .welcome-box h3 {
      margin: 0 0 10px 0;
      font-size: 24px;
      font-weight: 700;
    }
    
    .welcome-box p {
      margin: 0;
      opacity: 0.9;
      font-size: 14px;
    }
    
    .welcome-box .date-time {
      margin-top: 10px;
      font-size: 13px;
      opacity: 0.8;
    }
  </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  

  

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content-header">
      <h1>
        Dashboard Admisi
        <small>Pusat Kontrol Pendaftaran & Administrasi Pasien</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Admisi</li>
      </ol>
    </section>

    <section class="content">
      
      <!-- Welcome Box -->
      <div class="welcome-box">
        <h3>👋 Selamat Datang di Dashboard Admisi</h3>
        <p>Pusat Kontrol Pendaftaran & Administrasi Pasien</p>
        <div class="date-time">
          <i class="fa fa-calendar"></i> <span id="tanggalSekarang"></span> | 
          <i class="fa fa-clock-o"></i> <span id="clockDisplay"></span>
        </div>
      </div>

      <!-- Menu Cards -->
      <div class="row">
        
        <!-- Panggil Admisi -->
        <div class="col-md-4 col-sm-6">
          <a href="data_antri_admisi.php" 
             class="menu-box menu-panggil"
             style="display: block; text-decoration: none; color: inherit;">
            <div class="menu-box-content">
              <div class="menu-icon">
                <i class="fa fa-phone"></i>
              </div>
              <div class="menu-info">
                <div class="menu-title">Panggil Admisi</div>
                <div class="menu-desc">Kelola antrian pasien</div>
              </div>
              <div class="menu-arrow">
                <i class="fa fa-angle-right"></i>
              </div>
            </div>
          </a>
        </div>
        
        <!-- Display Admisi -->
        <div class="col-md-4 col-sm-6">
          <a href="display_admisi.php" target="_blank"
             class="menu-box menu-display"
             style="display: block; text-decoration: none; color: inherit;">
            <div class="menu-box-content">
              <div class="menu-icon">
                <i class="fa fa-television"></i>
              </div>
              <div class="menu-info">
                <div class="menu-title">Display Admisi</div>
                <div class="menu-desc">Tampilan layar antrian</div>
              </div>
              <div class="menu-arrow">
                <i class="fa fa-angle-right"></i>
              </div>
            </div>
          </a>
        </div>
        
        <!-- Fingerprint (Optional - jika ada) -->
        <!-- Uncomment jika Anda punya halaman fingerprint
        <div class="col-md-4 col-sm-6">
          <a href="fingerprint.php"
             class="menu-box menu-finger"
             style="display: block; text-decoration: none; color: inherit;">
            <div class="menu-box-content">
              <div class="menu-icon">
                <i class="fa fa-hand-paper-o"></i>
              </div>
              <div class="menu-info">
                <div class="menu-title">Fingerprint</div>
                <div class="menu-desc">Absensi sidik jari</div>
              </div>
              <div class="menu-arrow">
                <i class="fa fa-angle-right"></i>
              </div>
            </div>
          </a>
        </div>
        -->
        
        <!-- Ketersediaan Kamar -->
        <div class="col-md-4 col-sm-6">
          <a href="ketersediaan_kamar.php" target="_blank"
             class="menu-box menu-kamar"
             style="display: block; text-decoration: none; color: inherit;">
            <div class="menu-box-content">
              <div class="menu-icon">
                <i class="fa fa-building"></i>
              </div>
              <div class="menu-info">
                <div class="menu-title">Ketersediaan Kamar</div>
                <div class="menu-desc">Cek status kamar rawat</div>
              </div>
              <div class="menu-arrow">
                <i class="fa fa-angle-right"></i>
              </div>
            </div>
          </a>
        </div>
        
        <!-- Jadwal Dokter -->
        <div class="col-md-4 col-sm-6">
          <a href="display_jadwal_dokter.php" target="_blank"
             class="menu-box menu-jadwal"
             style="display: block; text-decoration: none; color: inherit;">
            <div class="menu-box-content">
              <div class="menu-icon">
                <i class="fa fa-calendar-check-o"></i>
              </div>
              <div class="menu-info">
                <div class="menu-title">Jadwal Dokter</div>
                <div class="menu-desc">Lihat jadwal praktik</div>
              </div>
              <div class="menu-arrow">
                <i class="fa fa-angle-right"></i>
              </div>
            </div>
          </a>
        </div>
        
        <!-- Kembali -->
        <div class="col-md-4 col-sm-6">
          <a href="dashboard.php"
             class="menu-box menu-back"
             style="display: block; text-decoration: none; color: inherit;">
            <div class="menu-box-content">
              <div class="menu-icon">
                <i class="fa fa-arrow-left"></i>
              </div>
              <div class="menu-info">
                <div class="menu-title">Kembali</div>
                <div class="menu-desc">Ke dashboard utama</div>
              </div>
              <div class="menu-arrow">
                <i class="fa fa-angle-right"></i>
              </div>
            </div>
          </a>
        </div>
        
      </div>

    </section>
  </div>


<?php
// Include footer
include 'includes/footer.php';
?>

</div>

<!-- jQuery 3 -->
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/js/adminlte.min.js"></script>

<script>
function updateClock() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const tanggal = now.toLocaleDateString('id-ID', options);
    const waktu = now.toLocaleTimeString('id-ID');
    
    document.getElementById('tanggalSekarang').innerHTML = tanggal;
    document.getElementById('clockDisplay').innerHTML = waktu;
}

setInterval(updateClock, 1000);
updateClock();
</script>

</body>
</html>
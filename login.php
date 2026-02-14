<?php
session_start();
include 'koneksi.php';
include 'koneksi2.php';

// Jika sudah login → arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Ambil informasi Rumah Sakit
try {
    $stmt = $pdo_simrs->query("SELECT * FROM setting LIMIT 1");
    $rs = $stmt->fetch(PDO::FETCH_ASSOC);
    $namaRS      = $rs['nama_instansi'] ?? 'Nama Rumah Sakit';
    $alamatRS    = $rs['alamat_instansi'] ?? '';
    $kabupatenRS = $rs['kabupaten'] ?? '';
    $propinsiRS  = $rs['propinsi'] ?? '';
} catch (Exception $e) {
    $namaRS = 'Nama Rumah Sakit';
    $alamatRS = $kabupatenRS = $propinsiRS = '';
}

$error = '';
$success = '';

/* =========================
        PROSES LOGIN
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {

    $login    = trim($_POST['login']);
    $password = trim($_POST['password']);

    if ($login === '' || $password === '') {
        $error = "Email/NIK dan Password wajib diisi.";
    } else {

        $stmt = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1")
            : $pdo->prepare("SELECT * FROM users WHERE nik = ? LIMIT 1");

        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];

            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Email/NIK atau Password salah.";
        }
    }
}


/* =========================
        PROSES REGISTER
========================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {

    $nik      = trim($_POST['nik']);
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $hp       = trim($_POST['hp']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if (!$nik || !$nama || !$email || !$password || !$confirm) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nik = ? OR email = ?");
        $cek->execute([$nik, $email]);

        if ($cek->fetchColumn() > 0) {
            $error = 'NIK atau Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $pdo->prepare("INSERT INTO users (nik, nama, email, hp, password) VALUES (?, ?, ?, ?, ?)")
                ->execute([$nik, $nama, $email, $hp, $hash]);

            $success = 'Pendaftaran berhasil! Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>MediFix - Login</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <!-- AdminLTE -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Source Sans Pro', sans-serif;
    }
    
    .login-box {
      width: 400px;
      margin: 5% auto;
    }
    
    .login-logo {
      background: white;
      padding: 25px;
      border-radius: 10px 10px 0 0;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      text-align: center;
    }
    
    .login-logo a {
      color: #3c8dbc;
      font-size: 35px;
      font-weight: 700;
      text-decoration: none;
    }
    
    .login-logo-text {
      font-size: 16px;
      color: #666;
      margin-top: 5px;
    }
    
    .login-box-body {
      background: #fff;
      padding: 30px;
      border-radius: 0 0 10px 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    
    .login-box-msg {
      margin: 0 0 20px;
      text-align: center;
      color: #555;
      font-size: 16px;
      font-weight: 600;
    }
    
    .hospital-info {
      background: #f9f9f9;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      border-left: 4px solid #3c8dbc;
    }
    
    .hospital-info h4 {
      margin: 0 0 10px 0;
      color: #3c8dbc;
      font-size: 16px;
      font-weight: 700;
    }
    
    .hospital-info p {
      margin: 5px 0;
      color: #666;
      font-size: 13px;
    }
    
    .hospital-info i {
      margin-right: 8px;
      color: #3c8dbc;
      width: 16px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-control {
      height: 45px;
      border-radius: 5px;
      border: 1px solid #d2d6de;
      box-shadow: none;
      font-size: 14px;
    }
    
    .form-control:focus {
      border-color: #3c8dbc;
      box-shadow: 0 0 0 0.2rem rgba(60,141,188,0.25);
    }
    
    .input-group-addon {
      background: #fff;
      border: 1px solid #d2d6de;
      border-right: none;
      border-radius: 5px 0 0 5px;
    }
    
    .input-group .form-control {
      border-left: none;
      border-radius: 0 5px 5px 0;
    }
    
    .btn-primary {
      background-color: #3c8dbc;
      border-color: #367fa9;
      height: 45px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 5px;
    }
    
    .btn-primary:hover,
    .btn-primary:focus {
      background-color: #367fa9;
      border-color: #2f6c8f;
    }
    
    .btn-success {
      height: 45px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 5px;
    }
    
    .alert {
      border-radius: 5px;
      margin-bottom: 20px;
    }
    
    .login-footer {
      text-align: center;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #f4f4f4;
      color: #999;
      font-size: 13px;
    }
    
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translate3d(0, -20px, 0);
      }
      to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
      }
    }
    
    .login-box {
      animation: fadeInDown 0.5s;
    }
    
    .form-register {
      display: none;
    }
    
    .form-register.active {
      display: block;
    }
    
    .form-login.active {
      display: block;
    }
    
    .form-login {
      display: block;
    }
  </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="#"><b>Medi</b>Fix</a>
    <div class="login-logo-text">Anjungan Pasien Mandiri & Sistem Antrian</div>
  </div>
  
  <div class="login-box-body">
    
    <!-- Hospital Info -->
    <div class="hospital-info">
      <h4><i class="fa fa-hospital-o"></i> <?= htmlspecialchars($namaRS) ?></h4>
      <?php if (!empty($alamatRS)): ?>
      <p><i class="fa fa-map-marker"></i> <?= htmlspecialchars($alamatRS) ?></p>
      <?php endif; ?>
      <?php if (!empty($kabupatenRS) || !empty($propinsiRS)): ?>
      <p><i class="fa fa-map"></i> <?= htmlspecialchars($kabupatenRS) ?><?= !empty($propinsiRS) ? ' - ' . htmlspecialchars($propinsiRS) : '' ?></p>
      <?php endif; ?>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
      <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
      <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form id="loginForm" class="form-login <?= isset($_POST['register_submit']) ? '' : 'active' ?>" method="post">
      <p class="login-box-msg">Silakan login untuk melanjutkan</p>
      
      <div class="form-group has-feedback">
        <div class="input-group">
          <span class="input-group-addon">
            <i class="fa fa-user"></i>
          </span>
          <input type="text" name="login" class="form-control" placeholder="Email atau NIK" required>
        </div>
      </div>
      
      <div class="form-group has-feedback">
        <div class="input-group">
          <span class="input-group-addon">
            <i class="fa fa-lock"></i>
          </span>
          <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
      </div>
      
      <div class="row">
        <div class="col-xs-12">
          <button type="submit" name="login_submit" class="btn btn-primary btn-block btn-flat">
            <i class="fa fa-sign-in"></i> Masuk Sekarang
          </button>
        </div>
      </div>
      
      <div class="login-footer">
        Belum punya akun? <a href="#" id="showRegister" style="color: #3c8dbc; font-weight: 600;">Daftar di sini</a>
      </div>
    </form>

    <!-- Register Form -->
    <form id="registerForm" class="form-register <?= isset($_POST['register_submit']) ? 'active' : '' ?>" method="post">
      <p class="login-box-msg">Buat Akun Baru</p>
      
      <div class="row">
        <div class="col-xs-6">
          <div class="form-group">
            <input type="text" name="nik" class="form-control" placeholder="NIK" required>
          </div>
        </div>
        <div class="col-xs-6">
          <div class="form-group">
            <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-xs-6">
          <div class="form-group">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
          </div>
        </div>
        <div class="col-xs-6">
          <div class="form-group">
            <input type="text" name="hp" class="form-control" placeholder="Nomor HP">
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-xs-6">
          <div class="form-group">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
          </div>
        </div>
        <div class="col-xs-6">
          <div class="form-group">
            <input type="password" name="confirm" class="form-control" placeholder="Konfirmasi Password" required>
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-xs-12">
          <button type="submit" name="register_submit" class="btn btn-success btn-block btn-flat">
            <i class="fa fa-user-plus"></i> Daftar Sekarang
          </button>
        </div>
      </div>
      
      <div class="login-footer">
        Sudah punya akun? <a href="#" id="showLogin" style="color: #3c8dbc; font-weight: 600;">Login di sini</a>
      </div>
    </form>

    <div class="login-footer" style="margin-top: 30px; border-top: none;">
      <p>
        <i class="fa fa-copyright"></i> <?= date('Y') ?> MediFix Apps<br>
        <small><i class="fa fa-whatsapp"></i> 082177846209</small>
      </p>
    </div>
  </div>
</div>

<!-- jQuery 3 -->
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<script>
document.getElementById('showRegister')?.addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('loginForm').style.display = 'none';
  document.getElementById('registerForm').style.display = 'block';
});

document.getElementById('showLogin')?.addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('registerForm').style.display = 'none';
  document.getElementById('loginForm').style.display = 'block';
});
</script>

</body>
</html>
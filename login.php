<?php
session_start();
include 'koneksi.php';
include 'koneksi2.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

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
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>MediFix — Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue:     #2b7fc1;
      --blue-dk:  #1f6099;
      --blue-lt:  #e8f3fb;
      --green:    #00a65a;
      --green-dk: #008d4c;
      --red:      #e74c3c;
      --gray:     #64748b;
      --gray-lt:  #f1f5f9;
      --border:   #e2e8f0;
      --text:     #1e293b;
      --white:    #ffffff;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      height: 100%;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 14px;
      color: var(--text);
      background: #0f172a;
    }

    /* ── Background ── */
    .bg {
      position: fixed; inset: 0; z-index: 0;
      background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #163352 100%);
      overflow: hidden;
    }
    .bg::before {
      content: '';
      position: absolute;
      width: 600px; height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(43,127,193,.18) 0%, transparent 70%);
      top: -150px; left: -150px;
    }
    .bg::after {
      content: '';
      position: absolute;
      width: 500px; height: 500px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(0,166,90,.1) 0%, transparent 70%);
      bottom: -100px; right: -100px;
    }

    /* ── Wrapper ── */
    .page {
      position: relative; z-index: 1;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    /* ── Card ── */
    .card {
      width: 100%;
      max-width: 900px;
      background: var(--white);
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      box-shadow: 0 24px 64px rgba(0,0,0,.45);
      animation: cardIn .5s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes cardIn {
      from { opacity: 0; transform: translateY(20px) scale(.98); }
      to   { opacity: 1; transform: translateY(0)    scale(1); }
    }

    /* ── Left Panel ── */
    .panel-left {
      flex: 0 0 42%;
      background: linear-gradient(160deg, #2b7fc1 0%, #1a5f96 100%);
      padding: 36px 28px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
      color: #fff;
    }
    .panel-left::before {
      content: '';
      position: absolute;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
      top: -80px; right: -80px;
    }
    .panel-left::after {
      content: '';
      position: absolute;
      width: 180px; height: 180px;
      border-radius: 50%;
      background: rgba(0,166,90,.12);
      bottom: -50px; left: -50px;
    }

    /* Brand */
    .brand { position: relative; z-index: 1; }
    .brand-logo {
      width: 52px; height: 52px;
      background: rgba(255,255,255,.18);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 14px;
      border: 1.5px solid rgba(255,255,255,.3);
    }
    .brand-logo i { font-size: 26px; color: #fff; }
    .brand h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; }
    .brand h1 span { font-weight: 400; opacity: .8; }
    .brand p { font-size: 12px; opacity: .75; font-weight: 500; }

    /* RS Info */
    .rs-box {
      position: relative; z-index: 1;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 10px;
      padding: 14px 16px;
      margin: 20px 0;
    }
    .rs-box h4 { font-size: 13px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 7px; }
    .rs-box p  { font-size: 12px; opacity: .85; display: flex; align-items: flex-start; gap: 7px; margin-bottom: 5px; line-height: 1.5; }
    .rs-box p:last-child { margin-bottom: 0; }
    .rs-box p i { width: 12px; flex-shrink: 0; margin-top: 2px; }

    /* Features */
    .features { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .feat {
      background: rgba(255,255,255,.1);
      border: 1px solid rgba(255,255,255,.18);
      border-radius: 8px;
      padding: 10px 12px;
      display: flex; align-items: center; gap: 8px;
    }
    .feat i { font-size: 16px; color: #7dd3fc; flex-shrink: 0; }
    .feat span { font-size: 11px; font-weight: 600; line-height: 1.4; }

    /* ── Right Panel ── */
    .panel-right {
      flex: 1;
      padding: 32px 32px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: #fafbfc;
      overflow-y: auto;
      max-height: 98vh;
    }
    .panel-right::-webkit-scrollbar { width: 4px; }
    .panel-right::-webkit-scrollbar-thumb { background: var(--blue); border-radius: 4px; }

    /* Form title */
    .form-title { margin-bottom: 20px; }
    .form-title h2 { font-size: 20px; font-weight: 800; color: var(--text); margin-bottom: 3px; }
    .form-title p  { font-size: 12px; color: var(--gray); }

    /* Alert */
    .alert {
      border-radius: 8px;
      padding: 10px 14px;
      margin-bottom: 16px;
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .alert-danger  { background: #fef2f2; color: #991b1b; border-left: 3px solid var(--red); }
    .alert-success { background: #f0fdf4; color: #14532d; border-left: 3px solid var(--green); }
    .alert .close  { margin-left: auto; background: none; border: none; cursor: pointer; opacity: .6; font-size: 16px; color: inherit; line-height: 1; }

    /* Form group */
    .fg { margin-bottom: 14px; }
    .fg label { display: block; font-size: 12px; font-weight: 600; color: var(--gray); margin-bottom: 5px; }
    .input-wrap { position: relative; }
    .input-wrap i {
      position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
      color: #94a3b8; font-size: 14px; z-index: 1;
    }
    .input-wrap input {
      width: 100%; height: 40px;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      padding: 0 12px 0 36px;
      font-size: 13px;
      font-family: inherit;
      background: #fff;
      transition: border-color .2s, box-shadow .2s;
      color: var(--text);
    }
    .input-wrap input:focus {
      outline: none;
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(43,127,193,.12);
    }
    .input-wrap input::placeholder { color: #cbd5e1; }

    /* 2-col grid */
    .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }

    /* Buttons */
    .btn {
      width: 100%; height: 40px;
      border: none; border-radius: 8px;
      font-size: 13px; font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 7px;
      transition: all .2s;
      margin-top: 4px;
    }
    .btn-blue  { background: var(--blue);  color: #fff; }
    .btn-blue:hover  { background: var(--blue-dk); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(43,127,193,.35); }
    .btn-green { background: var(--green); color: #fff; }
    .btn-green:hover { background: var(--green-dk); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,166,90,.3); }

    /* Toggle link */
    .toggle {
      text-align: center;
      margin-top: 16px;
      padding-top: 14px;
      border-top: 1px solid var(--border);
      font-size: 12px;
      color: var(--gray);
    }
    .toggle a { color: var(--blue); font-weight: 700; text-decoration: none; }
    .toggle a:hover { text-decoration: underline; }

    /* Footer */
    .foot {
      text-align: center;
      margin-top: 16px;
      font-size: 11px;
      color: #94a3b8;
      border-top: 1px solid var(--border);
      padding-top: 12px;
    }
    .foot a { color: var(--green); text-decoration: none; }
    .foot a:hover { text-decoration: underline; }

    /* Form visibility */
    .f-login, .f-register { display: none; }
    .f-login.active, .f-register.active { display: block; }

    /* ── Responsive ── */

    /* Tablet landscape */
    @media (max-width: 860px) {
      .panel-left { flex: 0 0 38%; padding: 28px 22px; }
      .panel-right { padding: 24px 24px; }
      .features { grid-template-columns: 1fr; }
    }

    /* Tablet portrait & small laptop */
    @media (max-width: 680px) {
      .card { flex-direction: column; max-width: 440px; border-radius: 14px; }
      .panel-left {
        flex: none;
        padding: 22px 20px;
      }
      .rs-box  { margin: 12px 0; }
      .features { display: none; } /* sembunyikan fitur di mobile agar tidak terlalu panjang */
      .panel-right { padding: 22px 20px; max-height: none; }
      .grid2 { grid-template-columns: 1fr; gap: 0; }
    }

    /* Small phone */
    @media (max-width: 380px) {
      .page { padding: 10px; }
      .panel-left { padding: 18px 16px; }
      .panel-right { padding: 18px 16px; }
      .brand h1 { font-size: 22px; }
    }
  </style>
</head>
<body>

<div class="bg"></div>

<div class="page">
  <div class="card">

    <!-- ═══ LEFT ═══ -->
    <div class="panel-left">
      <div class="brand">
        <div class="brand-logo"><i class="fa fa-heartbeat"></i></div>
        <h1>Medi<span>Fix</span></h1>
        <p>Anjungan Pasien Mandiri &amp; Sistem Antrian</p>
      </div>

      <div class="rs-box">
        <h4><i class="fa fa-hospital-o"></i> <?= htmlspecialchars($namaRS) ?></h4>
        <?php if (!empty($alamatRS)): ?>
        <p><i class="fa fa-map-marker"></i> <?= htmlspecialchars($alamatRS) ?></p>
        <?php endif; ?>
        <?php if (!empty($kabupatenRS) || !empty($propinsiRS)): ?>
        <p><i class="fa fa-map"></i> <?= htmlspecialchars($kabupatenRS) ?><?= !empty($propinsiRS) ? ' — ' . htmlspecialchars($propinsiRS) : '' ?></p>
        <?php endif; ?>
      </div>

      <div class="features">
        <div class="feat"><i class="fa fa-clock-o"></i><span>Antrian Real-time</span></div>
        <div class="feat"><i class="fa fa-mobile"></i><span>Akses Mudah</span></div>
        <div class="feat"><i class="fa fa-shield"></i><span>Data Aman</span></div>
        <div class="feat"><i class="fa fa-check-circle"></i><span>Mudah Digunakan</span></div>
      </div>
    </div>

    <!-- ═══ RIGHT ═══ -->
    <div class="panel-right">

      <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="fa fa-exclamation-triangle"></i>
        <span><?= htmlspecialchars($error) ?></span>
        <button class="close" onclick="this.parentElement.remove()">&times;</button>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fa fa-check-circle"></i>
        <span><?= htmlspecialchars($success) ?></span>
        <button class="close" onclick="this.parentElement.remove()">&times;</button>
      </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form id="fLogin" class="f-login <?= isset($_POST['register_submit']) ? '' : 'active' ?>" method="post">
        <div class="form-title">
          <h2>Selamat Datang 👋</h2>
          <p>Masuk untuk melanjutkan ke dashboard</p>
        </div>

        <div class="fg">
          <label>Email atau NIK</label>
          <div class="input-wrap">
            <i class="fa fa-user"></i>
            <input type="text" name="login" placeholder="Masukkan email atau NIK" required>
          </div>
        </div>

        <div class="fg">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fa fa-lock"></i>
            <input type="password" name="password" placeholder="Masukkan password" required>
          </div>
        </div>

        <button type="submit" name="login_submit" class="btn btn-blue">
          <i class="fa fa-sign-in"></i> Masuk Sekarang
        </button>

        <div class="toggle">
          Belum punya akun? <a href="#" id="toRegister">Daftar di sini</a>
        </div>
      </form>

      <!-- Register Form -->
      <form id="fRegister" class="f-register <?= isset($_POST['register_submit']) ? 'active' : '' ?>" method="post">
        <div class="form-title">
          <h2>Daftar Akun Baru</h2>
          <p>Lengkapi data berikut untuk membuat akun</p>
        </div>

        <div class="grid2">
          <div class="fg">
            <label>NIK</label>
            <div class="input-wrap">
              <i class="fa fa-id-card"></i>
              <input type="text" name="nik" placeholder="NIK" required>
            </div>
          </div>
          <div class="fg">
            <label>Nama Lengkap</label>
            <div class="input-wrap">
              <i class="fa fa-user"></i>
              <input type="text" name="nama" placeholder="Nama lengkap" required>
            </div>
          </div>
        </div>

        <div class="grid2">
          <div class="fg">
            <label>Email</label>
            <div class="input-wrap">
              <i class="fa fa-envelope"></i>
              <input type="email" name="email" placeholder="email@example.com" required>
            </div>
          </div>
          <div class="fg">
            <label>Nomor HP</label>
            <div class="input-wrap">
              <i class="fa fa-phone"></i>
              <input type="text" name="hp" placeholder="08xxxxxxxxxx">
            </div>
          </div>
        </div>

        <div class="grid2">
          <div class="fg">
            <label>Password</label>
            <div class="input-wrap">
              <i class="fa fa-lock"></i>
              <input type="password" name="password" placeholder="Buat password" required>
            </div>
          </div>
          <div class="fg">
            <label>Konfirmasi Password</label>
            <div class="input-wrap">
              <i class="fa fa-lock"></i>
              <input type="password" name="confirm" placeholder="Ulangi password" required>
            </div>
          </div>
        </div>

        <button type="submit" name="register_submit" class="btn btn-green">
          <i class="fa fa-user-plus"></i> Daftar Sekarang
        </button>

        <div class="toggle">
          Sudah punya akun? <a href="#" id="toLogin">Login di sini</a>
        </div>
      </form>

      <div class="foot">
        &copy; <?= date('Y') ?> MediFix Apps &nbsp;|&nbsp;
        <i class="fa fa-whatsapp"></i> <a href="https://wa.me/6282177846209" target="_blank">082177846209 - M.Wira Sb. S. Kom</a>
      </div>

    </div><!-- /panel-right -->
  </div><!-- /card -->
</div><!-- /page -->

<script>
document.getElementById('toRegister')?.addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('fLogin').classList.remove('active');
  document.getElementById('fRegister').classList.add('active');
});
document.getElementById('toLogin')?.addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('fRegister').classList.remove('active');
  document.getElementById('fLogin').classList.add('active');
});
</script>
</body>
</html>
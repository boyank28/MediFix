<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Refresh akses dari DB (jika belum ada di session)
if (!isset($_SESSION['akses']) || empty($_SESSION['akses'])) {
    include_once 'koneksi.php';
    $stmtAkses = $pdo->prepare("SELECT menu, izin FROM hak_akses WHERE user_id=?");
    $stmtAkses->execute([$_SESSION['user_id']]);
    $aksesData = $stmtAkses->fetchAll(PDO::FETCH_ASSOC);
    
    $_SESSION['akses'] = [];
    foreach ($aksesData as $row) {
        $_SESSION['akses'][$row['menu']] = $row['izin'];
    }
}

// Fungsi helper untuk cek permission
if (!function_exists('boleh')) {
    function boleh($menu) {
        return isset($_SESSION['akses'][$menu]) && $_SESSION['akses'][$menu] == 1;
    }
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?= $page_title ?? 'MediFix' ?></title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <!-- AdminLTE -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/skins/_all-skins.min.css">
  
  <?php if (isset($extra_css)): ?>
  <style>
    <?= $extra_css ?>
  </style>
  <?php endif; ?>
</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <!-- Header -->
  <header class="main-header">
    <a href="dashboard.php" class="logo">
      <span class="logo-mini"><b>M</b>F</span>
      <span class="logo-lg"><b>Medi</b>Fix</span>
    </a>

    <nav class="navbar navbar-static-top">
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>
      
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          <li class="dropdown user user-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-user"></i>
              <span class="hidden-xs"><?= htmlspecialchars($nama) ?></span>
            </a>
            <ul class="dropdown-menu">
              <li class="user-header">
                <p>
                  <?= htmlspecialchars($nama) ?>
                  <small>Member since <?= date('Y') ?></small>
                </p>
              </li>
              <li class="user-footer">
                <div class="pull-right">
                  <a href="logout.php" class="btn btn-default btn-flat">
                    <i class="fa fa-sign-out"></i> Logout
                  </a>
                </div>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </nav>
  </header>
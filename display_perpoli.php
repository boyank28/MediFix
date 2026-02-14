<?php
session_start();
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$today = date('Y-m-d');

try {
    $excluded_poli = ['IGDK','PL013','PL014','PL015','PL016','PL017','U0022','U0030'];
    $excluded_list = "'" . implode("','", $excluded_poli) . "'";

    // Ambil daftar poli dan dokter yang aktif hari ini
    $sql_poli = "
        SELECT 
            p.kd_poli, 
            p.nm_poli,
            d.kd_dokter,
            d.nm_dokter,
            COUNT(r.no_reg) as total_pasien,
            SUM(CASE WHEN r.stts = 'Sudah' THEN 1 ELSE 0 END) as sudah_dilayani,
            SUM(CASE WHEN r.stts IN ('Menunggu','Belum') THEN 1 ELSE 0 END) as menunggu
        FROM reg_periksa r
        JOIN poliklinik p ON r.kd_poli = p.kd_poli
        JOIN dokter d ON r.kd_dokter = d.kd_dokter
        WHERE r.tgl_registrasi = :tgl
          AND r.kd_poli NOT IN ($excluded_list)
        GROUP BY p.kd_poli, p.nm_poli, d.kd_dokter, d.nm_dokter
        ORDER BY p.nm_poli ASC, d.nm_dokter ASC";
    $stmt_poli = $pdo_simrs->prepare($sql_poli);
    $stmt_poli->bindValue(':tgl', $today);
    $stmt_poli->execute();
    $daftar_poli = $stmt_poli->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by poli
    $poli_groups = [];
    $total_semua = 0;
    $sudah_semua = 0;
    $menunggu_semua = 0;
    $batal_semua = 0;
    
    foreach ($daftar_poli as $item) {
        $kd_poli = $item['kd_poli'];
        if (!isset($poli_groups[$kd_poli])) {
            $poli_groups[$kd_poli] = [
                'nm_poli' => $item['nm_poli'],
                'dokters' => [],
                'total_pasien' => 0,
                'sudah_dilayani' => 0,
                'menunggu' => 0
            ];
        }
        $poli_groups[$kd_poli]['dokters'][] = $item;
        $poli_groups[$kd_poli]['total_pasien'] += $item['total_pasien'];
        $poli_groups[$kd_poli]['sudah_dilayani'] += $item['sudah_dilayani'];
        $poli_groups[$kd_poli]['menunggu'] += $item['menunggu'];
        
        $total_semua += $item['total_pasien'];
        $sudah_semua += $item['sudah_dilayani'];
        $menunggu_semua += $item['menunggu'];
    }
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Per Poli - MediFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        /* Top Bar */
        .top-bar {
            background: #4a90a4;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .brand {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Main Container */
        .container {
            display: flex;
            height: calc(100vh - 48px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 230px;
            background: #2c3e50;
            color: white;
            overflow-y: auto;
        }
        
        .user-panel {
            padding: 20px;
            border-bottom: 1px solid #34495e;
            text-align: center;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: #4a90a4;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-status {
            font-size: 12px;
            color: #2ecc71;
        }
        
        .menu-header {
            padding: 15px 20px;
            font-size: 11px;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-item:hover, .menu-item.active {
            background: #34495e;
            border-left: 3px solid #4a90a4;
            padding-left: 17px;
        }
        
        .menu-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .page-header {
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .page-subtitle {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .breadcrumb {
            display: flex;
            gap: 10px;
            align-items: center;
            float: right;
            margin-top: -30px;
            font-size: 13px;
        }
        
        .breadcrumb a {
            color: #4a90a4;
            text-decoration: none;
        }
        
        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
        }
        
        .stat-icon {
            width: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        
        .stat-icon.blue { background: #00a7d0; }
        .stat-icon.green { background: #00a65a; }
        .stat-icon.orange { background: #f39c12; }
        .stat-icon.red { background: #dd4b39; }
        
        .stat-content {
            padding: 15px;
            flex: 1;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        /* Table Section */
        .table-section {
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        /* Poli Grid */
        .poli-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .poli-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .poli-card:hover {
            border-color: #6c5ce7;
            box-shadow: 0 4px 12px rgba(108, 92, 231, 0.15);
            transform: translateY(-2px);
        }
        
        .poli-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .poli-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .poli-name {
            font-size: 15px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .dokter-count {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .poli-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 12px;
        }
        
        .mini-stat {
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 4px;
        }
        
        .mini-stat-label {
            font-size: 10px;
            color: #999;
            text-transform: uppercase;
        }
        
        .mini-stat-value {
            font-size: 16px;
            font-weight: 700;
            margin-top: 3px;
        }
        
        .mini-stat-value.blue { color: #00a7d0; }
        .mini-stat-value.green { color: #00a65a; }
        .mini-stat-value.orange { color: #f39c12; }
        
        .poli-action {
            margin-top: 12px;
            padding: 10px;
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: white;
            text-align: center;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .dokter-list {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #dee2e6;
        }
        
        .dokter-item {
            padding: 8px 10px;
            background: white;
            border-radius: 4px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }
        
        .dokter-item:hover {
            background: #e8f5e9;
            transform: translateX(5px);
        }
        
        .dokter-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .dokter-waiting {
            color: #f39c12;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="brand">
            <i class="fa-solid fa-bars"></i>
            <span>MediFix</span>
        </div>
        <div class="user-info">
            <i class="fa-solid fa-user"></i>
            <span><?= htmlspecialchars($nama) ?></span>
        </div>
    </div>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="user-panel">
                <div class="user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="user-name"><?= htmlspecialchars($nama) ?></div>
                <div class="user-status"><i class="fa-solid fa-circle"></i> Online</div>
            </div>
            
            <div class="menu-header">MENU NAVIGASI</div>
            
            <a href="dashboard.php" class="menu-item">
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="#" class="menu-item active">
                <i class="fa-solid fa-hospital"></i>
                <span>Poliklinik</span>
            </a>
            
            <a href="data_antri_poli.php" class="menu-item">
                <i class="fa-solid fa-phone"></i>
                <span>Panggil Poli</span>
            </a>
            
            <a href="display_perpoli.php" class="menu-item">
                <i class="fa-solid fa-tv"></i>
                <span>Display Per Poli</span>
            </a>
            
            <a href="display_poli.php" class="menu-item">
                <i class="fa-solid fa-display"></i>
                <span>Display Semua Poli</span>
            </a>
            
            <a href="display_jadwal_dokter.php" class="menu-item">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Jadwal Dokter</span>
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Pilih Display Poliklinik</h1>
                <div class="page-subtitle">Friday, <?= date('d F Y', strtotime($today)) ?></div>
                <div class="breadcrumb">
                    <i class="fa-solid fa-home"></i>
                    <a href="dashboard.php">Home</a> /
                    <span>Poliklinik</span> /
                    <span>Display Per Poli</span>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Pasien</div>
                        <div class="stat-value"><?= $total_semua ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Sudah Dilayani</div>
                        <div class="stat-value"><?= $sudah_semua ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Sedang Menunggu</div>
                        <div class="stat-value"><?= $menunggu_semua ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Batal</div>
                        <div class="stat-value"><?= $batal_semua ?></div>
                    </div>
                </div>
            </div>

            <!-- Poli List -->
            <div class="table-section">
                <h2 class="section-title">Daftar Poliklinik (<?= count($poli_groups) ?> Poli Aktif)</h2>
                
                <?php if (count($poli_groups) > 0): ?>
                <div class="poli-grid">
                    <?php foreach ($poli_groups as $kd_poli => $poli): ?>
                    
                    <div class="poli-card">
                        <div class="poli-header">
                            <div class="poli-icon">
                                <i class="fa-solid fa-hospital"></i>
                            </div>
                            <div>
                                <div class="poli-name"><?= htmlspecialchars($poli['nm_poli']) ?></div>
                                <div class="dokter-count">
                                    <i class="fa-solid fa-user-doctor"></i>
                                    <?= count($poli['dokters']) ?> Dokter
                                </div>
                            </div>
                        </div>
                        
                        <div class="poli-stats">
                            <div class="mini-stat">
                                <div class="mini-stat-label">Total</div>
                                <div class="mini-stat-value blue"><?= $poli['total_pasien'] ?></div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-stat-label">Selesai</div>
                                <div class="mini-stat-value green"><?= $poli['sudah_dilayani'] ?></div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-stat-label">Tunggu</div>
                                <div class="mini-stat-value orange"><?= $poli['menunggu'] ?></div>
                            </div>
                        </div>
                        
                        <?php if (count($poli['dokters']) > 1): ?>
                        <div class="dokter-list">
                            <?php foreach ($poli['dokters'] as $dok): ?>
                            <a href="display_poli.php?poli=<?= htmlspecialchars($kd_poli) ?>&dokter=<?= htmlspecialchars($dok['kd_dokter']) ?>" 
                               target="_blank"
                               class="dokter-item">
                                <span class="dokter-name">
                                    <i class="fa-solid fa-user-doctor"></i>
                                    <?= htmlspecialchars($dok['nm_dokter']) ?>
                                </span>
                                <span class="dokter-waiting">
                                    <i class="fa-solid fa-clock"></i> <?= $dok['menunggu'] ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="display_poli.php?poli=<?= htmlspecialchars($kd_poli) ?>" 
                           target="_blank"
                           class="poli-action">
                            <i class="fa-solid fa-tv"></i> Tampilkan Semua Dokter
                        </a>
                        <?php else: ?>
                        <?php $dok = $poli['dokters'][0]; ?>
                        <a href="display_poli.php?poli=<?= htmlspecialchars($kd_poli) ?>&dokter=<?= htmlspecialchars($dok['kd_dokter']) ?>" 
                           target="_blank"
                           class="poli-action">
                            <i class="fa-solid fa-tv"></i> Tampilkan Display
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #999;">
                    <i class="fa-solid fa-inbox" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                    Belum ada poliklinik yang memiliki pasien hari ini
                </p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Auto refresh setiap 30 detik
    setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
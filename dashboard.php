<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'koneksi.php';
include 'koneksi2.php';

$nama = $_SESSION['nama'] ?? 'Pengguna';
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

// === STATISTIK ADMISI ===
try {
    $stmt_admisi_total = $pdo_simrs->prepare("SELECT COUNT(*) FROM antrian_wira WHERE DATE(created_at) = ?");
    $stmt_admisi_total->execute([$today]);
    $admisi_total = (int)$stmt_admisi_total->fetchColumn();
    
    $stmt_admisi_status = $pdo_simrs->prepare("
        SELECT 
            SUM(CASE WHEN status='Menunggu' THEN 1 ELSE 0 END) as menunggu,
            SUM(CASE WHEN status='Dipanggil' THEN 1 ELSE 0 END) as dipanggil,
            SUM(CASE WHEN status='Selesai' THEN 1 ELSE 0 END) as selesai
        FROM antrian_wira
        WHERE DATE(created_at) = ?
    ");
    $stmt_admisi_status->execute([$today]);
    $admisi_status = $stmt_admisi_status->fetch(PDO::FETCH_ASSOC);
    $admisi_menunggu = $admisi_status['menunggu'] ?? 0;
    $admisi_dipanggil = $admisi_status['dipanggil'] ?? 0;
    $admisi_selesai = $admisi_status['selesai'] ?? 0;
} catch (PDOException $e) {
    $admisi_total = $admisi_menunggu = $admisi_dipanggil = $admisi_selesai = 0;
}

// === STATISTIK POLIKLINIK ===
try {
    $exclude_poli = ['IGDK','MCU01','PL010','PL011','PL013','PL014','PL015','PL016','PL017','U0022','U0026','U0028','U0030'];
    $placeholders = implode(',', array_fill(0, count($exclude_poli), '?'));
    
    $params = [$today];
    $params = array_merge($params, $exclude_poli);
    
    $stmt_poli_total = $pdo_simrs->prepare("
        SELECT COUNT(*) FROM reg_periksa r
        LEFT JOIN poliklinik p ON r.kd_poli = p.kd_poli
        WHERE r.tgl_registrasi = ? AND p.status='1' AND r.kd_poli NOT IN ($placeholders)
    ");
    $stmt_poli_total->execute($params);
    $poli_total = (int)$stmt_poli_total->fetchColumn();
    
    $stmt_poli_sudah = $pdo_simrs->prepare("
        SELECT COUNT(*) FROM reg_periksa r
        LEFT JOIN poliklinik p ON r.kd_poli = p.kd_poli
        WHERE r.tgl_registrasi = ? AND r.stts='Sudah' AND p.status='1' AND r.kd_poli NOT IN ($placeholders)
    ");
    $stmt_poli_sudah->execute($params);
    $poli_sudah = (int)$stmt_poli_sudah->fetchColumn();
    
    $stmt_poli_menunggu = $pdo_simrs->prepare("
        SELECT COUNT(*) FROM reg_periksa r
        LEFT JOIN poliklinik p ON r.kd_poli = p.kd_poli
        WHERE r.tgl_registrasi = ? AND r.stts IN ('Menunggu','Belum') AND p.status='1' AND r.kd_poli NOT IN ($placeholders)
    ");
    $stmt_poli_menunggu->execute($params);
    $poli_menunggu = (int)$stmt_poli_menunggu->fetchColumn();
    
    $stmt_poli_batal = $pdo_simrs->prepare("
        SELECT COUNT(*) FROM reg_periksa r
        LEFT JOIN poliklinik p ON r.kd_poli = p.kd_poli
        WHERE r.tgl_registrasi = ? AND r.stts='Batal' AND p.status='1' AND r.kd_poli NOT IN ($placeholders)
    ");
    $stmt_poli_batal->execute($params);
    $poli_batal = (int)$stmt_poli_batal->fetchColumn();
} catch (PDOException $e) {
    $poli_total = $poli_sudah = $poli_menunggu = $poli_batal = 0;
}

// === STATISTIK FARMASI ===
try {
    $stmt_farmasi = $pdo_simrs->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN EXISTS (SELECT 1 FROM resep_dokter_racikan rr WHERE rr.no_resep = ro.no_resep) THEN 1 ELSE 0 END) as racikan,
            SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM resep_dokter_racikan rr WHERE rr.no_resep = ro.no_resep) THEN 1 ELSE 0 END) as non_racikan
        FROM resep_obat ro
        WHERE ro.tgl_peresepan = CURDATE()
          AND ro.status = 'ralan'
          AND ro.jam_peresepan <> '00:00:00'
    ");
    $stmt_farmasi->execute();
    $farmasi = $stmt_farmasi->fetch(PDO::FETCH_ASSOC);
    $farmasi_total = $farmasi['total'] ?? 0;
    $farmasi_racikan = $farmasi['racikan'] ?? 0;
    $farmasi_non_racikan = $farmasi['non_racikan'] ?? 0;
} catch (PDOException $e) {
    $farmasi_total = $farmasi_racikan = $farmasi_non_racikan = 0;
}

// Set page title dan extra CSS
$page_title = 'Dashboard - MediFix';
$extra_css = '
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

/* Section Header */
.section-header {
  background: white;
  padding: 15px 20px;
  border-radius: 5px;
  margin-bottom: 15px;
  border-left: 4px solid;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.section-header h4 {
  margin: 0;
  font-weight: 700;
  font-size: 16px;
}

.section-header.admisi { border-left-color: #3c8dbc; }
.section-header.poli { border-left-color: #605ca8; }
.section-header.farmasi { border-left-color: #f39c12; }

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
  margin-bottom: 30px;
}

.stats-grid-3 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  margin-bottom: 30px;
}

.stat-card {
  background: #fff;
  border-radius: 5px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
  transition: all 0.3s ease;
  border-top: 3px solid;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.stat-card-content {
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  color: white;
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 13px;
  color: #666;
  margin-bottom: 5px;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #333;
}

/* Admisi Colors */
.stat-admisi-total { border-top-color: #3c8dbc; }
.stat-admisi-total .stat-icon { background: #3c8dbc; }
.stat-admisi-menunggu { border-top-color: #f39c12; }
.stat-admisi-menunggu .stat-icon { background: #f39c12; }
.stat-admisi-dipanggil { border-top-color: #00c0ef; }
.stat-admisi-dipanggil .stat-icon { background: #00c0ef; }
.stat-admisi-selesai { border-top-color: #00a65a; }
.stat-admisi-selesai .stat-icon { background: #00a65a; }

/* Poli Colors */
.stat-poli-total { border-top-color: #3c8dbc; }
.stat-poli-total .stat-icon { background: #3c8dbc; }
.stat-poli-sudah { border-top-color: #00a65a; }
.stat-poli-sudah .stat-icon { background: #00a65a; }
.stat-poli-menunggu { border-top-color: #f39c12; }
.stat-poli-menunggu .stat-icon { background: #f39c12; }
.stat-poli-batal { border-top-color: #dd4b39; }
.stat-poli-batal .stat-icon { background: #dd4b39; }

/* Farmasi Colors */
.stat-farmasi-total { border-top-color: #f39c12; }
.stat-farmasi-total .stat-icon { background: #f39c12; }
.stat-farmasi-racikan { border-top-color: #dd4b39; }
.stat-farmasi-racikan .stat-icon { background: #dd4b39; }
.stat-farmasi-non { border-top-color: #00a65a; }
.stat-farmasi-non .stat-icon { background: #00a65a; }

@media (max-width: 992px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .stats-grid-3 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 576px) {
  .stats-grid, .stats-grid-3 {
    grid-template-columns: 1fr;
  }
}
';

$extra_js = '
function updateClock() {
    const now = new Date();
    const options = { weekday: "long", year: "numeric", month: "long", day: "numeric" };
    const tanggal = now.toLocaleDateString("id-ID", options);
    const waktu = now.toLocaleTimeString("id-ID");
    
    document.getElementById("tanggalSekarang").innerHTML = tanggal;
    document.getElementById("clockDisplay").innerHTML = waktu;
}

setInterval(updateClock, 1000);
updateClock();

// Auto refresh every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
';

// Include header
include 'includes/header.php';

// Include sidebar
include 'includes/sidebar.php';
?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content-header">
      <h1>Dashboard</h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Dashboard</li>
      </ol>
    </section>

    <section class="content">
      
  

      <!-- ADMISI SECTION -->
      <?php if (boleh('admisi')): ?>
      <div class="section-header admisi">
        <h4><i class="fa fa-user-plus"></i> Statistik Admisi Hari Ini</h4>
      </div>
      
      <div class="stats-grid">
        <div class="stat-card stat-admisi-total">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-list-ol"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total Antrian</div>
              <div class="stat-value"><?= $admisi_total ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-admisi-menunggu">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-hourglass-half"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Menunggu</div>
              <div class="stat-value"><?= $admisi_menunggu ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-admisi-dipanggil">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-phone"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Dipanggil</div>
              <div class="stat-value"><?= $admisi_dipanggil ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-admisi-selesai">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-check-circle"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Selesai</div>
              <div class="stat-value"><?= $admisi_selesai ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- POLIKLINIK SECTION -->
      <?php if (boleh('poliklinik')): ?>
      <div class="section-header poli">
        <h4><i class="fa fa-hospital-o"></i> Statistik Poliklinik Hari Ini</h4>
      </div>
      
      <div class="stats-grid">
        <div class="stat-card stat-poli-total">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-users"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total Pasien</div>
              <div class="stat-value"><?= $poli_total ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-poli-sudah">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-check-circle"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Sudah Dilayani</div>
              <div class="stat-value"><?= $poli_sudah ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-poli-menunggu">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-clock-o"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Menunggu</div>
              <div class="stat-value"><?= $poli_menunggu ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-poli-batal">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-times-circle"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Batal</div>
              <div class="stat-value"><?= $poli_batal ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- FARMASI SECTION -->
      <?php if (boleh('farmasi')): ?>
      <div class="section-header farmasi">
        <h4><i class="fa fa-medkit"></i> Statistik Farmasi Hari Ini</h4>
      </div>
      
      <div class="stats-grid-3">
        <div class="stat-card stat-farmasi-total">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-list-ol"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total Resep</div>
              <div class="stat-value"><?= $farmasi_total ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-farmasi-racikan">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-flask"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Racikan</div>
              <div class="stat-value"><?= $farmasi_racikan ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-farmasi-non">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-plus-square"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Non Racikan</div>
              <div class="stat-value"><?= $farmasi_non_racikan ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </section>
  </div>

<?php
// Include footer
include 'includes/footer.php';
?>
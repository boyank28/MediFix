<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';

// ===== Ambil Data =====
$data = $pdo->query("SELECT * FROM setting_vclaim LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// ===== Simpan Data =====
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cons_id    = trim($_POST['cons_id']);
    $secret_key = trim($_POST['secret_key']);
    $user_key   = trim($_POST['user_key']);
    $base_url   = trim($_POST['base_url']);
    $kd_ppk     = trim($_POST['kd_ppk']);
    $nm_ppk     = trim($_POST['nm_ppk']);

    if($cons_id && $secret_key && $user_key && $base_url && $kd_ppk && $nm_ppk){
        try {
            $pdo->prepare("UPDATE setting_vclaim SET cons_id=?, secret_key=?, user_key=?, base_url=?, kd_ppk=?, nm_ppk=?, updated_at=NOW() WHERE id=1")
                ->execute([$cons_id, $secret_key, $user_key, $base_url, $kd_ppk, $nm_ppk]);

            $success = "✔ Setting VClaim BPJS berhasil diperbarui!";
            $data = $pdo->query("SELECT * FROM setting_vclaim LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "⚠ Gagal menyimpan: " . $e->getMessage();
        }
    } else {
        $error = "⚠ Semua field wajib diisi.";
    }
}

// Set page title dan extra CSS
$page_title = 'Setting VClaim BPJS - MediFix';
$extra_css = '
/* Welcome Box */
.welcome-box {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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

/* Info Card */
.info-card {
  background: white;
  border-radius: 5px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
  margin-bottom: 20px;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f8f9fc;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
}

.info-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 18px;
  flex-shrink: 0;
}

.info-content {
  flex: 1;
}

.info-label {
  font-size: 11px;
  color: #718096;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.info-value {
  font-size: 14px;
  font-weight: 700;
  color: #2d3748;
  word-break: break-all;
}

/* Form styling */
.form-group label {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 5px;
}

.form-group label i {
  color: #3b82f6;
  margin-right: 5px;
}

.form-control {
  border: 2px solid #e2e8f0;
  border-radius: 5px;
  padding: 8px 12px;
}

.form-control:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button */
.btn-save-custom {
  background: linear-gradient(135deg, #10b981, #059669);
  border: none;
  color: white;
  padding: 10px 20px;
  border-radius: 5px;
  font-weight: 600;
  transition: all 0.3s ease;
  width: 100%;
}

.btn-save-custom i {
  margin-right: 5px;
}

.btn-save-custom:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
  color: white;
}
';

$extra_js = '';

// Include header
include 'includes/header.php';

// Include sidebar
include 'includes/sidebar.php';
?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Setting VClaim BPJS</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Setting</a></li>
        <li class="active">Setting VClaim</li>
      </ol>
    </section>

    <section class="content">
      
      <!-- Welcome Box -->
      <div class="welcome-box">
        <h3><i class="fa fa-shield"></i> Setting VClaim BPJS Kesehatan</h3>
        <p>Konfigurasi bridging VClaim BPJS Kesehatan</p>
      </div>

      <div class="row">
        
        <!-- Info Card (Left) -->
        <div class="col-md-5">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-database"></i> Data VClaim Aktif</h3>
            </div>
            <div class="box-body">
              <div class="info-list">
                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-fingerprint"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Consumer ID</div>
                    <div class="info-value"><?= htmlspecialchars($data['cons_id'] ?? '-') ?></div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-shield"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Secret Key</div>
                    <div class="info-value">••••••••••••••</div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-key"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">User Key</div>
                    <div class="info-value">••••••••••••••</div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-globe"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Base URL</div>
                    <div class="info-value"><?= htmlspecialchars($data['base_url'] ?? '-') ?></div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-hospital-o"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Kode PPK</div>
                    <div class="info-value"><?= htmlspecialchars($data['kd_ppk'] ?? '-') ?></div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-building"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Nama PPK</div>
                    <div class="info-value"><?= htmlspecialchars($data['nm_ppk'] ?? '-') ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Card (Right) -->
        <div class="col-md-7">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-gear"></i> Form Pengaturan</h3>
            </div>
            <div class="box-body">

              <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible">
                  <button type="button" class="close" data-dismiss="alert">&times;</button>
                  <i class="fa fa-check-circle"></i> <?= $success ?>
                </div>
              <?php endif; ?>

              <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                  <button type="button" class="close" data-dismiss="alert">&times;</button>
                  <i class="fa fa-exclamation-triangle"></i> <?= $error ?>
                </div>
              <?php endif; ?>

              <form method="post">
                
                <div class="form-group">
                  <label>
                    <i class="fa fa-fingerprint"></i>
                    Consumer ID
                  </label>
                  <input type="text" 
                         name="cons_id" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['cons_id'] ?? '') ?>" 
                         placeholder="Masukkan Consumer ID"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-shield"></i>
                    Secret Key
                  </label>
                  <input type="text" 
                         name="secret_key" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['secret_key'] ?? '') ?>" 
                         placeholder="Masukkan Secret Key"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-key"></i>
                    User Key
                  </label>
                  <input type="text" 
                         name="user_key" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['user_key'] ?? '') ?>" 
                         placeholder="Masukkan User Key"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-globe"></i>
                    Base URL
                  </label>
                  <input type="text" 
                         name="base_url" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['base_url'] ?? '') ?>" 
                         placeholder="https://apijkn.bpjs-kesehatan.go.id/vclaim-rest"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-hospital-o"></i>
                    Kode PPK
                  </label>
                  <input type="text" 
                         name="kd_ppk" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['kd_ppk'] ?? '') ?>" 
                         placeholder="Masukkan Kode PPK"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-building"></i>
                    Nama PPK
                  </label>
                  <input type="text" 
                         name="nm_ppk" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['nm_ppk'] ?? '') ?>" 
                         placeholder="Masukkan Nama PPK Rumah Sakit"
                         required>
                </div>

                <div class="form-group">
                  <button type="submit" class="btn btn-primary btn-save-custom">
                    <i class="fa fa-save"></i> Simpan Perubahan
                  </button>
                </div>

              </form>
            </div>
          </div>
        </div>

      </div>

    </section>
  </div>

<?php
// Include footer
include 'includes/footer.php';
?>
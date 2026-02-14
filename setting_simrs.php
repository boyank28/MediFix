<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';

// ==== Ambil Data ====
$data = $pdo->query("SELECT * FROM setting_simrs LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$success = $error = "";

// ==== Simpan Data ====
if (isset($_POST['simpan'])) {

    $nama_simrs = trim($_POST['nama_simrs']);
    $host       = trim($_POST['host']);
    $username   = trim($_POST['username']);
    $password   = trim($_POST['password']);
    $database   = trim($_POST['database_name']);

    if($nama_simrs && $host && $username && $database){

        try {
            // Cek apakah sudah ada data di tabel
            $cek = $pdo->query("SELECT * FROM setting_simrs LIMIT 1")->fetch(PDO::FETCH_ASSOC);

            if (!$cek) {
                // INSERT pertama kali
                $stmt = $pdo->prepare("INSERT INTO setting_simrs (nama_simrs, host, username, password, database_name, updated_at) 
                                       VALUES (?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$nama_simrs, $host, $username, $password, $database]);
                
                if($result) {
                    $success = "✔ Setting SIMRS berhasil disimpan (INSERT)!";
                } else {
                    $error = "⚠ Gagal menyimpan data (INSERT).";
                }

            } else {
                // UPDATE jika sudah ada data - gunakan ID yang sebenarnya
                $stmt = $pdo->prepare("UPDATE setting_simrs 
                    SET nama_simrs=?, host=?, username=?, password=?, database_name=?, updated_at=NOW() 
                    WHERE id=?");
                $result = $stmt->execute([$nama_simrs, $host, $username, $password, $database, $cek['id']]);
                
                if($result) {
                    $success = "✔ Setting SIMRS berhasil diupdate!";
                } else {
                    $error = "⚠ Gagal update data.";
                }
            }

            // Refresh data setelah simpan
            $data = $pdo->query("SELECT * FROM setting_simrs LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $error = "⚠ Database Error: " . $e->getMessage();
        }

    } else {
        $error = "⚠ Semua field wajib diisi (kecuali password opsional).";
    }
}

// Set page title dan extra CSS
$page_title = 'Setting SIMRS - MediFix';
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
  border-left: 4px solid #667eea;
}

.info-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea, #764ba2);
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
  color: #667eea;
  margin-right: 5px;
}

.form-control {
  border: 2px solid #e2e8f0;
  border-radius: 5px;
  padding: 8px 12px;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Buttons */
.btn-custom {
  padding: 10px 20px;
  border-radius: 5px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-custom i {
  margin-right: 5px;
}

.btn-primary-custom {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  color: white;
}

.btn-primary-custom:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
      <h1>Setting SIMRS</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Setting</a></li>
        <li class="active">Setting SIMRS</li>
      </ol>
    </section>

    <section class="content">
      
      <!-- Welcome Box -->
      <div class="welcome-box">
        <h3><i class="fa fa-database"></i> Pengaturan Koneksi SIMRS</h3>
        <p>Kelola konfigurasi database sistem informasi rumah sakit</p>
      </div>

      <div class="row">
        
        <!-- Info Card (Left) -->
        <div class="col-md-5">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-info-circle"></i> Informasi Database Saat Ini</h3>
            </div>
            <div class="box-body">
              <div class="info-list">
                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-building"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Nama SIMRS</div>
                    <div class="info-value"><?= htmlspecialchars($data['nama_simrs'] ?? '-') ?></div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-server"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Host Server</div>
                    <div class="info-value"><?= htmlspecialchars($data['host'] ?? '-') ?></div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-user"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?= htmlspecialchars($data['username'] ?? '-') ?></div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-lock"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Password</div>
                    <div class="info-value">••••••••••••</div>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-icon">
                    <i class="fa fa-database"></i>
                  </div>
                  <div class="info-content">
                    <div class="info-label">Nama Database</div>
                    <div class="info-value"><?= htmlspecialchars($data['database_name'] ?? '-') ?></div>
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
              <h3 class="box-title"><i class="fa fa-gear"></i> Konfigurasi Database</h3>
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
                    <i class="fa fa-building"></i>
                    Nama SIMRS
                  </label>
                  <input type="text" 
                         name="nama_simrs" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['nama_simrs'] ?? '') ?>" 
                         placeholder="SIMRS KHANZA"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-server"></i>
                    Host Database
                  </label>
                  <input type="text" 
                         name="host" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['host'] ?? '') ?>" 
                         placeholder="localhost"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-user"></i>
                    Username Database
                  </label>
                  <input type="text" 
                         name="username" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['username'] ?? '') ?>" 
                         placeholder="root"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-lock"></i>
                    Password Database
                  </label>
                  <input type="password" 
                         name="password" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['password'] ?? '') ?>" 
                         placeholder="••••••••••••">
                  <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-database"></i>
                    Nama Database
                  </label>
                  <input type="text" 
                         name="database_name" 
                         class="form-control" 
                         value="<?= htmlspecialchars($data['database_name'] ?? '') ?>" 
                         placeholder="khanzaaptonline"
                         required>
                </div>

                <div class="form-group">
                  <div class="btn-group btn-group-justified" role="group">
                    <button type="submit" name="simpan" class="btn btn-primary btn-primary-custom">
                      <i class="fa fa-save"></i> Simpan Konfigurasi
                    </button>
                  </div>
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
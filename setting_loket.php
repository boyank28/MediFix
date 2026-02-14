<?php
session_start();
include 'koneksi.php';
include 'koneksi2.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';

$success = "";
$error = "";

// ==== PROSES SIMPAN ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_loket'])) {
    $nama_loket = trim($_POST['nama_loket']);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($nama_loket !== '') {
        try {
            $stmt = $pdo_simrs->prepare("INSERT INTO loket_admisi_wira (nama_loket, keterangan) VALUES (?,?)");
            $stmt->execute([$nama_loket, $keterangan]);
            $success = "✔ Loket berhasil ditambahkan!";
        } catch (PDOException $e) {
            $error = "⚠ Gagal menyimpan: " . $e->getMessage();
        }
    } else {
        $error = "⚠ Nama loket tidak boleh kosong!";
    }
}

$lokets = $pdo_simrs->query("SELECT * FROM loket_admisi_wira ORDER BY id DESC")
                   ->fetchAll(PDO::FETCH_ASSOC);

// Set page title dan extra CSS
$page_title = 'Setting Loket Admisi - MediFix';
$extra_css = '
/* Welcome Box */
.welcome-box {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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

/* Table Custom */
.table-loket {
  margin-bottom: 0;
}

.table-loket thead {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.table-loket thead th {
  font-weight: 600;
  border: none;
  padding: 12px;
}

.table-loket tbody td {
  vertical-align: middle;
  padding: 12px;
}

.table-loket tbody tr:hover {
  background-color: #fef3c7;
}

.loket-name {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: #2d3748;
}

.loket-icon {
  width: 35px;
  height: 35px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
}

/* Form Card */
.form-group label {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 5px;
}

.form-group label i {
  color: #f59e0b;
  margin-right: 5px;
}

.form-control {
  border: 2px solid #e2e8f0;
  border-radius: 5px;
  padding: 8px 12px;
}

.form-control:focus {
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
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
      <h1>Setting Loket Admisi</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Setting</a></li>
        <li class="active">Setting Loket</li>
      </ol>
    </section>

    <section class="content">
      
      <!-- Welcome Box -->
      <div class="welcome-box">
        <h3><i class="fa fa-inbox"></i> Setting Loket Admisi</h3>
        <p>Manajemen loket pelayanan admisi pasien</p>
      </div>

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

      <div class="row">
        
        <!-- Table Card (Left) -->
        <div class="col-md-8">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-list"></i> Daftar Loket Admisi</h3>
            </div>
            <div class="box-body">
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-loket">
                  <thead>
                    <tr>
                      <th width="50" class="text-center">#</th>
                      <th>Nama Loket</th>
                      <th>Keterangan</th>
                      <th width="160">Tanggal Dibuat</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $no = 1;
                    foreach ($lokets as $row): 
                    ?>
                    <tr>
                      <td class="text-center" style="color: #94a3b8; font-weight: 600;">
                        <?= $no++ ?>
                      </td>
                      <td>
                        <div class="loket-name">
                          <div class="loket-icon">
                            <i class="fa fa-inbox"></i>
                          </div>
                          <span><?= htmlspecialchars($row['nama_loket']) ?></span>
                        </div>
                      </td>
                      <td style="color: #64748b;">
                        <?= htmlspecialchars($row['keterangan']) ?: '-' ?>
                      </td>
                      <td style="color: #64748b; font-size: 12px;">
                        <?= htmlspecialchars($row['created_at'] ?? '-') ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Card (Right) -->
        <div class="col-md-4">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-plus-circle"></i> Tambah Loket Baru</h3>
            </div>
            <div class="box-body">
              <form method="POST">
                
                <div class="form-group">
                  <label>
                    <i class="fa fa-inbox"></i>
                    Nama Loket <span style="color: #ef4444;">*</span>
                  </label>
                  <input type="text" 
                         name="nama_loket" 
                         class="form-control" 
                         placeholder="Contoh: Loket 1"
                         required>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fa fa-comment"></i>
                    Keterangan <span style="color: #94a3b8; font-weight: 400;">(Opsional)</span>
                  </label>
                  <input type="text" 
                         name="keterangan" 
                         class="form-control" 
                         placeholder="Contoh: Umum / BPJS">
                </div>

                <div class="form-group">
                  <button type="submit" class="btn btn-primary btn-save-custom">
                    <i class="fa fa-save"></i> Simpan Loket
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
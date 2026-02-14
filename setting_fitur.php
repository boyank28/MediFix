<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';

$success = $error = "";

// ==== Simpan Data ====
if(isset($_POST['save'])){
    try {
        foreach($_POST['fitur'] as $kode => $status){
            $stmt = $pdo->prepare("UPDATE feature_control SET status=? WHERE kode_fitur=?");
            $stmt->execute([$status, $kode]);
        }
        $success = "✔ Pengaturan fitur berhasil diperbarui!";
    } catch (PDOException $e) {
        $error = "⚠ Gagal menyimpan: " . $e->getMessage();
    }
}

$data = $pdo->query("SELECT * FROM feature_control ORDER BY nama_fitur")->fetchAll();

// Set page title dan extra CSS
$page_title = 'Setting Fitur Anjungan - MediFix';
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

/* Table Custom */
.table-features {
  margin-bottom: 0;
}

.table-features thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.table-features thead th {
  font-weight: 600;
  border: none;
  padding: 12px;
}

.table-features tbody td {
  vertical-align: middle;
  padding: 12px;
}

.table-features tbody tr:hover {
  background-color: #f8f9fc;
}

.feature-name {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: #2d3748;
}

.feature-icon {
  width: 35px;
  height: 35px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
}

/* Switch Toggle */
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: .4s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .slider {
  background: linear-gradient(135deg, #10b981, #059669);
}

input:checked + .slider:before {
  transform: translateX(26px);
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

.btn-save-custom {
  background: linear-gradient(135deg, #10b981, #059669);
  border: none;
  color: white;
}

.btn-save-custom:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
  color: white;
}

.btn-back-custom {
  background: linear-gradient(135deg, #64748b, #475569);
  border: none;
  color: white;
}

.btn-back-custom:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(100, 116, 139, 0.4);
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
      <h1>Setting Fitur Anjungan</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Setting</a></li>
        <li class="active">Setting Fitur</li>
      </ol>
    </section>

    <section class="content">
      
      <!-- Welcome Box -->
      <div class="welcome-box">
        <h3><i class="fa fa-sliders"></i> Manajemen Fitur Anjungan</h3>
        <p>Kelola status aktivasi fitur pada sistem anjungan</p>
      </div>

      <div class="row">
        
        <!-- Main Content -->
        <div class="col-md-12">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-gear"></i> Daftar Fitur</h3>
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

              <form method="POST">
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-features">
                    <thead>
                      <tr>
                        <th width="50" class="text-center">#</th>
                        <th>Nama Fitur</th>
                        <th width="120" class="text-center">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $no = 1;
                      foreach($data as $row): 
                      ?>
                      <tr>
                        <td class="text-center" style="color: #94a3b8; font-weight: 600;">
                          <?= $no++ ?>
                        </td>
                        <td>
                          <div class="feature-name">
                            <div class="feature-icon">
                              <i class="fa fa-gear"></i>
                            </div>
                            <span><?= htmlspecialchars($row['nama_fitur']) ?></span>
                          </div>
                        </td>
                        <td class="text-center">
                          <label class="switch">
                            <input type="hidden" name="fitur[<?= $row['kode_fitur'] ?>]" value="0">
                            <input type="checkbox" 
                                   name="fitur[<?= $row['kode_fitur'] ?>]" 
                                   value="1" 
                                   <?= $row['status'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                          </label>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                  <div class="btn-group btn-group-justified" role="group">
                  
                    <button type="submit" name="save" class="btn btn-primary btn-save-custom">
                      <i class="fa fa-save"></i> Simpan Perubahan
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
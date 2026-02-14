<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';

// Ambil data
$menus = $pdo->query("SELECT * FROM menu_list ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// User yg dipilih
$selectedUser = $_GET['user_id'] ?? $users[0]['id'];

$success = $error = "";

// Simpan akses
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];

    try {
        $pdo->prepare("DELETE FROM hak_akses WHERE user_id=?")->execute([$user_id]);

        foreach ($menus as $m) {
            $izin = isset($_POST['akses'][$m['kode']]) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO hak_akses (user_id, menu, izin) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $m['kode'], $izin]);
        }

        // Sync session jika edit dirinya sendiri
        if ($_SESSION['user_id'] == $user_id) {
            $_SESSION['akses'] = [];
            $reload = $pdo->prepare("SELECT menu, izin FROM hak_akses WHERE user_id=?");
            $reload->execute([$user_id]);
            foreach ($reload->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $_SESSION['akses'][$row['menu']] = $row['izin'];
            }
        }

        $success = "✔ Hak akses berhasil diperbarui!";
    } catch (PDOException $e) {
        $error = "⚠ Gagal menyimpan: " . $e->getMessage();
    }
}

// Load akses user terpilih
$currentAccess = [];
$load = $pdo->prepare("SELECT menu, izin FROM hak_akses WHERE user_id=?");
$load->execute([$selectedUser]);
foreach ($load->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $currentAccess[$r['menu']] = $r['izin'];
}

// Get selected user name
$selectedUserName = '';
foreach($users as $u) {
    if($u['id'] == $selectedUser) {
        $selectedUserName = $u['nama'];
        break;
    }
}

// Set page title dan extra CSS
$page_title = 'Manajemen Hak Akses - MediFix';
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

/* User List Card */
.user-list-card {
  background: white;
  border-radius: 5px;
  padding: 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
  max-height: calc(100vh - 300px);
  overflow-y: auto;
}

.user-list-card::-webkit-scrollbar {
  width: 6px;
}

.user-list-card::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
}

.user-list-card::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.user-list-title {
  font-size: 13px;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.user-list-title i {
  color: #f59e0b;
}

.user-item {
  padding: 10px 12px;
  border-radius: 6px;
  margin-bottom: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #2d3748;
  text-decoration: none;
  border: 1px solid transparent;
}

.user-item:hover {
  background: #fef3c7;
  color: #2d3748;
  border-color: #fbbf24;
}

.user-item.active {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  font-weight: 600;
  border-color: #f59e0b;
}

.user-item i {
  font-size: 16px;
}

.user-badge-label {
  font-size: 9px;
  background: rgba(245, 158, 11, 0.2);
  color: #f59e0b;
  padding: 2px 6px;
  border-radius: 3px;
  margin-left: auto;
  font-weight: 700;
}

.user-item.active .user-badge-label {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

/* Info Bar */
.info-bar {
  background: #f8fafc;
  padding: 12px 16px;
  border-radius: 6px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-left: 4px solid #f59e0b;
}

.info-bar-left {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #2d3748;
}

.info-bar-left i {
  color: #f59e0b;
  font-size: 16px;
}

.info-bar-user {
  color: #667eea;
  font-weight: 700;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.btn-action-sm {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.btn-check-all {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.btn-uncheck-all {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
}

.btn-action-sm:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Table */
.table-access {
  margin-bottom: 20px;
}

.table-access thead {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.table-access thead th {
  font-weight: 600;
  font-size: 12px;
  padding: 12px;
  border: none;
}

.table-access tbody td {
  padding: 12px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.table-access tbody tr:last-child td {
  border-bottom: none;
}

.table-access tbody tr {
  transition: all 0.2s ease;
}

.table-access tbody tr:hover {
  background: #fef3c7;
}

.menu-name {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: #2d3748;
}

.menu-icon {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
}

/* Checkbox Custom */
.form-check-input {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e1;
  cursor: pointer;
}

.form-check-input:checked {
  background-color: #10b981;
  border-color: #10b981;
}

/* Submit Button */
.btn-save-access {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  color: white;
  padding: 12px 20px;
  border-radius: 5px;
  font-weight: 600;
  transition: all 0.3s ease;
  width: 100%;
}

.btn-save-access i {
  margin-right: 5px;
}

.btn-save-access:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  color: white;
}
';

$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
';

// Include header
include 'includes/header.php';

// Include sidebar
include 'includes/sidebar.php';
?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Manajemen Hak Akses</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Setting</a></li>
        <li class="active">Hak Akses</li>
      </ol>
    </section>

    <section class="content">
      
      <!-- Welcome Box -->
      <div class="welcome-box">
        <h3><i class="fa fa-shield"></i> Manajemen Hak Akses Pengguna</h3>
        <p>Kelola hak akses menu sistem untuk setiap pengguna</p>
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
        
        <!-- User List (Left) -->
        <div class="col-md-3">
          <div class="box">
            <div class="box-body" style="padding: 10px;">
              <div class="user-list-card">
                <div class="user-list-title">
                  <i class="fa fa-users"></i> Pilih Pengguna
                </div>
                
                <?php foreach($users as $u): ?>
                <a href="?user_id=<?= $u['id'] ?>" class="user-item <?= ($selectedUser == $u['id']) ? 'active' : '' ?>">
                  <i class="fa fa-user-circle"></i>
                  <span><?= htmlspecialchars($u['nama']) ?></span>
                  <?php if($u['id'] == $_SESSION['user_id']): ?>
                  <span class="user-badge-label">ANDA</span>
                  <?php endif; ?>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Access Table (Right) -->
        <div class="col-md-9">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title">
                <i class="fa fa-key"></i> Pengaturan Hak Akses Menu
              </h3>
            </div>
            <div class="box-body">

              <form method="POST">
                <input type="hidden" name="user_id" value="<?= $selectedUser ?>">
                
                <!-- Info Bar -->
                <div class="info-bar">
                  <div class="info-bar-left">
                    <i class="fa fa-user-circle"></i>
                    <span>Mengatur hak akses untuk: <span class="info-bar-user"><?= htmlspecialchars($selectedUserName) ?></span></span>
                  </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                  <button type="button" onclick="checkAll()" class="btn-action-sm btn-check-all">
                    <i class="fa fa-check-square"></i> Centang Semua
                  </button>
                  <button type="button" onclick="uncheckAll()" class="btn-action-sm btn-uncheck-all">
                    <i class="fa fa-square-o"></i> Kosongkan Semua
                  </button>
                </div>
                
                <!-- Table -->
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-access">
                    <thead>
                      <tr>
                        <th width="60" class="text-center">No</th>
                        <th>Nama Menu</th>
                        <th width="120" class="text-center">Hak Akses</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $no = 1;
                      foreach ($menus as $m): 
                      ?>
                      <tr>
                        <td class="text-center" style="color: #94a3b8; font-weight: 600;">
                          <?= $no++ ?>
                        </td>
                        <td>
                          <div class="menu-name">
                            <div class="menu-icon">
                              <i class="fa fa-th-large"></i>
                            </div>
                            <span><?= htmlspecialchars($m['nama_menu']) ?></span>
                          </div>
                        </td>
                        <td class="text-center">
                          <input type="checkbox" class="form-check-input menuCheck"
                                 name="akses[<?= $m['kode'] ?>]"
                                 <?= (!empty($currentAccess[$m['kode']])) ? 'checked' : '' ?>>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                
                <!-- Submit Button -->
                <div class="form-group">
                  <button type="submit" class="btn btn-primary btn-save-access">
                    <i class="fa fa-save"></i> Simpan Hak Akses
                  </button>
                </div>
              </form>

            </div>
          </div>
        </div>

      </div>

    </section>
  </div>

<?php if (!empty($success)): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: '<?= $success ?>',
    timer: 2000,
    showConfirmButton: false
});
</script>
<?php endif; ?>

<?php if (!empty($error)): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: '<?= $error ?>',
    confirmButtonText: 'OK'
});
</script>
<?php endif; ?>

<script>
function checkAll() {
    document.querySelectorAll('.menuCheck').forEach(c => c.checked = true);
}

function uncheckAll() {
    document.querySelectorAll('.menuCheck').forEach(c => c.checked = false);
}
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
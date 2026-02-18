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

$success = $error = "";

// ===== HAPUS DATA =====
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $pdo->prepare("DELETE FROM setting_vclaim WHERE id = ?")->execute([$id]);
        $success = "✔ Data VClaim berhasil dihapus!";
        $data = null;
    } catch (PDOException $e) {
        $error = "⚠ Gagal menghapus: " . $e->getMessage();
    }
}

// ===== SIMPAN / EDIT DATA =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $cons_id    = trim($_POST['cons_id']);
    $secret_key = trim($_POST['secret_key']);
    $user_key   = trim($_POST['user_key']);
    $base_url   = trim($_POST['base_url']);
    $kd_ppk     = trim($_POST['kd_ppk']);
    $nm_ppk     = trim($_POST['nm_ppk']);

    if ($cons_id && $secret_key && $user_key && $base_url && $kd_ppk && $nm_ppk) {
        try {
            if ($id) {
                // UPDATE jika sudah ada
                $pdo->prepare("UPDATE setting_vclaim SET cons_id=?, secret_key=?, user_key=?, base_url=?, kd_ppk=?, nm_ppk=?, updated_at=NOW() WHERE id=?")
                    ->execute([$cons_id, $secret_key, $user_key, $base_url, $kd_ppk, $nm_ppk, $id]);
                $success = "✔ Setting VClaim berhasil diperbarui!";
            } else {
                // INSERT jika belum ada data
                $pdo->prepare("INSERT INTO setting_vclaim (cons_id, secret_key, user_key, base_url, kd_ppk, nm_ppk, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())")
                    ->execute([$cons_id, $secret_key, $user_key, $base_url, $kd_ppk, $nm_ppk]);
                $success = "✔ Setting VClaim berhasil disimpan!";
            }
            // Refresh data setelah simpan
            $data = $pdo->query("SELECT * FROM setting_vclaim LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "⚠ Gagal menyimpan: " . $e->getMessage();
        }
    } else {
        $error = "⚠ Semua field wajib diisi.";
    }
}

$page_title = 'Setting VClaim BPJS - MediFix';
$extra_css = '
.welcome-box {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border-radius: 5px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.welcome-box h3 { margin: 0 0 10px 0; font-size: 24px; font-weight: 700; }
.welcome-box p  { margin: 0; opacity: 0.9; font-size: 14px; }

.info-list { display: flex; flex-direction: column; gap: 12px; }

.info-row {
  display: flex; align-items: center; gap: 12px;
  padding: 12px; background: #f8f9fc;
  border-radius: 8px; border-left: 4px solid #3b82f6;
}
.info-icon {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 8px; display: flex; align-items: center;
  justify-content: center; color: white; font-size: 18px; flex-shrink: 0;
}
.info-content { flex: 1; }
.info-label { font-size: 11px; color: #718096; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
.info-value  { font-size: 14px; font-weight: 700; color: #2d3748; word-break: break-all; }

.form-group label { font-weight: 600; color: #2d3748; margin-bottom: 5px; }
.form-group label i { color: #3b82f6; margin-right: 5px; }
.form-control { border: 2px solid #e2e8f0; border-radius: 5px; padding: 8px 12px; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

.btn-save-custom {
  background: linear-gradient(135deg, #10b981, #059669);
  border: none; color: white; padding: 10px 20px;
  border-radius: 5px; font-weight: 600;
  transition: all 0.3s ease; width: 100%;
}
.btn-save-custom:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.4); color: white; }

.btn-hapus-custom {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  border: none; color: white; padding: 8px 16px;
  border-radius: 5px; font-weight: 600;
  transition: all 0.3s ease;
}
.btn-hapus-custom:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239,68,68,0.4); color: white; }

.empty-state { text-align: center; padding: 40px 20px; color: #a0aec0; }
.empty-state i { font-size: 48px; margin-bottom: 15px; display: block; }
.empty-state p { font-size: 14px; }

.toggle-secret { cursor: pointer; color: #3b82f6; }
';

$extra_js = '
<script>
function toggleVisibility(fieldId, iconId) {
    var field = document.getElementById(fieldId);
    var icon  = document.getElementById(iconId);
    if (field.type === "password") {
        field.type = "text";
        icon.className = "fa fa-eye-slash toggle-secret";
    } else {
        field.type = "password";
        icon.className = "fa fa-eye toggle-secret";
    }
}

function konfirmasiHapus(url) {
    if (confirm("⚠ Apakah Anda yakin ingin menghapus data VClaim ini?\nTindakan ini tidak dapat dibatalkan!")) {
        window.location.href = url;
    }
}
</script>
';

include 'includes/header.php';
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

     

      <!-- Alert -->
      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <div class="row">

        <!-- ===== INFO CARD (Kiri) ===== -->
        <div class="col-md-5">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-database"></i> Data VClaim Aktif</h3>
              <?php if ($data): ?>
                <div class="box-tools pull-right">
                  <button class="btn btn-danger btn-xs btn-hapus-custom"
                          onclick="konfirmasiHapus('setting_vclaim.php?action=hapus&id=<?= $data['id'] ?>')">
                    <i class="fa fa-trash"></i> Hapus Data
                  </button>
                </div>
              <?php endif; ?>
            </div>
            <div class="box-body">
              <?php if ($data): ?>
                <div class="info-list">

                  <div class="info-row">
                    <div class="info-icon"><i class="fa fa-fingerprint"></i></div>
                    <div class="info-content">
                      <div class="info-label">Consumer ID</div>
                      <div class="info-value"><?= htmlspecialchars($data['cons_id']) ?></div>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-icon"><i class="fa fa-shield"></i></div>
                    <div class="info-content">
                      <div class="info-label">Secret Key</div>
                      <div class="info-value">••••••••••••••</div>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-icon"><i class="fa fa-key"></i></div>
                    <div class="info-content">
                      <div class="info-label">User Key</div>
                      <div class="info-value">••••••••••••••</div>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-icon"><i class="fa fa-globe"></i></div>
                    <div class="info-content">
                      <div class="info-label">Base URL</div>
                      <div class="info-value"><?= htmlspecialchars($data['base_url']) ?></div>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-icon"><i class="fa fa-hospital-o"></i></div>
                    <div class="info-content">
                      <div class="info-label">Kode PPK</div>
                      <div class="info-value"><?= htmlspecialchars($data['kd_ppk']) ?></div>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-icon"><i class="fa fa-building"></i></div>
                    <div class="info-content">
                      <div class="info-label">Nama PPK</div>
                      <div class="info-value"><?= htmlspecialchars($data['nm_ppk']) ?></div>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-icon"><i class="fa fa-clock-o"></i></div>
                    <div class="info-content">
                      <div class="info-label">Terakhir Diperbarui</div>
                      <div class="info-value"><?= date('d M Y H:i', strtotime($data['updated_at'])) ?></div>
                    </div>
                  </div>

                </div>
              <?php else: ?>
                <div class="empty-state">
                  <i class="fa fa-plug"></i>
                  <p><strong>Belum ada konfigurasi VClaim.</strong><br>Silakan isi form di sebelah kanan untuk menambahkan setting.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- ===== FORM CARD (Kanan) ===== -->
        <div class="col-md-7">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title">
                <i class="fa fa-<?= $data ? 'edit' : 'plus-circle' ?>"></i>
                <?= $data ? 'Edit Pengaturan VClaim' : 'Tambah Pengaturan VClaim' ?>
              </h3>
            </div>
            <div class="box-body">

              <form method="post">
                <!-- Hidden ID untuk mode edit -->
                <input type="hidden" name="id" value="<?= htmlspecialchars($data['id'] ?? '') ?>">

                <div class="form-group">
                  <label><i class="fa fa-fingerprint"></i> Consumer ID</label>
                  <input type="text"
                         name="cons_id"
                         class="form-control"
                         value="<?= htmlspecialchars($data['cons_id'] ?? '') ?>"
                         placeholder="Masukkan Consumer ID dari BPJS"
                         required>
                </div>

                <div class="form-group">
                  <label><i class="fa fa-shield"></i> Secret Key</label>
                  <div class="input-group">
                    <input type="password"
                           id="secret_key"
                           name="secret_key"
                           class="form-control"
                           value="<?= htmlspecialchars($data['secret_key'] ?? '') ?>"
                           placeholder="Masukkan Secret Key dari BPJS"
                           required>
                    <span class="input-group-addon" onclick="toggleVisibility('secret_key','icon_secret')" style="cursor:pointer;">
                      <i id="icon_secret" class="fa fa-eye toggle-secret"></i>
                    </span>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fa fa-key"></i> User Key</label>
                  <div class="input-group">
                    <input type="password"
                           id="user_key"
                           name="user_key"
                           class="form-control"
                           value="<?= htmlspecialchars($data['user_key'] ?? '') ?>"
                           placeholder="Masukkan User Key dari BPJS"
                           required>
                    <span class="input-group-addon" onclick="toggleVisibility('user_key','icon_user')" style="cursor:pointer;">
                      <i id="icon_user" class="fa fa-eye toggle-secret"></i>
                    </span>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fa fa-globe"></i> Base URL</label>
                  <input type="url"
                         name="base_url"
                         class="form-control"
                         value="<?= htmlspecialchars($data['base_url'] ?? '') ?>"
                         placeholder="https://apijkn.bpjs-kesehatan.go.id/vclaim-rest"
                         required>
                  <small class="text-muted">
                    <i class="fa fa-info-circle"></i>
                    Produksi: <code>https://apijkn.bpjs-kesehatan.go.id/vclaim-rest</code><br>
                    Development: <code>https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev</code>
                  </small>
                </div>

                <div class="row">
                  <div class="col-md-5">
                    <div class="form-group">
                      <label><i class="fa fa-hospital-o"></i> Kode PPK</label>
                      <input type="text"
                             name="kd_ppk"
                             class="form-control"
                             value="<?= htmlspecialchars($data['kd_ppk'] ?? '') ?>"
                             placeholder="Contoh: 0101R001"
                             required>
                    </div>
                  </div>
                  <div class="col-md-7">
                    <div class="form-group">
                      <label><i class="fa fa-building"></i> Nama PPK</label>
                      <input type="text"
                             name="nm_ppk"
                             class="form-control"
                             value="<?= htmlspecialchars($data['nm_ppk'] ?? '') ?>"
                             placeholder="Nama Rumah Sakit / Klinik"
                             required>
                    </div>
                  </div>
                </div>

                <div class="form-group" style="margin-top:10px;">
                  <button type="submit" class="btn btn-primary btn-save-custom">
                    <i class="fa fa-save"></i>
                    <?= $data ? 'Simpan Perubahan' : 'Simpan Data' ?>
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
echo $extra_js;
include 'includes/footer.php';
?>
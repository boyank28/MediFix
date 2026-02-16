<?php
session_start();
include 'koneksi.php';
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

// Mapping hari ke bahasa Indonesia
$hari_ini = strtoupper(date('l'));
$hari_map = [
    'SUNDAY' => 'MINGGU',
    'MONDAY' => 'SENIN',
    'TUESDAY' => 'SELASA',
    'WEDNESDAY' => 'RABU',
    'THURSDAY' => 'KAMIS',
    'FRIDAY' => 'JUMAT',
    'SATURDAY' => 'SABTU'
];
$hari_indo = $hari_map[$hari_ini] ?? 'SENIN';

$swal_data = null;

// ===============================
// PROSES PENDAFTARAN
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['no_rkm_medis'])) {
    try {
        $no_rkm_medis = trim($_POST['no_rkm_medis']);
        $kd_poli      = trim($_POST['kd_poli']);
        $kd_dokter    = trim($_POST['kd_dokter']);
        $kd_pj        = trim($_POST['kd_pj']);

        if (!$no_rkm_medis || !$kd_poli || !$kd_dokter || !$kd_pj)
            throw new Exception("Data tidak lengkap!");

        $tgl = date('Y-m-d');
        $jam = date('H:i:s');

        // CEK STATUS DAFTAR BARU/LAMA
        $stmtCekDaftar = $pdo_simrs->prepare("SELECT COUNT(*) FROM reg_periksa WHERE no_rkm_medis=?");
        $stmtCekDaftar->execute([$no_rkm_medis]);
        $stts_daftar = ($stmtCekDaftar->fetchColumn() > 0) ? "Lama" : "Baru";

        // CEK STATUS POLI
        $cekStatus = $pdo_simrs->prepare("
            SELECT COUNT(*) FROM reg_periksa WHERE no_rkm_medis=? AND kd_poli=?");
        $cekStatus->execute([$no_rkm_medis, $kd_poli]);
        $status_poli = ($cekStatus->fetchColumn() > 0) ? "Lama" : "Baru";

        // CEK KAMAR INAP
        $stmt_inap = $pdo_simrs->prepare("
            SELECT COUNT(*) FROM reg_periksa r 
            JOIN kamar_inap k ON r.no_rawat = k.no_rawat 
            WHERE r.no_rkm_medis=? AND k.stts_pulang='-' 
        ");
        $stmt_inap->execute([$no_rkm_medis]);
        if ($stmt_inap->fetchColumn() > 0)
            throw new Exception("Pasien sedang dalam perawatan inap!");

        // CEK SUDAH DAFTAR HARI INI
        $cek = $pdo_simrs->prepare("
            SELECT COUNT(*) FROM reg_periksa 
            WHERE no_rkm_medis=? AND kd_poli=? AND kd_dokter=? AND tgl_registrasi=?
        ");
        $cek->execute([$no_rkm_medis, $kd_poli, $kd_dokter, $tgl]);
        if ($cek->fetchColumn() > 0)
            throw new Exception("Pasien sudah terdaftar hari ini!");

        // NOMOR REG
        $stmt_no = $pdo_simrs->prepare("
            SELECT MAX(CAST(no_reg AS UNSIGNED)) 
            FROM reg_periksa WHERE tgl_registrasi=?");
        $stmt_no->execute([$tgl]);
        $no_reg = str_pad((($stmt_no->fetchColumn() ?: 0) + 1), 3, '0', STR_PAD_LEFT);

        // NOMOR RAWAT
        $stmt_rawat = $pdo_simrs->prepare("
            SELECT MAX(CAST(SUBSTRING(no_rawat, 12, 6) AS UNSIGNED))
            FROM reg_periksa WHERE tgl_registrasi=?
        ");
        $stmt_rawat->execute([$tgl]);
        $max_rawat_seq = $stmt_rawat->fetchColumn();
        $no_rawat = date('Y/m/d/') . str_pad((($max_rawat_seq ?: 0) + 1), 6, '0', STR_PAD_LEFT);

        // AMBIL DATA PASIEN
        $stmt_pasien = $pdo_simrs->prepare("
            SELECT 
                nm_pasien,
                alamat,
                tgl_lahir,
                keluarga AS hubunganpj,
                namakeluarga AS p_jawab,
                alamatpj
            FROM pasien 
            WHERE no_rkm_medis=?
        ");
        $stmt_pasien->execute([$no_rkm_medis]);
        $pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

        if (!$pasien)
            throw new Exception("Data pasien tidak ditemukan!");

        if (empty($pasien['tgl_lahir']))
            throw new Exception("Tanggal lahir belum diinput di data pasien!");

        $p_jawab     = $pasien['p_jawab'] ?: $pasien['nm_pasien'];
        $almt_pj     = $pasien['alamatpj'] ?: $pasien['alamat'];
        $hubunganpj  = $pasien['hubunganpj'] ?: "-";

        // HITUNG UMUR
        $lahir = new DateTime($pasien['tgl_lahir']);
        $today = new DateTime();
        $umur  = $today->diff($lahir)->y;

        // BIAYA REGISTRASI
        $stmt_biaya = $pdo_simrs->prepare("
            SELECT registrasi, registrasilama FROM poliklinik WHERE kd_poli=?");
        $stmt_biaya->execute([$kd_poli]);
        $biaya = $stmt_biaya->fetch(PDO::FETCH_ASSOC);
        $biaya_reg = ($stts_daftar == "Lama") ? $biaya['registrasilama'] : $biaya['registrasi'];

        // INSERT DATA REGISTRASI
        $stmt = $pdo_simrs->prepare("
            INSERT INTO reg_periksa 
            (no_reg,no_rawat,tgl_registrasi,jam_reg,kd_dokter,no_rkm_medis,kd_poli,
             p_jawab,almt_pj,hubunganpj,biaya_reg,stts,stts_daftar,status_lanjut,
             kd_pj,umurdaftar,sttsumur,status_bayar,status_poli)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->execute([
            $no_reg,
            $no_rawat,
            $tgl,
            $jam,
            $kd_dokter,
            $no_rkm_medis,
            $kd_poli,
            $p_jawab,
            $almt_pj,
            $hubunganpj,
            $biaya_reg,
            'Belum',
            $stts_daftar,
            'Ralan',
            $kd_pj,
            $umur,
            'Th',
            'Belum Bayar',
            $status_poli
        ]);

        $printUrl = "print_antrian.php?no_reg={$no_reg}&no_rawat={$no_rawat}&nm_pasien={$pasien['nm_pasien']}";

        $swal_data = [
            'icon' => 'success',
            'title' => 'Pendaftaran Berhasil!',
            'html'  => "<strong>No. Rawat:</strong> {$no_rawat}<br>
                        <strong>No Antri Poliklinik:</strong> {$kd_poli}-{$no_reg}",
            'confirmText' => 'Cetak Antrian',
            'cancelText'  => 'Tutup',
            'printUrl'    => $printUrl,
            'redirect'    => 'daftar_poli.php'
        ];

    } catch (Exception $e) {
        $swal_data = [
            'icon' => 'error',
            'title' => 'Gagal!',
            'text'  => $e->getMessage(),
            'confirmText' => 'OK',
            'redirect' => 'daftar_poli.php'
        ];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pendaftaran Poliklinik - MediFix</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  padding: 15px;
}

.container-custom {
  max-width: 1200px;
  margin: 0 auto;
}

/* Header */
.header-box {
  background: white;
  border-radius: 16px;
  padding: 20px 25px;
  margin-bottom: 15px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  text-align: center;
}

.header-box h4 {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.header-box h4 i {
  color: #667eea;
  font-size: 22px;
}

.header-box p {
  font-size: 13px;
  color: #64748b;
  margin: 5px 0 0;
}

/* Search Box */
.search-box {
  background: white;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 15px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.search-wrapper {
  position: relative;
  margin-bottom: 15px;
}

.search-wrapper input {
  height: 50px;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  font-size: 15px;
  font-weight: 500;
  padding: 0 100px 0 20px;
  transition: all 0.3s;
}

.search-wrapper input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-wrapper .btn-search {
  position: absolute;
  right: 5px;
  top: 5px;
  height: 40px;
  padding: 0 20px;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  color: white;
  font-weight: 600;
  font-size: 14px;
}

.btn-group-custom {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-custom {
  height: 44px;
  padding: 0 20px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-custom i {
  font-size: 16px;
}

.btn-keyboard {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}

.btn-exit {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
}

/* Table */
.table-box {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.table {
  margin: 0;
  font-size: 14px;
}

.table thead th {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  font-weight: 600;
  font-size: 13px;
  padding: 12px 10px;
  border: none;
}

.table tbody td {
  padding: 10px;
  vertical-align: middle;
  color: #334155;
  font-weight: 500;
}

.btn-pilih {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Modal */
.modal-content {
  border: none;
  border-radius: 20px;
  overflow: hidden;
}

.modal-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 18px 24px;
  border: none;
}

.modal-header .modal-title {
  font-size: 18px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-body {
  padding: 24px;
  background: #f8fafc;
}

.modal-body .form-label {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 8px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.modal-body .form-label i {
  color: #667eea;
  font-size: 14px;
}

.modal-body .form-control,
.modal-body .form-select {
  height: 46px;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  font-size: 14px;
  font-weight: 500;
  padding: 0 14px;
}

.modal-body .form-control:focus,
.modal-body .form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-body .form-control[readonly] {
  background: #f1f5f9;
  color: #64748b;
}

.modal-footer {
  padding: 18px 24px;
  border: none;
  background: white;
  gap: 10px;
}

.btn-modal {
  height: 46px;
  padding: 0 28px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-modal-success {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.btn-modal-secondary {
  background: #e2e8f0;
  color: #475569;
}

/* Virtual Keyboard */
.virtual-keyboard {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  padding: 20px;
  z-index: 2000;
  display: none;
  width: 95%;
  max-width: 800px;
}

.keyboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.keyboard-title {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 8px;
}

#closeKeyboard {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  border: none;
  font-size: 20px;
  cursor: pointer;
}

.key-row {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-bottom: 8px;
}

.key {
  min-width: 50px;
  height: 48px;
  background: #f8fafc;
  color: #1e293b;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.key:hover {
  background: white;
  border-color: #667eea;
}

.key.special {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-color: #667eea;
  min-width: 90px;
  font-size: 13px;
}

@media (max-width: 768px) {
  .header-box h4 {
    font-size: 16px;
  }
  
  .header-box p {
    font-size: 12px;
  }
  
  .search-wrapper input {
    height: 46px;
    font-size: 14px;
  }
  
  .table {
    font-size: 12px;
  }
  
  .key {
    min-width: 42px;
    height: 42px;
    font-size: 14px;
  }
}
</style>
</head>
<body>

<div class="container-custom">
  <!-- Header -->
  <div class="header-box">
    <h4>
      <i class="bi bi-hospital"></i>
      ANJUNGAN PENDAFTARAN PASIEN MANDIRI
    </h4>
    <p>Silakan cari data pasien untuk mendaftar ke poliklinik tujuan</p>
  </div>

  <!-- Search Box -->
  <div class="search-box">
    <form method="get">
      <div class="search-wrapper">
        <input 
          type="text" 
          id="inputCari" 
          name="cari" 
          class="form-control" 
          placeholder="Ketik No. RM atau Nama Pasien..." 
          value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>"
          autocomplete="off"
        >
        <button class="btn-search" type="submit">
          <i class="bi bi-search"></i> CARI
        </button>
      </div>
    </form>

    <div class="btn-group-custom">
      <button type="button" class="btn-custom btn-keyboard" onclick="toggleKeyboard()">
        <i class="bi bi-keyboard-fill"></i>
        KEYBOARD VIRTUAL
      </button>
      <a href="anjungan.php" class="btn-custom btn-exit">
        <i class="bi bi-box-arrow-left"></i>
        KELUAR
      </a>
    </div>
  </div>

  <!-- Results Table -->
  <?php
  if (isset($_GET['cari'])) {
      $keyword = trim($_GET['cari']);
      $stmt = $pdo_simrs->prepare("SELECT no_rkm_medis,nm_pasien,jk,tgl_lahir,alamat FROM pasien WHERE no_rkm_medis LIKE ? OR nm_pasien LIKE ? LIMIT 20");
      $stmt->execute(["%$keyword%", "%$keyword%"]);
      $pasien = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (!$pasien) {
          echo "<div class='table-box'>
                  <div class='alert alert-warning mb-0' role='alert'>
                    <i class='bi bi-exclamation-triangle-fill me-2'></i>
                    <strong>Data tidak ditemukan.</strong> Silakan periksa kembali No. RM atau Nama Pasien yang Anda masukkan.
                  </div>
                </div>";
      } else {
          echo "<div class='table-box'>";
          echo "<div class='table-responsive'>";
          echo "<table class='table table-hover align-middle mb-0'>";
          echo "<thead>
                  <tr>
                    <th>NO. RM</th>
                    <th>NAMA PASIEN</th>
                    <th>JK</th>
                    <th>TGL LAHIR</th>
                    <th>ALAMAT</th>
                    <th>AKSI</th>
                  </tr>
                </thead>
                <tbody>";
          
          foreach ($pasien as $p) {
              $no = htmlspecialchars($p['no_rkm_medis']);
              $nm = htmlspecialchars($p['nm_pasien']);
              $jk = htmlspecialchars($p['jk']);
              $tgl_lahir = htmlspecialchars($p['tgl_lahir']);
              $alamat = htmlspecialchars($p['alamat']);
              
              $tgl_format = date('d/m/Y', strtotime($tgl_lahir));
              
              echo "<tr>
                  <td><strong>{$no}</strong></td>
                  <td>{$nm}</td>
                  <td class='text-center'><span class='badge bg-secondary'>{$jk}</span></td>
                  <td class='text-center'>{$tgl_format}</td>
                  <td>{$alamat}</td>
                  <td class='text-center'>
                      <button type='button' class='btn-pilih' data-bs-toggle='modal' data-bs-target='#modalDaftar' 
                      data-norm='{$no}' data-nama='{$nm}'>
                      <i class='bi bi-person-check-fill'></i> PILIH
                      </button>
                  </td>
              </tr>";
          }
          echo "</tbody></table></div></div>";
      }
  }
  ?>
</div>

<!-- Modal Daftar Poli -->
<div class="modal fade" id="modalDaftar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="post" id="formDaftar">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-clipboard2-pulse-fill"></i>
            FORMULIR PENDAFTARAN POLIKLINIK
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
          <input type="hidden" name="no_rkm_medis" id="no_rkm_medis">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-person-circle"></i>
                NAMA PASIEN
              </label>
              <input type="text" id="nama_pasien" class="form-control" readonly>
            </div>
            
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-building-fill-add"></i>
                POLIKLINIK TUJUAN
              </label>
              <select name="kd_poli" id="kd_poli" class="form-select" required>
                <option value="">-- Pilih Poliklinik --</option>
                <?php
                $poli = $pdo_simrs->prepare("SELECT DISTINCT j.kd_poli, p.nm_poli FROM jadwal j 
                                             JOIN poliklinik p ON j.kd_poli=p.kd_poli 
                                             WHERE j.hari_kerja=? ORDER BY p.nm_poli");
                $poli->execute([$hari_indo]);
                foreach ($poli as $pl) {
                    $kd = htmlspecialchars($pl['kd_poli']);
                    $nm = htmlspecialchars($pl['nm_poli']);
                    echo "<option value='{$kd}'>{$nm}</option>";
                }
                ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-person-badge-fill"></i>
                DOKTER PEMERIKSA
              </label>
              <select name="kd_dokter" id="kd_dokter" class="form-select" required>
                <option value="">-- Pilih Dokter --</option>
                <?php
                $dok = $pdo_simrs->prepare("SELECT DISTINCT j.kd_dokter, d.nm_dokter FROM jadwal j 
                                            JOIN dokter d ON j.kd_dokter=d.kd_dokter 
                                            WHERE j.hari_kerja=? ORDER BY d.nm_dokter");
                $dok->execute([$hari_indo]);
                foreach ($dok as $d) {
                    $kd = htmlspecialchars($d['kd_dokter']);
                    $nm = htmlspecialchars($d['nm_dokter']);
                    echo "<option value='{$kd}'>{$nm}</option>";
                }
                ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-credit-card-fill"></i>
                CARA PEMBAYARAN
              </label>
              <select name="kd_pj" id="kd_pj" class="form-select" required>
                <option value="">-- Pilih Cara Bayar --</option>
                <?php
                $penjab = $pdo_simrs->query("SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab");
                foreach ($penjab as $pj) {
                    $kd = htmlspecialchars($pj['kd_pj']);
                    $pn = htmlspecialchars($pj['png_jawab']);
                    echo "<option value='{$kd}'>{$pn}</option>";
                }
                ?>
              </select>
            </div>

            <div class="col-12">
              <div class="alert alert-info mb-0" style="background: #dbeafe; border: none; color: #1e40af; font-size: 13px;">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Perhatian:</strong> Pastikan semua data yang Anda isi sudah benar sebelum menyimpan.
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn-modal btn-modal-success">
            <i class="bi bi-check-circle-fill"></i>
            SIMPAN PENDAFTARAN
          </button>
          <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle-fill"></i>
            BATAL
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Virtual Keyboard -->
<div id="keyboard" class="virtual-keyboard">
  <div class="keyboard-header">
    <div class="keyboard-title">
      <i class="bi bi-keyboard"></i>
      KEYBOARD VIRTUAL
    </div>
    <button id="closeKeyboard">×</button>
  </div>
  <div class="key-row" id="row1"></div>
  <div class="key-row" id="row2"></div>
  <div class="key-row" id="row3"></div>
  <div class="key-row" id="row4"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ===========================
// VOICE GUIDANCE SYSTEM
// ===========================
let currentStep = 0;
const voices = {
  welcome: "Selamat datang di anjungan pendaftaran pasien mandiri. Silakan masukkan nomor rekam medis atau nama  Anda.",
  search: "Silakan klik tombol cari untuk mencari data pasien.",
  selectPatient: "Silakan pilih nama pasien yang sesuai dari daftar.",
  selectPoli: "Silakan pilih poliklinik tujuan Anda.",
  selectDoctor: "Silakan pilih dokter pemeriksa yang Anda inginkan.",
  selectPayment: "Silakan pilih cara pembayaran Anda.",
  confirmSubmit: "Pastikan semua data sudah benar, kemudian klik simpan pendaftaran.",
  success: "Pendaftaran berhasil. Silakan ambil nomor antrian Anda.",
  error: "Terjadi kesalahan. Silakan coba lagi."
};

function speak(text) {
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 0.9;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.includes('id'));
    if (idVoice) utterance.voice = idVoice;
    
    window.speechSynthesis.speak(utterance);
  }
}

// Welcome voice on page load
window.addEventListener('load', () => {
  setTimeout(() => speak(voices.welcome), 500);
  inputCari.focus();
});

// Step 1: Input field focus
const inputCari = document.getElementById('inputCari');
inputCari.addEventListener('focus', () => {
  if (currentStep === 0) {
    speak(voices.search);
    currentStep = 1;
  }
});

// Step 2: Search button click
document.querySelector('.btn-search').addEventListener('click', () => {
  if (inputCari.value.trim()) {
    speak("Mencari data pasien. Mohon tunggu sebentar.");
  }
});

// Step 3: Select patient button
const modal = document.getElementById('modalDaftar');
modal.addEventListener('show.bs.modal', e => {
  const btn = e.relatedTarget;
  document.getElementById('no_rkm_medis').value = btn.dataset.norm;
  document.getElementById('nama_pasien').value = btn.dataset.nama;
  
  setTimeout(() => speak(voices.selectPoli), 500);
  currentStep = 3;
});

// Step 4: Poli selection
const kdPoli = document.getElementById('kd_poli');
kdPoli.addEventListener('change', () => {
  if (kdPoli.value) {
    const poliText = kdPoli.options[kdPoli.selectedIndex].text;
    speak(`Anda memilih ${poliText}. ${voices.selectDoctor}`);
    currentStep = 4;
  }
});

// Step 5: Doctor selection
const kdDokter = document.getElementById('kd_dokter');
kdDokter.addEventListener('change', () => {
  if (kdDokter.value) {
    const dokterText = kdDokter.options[kdDokter.selectedIndex].text;
    speak(`Anda memilih ${dokterText}. ${voices.selectPayment}`);
    currentStep = 5;
  }
});

// Step 6: Payment selection
const kdPj = document.getElementById('kd_pj');
kdPj.addEventListener('change', () => {
  if (kdPj.value) {
    const pjText = kdPj.options[kdPj.selectedIndex].text;
    speak(`Anda memilih ${pjText}. ${voices.confirmSubmit}`);
    currentStep = 6;
  }
});

// Step 7: Form submit
document.getElementById('formDaftar').addEventListener('submit', (e) => {
  speak("Menyimpan data pendaftaran. Mohon tunggu sebentar.");
});

// ===========================
// VIRTUAL KEYBOARD
// ===========================
const keyboard = document.getElementById('keyboard');
const keys1 = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
const keys2 = ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'];
const keys3 = ['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L'];
const keys4 = ['Z', 'X', 'C', 'V', 'B', 'N', 'M', 'Backspace', 'Space'];

function renderKeys(keys, rowId) {
  const row = document.getElementById(rowId);
  keys.forEach(k => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'key' + (k === 'Backspace' || k === 'Space' ? ' special' : '');
    
    if (k === 'Space') {
      btn.innerHTML = '<i class="bi bi-space"></i> SPASI';
    } else if (k === 'Backspace') {
      btn.innerHTML = '<i class="bi bi-backspace"></i> HAPUS';
    } else {
      btn.textContent = k;
    }
    
    btn.onclick = () => pressKey(k);
    row.appendChild(btn);
  });
}

function pressKey(k) {
  if (k === 'Backspace') {
    inputCari.value = inputCari.value.slice(0, -1);
  } else if (k === 'Space') {
    inputCari.value += ' ';
  } else {
    inputCari.value += k;
  }
  inputCari.focus();
}

function toggleKeyboard() {
  if (keyboard.style.display === 'block') {
    keyboard.style.display = 'none';
  } else {
    keyboard.style.display = 'block';
    inputCari.focus();
    speak("Keyboard virtual dibuka. Silakan ketik nomor rekam medis atau nama pasien Anda.");
  }
}

document.getElementById('closeKeyboard').onclick = () => {
  keyboard.style.display = 'none';
  speak("Keyboard virtual ditutup.");
};

renderKeys(keys1, 'row1');
renderKeys(keys2, 'row2');
renderKeys(keys3, 'row3');
renderKeys(keys4, 'row4');

// Load voices
if ('speechSynthesis' in window) {
  speechSynthesis.onvoiceschanged = () => {
    speechSynthesis.getVoices();
  };
  speechSynthesis.getVoices();
}
</script>

<?php if ($swal_data): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const data = <?= json_encode($swal_data, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

  if (data.icon === 'success') {
    speak(voices.success);
    
    Swal.fire({
      icon: data.icon,
      title: data.title,
      html: `<div style="font-size: 16px; line-height: 1.8;">${data.html || data.text || ''}</div>`,
      showCancelButton: true,
      confirmButtonText: '<i class="bi bi-printer-fill me-2"></i>' + (data.confirmText || 'Cetak'),
      cancelButtonText: '<i class="bi bi-x-circle me-2"></i>' + (data.cancelText || 'Tutup'),
      allowOutsideClick: false,
      allowEscapeKey: false,
      customClass: {
        confirmButton: 'btn-modal btn-modal-success',
        cancelButton: 'btn-modal btn-modal-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        if (data.printUrl) window.open(data.printUrl, '_blank');
        window.location = data.redirect || 'daftar_poli.php';
      } else {
        window.location = data.redirect || 'daftar_poli.php';
      }
    });
  } else {
    speak(voices.error);
    
    Swal.fire({
      icon: data.icon || 'error',
      title: data.title || 'Perhatian',
      html: `<div style="font-size: 16px;">${data.text || ''}</div>`,
      confirmButtonText: '<i class="bi bi-check-circle me-2"></i>' + (data.confirmText || 'OK'),
      allowOutsideClick: false,
      allowEscapeKey: false,
      customClass: {
        confirmButton: 'btn-modal btn-modal-success'
      },
      buttonsStyling: false
    }).then(() => {
      window.location = data.redirect || 'daftar_poli.php';
    });
  }
});
</script>
<?php endif; ?>

</body>
</html>
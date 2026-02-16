<?php
session_start();
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$kodeAntrian = null;
$errorMsg = null;
$successMsg = null;

// Ambil identitas rumah sakit
try {
    $stmt = $pdo_simrs->query("SELECT nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email FROM setting LIMIT 1");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $setting = [
        'nama_instansi' => 'RS Permata Hati',
        'alamat_instansi' => 'Jl. Kesehatan No. 123',
        'kabupaten' => 'Kota Sehat',
        'propinsi' => 'Provinsi',
        'kontak' => '(021) 1234567',
        'email' => 'info@rspermatahati.com'
    ];
}

// === LOGIKA AMBIL NOMOR ANTRIAN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ambil'])) {
    try {
        $jenis = 'ADMISI';
        $tgl = date('Y-m-d');

        // Ambil nomor terakhir hari ini
        $stmt = $pdo_simrs->prepare("
            SELECT nomor AS last_nomor
            FROM antrian_wira
            WHERE jenis = ? AND DATE(created_at) = ?
            ORDER BY CAST(SUBSTRING(nomor, 2) AS UNSIGNED) DESC
            LIMIT 1
        ");
        $stmt->execute([$jenis, $tgl]);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);

        $lastNomor = 0;
        if ($last && isset($last['last_nomor'])) {
            $lastNomor = (int)ltrim($last['last_nomor'], 'A');
        }

        // Buat nomor baru
        $nomorBaru = $lastNomor + 1;
        $kodeAntrian = 'A' . str_pad($nomorBaru, 3, '0', STR_PAD_LEFT);

        // Simpan ke database
        $stmt = $pdo_simrs->prepare("
            INSERT INTO antrian_wira (jenis, nomor, status, created_at)
            VALUES (?, ?, 'Menunggu', NOW())
        ");
        $stmt->execute([$jenis, $kodeAntrian]);

        // Set flag untuk auto print
        $successMsg = $kodeAntrian;

    } catch (Exception $e) {
        $errorMsg = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Anjungan Antrian Admisi - <?= htmlspecialchars($setting['nama_instansi']) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e22ce 100%);
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 20px;
  overflow-x: hidden;
}

/* Animated Background Particles */
.bg-animated {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  z-index: 0;
  pointer-events: none;
}

.particle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  animation: float-particle 15s infinite ease-in-out;
}

.particle:nth-child(1) { width: 80px; height: 80px; top: 10%; left: 10%; animation-delay: 0s; }
.particle:nth-child(2) { width: 120px; height: 120px; top: 70%; left: 80%; animation-delay: 3s; }
.particle:nth-child(3) { width: 60px; height: 60px; top: 40%; left: 70%; animation-delay: 6s; }
.particle:nth-child(4) { width: 100px; height: 100px; top: 80%; left: 20%; animation-delay: 9s; }
.particle:nth-child(5) { width: 70px; height: 70px; top: 20%; left: 60%; animation-delay: 12s; }

@keyframes float-particle {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
  25% { transform: translate(30px, -40px) scale(1.1); opacity: 0.5; }
  50% { transform: translate(-20px, 30px) scale(0.9); opacity: 0.4; }
  75% { transform: translate(40px, 20px) scale(1.05); opacity: 0.6; }
}

/* Glass Morphism Container */
.main-container {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1200px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(40px);
  border-radius: 32px;
  box-shadow: 0 40px 100px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.2);
  overflow: hidden;
  animation: slideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(60px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Top Bar with Logo */
.top-bar {
  background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
  padding: 25px 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 15px;
}

.logo-icon {
  width: 60px;
  height: 60px;
  background: white;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.logo-icon i {
  font-size: 32px;
  background: linear-gradient(135deg, #1e40af, #7c3aed);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.logo-text h1 {
  font-size: 24px;
  font-weight: 800;
  color: white;
  margin: 0;
  line-height: 1.2;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.logo-text p {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.9);
  margin: 0;
  font-weight: 500;
}

.status-badge {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  padding: 10px 20px;
  border-radius: 50px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-badge i {
  color: #10b981;
  font-size: 18px;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.1); }
}

.status-badge span {
  color: white;
  font-weight: 600;
  font-size: 14px;
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  padding: 50px;
}

/* Left Panel */
.left-panel {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.welcome-section {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border-radius: 20px;
  padding: 30px;
  border: 2px solid #bfdbfe;
  position: relative;
  overflow: hidden;
}

.welcome-section::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
  border-radius: 50%;
}

.welcome-section h2 {
  font-size: 32px;
  font-weight: 900;
  background: linear-gradient(135deg, #1e40af, #3b82f6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 12px;
  position: relative;
  z-index: 1;
}

.welcome-section p {
  color: #1e40af;
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  line-height: 1.6;
  position: relative;
  z-index: 1;
}

.info-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.info-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  border: 2px solid #f1f5f9;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.info-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(180deg, #3b82f6, #8b5cf6);
  transition: width 0.3s ease;
}

.info-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  border-color: #bfdbfe;
}

.info-card:hover::before {
  width: 100%;
  opacity: 0.05;
}

.info-card-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #dbeafe, #e0e7ff);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.info-card-icon i {
  font-size: 24px;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.info-card-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 6px;
}

.info-card-value {
  font-size: 20px;
  font-weight: 800;
  color: #1e293b;
  font-family: 'Inter', sans-serif;
}

.instruction-box {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-radius: 16px;
  padding: 20px 24px;
  border: 2px solid #fbbf24;
  display: flex;
  align-items: center;
  gap: 15px;
}

.instruction-box i {
  font-size: 32px;
  color: #d97706;
  flex-shrink: 0;
}

.instruction-box p {
  color: #92400e;
  font-weight: 700;
  font-size: 15px;
  margin: 0;
  line-height: 1.5;
}

/* Right Panel */
.right-panel {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 25px;
}

.ticket-display {
  text-align: center;
  margin-bottom: 10px;
}

.ticket-icon-wrapper {
  position: relative;
  display: inline-block;
}

.ticket-icon {
  width: 180px;
  height: 180px;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 25px 60px rgba(59, 130, 246, 0.4);
  animation: float-icon 3s ease-in-out infinite;
  position: relative;
}

@keyframes float-icon {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.ticket-icon::before {
  content: '';
  position: absolute;
  width: 200px;
  height: 200px;
  border: 3px solid rgba(59, 130, 246, 0.3);
  border-radius: 50%;
  animation: ripple-effect 2s ease-out infinite;
}

.ticket-icon::after {
  content: '';
  position: absolute;
  width: 220px;
  height: 220px;
  border: 3px solid rgba(139, 92, 246, 0.2);
  border-radius: 50%;
  animation: ripple-effect 2s ease-out infinite 1s;
}

@keyframes ripple-effect {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(1.3);
    opacity: 0;
  }
}

.ticket-icon i {
  font-size: 90px;
  color: white;
  position: relative;
  z-index: 1;
}

.sparkles {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
}

.sparkle {
  position: absolute;
  width: 8px;
  height: 8px;
  background: white;
  border-radius: 50%;
  animation: sparkle 2s ease-in-out infinite;
}

.sparkle:nth-child(1) { top: 10%; left: 20%; animation-delay: 0s; }
.sparkle:nth-child(2) { top: 80%; left: 80%; animation-delay: 0.5s; }
.sparkle:nth-child(3) { top: 50%; left: 10%; animation-delay: 1s; }
.sparkle:nth-child(4) { top: 20%; left: 90%; animation-delay: 1.5s; }

@keyframes sparkle {
  0%, 100% { opacity: 0; transform: scale(0); }
  50% { opacity: 1; transform: scale(1); }
}

/* Alert Error */
.alert-modern {
  border-radius: 16px;
  border: none;
  padding: 18px 22px;
  font-weight: 700;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 15px;
  animation: shake 0.5s ease;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

.alert-danger {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
  color: #991b1b;
  border: 2px solid #fca5a5;
}

.alert-danger i {
  font-size: 28px;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.btn-modern {
  height: 70px;
  border: none;
  border-radius: 18px;
  font-weight: 800;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  position: relative;
  overflow: hidden;
  cursor: pointer;
  text-decoration: none;
}

.btn-modern::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
}

.btn-modern:hover::before {
  width: 500px;
  height: 500px;
}

.btn-modern i {
  font-size: 32px;
  position: relative;
  z-index: 1;
}

.btn-modern span {
  position: relative;
  z-index: 1;
}

.btn-primary-modern {
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
  box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
}

.btn-primary-modern:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 40px rgba(59, 130, 246, 0.5);
  color: white;
}

.btn-secondary-modern {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  color: white;
}

.btn-secondary-modern:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 40px rgba(100, 116, 139, 0.4);
  color: white;
}

/* Footer Modern */
.footer-modern {
  background: linear-gradient(135deg, #f8fafc, #e2e8f0);
  padding: 25px 50px;
  border-top: 2px solid #cbd5e1;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.footer-item {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #475569;
  font-size: 14px;
  font-weight: 600;
}

.footer-item i {
  font-size: 18px;
  color: #3b82f6;
}

.footer-item .highlight {
  color: #1e40af;
  font-weight: 800;
}

/* Print Styles */
@media print {
  @page {
    size: 80mm auto;
    margin: 0;
  }
  
  body {
    background: white;
  }
  
  body > *:not(.print-area) {
    display: none !important;
  }
  
  .print-area {
    display: block !important;
    position: static !important;
    width: 80mm !important;
    padding: 0 !important;
    margin: 0 !important;
    background: white !important;
  }
  
  .print-area * {
    visibility: visible !important;
  }
}

.print-area {
  display: none;
}

/* Responsive Design */
@media (max-width: 992px) {
  .content-grid {
    grid-template-columns: 1fr;
    padding: 30px;
  }
  
  .top-bar {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }
  
  .info-cards {
    grid-template-columns: 1fr;
  }
  
  .ticket-icon {
    width: 150px;
    height: 150px;
  }
  
  .ticket-icon i {
    font-size: 75px;
  }
  
  .footer-modern {
    grid-template-columns: 1fr;
    text-align: center;
    padding: 20px;
  }
  
  .footer-item {
    justify-content: center;
  }
}

@media (max-width: 576px) {
  .main-container {
    border-radius: 20px;
  }
  
  .content-grid {
    padding: 20px;
    gap: 25px;
  }
  
  .welcome-section h2 {
    font-size: 26px;
  }
  
  .btn-modern {
    height: 60px;
    font-size: 18px;
  }
  
  .btn-modern i {
    font-size: 28px;
  }
}
</style>
</head>
<body>

<!-- Animated Background -->
<div class="bg-animated">
  <div class="particle"></div>
  <div class="particle"></div>
  <div class="particle"></div>
  <div class="particle"></div>
  <div class="particle"></div>
</div>

<div class="main-container">
  <!-- Top Bar -->
  <div class="top-bar">
    <div class="logo-section">
      <div class="logo-icon">
        <i class="bi bi-hospital-fill"></i>
      </div>
      <div class="logo-text">
        <h1><?= htmlspecialchars($setting['nama_instansi']) ?></h1>
        <p><?= htmlspecialchars($setting['kabupaten']) ?>, <?= htmlspecialchars($setting['propinsi']) ?></p>
      </div>
    </div>
    <div class="status-badge">
      <i class="bi bi-check-circle-fill"></i>
      <span>Sistem Aktif</span>
    </div>
  </div>
  
  <!-- Content Grid -->
  <div class="content-grid">
    <!-- Left Panel -->
    <div class="left-panel">
      <div class="welcome-section">
        <h2>🎫 Selamat Datang</h2>
        <p>Silakan ambil nomor antrian untuk pendaftaran pasien. Sistem kami akan memproses pendaftaran Anda dengan cepat dan efisien.</p>
      </div>
      
      <div class="info-cards">
        <div class="info-card">
          <div class="info-card-icon">
            <i class="bi bi-calendar-event-fill"></i>
          </div>
          <div class="info-card-label">Tanggal</div>
          <div class="info-card-value" id="tanggal"></div>
        </div>
        
        <div class="info-card">
          <div class="info-card-icon">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="info-card-label">Waktu Sekarang</div>
          <div class="info-card-value" id="waktu"></div>
        </div>
      </div>
      
      <div class="instruction-box">
        <i class="bi bi-info-circle-fill"></i>
        <p>Tekan tombol "Ambil Nomor Antrian" untuk mendapatkan nomor antrian pendaftaran Anda</p>
      </div>
    </div>
    
    <!-- Right Panel -->
    <div class="right-panel">
      <div class="ticket-display">
        <div class="ticket-icon-wrapper">
          <div class="sparkles">
            <div class="sparkle"></div>
            <div class="sparkle"></div>
            <div class="sparkle"></div>
            <div class="sparkle"></div>
          </div>
          <div class="ticket-icon">
            <i class="bi bi-ticket-perforated-fill"></i>
          </div>
        </div>
      </div>
      
      <!-- Alert Error -->
      <?php if (!empty($errorMsg)): ?>
        <div class="alert-modern alert-danger">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div><?= htmlspecialchars($errorMsg) ?></div>
        </div>
      <?php endif; ?>
      
      <!-- Form -->
      <form method="post" id="formAntrian">
        <div class="action-buttons">
          <button type="submit" name="ambil" class="btn-modern btn-primary-modern">
            <i class="bi bi-ticket-detailed-fill"></i>
            <span>Ambil Nomor Antrian</span>
          </button>
          <a href="anjungan.php" class="btn-modern btn-secondary-modern">
            <i class="bi bi-arrow-left-circle-fill"></i>
            <span>Kembali</span>
          </a>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Footer -->
  <div class="footer-modern">
    <div class="footer-item">
      <i class="bi bi-geo-alt-fill"></i>
      <span><?= htmlspecialchars($setting['alamat_instansi']) ?></span>
    </div>
    <div class="footer-item">
      <i class="bi bi-telephone-fill"></i>
      <span class="highlight"><?= htmlspecialchars($setting['kontak']) ?></span>
    </div>
    <div class="footer-item">
      <i class="bi bi-envelope-fill"></i>
      <span><?= htmlspecialchars($setting['email']) ?></span>
    </div>
    <div class="footer-item">
      <i class="bi bi-code-slash"></i>
      <span>Powered by <span class="highlight">MediFix</span></span>
    </div>
  </div>
</div>

<!-- Print Area (Karcis Thermal 80mm) -->
<?php if (!empty($successMsg)): ?>
<div class="print-area" id="printArea">
  <div style="width:80mm; padding:10mm 5mm; font-family:'Courier New',monospace; background:white; text-align:center;">
    <!-- Header RS -->
    <div style="margin-bottom:8px;">
      <h2 style="font-size:20px; font-weight:900; margin:0 0 6px 0; color:#000; text-transform:uppercase; letter-spacing:0.5px;">
        <?= htmlspecialchars($setting['nama_instansi']) ?>
      </h2>
      <p style="font-size:11px; margin:3px 0; color:#333; line-height:1.4;">
        <?= htmlspecialchars($setting['alamat_instansi']) ?>
      </p>
      <p style="font-size:11px; margin:3px 0; color:#333;">
        <?= htmlspecialchars($setting['kabupaten']) ?>, <?= htmlspecialchars($setting['propinsi']) ?>
      </p>
      <p style="font-size:11px; margin:3px 0; color:#333;">
        Telp: <?= htmlspecialchars($setting['kontak']) ?> | <?= htmlspecialchars($setting['email']) ?>
      </p>
    </div>
    
    <div style="border-top:2px dashed #333; margin:10px 0;"></div>
    
    <!-- Nomor Antrian -->
    <div style="margin:15px 0;">
      <p style="font-size:14px; font-weight:700; margin:0 0 8px 0; color:#000; text-transform:uppercase; letter-spacing:1px;">
        Nomor Antrian Anda
      </p>
      <div style="background:linear-gradient(135deg, #3b82f6, #8b5cf6); padding:20px; border-radius:12px; margin:10px 0;">
        <h1 style="font-size:80px; margin:0; font-weight:900; color:#fff; letter-spacing:5px; text-shadow:0 4px 10px rgba(0,0,0,0.3);">
          <?= htmlspecialchars($successMsg) ?>
        </h1>
      </div>
    </div>
    
    <div style="margin:12px 0;">
      <p style="font-size:15px; font-weight:800; margin:5px 0; color:#000; text-transform:uppercase;">
        ANTRIAN PENDAFTARAN / ADMISI
      </p>
    </div>
    
    <div style="border-top:2px dashed #333; margin:10px 0;"></div>
    
    <!-- Detail Waktu -->
    <div style="margin:12px 0; text-align:left; padding:0 10px;">
      <p style="font-size:12px; margin:5px 0; color:#333;">
        <strong>Tanggal:</strong> <?= date('d F Y') ?>
      </p>
      <p style="font-size:12px; margin:5px 0; color:#333;">
        <strong>Waktu:</strong> <?= date('H:i:s') ?> WIB
      </p>
    </div>
    
    <div style="border-top:2px dashed #333; margin:10px 0;"></div>
    
    <!-- Pesan -->
    <div style="margin:12px 0;">
      <p style="font-size:11px; margin:8px 0; color:#333; line-height:1.5;">
        <strong>Terima kasih</strong> telah mengambil nomor antrian.
      </p>
      <p style="font-size:11px; margin:8px 0; color:#333; line-height:1.5;">
        Silakan menunggu panggilan di ruang tunggu pendaftaran.
      </p>
      <p style="font-size:11px; margin:8px 0; color:#333; line-height:1.5;">
        Mohon siapkan dokumen identitas (KTP/KK) dan kartu BPJS/asuransi (jika ada).
      </p>
    </div>
    
    <div style="border-top:1px dashed #ccc; margin:10px 0;"></div>
    
    <!-- Footer Karcis -->
    <div style="margin:8px 0;">
      <p style="font-size:9px; margin:5px 0; color:#666;">
        Dicetak: <?= date('d/m/Y H:i:s') ?> | Sistem MediFix v2.0
      </p>
      <p style="font-size:9px; margin:5px 0; color:#666;">
        Support: 082177846209 | www.medifix.id
      </p>
      <p style="font-size:10px; margin:8px 0; font-weight:700; color:#000;">
        SELAMAT BEROBAT - SEMOGA LEKAS SEMBUH
      </p>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
// Real-time Date & Time
function updateDateTime() {
  const now = new Date();
  
  const optionsTanggal = { 
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  };
  
  const optionsWaktu = {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  };
  
  const tanggal = now.toLocaleDateString('id-ID', optionsTanggal);
  const waktu = now.toLocaleTimeString('id-ID', optionsWaktu);
  
  document.getElementById('tanggal').textContent = tanggal;
  document.getElementById('waktu').textContent = waktu + ' WIB';
}

setInterval(updateDateTime, 1000);
updateDateTime();

// Auto Print and Redirect
<?php if (!empty($successMsg)): ?>
window.onload = function() {
  setTimeout(function() {
    window.print();
    
    // Redirect setelah print dialog
    setTimeout(function() {
      window.location.href = 'antrian_admisi.php';
    }, 1000);
  }, 500);
};
<?php endif; ?>
</script>

</body>
</html>
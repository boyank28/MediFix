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
        'nama_instansi'    => 'RS Permata Hati',
        'alamat_instansi'  => 'Jl. Kesehatan No. 123',
        'kabupaten'        => 'Kota Sehat',
        'propinsi'         => 'Provinsi',
        'kontak'           => '(021) 1234567',
        'email'            => 'info@rspermatahati.com'
    ];
}

// === LOGIKA AMBIL NOMOR ANTRIAN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ambil'])) {
    try {
        $jenis = 'ADMISI';
        $tgl   = date('Y-m-d');

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

        $nomorBaru   = $lastNomor + 1;
        $kodeAntrian = 'A' . str_pad($nomorBaru, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo_simrs->prepare("
            INSERT INTO antrian_wira (jenis, nomor, status, created_at)
            VALUES (?, ?, 'Menunggu', NOW())
        ");
        $stmt->execute([$jenis, $kodeAntrian]);

        $successMsg = $kodeAntrian;

    } catch (Exception $e) {
        $errorMsg = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Data karcis untuk JS (disiapkan di PHP agar aman di-escape)
$karcisData = [];
if (!empty($successMsg)) {
    $karcisData = [
        'nomor'           => $successMsg,
        'nama_instansi'   => $setting['nama_instansi'],
        'alamat_instansi' => $setting['alamat_instansi'],
        'kabupaten'       => $setting['kabupaten'],
        'propinsi'        => $setting['propinsi'],
        'kontak'          => $setting['kontak'],
        'email'           => $setting['email'],
        'tanggal'         => date('d F Y'),
        'waktu'           => date('H:i:s'),
        'dicetak'         => date('d/m/Y H:i:s'),
    ];
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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@600;700;800;900&display=swap" rel="stylesheet">

<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100vh; overflow:hidden; font-family:'Poppins',sans-serif; }
body { background:linear-gradient(135deg,#667eea 0%,#764ba2 50%,#f093fb 100%); position:relative; }

.bg-animated { position:fixed; top:0; left:0; width:100%; height:100%; overflow:hidden; z-index:0; }
.particle { position:absolute; background:rgba(255,255,255,0.1); border-radius:50%; animation:float 20s infinite ease-in-out; }
.particle:nth-child(1){width:120px;height:120px;top:20%;left:15%;animation-delay:0s}
.particle:nth-child(2){width:80px;height:80px;top:60%;left:75%;animation-delay:4s}
.particle:nth-child(3){width:100px;height:100px;top:75%;left:25%;animation-delay:8s}
.particle:nth-child(4){width:60px;height:60px;top:35%;left:80%;animation-delay:12s}
@keyframes float{0%,100%{transform:translate(0,0) scale(1);opacity:.3}33%{transform:translate(40px,-60px) scale(1.2);opacity:.5}66%{transform:translate(-30px,40px) scale(.8);opacity:.4}}

.main-wrapper { position:relative; z-index:1; height:100vh; display:flex; flex-direction:column; }

.header-bar { background:rgba(255,255,255,.98); backdrop-filter:blur(20px); padding:1.2vh 3vw; box-shadow:0 4px 30px rgba(0,0,0,.1); border-bottom:3px solid transparent; border-image:linear-gradient(90deg,#667eea,#764ba2,#f093fb); border-image-slice:1; }
.header-content { display:flex; align-items:center; justify-content:space-between; max-width:1600px; margin:0 auto; }
.logo-section { display:flex; align-items:center; gap:1.5vw; }
.logo-icon { width:5vw; height:5vw; max-width:70px; max-height:70px; min-width:50px; min-height:50px; background:linear-gradient(135deg,#667eea,#764ba2); border-radius:16px; display:flex; align-items:center; justify-content:center; box-shadow:0 8px 24px rgba(102,126,234,.4); animation:pulse-logo 3s ease-in-out infinite; }
@keyframes pulse-logo{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
.logo-icon i { font-size:2.2vw; color:white; }
.hospital-info h1 { font-size:clamp(18px,2vw,28px); font-weight:900; background:linear-gradient(135deg,#667eea,#764ba2); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin:0; line-height:1.2; }
.hospital-info p { font-size:clamp(11px,1vw,14px); color:#64748b; margin:.3vh 0 0 0; font-weight:600; }
.status-indicator { display:flex; align-items:center; gap:.8vw; background:linear-gradient(135deg,#10b981,#059669); padding:.8vh 1.5vw; border-radius:50px; box-shadow:0 4px 16px rgba(16,185,129,.3); }
.status-indicator i { color:white; font-size:clamp(16px,1.5vw,22px); animation:pulse-dot 2s infinite; }
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.5}}
.status-indicator span { color:white; font-weight:700; font-size:clamp(12px,1.2vw,16px); }

.content-area { flex:1; display:flex; align-items:center; justify-content:center; padding:2vh 3vw; }
.content-grid { display:grid; grid-template-columns:1fr 1fr; gap:3vw; max-width:1600px; width:100%; height:100%; align-items:center; }

.left-section { display:flex; flex-direction:column; gap:2vh; height:100%; justify-content:center; }
.welcome-card { background:rgba(255,255,255,.95); backdrop-filter:blur(20px); border-radius:24px; padding:3vh 2.5vw; box-shadow:0 20px 60px rgba(0,0,0,.15); border:2px solid rgba(255,255,255,.5); position:relative; overflow:hidden; }
.welcome-card::before { content:''; position:absolute; top:-50%; right:-20%; width:300px; height:300px; background:radial-gradient(circle,rgba(102,126,234,.1) 0%,transparent 70%); border-radius:50%; }
.welcome-card h2 { font-size:clamp(24px,3vw,42px); font-weight:900; background:linear-gradient(135deg,#667eea,#764ba2); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin:0 0 1.5vh 0; position:relative; z-index:1; }
.welcome-card p { color:#475569; font-size:clamp(13px,1.3vw,18px); font-weight:600; line-height:1.6; margin:0; position:relative; z-index:1; }

.datetime-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.5vw; }
.datetime-card { background:rgba(255,255,255,.95); backdrop-filter:blur(20px); border-radius:20px; padding:2.5vh 1.5vw; box-shadow:0 8px 32px rgba(0,0,0,.1); border:2px solid rgba(255,255,255,.5); transition:all .3s ease; position:relative; overflow:hidden; }
.datetime-card::before { content:''; position:absolute; top:0; left:0; width:5px; height:100%; background:linear-gradient(180deg,#667eea,#764ba2); transition:width .3s ease; }
.datetime-card:hover { transform:translateY(-5px); box-shadow:0 12px 40px rgba(0,0,0,.15); }
.datetime-icon { width:clamp(40px,4vw,56px); height:clamp(40px,4vw,56px); background:linear-gradient(135deg,#ede9fe,#ddd6fe); border-radius:14px; display:flex; align-items:center; justify-content:center; margin-bottom:1.5vh; }
.datetime-icon i { font-size:clamp(20px,2vw,28px); background:linear-gradient(135deg,#667eea,#764ba2); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.datetime-label { font-size:clamp(10px,1vw,13px); color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:.8vh; }
.datetime-value { font-size:clamp(16px,1.8vw,24px); font-weight:800; color:#1e293b; font-family:'Inter',sans-serif; }

.instruction-card { background:linear-gradient(135deg,rgba(251,191,36,.2),rgba(245,158,11,.2)); backdrop-filter:blur(20px); border-radius:20px; padding:2vh 2vw; border:2px solid rgba(251,191,36,.4); display:flex; align-items:center; gap:1.5vw; box-shadow:0 8px 32px rgba(251,191,36,.2); }
.instruction-card i { font-size:clamp(24px,2.5vw,36px); color:#d97706; flex-shrink:0; }
.instruction-card p { color:#92400e; font-weight:700; font-size:clamp(12px,1.2vw,16px); margin:0; line-height:1.5; }

.right-section { display:flex; flex-direction:column; gap:2.5vh; height:100%; justify-content:center; align-items:center; }
.ticket-showcase { position:relative; margin-bottom:1vh; }
.ticket-circle { width:clamp(140px,15vw,200px); height:clamp(140px,15vw,200px); background:linear-gradient(135deg,#667eea 0%,#764ba2 50%,#f093fb 100%); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 30px 80px rgba(102,126,234,.5); animation:float-ticket 4s ease-in-out infinite; position:relative; }
@keyframes float-ticket{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-15px) rotate(5deg)}}
.ticket-circle::before,.ticket-circle::after { content:''; position:absolute; border-radius:50%; border:3px solid rgba(102,126,234,.3); animation:ripple-out 3s ease-out infinite; }
.ticket-circle::before{width:120%;height:120%}
.ticket-circle::after{width:140%;height:140%;animation-delay:1.5s}
@keyframes ripple-out{0%{transform:scale(1);opacity:.6}100%{transform:scale(1.3);opacity:0}}
.ticket-circle i { font-size:clamp(60px,7vw,100px); color:white; position:relative; z-index:1; filter:drop-shadow(0 4px 8px rgba(0,0,0,.2)); }

.alert-box { background:linear-gradient(135deg,#fee2e2,#fecaca); border:2px solid #fca5a5; border-radius:16px; padding:1.8vh 2vw; display:flex; align-items:center; gap:1.2vw; box-shadow:0 8px 24px rgba(220,38,38,.2); animation:shake .5s ease; width:100%; }
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-10px)}75%{transform:translateX(10px)}}
.alert-box i { font-size:clamp(20px,2vw,28px); color:#991b1b; }
.alert-box div { color:#991b1b; font-weight:700; font-size:clamp(12px,1.2vw,16px); }

.button-container { display:flex; flex-direction:column; gap:1.5vh; width:100%; max-width:500px; }
.btn-action { height:clamp(55px,7vh,75px); border:none; border-radius:18px; font-weight:800; font-size:clamp(16px,1.8vw,22px); display:flex; align-items:center; justify-content:center; gap:1vw; transition:all .4s cubic-bezier(.175,.885,.32,1.275); box-shadow:0 10px 30px rgba(0,0,0,.2); cursor:pointer; text-decoration:none; position:relative; overflow:hidden; }
.btn-action::before { content:''; position:absolute; top:50%; left:50%; width:0; height:0; border-radius:50%; background:rgba(255,255,255,.3); transform:translate(-50%,-50%); transition:width .6s,height .6s; }
.btn-action:hover::before { width:600px; height:600px; }
.btn-action i { font-size:clamp(24px,2.5vw,34px); position:relative; z-index:1; }
.btn-action span { position:relative; z-index:1; }
.btn-primary-action { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; }
.btn-primary-action:hover { transform:translateY(-5px) scale(1.02); box-shadow:0 15px 50px rgba(102,126,234,.5); color:white; }
.btn-secondary-action { background:linear-gradient(135deg,#64748b,#475569); color:white; }
.btn-secondary-action:hover { transform:translateY(-5px) scale(1.02); box-shadow:0 15px 50px rgba(100,116,139,.4); color:white; }

.footer-bar { background:rgba(255,255,255,.95); backdrop-filter:blur(20px); padding:1.2vh 3vw; border-top:2px solid rgba(102,126,234,.2); box-shadow:0 -4px 30px rgba(0,0,0,.1); }
.footer-content { display:flex; align-items:center; justify-content:space-between; max-width:1600px; margin:0 auto; flex-wrap:wrap; gap:1vh 2vw; }
.footer-item { display:flex; align-items:center; gap:.6vw; color:#64748b; font-size:clamp(10px,1vw,13px); font-weight:600; }
.footer-item i { font-size:clamp(14px,1.2vw,18px); color:#667eea; }
.footer-item .highlight { color:#1e293b; font-weight:800; }

/* Sembunyikan print-area dari tampilan layar */
.print-area { display:none !important; }

/* Responsive */
@media (max-width:1024px){.content-grid{grid-template-columns:1fr}.datetime-grid{grid-template-columns:1fr}.header-content{flex-direction:column;gap:1.5vh}.footer-content{flex-direction:column;text-align:center}}
@media (max-width:768px){.logo-section{flex-direction:column;text-align:center}.status-indicator{width:100%;justify-content:center}}
</style>
</head>
<body>

<!-- Animated Background -->
<div class="bg-animated">
  <div class="particle"></div>
  <div class="particle"></div>
  <div class="particle"></div>
  <div class="particle"></div>
</div>

<!-- Main Wrapper -->
<div class="main-wrapper">

  <!-- Header -->
  <div class="header-bar">
    <div class="header-content">
      <div class="logo-section">
        <div class="logo-icon"><i class="bi bi-hospital-fill"></i></div>
        <div class="hospital-info">
          <h1><?= htmlspecialchars($setting['nama_instansi']) ?></h1>
          <p><?= htmlspecialchars($setting['alamat_instansi']) ?> • <?= htmlspecialchars($setting['kabupaten']) ?>, <?= htmlspecialchars($setting['propinsi']) ?></p>
        </div>
      </div>
      <div class="status-indicator">
        <i class="bi bi-circle-fill"></i>
        <span>Sistem Online</span>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="content-area">
    <div class="content-grid">

      <!-- Kiri -->
      <div class="left-section">
        <div class="welcome-card">
          <h2>🎫 Selamat Datang</h2>
          <p>Silakan ambil nomor antrian untuk pendaftaran pasien. Sistem kami akan memproses pendaftaran Anda dengan cepat dan profesional.</p>
        </div>

        <div class="datetime-grid">
          <div class="datetime-card">
            <div class="datetime-icon"><i class="bi bi-calendar-event-fill"></i></div>
            <div class="datetime-label">Tanggal Hari Ini</div>
            <div class="datetime-value" id="tanggal"></div>
          </div>
          <div class="datetime-card">
            <div class="datetime-icon"><i class="bi bi-clock-history"></i></div>
            <div class="datetime-label">Waktu Sekarang</div>
            <div class="datetime-value" id="waktu"></div>
          </div>
        </div>

        <div class="instruction-card">
          <i class="bi bi-info-circle-fill"></i>
          <p>Tekan tombol <strong>"Ambil Nomor Antrian"</strong> di sebelah kanan untuk mendapatkan nomor antrian Anda</p>
        </div>
      </div>

      <!-- Kanan -->
      <div class="right-section">
        <div class="ticket-showcase">
          <div class="ticket-circle">
            <i class="bi bi-ticket-perforated-fill"></i>
          </div>
        </div>

        <?php if (!empty($errorMsg)): ?>
        <div class="alert-box">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div><?= htmlspecialchars($errorMsg) ?></div>
        </div>
        <?php endif; ?>

        <form method="post" id="formAntrian" style="width:100%;display:flex;justify-content:center;">
          <div class="button-container">
            <button type="submit" name="ambil" class="btn-action btn-primary-action">
              <i class="bi bi-ticket-detailed-fill"></i>
              <span>Ambil Nomor Antrian</span>
            </button>
            <a href="anjungan.php" class="btn-action btn-secondary-action">
              <i class="bi bi-arrow-left-circle-fill"></i>
              <span>Kembali ke Menu</span>
            </a>
          </div>
        </form>
      </div>

    </div>
  </div>

  <!-- Footer -->
  <div class="footer-bar">
    <div class="footer-content">
      <div class="footer-item"><i class="bi bi-telephone-fill"></i><span class="highlight"><?= htmlspecialchars($setting['kontak']) ?></span></div>
      <div class="footer-item"><i class="bi bi-envelope-fill"></i><span><?= htmlspecialchars($setting['email']) ?></span></div>
      <div class="footer-item"><i class="bi bi-c-circle"></i><span><?= date('Y') ?> <span class="highlight"><?= htmlspecialchars($setting['nama_instansi']) ?></span></span></div>
      <div class="footer-item"><i class="bi bi-code-slash"></i><span>Powered by <span class="highlight">MediFix</span></span></div>
    </div>
  </div>

</div>

<!-- Print Area (tersembunyi di layar, hanya untuk JS print) -->
<?php if (!empty($successMsg)): ?>
<div class="print-area" id="printArea">
  <div style="width:7.5cm;padding:0.35cm 0.3cm;font-family:'Courier New',monospace;background:white;">

    <!-- Header RS -->
    <div style="text-align:center;margin-bottom:0.15cm;">
      <div style="font-size:13px;font-weight:900;margin:0 0 0.1cm 0;color:#000;text-transform:uppercase;line-height:1.2;">
        <?= htmlspecialchars($setting['nama_instansi']) ?>
      </div>
      <div style="font-size:8px;color:#333;line-height:1.4;">
        <?= htmlspecialchars($setting['alamat_instansi']) ?>
      </div>
      <div style="font-size:8px;color:#333;line-height:1.3;">
        <?= htmlspecialchars($setting['kabupaten']) ?>, <?= htmlspecialchars($setting['propinsi']) ?>
      </div>
      <div style="font-size:7.5px;color:#444;line-height:1.3;">
        Telp: <?= htmlspecialchars($setting['kontak']) ?> | <?= htmlspecialchars($setting['email']) ?>
      </div>
    </div>

    <div style="border-top:1.5px dashed #000;margin:0.15cm 0;"></div>

    <!-- Nomor Antrian -->
    <div style="text-align:center;margin:0.2cm 0;">
      <div style="font-size:9px;font-weight:700;color:#000;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.15cm;">
        Nomor Antrian Anda
      </div>
      <div style="border:2px solid #000;border-radius:6px;padding:0.25cm 0.1cm;margin:0.1cm 0;background:#f5f5f5;">
        <div style="font-size:52px;font-weight:900;color:#000;letter-spacing:4px;line-height:1;font-family:'Courier New',monospace;">
          <?= htmlspecialchars($successMsg) ?>
        </div>
      </div>
      <div style="font-size:10px;font-weight:800;color:#000;text-transform:uppercase;letter-spacing:0.5px;margin-top:0.1cm;">
        ANTRIAN PENDAFTARAN
      </div>
    </div>

    <div style="border-top:1.5px dashed #000;margin:0.15cm 0;"></div>

    <!-- Detail Waktu -->
    <div style="margin:0.1cm 0;padding:0 0.1cm;">
      <div style="font-size:9px;color:#333;margin:0.06cm 0;line-height:1.3;">
        <strong>Tanggal :</strong> <?= date('d F Y') ?>
      </div>
      <div style="font-size:9px;color:#333;margin:0.06cm 0;line-height:1.3;">
        <strong>Waktu &nbsp; :</strong> <?= date('H:i:s') ?> WIB
      </div>
    </div>

    <div style="border-top:1.5px dashed #000;margin:0.15cm 0;"></div>

    <!-- Instruksi -->
    <div style="text-align:center;margin:0.1cm 0;">
      <div style="font-size:8px;color:#333;line-height:1.5;margin:0.05cm 0;">
        Terima kasih telah mengambil nomor antrian.
      </div>
      <div style="font-size:8px;color:#333;line-height:1.5;margin:0.05cm 0;">
        Silakan menunggu panggilan di ruang tunggu.
      </div>
      <div style="font-size:7.5px;color:#555;line-height:1.4;margin:0.05cm 0;">
        Siapkan: KTP / KK &amp; Kartu BPJS (jika ada)
      </div>
    </div>

    <div style="border-top:1px dashed #bbb;margin:0.15cm 0;"></div>

    <!-- Footer Karcis -->
    <div style="text-align:center;margin:0.05cm 0;">
      <div style="font-size:7px;color:#666;line-height:1.3;">
        Dicetak: <?= date('d/m/Y H:i:s') ?>
      </div>
      <div style="font-size:7px;color:#666;line-height:1.3;">
        Sistem MediFix v2.0
      </div>
      <div style="font-size:9px;font-weight:700;color:#000;letter-spacing:0.3px;margin-top:0.1cm;">
        SELAMAT BEROBAT
      </div>
    </div>

  </div>
</div>
<?php endif; ?>

<script>
// Real-time Date & Time
function updateDateTime() {
  const now = new Date();
  document.getElementById('tanggal').textContent = now.toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric'});
  document.getElementById('waktu').textContent   = now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false});
}
setInterval(updateDateTime, 1000);
updateDateTime();

// ============================================================
// AUTO PRINT — iframe tersembunyi, langsung print tanpa popup
// Lebih andal di kiosk karena tidak kena blokir popup browser
// ============================================================
<?php if (!empty($successMsg)): ?>
window.addEventListener('load', function () {

  var karcisHtml = document.getElementById('printArea').innerHTML;

  // Buat iframe tersembunyi
  var iframe = document.createElement('iframe');
  iframe.style.position   = 'fixed';
  iframe.style.top        = '-9999px';
  iframe.style.left       = '-9999px';
  iframe.style.width      = '7.5cm';
  iframe.style.height     = '1px';
  iframe.style.border     = 'none';
  document.body.appendChild(iframe);

  // Tulis konten karcis ke iframe
  var doc = iframe.contentWindow.document;
  doc.open();
  doc.write(
    '<!DOCTYPE html><html><head>' +
    '<meta charset="utf-8">' +
    '<title>Karcis</title>' +
    '<style>' +
    '  @page { size: 7.5cm auto; margin: 0mm; }' +
    '  html, body { margin:0; padding:0; width:7.5cm; background:white; }' +
    '  * { box-sizing:border-box; }' +
    '</style>' +
    '</head><body>' +
    karcisHtml +
    '</body></html>'
  );
  doc.close();

  // Tunggu iframe selesai load lalu print
  iframe.onload = function () {
    setTimeout(function () {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();

      // Setelah print dialog, redirect
      iframe.contentWindow.addEventListener('afterprint', function () {
        setTimeout(function () {
          window.location.href = 'antrian_admisi.php';
        }, 500);
      });

      // Fallback redirect jika afterprint tidak terpicu
      setTimeout(function () {
        window.location.href = 'antrian_admisi.php';
      }, 8000);

    }, 300);
  };

});
<?php endif; ?>
</script>

</body>
</html>
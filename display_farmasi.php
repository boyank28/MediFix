<?php
session_start();
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$nama = $_SESSION['nama'] ?? 'Pengguna';

function tanggalIndonesia($tgl) {
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $tanggal = date('j', strtotime($tgl));
    $bulanIdx = $bulan[(int)date('n', strtotime($tgl))];
    $tahun = date('Y', strtotime($tgl));
    $hariNama = $hari[(int)date('w', strtotime($tgl))];
    return "$hariNama, $tanggal $bulanIdx $tahun";
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Display Antrian Farmasi - MediFix</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --secondary: #f59e0b;
    --accent: #06b6d4;
    --dark: #0f172a;
    --light: #f8fafc;
    --gray: #64748b;
    --glass: rgba(255, 255, 255, 0.1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    min-height: 100vh;
    overflow-x: hidden;
    position: relative;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(245, 158, 11, 0.1) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.container {
    position: relative;
    z-index: 1;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Header */
.header {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.5rem 0;
    margin-bottom: 2rem;
    animation: slideDown 0.6s ease-out;
}

@keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.header-content {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 2rem;
    align-items: center;
}

.brand {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.brand-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 32px rgba(37, 99, 235, 0.3);
}

.brand-icon i {
    font-size: 28px;
    color: white;
}

.brand-text h1 {
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin: 0;
    letter-spacing: -0.5px;
}

.brand-text p {
    font-size: 14px;
    color: var(--gray);
    margin: 0;
    font-weight: 500;
}

.header-center {
    text-align: center;
}

.date-display {
    font-size: 14px;
    color: var(--gray);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.clock-display {
    font-size: 36px;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-variant-numeric: tabular-nums;
    letter-spacing: 2px;
}

/* Main Grid */
.main-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin-bottom: 2rem;
    animation: fadeIn 0.8s ease-out 0.2s both;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Queue Card */
.queue-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.queue-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
}

.queue-card.racikan::before {
    background: linear-gradient(90deg, var(--secondary), #dc2626);
}

.queue-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    border-color: rgba(255, 255, 255, 0.2);
}

.card-header {
    padding: 1.5rem 2rem;
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 18px;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.card-title i {
    font-size: 24px;
    color: var(--accent);
}

.card-title.racikan i {
    color: var(--secondary);
}

.card-body {
    padding: 3rem 2rem;
    text-align: center;
    min-height: 360px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.status-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 2rem;
}

.queue-number {
    font-size: 120px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 2rem;
    font-variant-numeric: tabular-nums;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.queue-number.racikan {
    background: linear-gradient(135deg, var(--secondary), #dc2626);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.queue-number.empty {
    color: rgba(255, 255, 255, 0.1);
    font-size: 80px;
    background: none;
    -webkit-text-fill-color: initial;
}

.queue-number.active {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Smooth fade transition for content change */
.queue-number.fade-out {
    opacity: 0;
    transform: scale(0.95);
}

.queue-number.fade-in {
    animation: fadeInScale 0.5s ease-out forwards;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.patient-info {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 1.5rem;
    margin-top: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.info-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    font-size: 16px;
    font-weight: 500;
    color: white;
    transition: all 0.3s ease;
}

.info-item i {
    font-size: 20px;
    color: var(--accent);
}

.info-item.empty {
    color: var(--gray);
}

/* Info Banner */
.info-banner {
    background: rgba(245, 158, 11, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    animation: fadeIn 1s ease-out 0.4s both;
}

.info-banner-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-banner-header i {
    font-size: 28px;
    color: var(--secondary);
}

.info-banner-title {
    font-size: 18px;
    font-weight: 700;
    color: white;
}

.info-banner-content {
    font-size: 15px;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.8);
}

.info-banner-content strong {
    color: var(--secondary);
    font-weight: 600;
}

/* Footer */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem 0;
    z-index: 100;
}

.marquee-container {
    overflow: hidden;
    white-space: nowrap;
}

.marquee {
    display: inline-block;
    padding-left: 100%;
    animation: scroll 30s linear infinite;
    color: white;
    font-size: 16px;
    font-weight: 500;
}

@keyframes scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}

.marquee i {
    color: var(--secondary);
    margin: 0 0.5rem;
}

/* Update Indicator */
.update-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    background: rgba(16, 185, 129, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 12px;
    padding: 8px 16px;
    color: #10b981;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    opacity: 0;
    transform: translateX(100px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.update-indicator.show {
    opacity: 1;
    transform: translateX(0);
}

.update-indicator i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (min-width: 1920px) {
    .queue-number { font-size: 180px; }
    .queue-number.empty { font-size: 120px; }
    .card-body { min-height: 460px; padding: 4rem 3rem; }
    .card-title { font-size: 24px; }
    .info-item { font-size: 20px; }
    .clock-display { font-size: 48px; }
}

@media (max-width: 1024px) {
    .main-grid { grid-template-columns: 1fr; }
    .header-content { grid-template-columns: 1fr; text-align: center; gap: 1rem; }
    .header-center { order: -1; }
}

@media (max-width: 768px) {
    .queue-number { font-size: 90px; }
    .queue-number.empty { font-size: 60px; }
    .card-body { padding: 2rem 1.5rem; min-height: 280px; }
    .card-title { font-size: 16px; }
    .info-item { font-size: 14px; }
    .clock-display { font-size: 32px; }
    .marquee { font-size: 14px; }
}
</style>
</head>
<body>

<!-- Update Indicator -->
<div class="update-indicator" id="updateIndicator">
    <i class="bi bi-arrow-clockwise"></i>
    <span>Memperbarui data...</span>
</div>

<!-- Header -->
<div class="header">
    <div class="container">
        <div class="header-content">
            <div class="brand">
                <div class="brand-icon">
                    <i class="bi bi-capsule-pill"></i>
                </div>
                <div class="brand-text">
                    <h1>Antrian Farmasi</h1>
                    <p>RS Permata Hati</p>
                </div>
            </div>
            
            <div class="header-center">
                <div class="date-display">
                    <i class="bi bi-calendar3"></i>
                    <?= tanggalIndonesia(date('Y-m-d')) ?>
                </div>
                <div class="clock-display" id="clock">00:00:00</div>
            </div>
            
            <div style="width: 56px;"></div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container" style="padding-bottom: 100px;">
    
    <!-- Queue Grid -->
    <div class="main-grid">
        
        <!-- Non Racikan -->
        <div class="queue-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="bi bi-prescription2"></i>
                    <span>Non Racikan</span>
                </div>
            </div>
            <div class="card-body">
                <div class="status-label">Sedang Dilayani</div>
                <div class="queue-number empty" id="nonRacikanNumber">-</div>
                <div class="patient-info" id="nonRacikanInfo">
                    <div class="info-item empty">
                        <i class="bi bi-hourglass-split"></i>
                        <span>Menunggu Panggilan</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Racikan -->
        <div class="queue-card racikan">
            <div class="card-header">
                <div class="card-title racikan">
                    <i class="bi bi-capsule"></i>
                    <span>Racikan</span>
                </div>
            </div>
            <div class="card-body">
                <div class="status-label">Sedang Dilayani</div>
                <div class="queue-number racikan empty" id="racikanNumber">-</div>
                <div class="patient-info" id="racikanInfo">
                    <div class="info-item empty">
                        <i class="bi bi-hourglass-split"></i>
                        <span>Menunggu Panggilan</span>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Info Banner -->
    <div class="info-banner">
        <div class="info-banner-header">
            <i class="bi bi-info-circle-fill"></i>
            <div class="info-banner-title">Informasi Waktu Tunggu Resep Racikan</div>
        </div>
        <div class="info-banner-content">
            Sesuai <strong>Permenkes 72/2016</strong>, obat racikan membutuhkan proses tambahan 
            (penimbangan, peracikan, pelabelan & validasi apoteker). 
            Estimasi waktu pelayanan: <strong>± 15 – 60 menit</strong>. 
            Terima kasih atas kesabaran dan pengertian Anda 🙏
        </div>
    </div>
    
</div>

<!-- Footer -->
<div class="footer">
    <div class="marquee-container">
        <div class="marquee">
            <i class="bi bi-heart-pulse-fill"></i> 
            Selamat datang di RS Permata Hati
            <i class="bi bi-dot"></i>
            Layanan Farmasi Siap Melayani Anda dengan Sepenuh Hati
            <i class="bi bi-dot"></i>
            Mohon ambil nomor antrian dan tunggu panggilan Anda
            <i class="bi bi-dot"></i>
            Terima kasih atas kepercayaan Anda
            <i class="bi bi-heart-pulse-fill"></i>
            &nbsp;&nbsp;&nbsp;&nbsp;
        </div>
    </div>
</div>

<script>
// ========================================
// CLOCK UPDATE
// ========================================
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
}

setInterval(updateClock, 1000);
updateClock();

// ========================================
// SMOOTH DATA UPDATE WITHOUT RELOAD
// ========================================
let currentData = {
    nonRacikan: null,
    racikan: null
};

function updateDisplay(data, type) {
    const numberEl = document.getElementById(`${type}Number`);
    const infoEl = document.getElementById(`${type}Info`);
    
    const newData = data[type === 'nonRacikan' ? 'non_racikan' : 'racikan'];
    const oldData = currentData[type];
    
    // Check if data changed
    const dataChanged = !oldData || 
                       oldData.nomor !== newData.nomor || 
                       oldData.nama !== newData.nama;
    
    if (dataChanged) {
        // Smooth transition
        numberEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        numberEl.style.opacity = '0';
        numberEl.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            // Update content
            if (newData.has_data) {
                numberEl.textContent = newData.nomor;
                numberEl.className = `queue-number ${type === 'racikan' ? 'racikan' : ''} active`;
                
                infoEl.innerHTML = `
                    <div class="info-item">
                        <i class="bi bi-person-fill"></i>
                        <span>${newData.nama}</span>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-hospital-fill"></i>
                        <span>${newData.poli}</span>
                    </div>
                `;
            } else {
                numberEl.textContent = '-';
                numberEl.className = `queue-number ${type === 'racikan' ? 'racikan' : ''} empty`;
                
                infoEl.innerHTML = `
                    <div class="info-item empty">
                        <i class="bi bi-hourglass-split"></i>
                        <span>Menunggu Panggilan</span>
                    </div>
                `;
            }
            
            // Fade in
            numberEl.style.opacity = '1';
            numberEl.style.transform = 'scale(1)';
        }, 300);
    }
    
    currentData[type] = newData;
}

function fetchData() {
    const indicator = document.getElementById('updateIndicator');
    
    // Show update indicator briefly
    indicator.classList.add('show');
    
    fetch('api_farmasi_display.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDisplay(data, 'nonRacikan');
                updateDisplay(data, 'racikan');
            }
            
            // Hide indicator
            setTimeout(() => {
                indicator.classList.remove('show');
            }, 1000);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            indicator.classList.remove('show');
        });
}

// Initial load
fetchData();

// Auto update every 3 seconds
let updateInterval = setInterval(fetchData, 3000);

// Pause when tab is not visible
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(updateInterval);
    } else {
        fetchData(); // Immediate update when tab becomes visible
        updateInterval = setInterval(fetchData, 3000);
    }
});

// ========================================
// SMOOTH PAGE LOAD
// ========================================
window.addEventListener('load', () => {
    document.body.style.opacity = '1';
});
</script>

</body>
</html>
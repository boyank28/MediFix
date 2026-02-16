<?php
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

try {
    // 🔹 Daftar KODE KAMAR yang tidak ingin ditampilkan
    $excludedKamar = [];

    // 🔹 Daftar KODE BANGSAL yang mau disembunyikan seluruhnya
    $excludedBangsal = [
       'B0213', 'K302','B0114','B0115','B0112','B0113','RR01','RR02','RR03','RR04','B0219'
      ,'B0073','VK1','VK2','OM','OK1','OK2','OK3','OK4','B0081','B0082','B0083','B0084','P001'
      ,'B0096','K019','K020','K021','B0102','ISOC1','K308','M9B','NICU','B0100','B0212','TES','B0118'
    ];

    // 🔹 Normalisasi ke huruf besar & hapus spasi
    $excludedKamar = array_map(fn($v) => strtoupper(trim($v)), $excludedKamar);
    $excludedBangsal = array_map(fn($v) => strtoupper(trim($v)), $excludedBangsal);

    // 🔹 Siapkan untuk query SQL
    $excludedKamarList = "'" . implode("','", $excludedKamar) . "'";
    $excludedBangsalList = !empty($excludedBangsal)
        ? "'" . implode("','", $excludedBangsal) . "'"
        : '';

    // 🔹 Query utama
    $sql = "
        SELECT 
            kamar.kd_kamar, 
            bangsal.nm_bangsal, 
            kamar.kelas, 
            kamar.status 
        FROM kamar 
        INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal 
        WHERE kamar.status IN ('KOSONG', 'ISI')
          AND UPPER(TRIM(kamar.kd_kamar)) NOT IN ($excludedKamarList)
    ";

    if (!empty($excludedBangsalList)) {
        $sql .= " AND UPPER(TRIM(kamar.kd_bangsal)) NOT IN ($excludedBangsalList)";
    }

    $sql .= " ORDER BY kamar.kelas, bangsal.nm_bangsal, kamar.kd_kamar";

    $stmt = $pdo_simrs->query($sql);
    $kamar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 Hitung total & rekap
    $rekap = [];
    $totalIsi = 0; 
    $totalKosong = 0;
    foreach ($kamar as $k) {
        $kelas = $k['kelas'];
        $status = $k['status'];
        if (!isset($rekap[$kelas])) {
            $rekap[$kelas] = ['ISI' => 0, 'KOSONG' => 0];
        }
        $rekap[$kelas][$status]++;
        if ($status == 'ISI') $totalIsi++;
        if ($status == 'KOSONG') $totalKosong++;
    }
    
    $totalKamar = count($kamar);
} catch (PDOException $e) {
    die('Terjadi kesalahan: ' . $e->getMessage());
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ketersediaan Kamar - RS Permata Hati</title>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #00d4aa;
    --secondary: #0088ff;
    --success: #00e676;
    --danger: #ff5252;
    --warning: #ffa726;
    --dark: #0a1929;
    --light: #f8fafb;
    --card-bg: rgba(255, 255, 255, 0.98);
    --shadow: rgba(10, 25, 41, 0.08);
}

html, body {
    height: 100vh;
    overflow: hidden;
}

body {
    font-family: 'DM Sans', -apple-system, sans-serif;
    background: linear-gradient(160deg, #0a1929 0%, #132f4c 50%, #1e4976 100%);
    position: relative;
}

/* Background Effects */
body::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 80%;
    height: 80%;
    background: radial-gradient(circle, rgba(0, 212, 170, 0.15) 0%, transparent 70%);
    border-radius: 50%;
    animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

/* Header */
.header {
    position: relative;
    z-index: 10;
    background: rgba(10, 25, 41, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 3px solid var(--primary);
    padding: 1.5vh 3vw;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 2vw;
    align-items: center;
    box-shadow: 0 4px 30px rgba(0, 212, 170, 0.2);
}

.brand-section {
    display: flex;
    align-items: center;
    gap: 1.2vw;
}

.brand-icon {
    width: 4.5vw;
    height: 4.5vw;
    min-width: 55px;
    min-height: 55px;
    max-width: 75px;
    max-height: 75px;
    background: linear-gradient(135deg, var(--primary) 0%, #00aa88 100%);
    border-radius: 1vw;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(0, 212, 170, 0.4);
}

.brand-icon i {
    font-size: 2.5vw;
    color: white;
}

.brand-text h1 {
    font-family: 'Archivo Black', sans-serif;
    font-size: 2.2vw;
    color: white;
    margin: 0;
    line-height: 1;
    text-transform: uppercase;
    letter-spacing: -0.02em;
    background: linear-gradient(135deg, #ffffff 0%, var(--primary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.brand-text p {
    font-size: 1vw;
    color: rgba(255, 255, 255, 0.7);
    margin: 0.3vh 0 0 0;
    font-weight: 600;
    letter-spacing: 0.05em;
}

.header-stats {
    display: flex;
    gap: 1.5vw;
    justify-content: center;
}

.header-stat-item {
    display: flex;
    align-items: center;
    gap: 0.8vw;
    padding: 1vh 1.5vw;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 0.8vw;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.header-stat-icon {
    width: 2.5vw;
    height: 2.5vw;
    min-width: 35px;
    min-height: 35px;
    border-radius: 0.5vw;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-stat-icon.total {
    background: linear-gradient(135deg, var(--secondary), #0066cc);
}

.header-stat-icon.available {
    background: linear-gradient(135deg, var(--success), #00c853);
}

.header-stat-icon.occupied {
    background: linear-gradient(135deg, var(--danger), #d32f2f);
}

.header-stat-icon i {
    font-size: 1.3vw;
    color: white;
}

.header-stat-text {
    text-align: left;
}

.header-stat-label {
    font-size: 0.75vw;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.header-stat-value {
    font-family: 'Archivo Black', sans-serif;
    font-size: 1.8vw;
    color: white;
    line-height: 1;
}

.header-info {
    text-align: right;
}

.live-time {
    font-family: 'Archivo Black', sans-serif;
    font-size: 2.8vw;
    color: var(--primary);
    font-variant-numeric: tabular-nums;
    line-height: 1;
    text-shadow: 0 0 30px rgba(0, 212, 170, 0.6);
    letter-spacing: -0.02em;
}

.live-date {
    font-size: 1vw;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 600;
    margin-top: 0.3vh;
    letter-spacing: 0.03em;
}

/* Main Content */
.main-content {
    position: relative;
    z-index: 10;
    padding: 2vh 3vw 10vh 3vw;
    height: calc(100vh - 12vh);
    overflow: hidden;
}

.rooms-container {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.rooms-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1.2vw;
    flex: 1;
    align-content: start;
}

.room-card {
    background: var(--card-bg);
    border-radius: 1vw;
    padding: 1.5vh 1vw;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 20px var(--shadow);
    border: 2px solid transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.room-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 0.3vh;
    background: var(--room-accent);
}

.room-card.available {
    --room-accent: var(--success);
    border-color: rgba(0, 230, 118, 0.2);
}

.room-card.available:hover {
    transform: translateY(-0.5vh);
    box-shadow: 0 8px 32px rgba(0, 230, 118, 0.25);
    border-color: var(--success);
}

.room-card.occupied {
    --room-accent: var(--danger);
    border-color: rgba(255, 82, 82, 0.2);
}

.room-card.occupied:hover {
    transform: translateY(-0.5vh);
    box-shadow: 0 8px 32px rgba(255, 82, 82, 0.25);
    border-color: var(--danger);
}

.bed-icon {
    width: 3.5vw;
    height: 3.5vw;
    min-width: 45px;
    min-height: 45px;
    max-width: 60px;
    max-height: 60px;
    background: linear-gradient(135deg, var(--room-accent), var(--room-accent));
    border-radius: 0.8vw;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1vh;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.bed-icon svg {
    width: 60%;
    height: 60%;
    fill: white;
}

.room-name {
    font-size: 0.95vw;
    font-weight: 700;
    color: var(--dark);
    text-align: center;
    margin-bottom: 0.8vh;
    line-height: 1.3;
    min-height: 2.5vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.room-class {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5vh 1vw;
    border-radius: 0.4vw;
    font-size: 0.8vw;
    font-weight: 800;
    color: white;
    margin-bottom: 0.8vh;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    min-width: 4vw;
}

.class-vip { background: linear-gradient(135deg, #ff6b9d, #c44569); }
.class-1 { background: linear-gradient(135deg, var(--secondary), #0066cc); }
.class-2 { background: linear-gradient(135deg, #ffa726, #ef6c00); }
.class-3 { background: linear-gradient(135deg, #ab47bc, #7b1fa2); }

.room-status {
    font-size: 0.85vw;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4vw;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.room-status.available {
    color: var(--success);
}

.room-status.occupied {
    color: var(--danger);
}

.room-status i {
    font-size: 1vw;
}

/* Page Indicator */
.page-indicator {
    position: fixed;
    bottom: 7vh;
    right: 3vw;
    z-index: 20;
    background: rgba(10, 25, 41, 0.95);
    backdrop-filter: blur(10px);
    padding: 1.2vh 1.8vw;
    border-radius: 0.8vw;
    border: 2px solid var(--primary);
    box-shadow: 0 4px 20px rgba(0, 212, 170, 0.3);
    display: flex;
    align-items: center;
    gap: 0.8vw;
}

.page-indicator .page-label {
    font-size: 0.9vw;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 600;
}

.page-indicator .page-numbers {
    font-family: 'Archivo Black', sans-serif;
    font-size: 1.4vw;
    color: var(--primary);
}

.page-indicator .current {
    color: white;
}

/* Footer */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 10;
    background: rgba(10, 25, 41, 0.95);
    backdrop-filter: blur(20px);
    border-top: 3px solid var(--primary);
    padding: 1vh 0;
    box-shadow: 0 -4px 30px rgba(0, 212, 170, 0.2);
    overflow: hidden;
}

.marquee-container {
    width: 100%;
    overflow: hidden;
}

.marquee-content {
    display: inline-flex;
    white-space: nowrap;
    animation: marquee 40s linear infinite;
    font-size: 1.1vw;
    font-weight: 600;
    color: white;
    gap: 3vw;
}

@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.marquee-item {
    display: inline-flex;
    align-items: center;
    gap: 0.8vw;
}

.marquee-item i {
    color: var(--primary);
    font-size: 1.3vw;
}

.kelas {
    color: var(--primary);
    font-weight: 800;
}

.available {
    color: var(--success);
    font-weight: 800;
}

.occupied {
    color: var(--danger);
    font-weight: 800;
}

/* Smooth Fade Transition */
.rooms-grid {
    animation: fadeIn 0.6s ease-in-out;
}

@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Adjustments */
@media (max-height: 900px) {
    .rooms-grid {
        grid-template-columns: repeat(7, 1fr);
        gap: 1vw;
    }
    
    .room-card {
        padding: 1.2vh 0.8vw;
    }
}

@media (max-height: 768px) {
    .rooms-grid {
        grid-template-columns: repeat(8, 1fr);
        gap: 0.8vw;
    }
}

@media (min-width: 1920px) {
    .rooms-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="brand-section">
        <div class="brand-icon">
            <i class="bi bi-hospital"></i>
        </div>
        <div class="brand-text">
            <h1>Ketersediaan Tempat Tidur</h1>
            <p>RS Permata Hati</p>
        </div>
    </div>
    
    <div class="header-stats">
        <div class="header-stat-item">
            <div class="header-stat-icon total">
                <i class="bi bi-grid-3x3-gap-fill"></i>
            </div>
            <div class="header-stat-text">
                <div class="header-stat-label">Total TT</div>
                <div class="header-stat-value"><?= $totalKamar ?></div>
            </div>
        </div>
        
        <div class="header-stat-item">
            <div class="header-stat-icon available">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="header-stat-text">
                <div class="header-stat-label">Tersedia</div>
                <div class="header-stat-value"><?= $totalKosong ?></div>
            </div>
        </div>
        
        <div class="header-stat-item">
            <div class="header-stat-icon occupied">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="header-stat-text">
                <div class="header-stat-label">Terisi</div>
                <div class="header-stat-value"><?= $totalIsi ?></div>
            </div>
        </div>
    </div>
    
    <div class="header-info">
        <div class="live-time" id="liveTime">00:00:00</div>
        <div class="live-date" id="liveDate">-</div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="rooms-container">
        <div class="rooms-grid" id="roomsGrid">
            <?php foreach ($kamar as $k): ?>
                <?php
                    $kelasClass = 'class-3';
                    if (stripos($k['kelas'], 'VIP') !== false) $kelasClass = 'class-vip';
                    elseif (stripos($k['kelas'], '1') !== false) $kelasClass = 'class-1';
                    elseif (stripos($k['kelas'], '2') !== false) $kelasClass = 'class-2';
                    
                    $statusClass = ($k['status'] == 'KOSONG') ? 'available' : 'occupied';
                ?>
                <div class="room-card <?= $statusClass ?>" data-room>
                    <div class="bed-icon">
                        <svg viewBox="0 0 16 16">
                            <path d="M0 7V3a1 1 0 0 1 1-1h1a2 2 0 1 1 4 0h4a2 2 0 1 1 4 0h1a1 1 0 0 1 1 1v4H0zm0 1h16v5h-1v-2h-1v2h-1v-2H3v2H2v-2H1v2H0V8z"/>
                        </svg>
                    </div>
                    <div class="room-name"><?= htmlspecialchars($k['nm_bangsal']) ?></div>
                    <div class="room-class <?= $kelasClass ?>"><?= htmlspecialchars($k['kelas']) ?></div>
                    <div class="room-status <?= $statusClass ?>">
                        <i class="bi bi-<?= $k['status'] == 'KOSONG' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                        <?= $k['status'] == 'KOSONG' ? 'Tersedia' : 'Terisi' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>



<!-- Footer -->
<div class="footer">
    <div class="marquee-container">
        <div class="marquee-content">
            <?php
            $marqueeContent = [];
            foreach ($rekap as $kelas => $jumlah) {
                $total = $jumlah['ISI'] + $jumlah['KOSONG'];
                $marqueeContent[] = "<span class='marquee-item'>
                    <i class='bi bi-info-circle-fill'></i>
                    <span class='kelas'>" . htmlspecialchars($kelas) . "</span>: 
                    <span class='available'>{$jumlah['KOSONG']} tersedia</span> • 
                    <span class='occupied'>{$jumlah['ISI']} terisi</span>
                    (Total: {$total} TT)
                </span>";
            }
            $content = implode("", $marqueeContent);
            echo $content . $content;
            ?>
        </div>
    </div>
</div>

<script>
// Clock Update
function updateClock() {
    const now = new Date();
    const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const date = now.toLocaleDateString('id-ID', options);
    
    document.getElementById('liveTime').textContent = time;
    document.getElementById('liveDate').textContent = date;
}
setInterval(updateClock, 1000);
updateClock();

// Responsive Pagination System
const rooms = Array.from(document.querySelectorAll('[data-room]'));
const totalRooms = rooms.length;

// Calculate items per page based on screen size
function getItemsPerPage() {
    const height = window.innerHeight;
    const width = window.innerWidth;
    
    // Adjust based on screen resolution
    if (height <= 768) {
        return 48; // 8 columns x 6 rows
    } else if (height <= 900) {
        return 42; // 7 columns x 6 rows
    } else if (width >= 1920) {
        return 30; // 5 columns x 6 rows
    } else {
        return 36; // 6 columns x 6 rows (default)
    }
}

let ITEMS_PER_PAGE = getItemsPerPage();
let totalPages = Math.ceil(totalRooms / ITEMS_PER_PAGE);
let currentPage = 0;

document.getElementById('totalPages').textContent = totalPages;

function showPage(pageIndex) {
    const start = pageIndex * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    
    rooms.forEach((room, index) => {
        if (index >= start && index < end) {
            room.style.display = 'flex';
        } else {
            room.style.display = 'none';
        }
    });
    
    document.getElementById('currentPage').textContent = pageIndex + 1;
}

// Initial page display
showPage(currentPage);

// Auto slide every 5 seconds
let slideInterval = setInterval(() => {
    currentPage = (currentPage + 1) % totalPages;
    showPage(currentPage);
}, 5000);

// Handle window resize
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        ITEMS_PER_PAGE = getItemsPerPage();
        totalPages = Math.ceil(totalRooms / ITEMS_PER_PAGE);
        document.getElementById('totalPages').textContent = totalPages;
        currentPage = 0;
        showPage(currentPage);
    }, 250);
});

// Smooth auto-refresh every 30 seconds
setInterval(() => {
    fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const newGrid = doc.querySelector('#roomsGrid');
        const currentGrid = document.querySelector('#roomsGrid');
        
        if (newGrid && currentGrid) {
            currentGrid.style.opacity = '0';
            currentGrid.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                currentGrid.innerHTML = newGrid.innerHTML;
                
                const newRooms = Array.from(document.querySelectorAll('[data-room]'));
                rooms.length = 0;
                rooms.push(...newRooms);
                
                ITEMS_PER_PAGE = getItemsPerPage();
                totalPages = Math.ceil(rooms.length / ITEMS_PER_PAGE);
                document.getElementById('totalPages').textContent = totalPages;
                
                showPage(currentPage);
                
                currentGrid.style.opacity = '1';
            }, 300);
        }
        
        const newStats = doc.querySelector('.header-stats');
        const currentStats = document.querySelector('.header-stats');
        if (newStats && currentStats) {
            currentStats.innerHTML = newStats.innerHTML;
        }
        
        const newMarquee = doc.querySelector('.marquee-content');
        const currentMarquee = document.querySelector('.marquee-content');
        if (newMarquee && currentMarquee) {
            currentMarquee.innerHTML = newMarquee.innerHTML;
        }
    })
    .catch(error => {
        console.error('Refresh error:', error);
    });
}, 30000);
</script>

</body>
</html> 
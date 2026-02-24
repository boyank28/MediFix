<?php
session_start();
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

$today = date('Y-m-d');

try {
    // Ambil data terakhir yang statusnya "Dipanggil"
    $stmt = $pdo_simrs->prepare("
        SELECT a.*, l.nama_loket
        FROM antrian_wira a
        LEFT JOIN loket_admisi_wira l ON a.loket_id = l.id
        WHERE DATE(a.created_at) = ? AND a.status = 'Dipanggil'
        ORDER BY a.waktu_panggil DESC LIMIT 1
    ");
    $stmt->execute([$today]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    // Statistik
    $stmt2 = $pdo_simrs->prepare("SELECT COUNT(*) FROM antrian_wira WHERE DATE(created_at) = ?");
    $stmt2->execute([$today]);
    $total = $stmt2->fetchColumn();

    $stmt3 = $pdo_simrs->prepare("SELECT COUNT(*) FROM antrian_wira WHERE DATE(created_at) = ? AND status='Menunggu'");
    $stmt3->execute([$today]);
    $menunggu = $stmt3->fetchColumn();

} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Display Antrian Admisi — MediFix</title>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

:root {
    --primary:  #00d4aa;
    --secondary:#0088ff;
    --success:  #00e676;
    --danger:   #ff5252;
    --warning:  #fbbf24;
    --dark:     #0a1929;
    --card-bg:  rgba(255,255,255,0.98);
    --shadow:   rgba(10,25,41,0.12);
}

html, body {
    height:100vh; overflow:hidden;
    font-family:'DM Sans',sans-serif;
    background:linear-gradient(160deg, #0a1929 0%, #132f4c 50%, #1e4976 100%);
    position:relative;
}
body::before {
    content:''; position:absolute; top:-50%; right:-20%;
    width:80%; height:80%;
    background:radial-gradient(circle, rgba(0,212,170,.13) 0%, transparent 70%);
    border-radius:50%; animation:bgPulse 18s ease-in-out infinite; pointer-events:none;
}
body::after {
    content:''; position:absolute; bottom:-30%; left:-15%;
    width:60%; height:60%;
    background:radial-gradient(circle, rgba(0,136,255,.10) 0%, transparent 70%);
    border-radius:50%; animation:bgPulse 22s ease-in-out infinite reverse; pointer-events:none;
}
@keyframes bgPulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.12);opacity:.7} }

/* ===== HEADER — identik dengan display_poli & display_farmasi ===== */
.header {
    position:relative; z-index:10;
    background:rgba(10,25,41,.95);
    backdrop-filter:blur(20px);
    border-bottom:3px solid var(--primary);
    padding:1.2vh 3vw;
    display:grid; grid-template-columns:auto 1fr auto;
    gap:2vw; align-items:center;
    box-shadow:0 4px 30px rgba(0,212,170,.2);
}

.brand-section { display:flex; align-items:center; gap:1.2vw; }
.brand-icon {
    width:4.5vw; height:4.5vw;
    min-width:52px; min-height:52px; max-width:72px; max-height:72px;
    background:linear-gradient(135deg,var(--primary),#00aa88);
    border-radius:1vw; display:flex; align-items:center; justify-content:center;
    box-shadow:0 8px 24px rgba(0,212,170,.4);
}
.brand-icon i { font-size:2.4vw; color:#fff; }
.brand-text h1 {
    font-family:'Archivo Black',sans-serif;
    font-size:2vw; color:#fff; margin:0; line-height:1;
    text-transform:uppercase; letter-spacing:-.02em;
    background:linear-gradient(135deg,#fff 0%,var(--primary) 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
}
.brand-text p { font-size:.9vw; color:rgba(255,255,255,.7); margin:.3vh 0 0; font-weight:600; letter-spacing:.05em; }

.header-stats { display:flex; gap:1.2vw; justify-content:center; }
.header-stat-item {
    display:flex; align-items:center; gap:.8vw;
    padding:.9vh 1.3vw;
    background:rgba(255,255,255,.08);
    border-radius:.8vw; border:1px solid rgba(255,255,255,.1);
}
.header-stat-icon {
    width:2.4vw; height:2.4vw; min-width:32px; min-height:32px;
    border-radius:.5vw; display:flex; align-items:center; justify-content:center;
}
.hsi-blue   { background:linear-gradient(135deg,var(--secondary),#0066cc); }
.hsi-yellow { background:linear-gradient(135deg,var(--warning),#e09000); }
.hsi-green  { background:linear-gradient(135deg,var(--success),#00c853); }
.header-stat-icon i  { font-size:1.2vw; color:#fff; }
.header-stat-label   { font-size:.72vw; color:rgba(255,255,255,.6); font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
.header-stat-value   { font-family:'Archivo Black',sans-serif; font-size:1.8vw; color:#fff; line-height:1; }

/* Jam + WIB */
.header-info { text-align:right; }
.live-time-wrap { display:flex; align-items:flex-end; justify-content:flex-end; gap:.5vw; line-height:1; }
.live-time {
    font-family:'Archivo Black',sans-serif; font-size:2.8vw; color:var(--primary);
    letter-spacing:-.02em; text-shadow:0 0 30px rgba(0,212,170,.6);
}
.live-tz {
    font-family:'Archivo Black',sans-serif; font-size:1vw; color:var(--primary);
    opacity:.75; margin-bottom:.35vw; letter-spacing:.05em;
}
.live-date { font-size:.9vw; color:rgba(255,255,255,.8); font-weight:600; margin-top:.3vh; }

.sync-dot {
    width:9px; height:9px; border-radius:50%;
    background:#10b981; display:inline-block; margin-left:8px;
    animation:syncBlink 2s ease-in-out infinite; vertical-align:middle;
}
@keyframes syncBlink { 0%,100%{opacity:1} 50%{opacity:.3} }

/* ===== MAIN LAYOUT ===== */
.main-content {
    position:relative; z-index:1;
    height:calc(100vh - 11vh - 9.5vh);
    padding:2vh 2.5vw 0;
    display:grid;
    grid-template-columns:1.5fr 1fr;
    gap:2.5vw;
    overflow:hidden;
}

/* ===== VIDEO PANEL ===== */
.video-panel {
    background:rgba(0,0,0,.5);
    border-radius:1.5vw; overflow:hidden;
    border:2px solid rgba(255,255,255,.08);
    box-shadow:0 8px 40px rgba(0,0,0,.5);
    position:relative;
}
.video-panel::before {
    content:''; position:absolute; top:0; left:0; right:0; height:5px;
    background:linear-gradient(90deg,var(--primary),var(--secondary)); z-index:1;
}
.video-panel iframe { width:100%; height:100%; border:none; display:block; }

/* ===== PANEL ANTRIAN ===== */
.queue-panel { display:flex; flex-direction:column; gap:2vh; min-height:0; }

/* Kartu nomor dipanggil */
.queue-main-card {
    background:var(--card-bg);
    border-radius:1.5vw; overflow:hidden;
    display:flex; flex-direction:column;
    box-shadow:0 8px 40px var(--shadow);
    border:3px solid transparent;
    flex:1; min-height:0; position:relative;
    transition:border-color .4s, box-shadow .4s;
}
.queue-main-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:5px;
    background:linear-gradient(90deg,var(--primary),var(--secondary)); z-index:1;
}
.queue-main-card.has-call {
    border-color:var(--warning);
    animation:cardGlow 2s ease-in-out infinite;
}
@keyframes cardGlow {
    0%,100%{box-shadow:0 8px 40px var(--shadow)}
    50%{box-shadow:0 8px 60px rgba(251,191,36,.5)}
}

/* Header kartu */
.qcard-head {
    padding:1.5vh 2vw 1.2vh;
    background:linear-gradient(135deg,#0a1929,#132f4c);
    display:flex; align-items:center; gap:1vw; flex-shrink:0;
}
.qcard-icon {
    width:3.8vw; height:3.8vw;
    min-width:46px; min-height:46px; max-width:64px; max-height:64px;
    background:linear-gradient(135deg,var(--primary),var(--secondary));
    border-radius:.8vw; display:flex; align-items:center; justify-content:center;
    flex-shrink:0; box-shadow:0 4px 16px rgba(0,212,170,.35);
}
.qcard-icon i   { font-size:2vw; color:#fff; }
.qcard-title    { font-family:'Archivo Black',sans-serif; font-size:1.5vw; color:#fff; line-height:1.1; }
.qcard-sub      { font-size:.85vw; color:rgba(255,255,255,.5); font-weight:600; margin-top:.3vh; }

/* Body nomor */
.qcard-body {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:1.5vh 2vw 2vh; min-height:0;
}
.call-label {
    font-size:.8vw; font-weight:700; color:rgba(0,0,0,.3);
    text-transform:uppercase; letter-spacing:.15em; margin-bottom:.8vh;
}

/* Nomor antrian — besar, terbaca dari jauh di TV */
.call-number {
    font-family:'Archivo Black',sans-serif;
    font-size:15vw; line-height:1;
    background:linear-gradient(135deg,var(--primary),var(--secondary));
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    letter-spacing:-.02em;
    transition:opacity .3s, transform .3s;
}
.call-number.active { animation:numPulse 2s ease-in-out infinite; }
@keyframes numPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.04)} }
.call-number.empty  { font-size:10vw; -webkit-text-fill-color:rgba(0,0,0,.1); background:none; }

/* Badge loket */
.loket-badge {
    margin-top:1.8vh;
    background:linear-gradient(135deg,var(--warning),#d97706);
    color:#1c1400; padding:1vh 2.5vw;
    border-radius:5vw; font-family:'Archivo Black',sans-serif;
    font-size:1.4vw; display:inline-flex; align-items:center; gap:.6vw;
    box-shadow:0 6px 20px rgba(251,191,36,.4);
    animation:numPulse 2s ease-in-out infinite;
}
.loket-badge i { font-size:1.4vw; }
.loket-badge.empty {
    background:rgba(0,0,0,.07); color:rgba(0,0,0,.3);
    box-shadow:none; animation:none;
    font-family:'DM Sans',sans-serif; font-size:1vw; font-weight:600;
}

/* ===== STAT CARDS — 3 kolom ===== */
.stats-grid {
    display:grid; grid-template-columns:repeat(3,1fr); gap:1.2vw; flex-shrink:0;
}
.stat-card {
    background:var(--card-bg); border-radius:1.2vw; overflow:hidden;
    padding:1.5vh 1.2vw;
    box-shadow:0 4px 20px var(--shadow);
    display:flex; align-items:center; gap:1vw; position:relative;
}
.stat-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:4px;
}
.stat-card.s-total::before   { background:linear-gradient(90deg,var(--secondary),#0066cc); }
.stat-card.s-waiting::before { background:linear-gradient(90deg,var(--warning),#d97706); }
.stat-card.s-done::before    { background:linear-gradient(90deg,var(--success),#00c853); }
.stat-icon {
    width:2.8vw; height:2.8vw; min-width:36px; min-height:36px;
    border-radius:.7vw; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.stat-card.s-total   .stat-icon { background:linear-gradient(135deg,var(--secondary),#0066cc); }
.stat-card.s-waiting .stat-icon { background:linear-gradient(135deg,var(--warning),#d97706); }
.stat-card.s-done    .stat-icon { background:linear-gradient(135deg,var(--success),#00c853); }
.stat-icon i  { font-size:1.3vw; color:#fff; }
.stat-label   { font-size:.7vw; font-weight:700; color:rgba(0,0,0,.4); text-transform:uppercase; letter-spacing:.05em; }
.stat-value   { font-family:'Archivo Black',sans-serif; font-size:2vw; color:var(--dark); line-height:1.1; }

/* ===== FOOTER — identik dengan display_poli & display_farmasi ===== */
.footer {
    position:fixed; bottom:0; left:0; right:0; z-index:10;
    background:rgba(10,25,41,.97);
    backdrop-filter:blur(20px);
    border-top:3px solid var(--primary);
    overflow:hidden;
    box-shadow:0 -4px 30px rgba(0,212,170,.2);
}
.marquee-row {
    border-bottom:1px solid rgba(255,255,255,.08);
    padding:.6vh 0; overflow:hidden;
}
.marquee-content {
    display:inline-flex; white-space:nowrap;
    font-size:.95vw; font-weight:600; color:#fff;
    animation:mqScroll 70s linear infinite;
}
@keyframes mqScroll { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
.mq-item { display:inline-flex; align-items:center; gap:.6vw; padding:0 3vw 0 0; }
.mq-item i { color:var(--primary); }

.footer-copy {
    padding:.4vh 2vw;
    display:flex; align-items:center; justify-content:space-between;
    background:rgba(0,0,0,.35);
}
.footer-copy-left {
    font-size:.68vw; color:rgba(255,255,255,.4); font-weight:500;
    display:flex; align-items:center; gap:.5vw;
}
.footer-copy-left i { color:var(--primary); font-size:.7vw; }
.footer-copy-right {
    font-size:.68vw; color:rgba(255,255,255,.35); font-weight:500; letter-spacing:.03em;
}
.footer-copy-right span { color:var(--primary); font-weight:700; }
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<div class="header">
    <div class="brand-section">
        <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
        <div class="brand-text">
            <h1>Display Antrian Admisi <span class="sync-dot" id="syncDot"></span></h1>
            <p>Pendaftaran &amp; Admisi &mdash; RS Permata Hati</p>
        </div>
    </div>

    <div class="header-stats">
        <div class="header-stat-item">
            <div class="header-stat-icon hsi-blue"><i class="bi bi-list-ol"></i></div>
            <div>
                <div class="header-stat-label">Total Antrian</div>
                <div class="header-stat-value" id="hTotal"><?= $total ?></div>
            </div>
        </div>
        <div class="header-stat-item">
            <div class="header-stat-icon hsi-yellow"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="header-stat-label">Menunggu</div>
                <div class="header-stat-value" id="hMenunggu"><?= $menunggu ?></div>
            </div>
        </div>
        <div class="header-stat-item">
            <div class="header-stat-icon hsi-green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="header-stat-label">Selesai</div>
                <div class="header-stat-value" id="hSelesai">0</div>
            </div>
        </div>
    </div>

    <div class="header-info">
        <div class="live-time-wrap">
            <div class="live-time" id="liveTime">00:00:00</div>
            <div class="live-tz">WIB</div>
        </div>
        <div class="live-date" id="liveDate">&mdash;</div>
    </div>
</div>

<!-- ===== MAIN ===== -->
<div class="main-content">

    <!-- Panel Video kiri -->
    <div class="video-panel">
        <iframe
            src="https://www.youtube.com/embed/9NfAMjbfH5o?autoplay=1&mute=1&loop=1&playlist=9NfAMjbfH5o&controls=0&showinfo=0&rel=0"
            allow="autoplay; fullscreen"
            allowfullscreen>
        </iframe>
    </div>

    <!-- Panel Antrian kanan -->
    <div class="queue-panel">

        <!-- Kartu nomor dipanggil -->
        <div class="queue-main-card <?= $current ? 'has-call' : '' ?>" id="queueCard">
            <div class="qcard-head">
                <div class="qcard-icon"><i class="bi bi-megaphone-fill"></i></div>
                <div>
                    <div class="qcard-title">Nomor Dipanggil</div>
                    <div class="qcard-sub">Silakan menuju loket yang tertera</div>
                </div>
            </div>
            <div class="qcard-body">
                <div class="call-label"><i class="bi bi-broadcast"></i>&nbsp; Sedang Dipanggil</div>

                <?php if ($current): ?>
                <div class="call-number active" id="callNumber"><?= htmlspecialchars($current['nomor']) ?></div>
                <div class="loket-badge" id="callLoket">
                    <i class="bi bi-arrow-right-circle-fill"></i>
                    <?= htmlspecialchars($current['nama_loket'] ?? 'Loket') ?>
                </div>
                <?php else: ?>
                <div class="call-number empty" id="callNumber">&mdash;&mdash;&mdash;</div>
                <div class="loket-badge empty" id="callLoket">
                    <i class="bi bi-hourglass-split"></i>&nbsp; Menunggu Panggilan...
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3 Stat cards -->
        <div class="stats-grid">
            <div class="stat-card s-total">
                <div class="stat-icon"><i class="bi bi-list-ol"></i></div>
                <div>
                    <div class="stat-label">Total</div>
                    <div class="stat-value" id="sTotal"><?= $total ?></div>
                </div>
            </div>
            <div class="stat-card s-waiting">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-label">Menunggu</div>
                    <div class="stat-value" id="sMenunggu"><?= $menunggu ?></div>
                </div>
            </div>
            <div class="stat-card s-done">
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="stat-label">Selesai</div>
                    <div class="stat-value" id="sSelesai">0</div>
                </div>
            </div>
        </div>

    </div><!-- /queue-panel -->

</div><!-- /main-content -->

<!-- ===== FOOTER — identik dengan display_poli & display_farmasi ===== -->
<div class="footer">
    <div class="marquee-row">
        <div class="marquee-content">
            <?php
            $items = [
                ['bi-hospital-fill',     'Selamat datang di RS Permata Hati — Melayani dengan Sepenuh Hati'],
                ['bi-megaphone-fill',    'Harap perhatikan nomor antrian yang ditampilkan dan menunggu dengan tertib'],
                ['bi-person-check-fill', 'Siapkan kartu identitas dan dokumen pendaftaran Anda sebelum dipanggil'],
                ['bi-shield-check-fill', 'Keselamatan dan kenyamanan pasien adalah prioritas utama kami'],
                ['bi-heart-pulse-fill',  'Terima kasih telah mempercayakan kesehatan Anda kepada RS Permata Hati'],
                ['bi-info-circle-fill',  'Informasi lebih lanjut silakan hubungi petugas admisi di loket kami'],
            ];
            $mq = '';
            foreach ($items as $it) {
                $mq .= "<span class='mq-item'><i class='bi {$it[0]}'></i><span>{$it[1]}</span></span>";
            }
            echo $mq . $mq;
            ?>
        </div>
    </div>
    <div class="footer-copy">
        <div class="footer-copy-left">
            <i class="bi bi-shield-check-fill"></i>
            &copy; <?= date('Y') ?> <span style="color:var(--primary);font-weight:700;margin:0 .2vw">MediFix</span>
            &mdash; Anjungan Pasien Mandiri &amp; Sistem Antrian
            &nbsp;<span style="color:rgba(255,255,255,.2)">|</span>&nbsp;
            <i class="bi bi-person-fill"></i>
            <span style="color:var(--primary);font-weight:700">M. Wira Satria Buana</span>
        </div>
        <div class="footer-copy-right">
            Powered by <span>MediFix</span> &middot; v1.0
        </div>
    </div>
</div>

<script>
/* ===== CLOCK — format manual, seragam ===== */
var HARI  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
var BULAN = ['Januari','Februari','Maret','April','Mei','Juni',
             'Juli','Agustus','September','Oktober','November','Desember'];
function pad2(n){ return String(n).padStart(2,'0'); }
function updateClock(){
    var now = new Date();
    document.getElementById('liveTime').textContent =
        pad2(now.getHours())+':'+pad2(now.getMinutes())+':'+pad2(now.getSeconds());
    document.getElementById('liveDate').textContent =
        HARI[now.getDay()]+', '+now.getDate()+' '+BULAN[now.getMonth()]+' '+now.getFullYear();
}
updateClock();
setInterval(updateClock, 1000);

/* ===== STATE ===== */
var lastNomor = '<?= addslashes($current['nomor'] ?? '') ?>';

/* ===== RENDER ANTRIAN — fade transition tanpa kedip ===== */
function renderQueue(data){
    var numEl = document.getElementById('callNumber');
    var lokEl = document.getElementById('callLoket');
    var card  = document.getElementById('queueCard');

    numEl.style.opacity   = '0';
    numEl.style.transform = 'scale(.92)';

    setTimeout(function(){
        if(data.nomor){
            numEl.className   = 'call-number active';
            numEl.textContent = data.nomor;
            lokEl.className   = 'loket-badge';
            lokEl.innerHTML   = '<i class="bi bi-arrow-right-circle-fill"></i> '+(data.loket||'Loket');
            card.classList.add('has-call');
        } else {
            numEl.className   = 'call-number empty';
            numEl.textContent = '\u2014\u2014\u2014';
            lokEl.className   = 'loket-badge empty';
            lokEl.innerHTML   = '<i class="bi bi-hourglass-split"></i>&nbsp; Menunggu Panggilan...';
            card.classList.remove('has-call');
        }

        /* Update statistik */
        var tot = data.total    || 0;
        var tgu = data.menunggu || 0;
        var sdh = data.selesai  || 0;
        ['hTotal','sTotal'].forEach(function(id){
            var e = document.getElementById(id); if(e) e.textContent = tot;
        });
        ['hMenunggu','sMenunggu'].forEach(function(id){
            var e = document.getElementById(id); if(e) e.textContent = tgu;
        });
        ['hSelesai','sSelesai'].forEach(function(id){
            var e = document.getElementById(id); if(e) e.textContent = sdh;
        });

        numEl.style.opacity   = '1';
        numEl.style.transform = 'scale(1)';
    }, 280);
}

/* ===== POLLING ===== */
function pollAntrian(){
    fetch('get_antrian.php')
    .then(function(r){ return r.json(); })
    .then(function(data){
        document.getElementById('syncDot').style.background = '#10b981';
        var isNew = data.nomor && data.nomor !== lastNomor;
        renderQueue(data);
        if(isNew){
            lastNomor = data.nomor;
            playSound(data.nomor, data.loket || '');
        }
    })
    .catch(function(){
        document.getElementById('syncDot').style.background = '#ef4444';
    });
}

var pollInterval = setInterval(pollAntrian, 3000);

document.addEventListener('visibilitychange', function(){
    if(document.hidden){
        clearInterval(pollInterval);
    } else {
        pollAntrian();
        pollInterval = setInterval(pollAntrian, 3000);
    }
});

/* ===== SUARA — identik dengan kode asli ===== */
function angkaToSound(angka){
    var files = {'0':'nol','1':'satu','2':'dua','3':'tiga','4':'empat','5':'lima',
                 '6':'enam','7':'tujuh','8':'delapan','9':'sembilan',
                 '10':'sepuluh','11':'sebelas'};
    var arr = [];
    var n   = parseInt(angka);
    if(n <= 11){
        arr.push('sound/'+files[n]+'.mp3');
    } else if(n < 20){
        arr.push('sound/'+files[n-10]+'.mp3');
        arr.push('sound/belas.mp3');
    } else if(n < 100){
        arr.push('sound/'+files[Math.floor(n/10)]+'.mp3');
        arr.push('sound/puluh.mp3');
        if(n%10 > 0) arr.push('sound/'+files[n%10]+'.mp3');
    } else if(n < 1000){
        var ratus = Math.floor(n/100);
        var sisa  = n % 100;
        if(ratus === 1){ arr.push('sound/seratus.mp3'); }
        else { arr.push('sound/'+files[ratus]+'.mp3'); arr.push('sound/ratus.mp3'); }
        if(sisa > 0) arr = arr.concat(angkaToSound(sisa.toString()));
    }
    return arr;
}

function playSound(nomor, loket){
    var sounds = ['sound/opening.mp3','sound/nomor antrian.mp3'];
    sounds.push('sound/'+nomor.charAt(0)+'.mp3');
    sounds = sounds.concat(angkaToSound(nomor.substring(1)));
    sounds.push('sound/silahkan menuju loket.mp3');
    var m = loket.match(/\d+/);
    if(m) sounds = sounds.concat(angkaToSound(m[0]));
    playSeq(sounds);
}

function playSeq(arr){
    if(!arr.length) return;
    var a = new Audio(arr[0]);
    a.addEventListener('ended', function(){ playSeq(arr.slice(1)); });
    a.play().catch(function(){});
}

/* Aktifkan audio pada klik pertama */
document.addEventListener('click', function init(){
    var a = new Audio('sound/opening.mp3');
    a.volume = 0;
    a.play().catch(function(){});
    document.removeEventListener('click', init);
}, {once:true});
</script>
</body>
</html>
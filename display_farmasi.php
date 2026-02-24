<?php
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

$tgl = date('Y-m-d');

function getLatestCall($pdo, $tgl, $jenis) {
    $stmt = $pdo->prepare("
        SELECT no_antrian, no_rawat, nm_pasien, nm_poli, nm_dokter, jml_panggil,
               UNIX_TIMESTAMP(updated_at) AS ts
        FROM simpan_antrian_farmasi_wira
        WHERE tgl_panggil = ? AND jenis_resep = ?
        ORDER BY updated_at DESC LIMIT 1
    ");
    $stmt->execute([$tgl, $jenis]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

try {
    $nr = getLatestCall($pdo_simrs, $tgl, 'Non Racikan');
    $r  = getLatestCall($pdo_simrs, $tgl, 'Racikan');
} catch (PDOException $e) {
    $nr = $r = null;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Display Antrian Farmasi — MediFix</title>
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
    /* Warna khusus farmasi */
    --nr-color: #00d4aa;   /* Non Racikan — teal (sama dengan primary) */
    --rc-color: #f59e0b;   /* Racikan — amber */
}

html, body {
    height: 100vh;
    overflow: hidden;
    font-family: 'DM Sans', sans-serif;
    background: linear-gradient(160deg, #0a1929 0%, #132f4c 50%, #1e4976 100%);
    position: relative;
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

/* ===== HEADER — identik dengan display_poli ===== */
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
    background:linear-gradient(135deg, var(--primary), #00aa88);
    border-radius:1vw; display:flex; align-items:center; justify-content:center;
    box-shadow:0 8px 24px rgba(0,212,170,.4);
}
.brand-icon i { font-size:2.4vw; color:#fff; }
.brand-text h1 {
    font-family:'Archivo Black', sans-serif;
    font-size:2vw; color:#fff; margin:0; line-height:1;
    text-transform:uppercase; letter-spacing:-.02em;
    background:linear-gradient(135deg, #fff 0%, var(--primary) 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
}
.brand-text p { font-size:.9vw; color:rgba(255,255,255,.7); margin:.3vh 0 0; font-weight:600; letter-spacing:.05em; }

/* Stat box tengah header */
.header-stats { display:flex; gap:1.2vw; justify-content:center; }
.header-stat-item {
    display:flex; align-items:center; gap:.8vw;
    padding:.9vh 1.5vw;
    background:rgba(255,255,255,.08);
    border-radius:.8vw; border:1px solid rgba(255,255,255,.1);
}
.header-stat-icon {
    width:2.4vw; height:2.4vw; min-width:32px; min-height:32px;
    border-radius:.5vw; display:flex; align-items:center; justify-content:center;
}
.header-stat-icon.nr  { background:linear-gradient(135deg, var(--nr-color), #009e80); }
.header-stat-icon.rc  { background:linear-gradient(135deg, var(--rc-color), #d97706); }
.header-stat-icon i { font-size:1.2vw; color:#fff; }
.header-stat-label { font-size:.72vw; color:rgba(255,255,255,.6); font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
.header-stat-value { font-family:'Archivo Black',sans-serif; font-size:1.8vw; color:#fff; line-height:1; }

/* Jam + WIB */
.header-info { text-align:right; }
.live-time-wrap { display:flex; align-items:flex-end; justify-content:flex-end; gap:.5vw; line-height:1; }
.live-time {
    font-family:'Archivo Black',sans-serif;
    font-size:2.8vw; color:var(--primary);
    letter-spacing:-.02em;
    text-shadow:0 0 30px rgba(0,212,170,.6);
}
.live-tz {
    font-family:'Archivo Black',sans-serif;
    font-size:1vw; color:var(--primary);
    opacity:.75; margin-bottom:.35vw; letter-spacing:.05em;
}
.live-date { font-size:.9vw; color:rgba(255,255,255,.8); font-weight:600; margin-top:.3vh; }

/* sync dot */
.sync-dot {
    width:9px; height:9px; border-radius:50%;
    background:#10b981; display:inline-block; margin-left:8px;
    animation:syncBlink 2s ease-in-out infinite; vertical-align:middle;
}
@keyframes syncBlink { 0%,100%{opacity:1} 50%{opacity:.3} }

/* ===== MAIN CONTENT ===== */
.main-content {
    position:relative; z-index:1;
    height:calc(100vh - 11vh - 9.5vh);
    padding:2.5vh 4vw 0;
    display:flex; flex-direction:column; gap:2vh;
    overflow:hidden;
}

/* ===== GRID DUA CARD FARMASI ===== */
.farmasi-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:3vw;
    flex:1;
    min-height:0;
}

/* ===== KARTU FARMASI ===== */
.farmasi-card {
    background:var(--card-bg);
    border-radius:1.5vw;
    overflow:hidden;
    display:flex; flex-direction:column;
    box-shadow:0 8px 40px var(--shadow);
    border:3px solid transparent;
    position:relative;
    transition:border-color .4s, box-shadow .4s;
}

/* Garis aksen atas */
.farmasi-card::before {
    content:'';
    position:absolute; top:0; left:0; right:0; height:5px;
    background:linear-gradient(90deg, var(--nr-color), #009e80);
    z-index:1;
}
.farmasi-card.racikan::before {
    background:linear-gradient(90deg, var(--rc-color), #d97706);
}

/* Glow saat ada panggilan aktif */
.farmasi-card.has-call {
    border-color:var(--warning);
    animation:cardGlow 2s ease-in-out infinite;
}
@keyframes cardGlow {
    0%,100%{box-shadow:0 8px 40px var(--shadow)}
    50%{box-shadow:0 8px 60px rgba(251,191,36,.5)}
}

/* Header kartu */
.fcard-head {
    padding:1.5vh 2vw 1.2vh;
    background:linear-gradient(135deg, #0a1929, #132f4c);
    display:flex; align-items:center; gap:1.2vw;
    flex-shrink:0;
}
.fcard-icon {
    width:3.8vw; height:3.8vw;
    min-width:46px; min-height:46px; max-width:64px; max-height:64px;
    border-radius:.8vw; display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.fcard-icon.nr { background:linear-gradient(135deg, var(--nr-color), #009e80); }
.fcard-icon.rc { background:linear-gradient(135deg, var(--rc-color), #d97706); }
.fcard-icon i  { font-size:2vw; color:#fff; }
.fcard-head-text { flex:1; }
.fcard-title {
    font-family:'Archivo Black',sans-serif;
    font-size:1.5vw; color:#fff; line-height:1.1;
}
.fcard-subtitle { font-size:.85vw; color:rgba(255,255,255,.55); font-weight:600; margin-top:.3vh; }

/* Body kartu — area utama nomor antrian */
.fcard-body {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:2vh 2vw 1.5vh;
    position:relative;
}

/* Label "Sedang Dipanggil" */
.call-label {
    font-size:.8vw; font-weight:700; color:rgba(0,0,0,.35);
    text-transform:uppercase; letter-spacing:.15em;
    margin-bottom:1.5vh;
}

/* Nomor antrian BESAR */
.call-number {
    font-family:'Archivo Black',sans-serif;
    font-size:13vw; line-height:1;
    color:var(--nr-color);
    text-shadow:0 0 60px rgba(0,212,170,.25);
    transition:opacity .3s, transform .3s;
    letter-spacing:-.02em;
}
.call-number.racikan {
    color:var(--rc-color);
    text-shadow:0 0 60px rgba(245,158,11,.25);
}
.call-number.empty {
    font-size:9vw;
    color:rgba(0,0,0,.1);
    text-shadow:none;
}
.call-number.active {
    animation:numBig 2s ease-in-out infinite;
}
@keyframes numBig { 0%,100%{transform:scale(1)} 50%{transform:scale(1.03)} }

/* Info pasien di bawah nomor */
.call-info {
    width:100%; margin-top:2vh;
    background:linear-gradient(135deg, rgba(10,25,41,.06), rgba(10,25,41,.03));
    border-radius:1vw; padding:1.2vh 1.5vw;
    border:1px solid rgba(0,0,0,.07);
}
.ci-row {
    display:flex; align-items:center; gap:.8vw;
    padding:.6vh 0;
    border-bottom:1px solid rgba(0,0,0,.05);
    font-size:.95vw; font-weight:600; color:#1a2e44;
}
.ci-row:last-child { border-bottom:none; }
.ci-row i { font-size:1.1vw; flex-shrink:0; }
.ci-row.nr-icon i { color:var(--nr-color); }
.ci-row.rc-icon i { color:var(--rc-color); }
.ci-row.empty-row { color:rgba(0,0,0,.3); justify-content:center; }
.ci-name { font-size:1.1vw; font-weight:800; }
.ci-poli { font-size:.85vw; color:rgba(0,0,0,.5); }

/* Badge dipanggil ulang */
.repeat-badge {
    display:inline-flex; align-items:center; gap:.3vw;
    margin-top:.8vh; padding:.3vh .8vw;
    background:rgba(245,158,11,.15); border:1px solid rgba(245,158,11,.35);
    border-radius:2vw; font-size:.75vw; font-weight:700; color:#d97706;
}

/* Info banner bawah (waktu tunggu racikan) */
.info-banner {
    background:linear-gradient(135deg, rgba(0,212,170,.08), rgba(0,136,255,.06));
    border:1px solid rgba(0,212,170,.2);
    border-radius:1vw; padding:1.2vh 2vw;
    display:flex; align-items:flex-start; gap:1.2vw;
    flex-shrink:0;
}
.info-banner i { font-size:1.5vw; color:var(--primary); flex-shrink:0; margin-top:.2vh; }
.info-banner-text { flex:1; }
.info-banner-title { font-size:.88vw; font-weight:800; color:#0a1929; margin-bottom:.3vh; }
.info-banner-desc  { font-size:.8vw; color:rgba(0,0,0,.6); line-height:1.6; }
.info-banner-desc strong { color:var(--dark); font-weight:700; }

/* ===== FOOTER — identik dengan display_poli ===== */
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
    animation:mqScroll 60s linear infinite;
}
@keyframes mqScroll { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
.mq-item { display:inline-flex; align-items:center; gap:.6vw; padding:0 3vw 0 0; }
.mq-item i { color:var(--primary); }
.mq-label { color:rgba(255,255,255,.5); font-weight:600; }
.mq-val   { color:#fff; font-weight:800; }
.mq-val.nr { color:var(--nr-color); }
.mq-val.rc { color:var(--rc-color); }
.mq-sep   { color:rgba(255,255,255,.15); margin:0 .3vw; }

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
        <div class="brand-icon"><i class="bi bi-capsule-pill"></i></div>
        <div class="brand-text">
            <h1>Display Antrian Farmasi <span class="sync-dot" id="syncDot"></span></h1>
            <p>Instalasi Farmasi &mdash; RS Permata Hati</p>
        </div>
    </div>

    <!-- Stat: Non Racikan & Racikan -->
    <div class="header-stats">
        <div class="header-stat-item">
            <div class="header-stat-icon nr"><i class="bi bi-prescription2"></i></div>
            <div>
                <div class="header-stat-label">Non Racikan</div>
                <div class="header-stat-value" id="statNR"><?= $nr ? htmlspecialchars($nr['no_antrian']) : '&mdash;' ?></div>
            </div>
        </div>
        <div class="header-stat-item">
            <div class="header-stat-icon rc"><i class="bi bi-capsule"></i></div>
            <div>
                <div class="header-stat-label">Racikan</div>
                <div class="header-stat-value" id="statRC"><?= $r ? htmlspecialchars($r['no_antrian']) : '&mdash;' ?></div>
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

    <div class="farmasi-grid">

        <!-- ========== NON RACIKAN ========== -->
        <div class="farmasi-card <?= $nr ? 'has-call' : '' ?>" id="cardNR">
            <div class="fcard-head">
                <div class="fcard-icon nr"><i class="bi bi-prescription2"></i></div>
                <div class="fcard-head-text">
                    <div class="fcard-title">Non Racikan</div>
                    <div class="fcard-subtitle">Resep jadi / obat paten</div>
                </div>
            </div>
            <div class="fcard-body">
                <div class="call-label"><i class="bi bi-megaphone-fill"></i>&nbsp; Sedang Dipanggil</div>
                <?php if ($nr): ?>
                <div class="call-number active" id="nrNumber"><?= htmlspecialchars($nr['no_antrian']) ?></div>
                <div class="call-info" id="nrInfo">
                    <div class="ci-row nr-icon">
                        <i class="bi bi-person-fill"></i>
                        <div>
                            <div class="ci-name"><?= htmlspecialchars($nr['nm_pasien']) ?></div>
                            <div class="ci-poli"><?= htmlspecialchars($nr['nm_poli'] ?: 'Instalasi Farmasi') ?></div>
                        </div>
                    </div>
                    <?php if ($nr['jml_panggil'] > 1): ?>
                    <div class="ci-row nr-icon">
                        <i class="bi bi-arrow-repeat"></i>
                        <span class="repeat-badge">Dipanggil <?= $nr['jml_panggil'] ?>x</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="call-number empty" id="nrNumber">&mdash;</div>
                <div class="call-info" id="nrInfo">
                    <div class="ci-row empty-row">
                        <i class="bi bi-hourglass-split" style="color:rgba(0,0,0,.2)"></i>
                        Menunggu Panggilan...
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ========== RACIKAN ========== -->
        <div class="farmasi-card racikan <?= $r ? 'has-call' : '' ?>" id="cardRC">
            <div class="fcard-head">
                <div class="fcard-icon rc"><i class="bi bi-capsule"></i></div>
                <div class="fcard-head-text">
                    <div class="fcard-title">Racikan</div>
                    <div class="fcard-subtitle">Resep diracik / puyer</div>
                </div>
            </div>
            <div class="fcard-body">
                <div class="call-label"><i class="bi bi-megaphone-fill"></i>&nbsp; Sedang Dipanggil</div>
                <?php if ($r): ?>
                <div class="call-number racikan active" id="rNumber"><?= htmlspecialchars($r['no_antrian']) ?></div>
                <div class="call-info" id="rInfo">
                    <div class="ci-row rc-icon">
                        <i class="bi bi-person-fill"></i>
                        <div>
                            <div class="ci-name"><?= htmlspecialchars($r['nm_pasien']) ?></div>
                            <div class="ci-poli"><?= htmlspecialchars($r['nm_poli'] ?: 'Instalasi Farmasi') ?></div>
                        </div>
                    </div>
                    <?php if ($r['jml_panggil'] > 1): ?>
                    <div class="ci-row rc-icon">
                        <i class="bi bi-arrow-repeat"></i>
                        <span class="repeat-badge" style="color:#d97706;border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.15)">
                            Dipanggil <?= $r['jml_panggil'] ?>x
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="call-number racikan empty" id="rNumber">&mdash;</div>
                <div class="call-info" id="rInfo">
                    <div class="ci-row empty-row">
                        <i class="bi bi-hourglass-split" style="color:rgba(0,0,0,.2)"></i>
                        Menunggu Panggilan...
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /farmasi-grid -->

    <!-- Info banner waktu tunggu -->
    <div class="info-banner">
        <i class="bi bi-info-circle-fill"></i>
        <div class="info-banner-text">
            <div class="info-banner-title">Informasi Waktu Tunggu Resep Racikan</div>
            <div class="info-banner-desc">
                Sesuai <strong>Permenkes No. 72 Tahun 2016</strong>, obat racikan memerlukan proses tambahan
                (penimbangan, peracikan, pelabelan &amp; validasi apoteker).
                Estimasi waktu pelayanan: <strong>± 15 – 60 menit</strong>.
                Terima kasih atas kesabaran Anda 🙏
            </div>
        </div>
    </div>

</div><!-- /main-content -->

<!-- ===== FOOTER — seragam dengan display_poli ===== -->
<div class="footer">
    <div class="marquee-row">
        <div class="marquee-content" id="marqueeContent">
            <?php
            $mq = '';
            $items = [
                ['icon'=>'bi-heart-pulse-fill', 'text'=>'Selamat datang di RS Permata Hati — Layanan Farmasi siap melayani Anda'],
                ['icon'=>'bi-prescription2',    'text'=>'Mohon perhatikan nomor antrian Non Racikan yang ditampilkan'],
                ['icon'=>'bi-capsule',           'text'=>'Resep Racikan memerlukan waktu ± 15–60 menit — Terima kasih atas kesabaran Anda'],
                ['icon'=>'bi-bell-fill',         'text'=>'Harap berada di area farmasi saat nomor antrian Anda dipanggil'],
                ['icon'=>'bi-shield-check-fill', 'text'=>'Pastikan resep dan identitas Anda sudah lengkap sebelum menyerahkan resep'],
                ['icon'=>'bi-chat-heart-fill',   'text'=>'RS Permata Hati mengutamakan keselamatan dan kenyamanan pasien'],
            ];
            foreach ($items as $it) {
                $mq .= "<span class='mq-item'><i class='bi {$it['icon']}'></i><span>{$it['text']}</span></span>";
            }
            echo $mq . $mq; // duplikat untuk loop seamless
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
/* ===== CLOCK ===== */
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
var tsNR = <?= $nr ? (int)$nr['ts'] : 0 ?>;
var tsR  = <?= $r  ? (int)$r['ts']  : 0 ?>;

/* Preload suara bel */
var bellAudio = new Audio('sound/opening.mp3');
bellAudio.preload = 'auto';

/* ===== RENDER KARTU ===== */
function renderCard(type, d){
    var isRC    = (type === 'r');
    var numEl   = document.getElementById(isRC ? 'rNumber'  : 'nrNumber');
    var infoEl  = document.getElementById(isRC ? 'rInfo'    : 'nrInfo');
    var cardEl  = document.getElementById(isRC ? 'cardRC'   : 'cardNR');
    var statEl  = document.getElementById(isRC ? 'statRC'   : 'statNR');
    var rcCls   = isRC ? ' racikan' : '';
    var iconCls = isRC ? 'rc-icon'  : 'nr-icon';
    var iconClr = isRC ? '#d97706'  : 'var(--nr-color)';

    /* Fade out */
    numEl.style.opacity   = '0';
    numEl.style.transform = 'scale(.92)';

    setTimeout(function(){
        if(d.has_data){
            numEl.className   = 'call-number'+rcCls+' active';
            numEl.textContent = d.no_antrian;

            var badge = d.jml_panggil > 1
                ? '<div class="ci-row '+iconCls+'"><i class="bi bi-arrow-repeat" style="color:'+iconClr+'"></i>'
                  +'<span class="repeat-badge" style="color:'+iconClr+'">Dipanggil '+d.jml_panggil+'x</span></div>'
                : '';
            infoEl.innerHTML =
                '<div class="ci-row '+iconCls+'">'
                +'<i class="bi bi-person-fill"></i>'
                +'<div>'
                +'<div class="ci-name">'+d.nm_pasien+'</div>'
                +'<div class="ci-poli">'+(d.nm_poli||'Instalasi Farmasi')+'</div>'
                +'</div></div>'+badge;

            cardEl.classList.add('has-call');
            if(statEl) statEl.textContent = d.no_antrian;
        } else {
            numEl.className   = 'call-number'+rcCls+' empty';
            numEl.textContent = '\u2014';
            infoEl.innerHTML  = '<div class="ci-row empty-row">'
                +'<i class="bi bi-hourglass-split" style="color:rgba(0,0,0,.2)"></i>'
                +'Menunggu Panggilan...</div>';
            cardEl.classList.remove('has-call');
            if(statEl) statEl.textContent = '\u2014';
        }

        /* Fade in */
        numEl.style.opacity   = '1';
        numEl.style.transform = 'scale(1)';
    }, 280);
}

/* ===== POLLING ===== */
function pollFarmasi(){
    fetch('get_current_call_farmasi.php?since_nr='+tsNR+'&since_r='+tsR)
    .then(function(r){ return r.json(); })
    .then(function(data){
        document.getElementById('syncDot').style.background = '#10b981';
        if(!data.changed) return;

        var hasNew = false;

        if(data.ts_nr > tsNR){
            tsNR = data.ts_nr;
            renderCard('nr', data.non_racikan);
            hasNew = true;
        }
        if(data.ts_r > tsR){
            tsR = data.ts_r;
            renderCard('r', data.racikan);
            hasNew = true;
        }

        if(hasNew){
            bellAudio.currentTime = 0;
            bellAudio.play().catch(function(){});
        }
    })
    .catch(function(){
        document.getElementById('syncDot').style.background = '#ef4444';
    });
}

var pollInterval = setInterval(pollFarmasi, 2000);

/* Pause saat tab tidak aktif */
document.addEventListener('visibilitychange', function(){
    if(document.hidden){
        clearInterval(pollInterval);
    } else {
        pollFarmasi();
        pollInterval = setInterval(pollFarmasi, 2000);
    }
});
/* Tidak ada location.reload() — tidak berkedip */
</script>
</body>
</html>
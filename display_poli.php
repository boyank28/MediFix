<?php
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

$today  = date('Y-m-d');
$poli   = $_GET['poli']   ?? '';
$dokter = $_GET['dokter'] ?? '';

// === POLI YANG DISEMBUNYIKAN ===
$excluded_poli = ['IGDK','PL013','PL014','PL015','PL016','PL017','U0022','U0030'];
$excluded_list = "'" . implode("','", $excluded_poli) . "'";

try {
    $sqlDokter = "
        SELECT DISTINCT
            r.kd_dokter, r.kd_poli,
            d.nm_dokter, p.nm_poli,
            NULL AS jam_mulai, NULL AS jam_selesai
        FROM reg_periksa r
        LEFT JOIN dokter     d ON r.kd_dokter = d.kd_dokter
        LEFT JOIN poliklinik p ON r.kd_poli   = p.kd_poli
        WHERE r.tgl_registrasi = ?
          AND r.kd_poli NOT IN ($excluded_list)
    ";
    $paramsDokter = [$today];
    if (!empty($poli))   { $sqlDokter .= " AND r.kd_poli = ?";   $paramsDokter[] = $poli; }
    if (!empty($dokter)) { $sqlDokter .= " AND r.kd_dokter = ?"; $paramsDokter[] = $dokter; }
    $sqlDokter .= " ORDER BY p.nm_poli, d.nm_dokter";

    $stmtDokter = $pdo_simrs->prepare($sqlDokter);
    $stmtDokter->execute($paramsDokter);
    $daftar_dokter = $stmtDokter->fetchAll(PDO::FETCH_ASSOC);

    $dokter_data = [];
    foreach ($daftar_dokter as $dok) {
        $kd_d = $dok['kd_dokter'];
        $kd_p = $dok['kd_poli'];

        $sqlQ = "
            SELECT r.no_reg, r.no_rawat, r.kd_poli, r.stts, ps.nm_pasien
            FROM reg_periksa r
            LEFT JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
            WHERE r.tgl_registrasi = ? AND r.kd_dokter = ? AND r.kd_poli = ?
            ORDER BY r.no_reg+0 ASC
        ";
        $stmtQ = $pdo_simrs->prepare($sqlQ);
        $stmtQ->execute([$today, $kd_d, $kd_p]);
        $pasien_list = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

        $sqlCall = "
            SELECT no_antrian, nm_pasien, no_rawat, jml_panggil
            FROM simpan_antrian_poli_wira
            WHERE tgl_panggil = ? AND kd_dokter = ? AND kd_poli = ?
            ORDER BY updated_at DESC LIMIT 1
        ";
        $stmtCall = $pdo_simrs->prepare($sqlCall);
        $stmtCall->execute([$today, $kd_d, $kd_p]);
        $current_call = $stmtCall->fetch(PDO::FETCH_ASSOC) ?: null;

        $total    = count($pasien_list);
        $sudah    = count(array_filter($pasien_list, fn($p) => $p['stts'] === 'Sudah'));
        $menunggu = count(array_filter($pasien_list, fn($p) => in_array($p['stts'], ['Menunggu','Belum'])));

        $dokter_data[] = [
            'kd_dokter'    => $kd_d,
            'kd_poli'      => $kd_p,
            'nm_dokter'    => $dok['nm_dokter'],
            'nm_poli'      => $dok['nm_poli'],
            'jam_mulai'    => $dok['jam_mulai'],
            'jam_selesai'  => $dok['jam_selesai'],
            'pasien'       => $pasien_list,
            'current_call' => $current_call,
            'total'        => $total,
            'sudah'        => $sudah,
            'menunggu'     => $menunggu,
        ];
    }

    $globalTotal    = array_sum(array_column($dokter_data, 'total'));
    $globalSudah    = array_sum(array_column($dokter_data, 'sudah'));
    $globalMenunggu = array_sum(array_column($dokter_data, 'menunggu'));

} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Display Antrian Poliklinik</title>
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
    --shadow:   rgba(10,25,41,0.10);
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
    background:radial-gradient(circle,rgba(0,212,170,.13) 0%,transparent 70%);
    border-radius:50%; animation:bgPulse 18s ease-in-out infinite; pointer-events:none;
}
body::after {
    content:''; position:absolute; bottom:-30%; left:-15%;
    width:60%; height:60%;
    background:radial-gradient(circle,rgba(0,136,255,.10) 0%,transparent 70%);
    border-radius:50%; animation:bgPulse 22s ease-in-out infinite reverse; pointer-events:none;
}
@keyframes bgPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.12);opacity:.7}}

/* ===== HEADER ===== */
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
.header-stat-icon.total   { background:linear-gradient(135deg,var(--secondary),#0066cc); }
.header-stat-icon.avail   { background:linear-gradient(135deg,var(--success),#00c853); }
.header-stat-icon.occ     { background:linear-gradient(135deg,var(--danger),#d32f2f); }
.header-stat-icon.waiting { background:linear-gradient(135deg,var(--warning),#e09000); }
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
    opacity:.75; margin-bottom:.35vw;
    letter-spacing:.05em;
}
.live-date { font-size:.9vw; color:rgba(255,255,255,.8); font-weight:600; margin-top:.3vh; }

/* ===== MAIN ===== */
.main-content {
    position:relative; z-index:1;
    padding:1.5vh 2.5vw 0;
    height:calc(100vh - 11vh - 10vh - 3.5vh);
    overflow:hidden;
}
.page-wrapper { height:100%; position:relative; }

/* Opacity-based paging — TIDAK pakai display:none agar tidak kedip */
.page-slide {
    position:absolute; inset:0; height:100%;
    display:flex; flex-direction:column;
    opacity:0; visibility:hidden;
    transition:opacity .7s ease, visibility .7s ease;
    pointer-events:none;
}
.page-slide.active {
    opacity:1; visibility:visible; pointer-events:auto; position:relative;
}

.dokter-grid {
    display:grid; gap:1.4vw;
    width:100%; height:100%;
    grid-template-columns:repeat(var(--cols,3),1fr);
    grid-auto-rows:1fr;
}

/* ===== KARTU DOKTER ===== */
.dokter-card {
    background:var(--card-bg); border-radius:1.2vw; overflow:hidden;
    display:flex; flex-direction:column;
    box-shadow:0 4px 20px var(--shadow);
    border:2px solid transparent;
    height:100%; min-height:0;
}
.dokter-card.has-call {
    border-color:var(--warning);
    animation:cardGlow 2s ease-in-out infinite;
}
@keyframes cardGlow{
    0%,100%{box-shadow:0 4px 20px var(--shadow)}
    50%{box-shadow:0 6px 35px rgba(251,191,36,.5)}
}

.card-head {
    padding:1.2vh 1.4vw 1vh;
    background:linear-gradient(135deg,#0a1929,#132f4c);
    display:flex; align-items:flex-start; gap:.9vw; flex-shrink:0;
}
.dokter-avatar {
    width:3.2vw; height:3.2vw; min-width:40px; min-height:40px; max-width:54px; max-height:54px;
    background:linear-gradient(135deg,var(--primary),#0088ff);
    border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.dokter-avatar i { font-size:1.6vw; color:#fff; }
.card-head-info { flex:1; min-width:0; }
.nm-dokter {
    font-family:'Archivo Black',sans-serif;
    font-size:1.15vw; color:#fff; line-height:1.2;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.nm-poli { font-size:.88vw; color:var(--primary); font-weight:600; margin-top:.3vh; }

.now-serving {
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    padding:1vh 1.4vw; display:flex; align-items:center; gap:1.4vw; flex-shrink:0;
}
.ns-left { display:flex; flex-direction:column; flex-shrink:0; }
.ns-label { font-size:.78vw; font-weight:700; color:#78350f; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.1vh; }
.ns-number {
    font-family:'Archivo Black',sans-serif; font-size:2.8vw; color:#1c1400; line-height:1;
    animation:numPulse 2s ease-in-out infinite;
}
@keyframes numPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
.ns-right { flex:1; min-width:0; }
.ns-name { font-size:1.1vw; font-weight:800; color:#78350f; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ns-info { font-size:.78vw; color:#92400e; margin-top:.2vh; }
.ns-empty {
    background:rgba(10,25,41,.06); padding:.9vh 1.4vw;
    display:flex; align-items:center; gap:.5vw;
    font-size:.85vw; color:rgba(0,0,0,.4); font-weight:600; flex-shrink:0;
}

.card-stats { display:flex; border-bottom:1px solid rgba(0,0,0,.07); flex-shrink:0; }
.cs-item { flex:1; text-align:center; padding:.8vh .5vw; border-right:1px solid rgba(0,0,0,.07); }
.cs-item:last-child { border-right:none; }
.cs-val { font-family:'Archivo Black',sans-serif; font-size:1.6vw; line-height:1; }
.cs-val.total  { color:#0088ff; }
.cs-val.sudah  { color:#00c853; }
.cs-val.tunggu { color:#ff5252; }
.cs-label { font-size:.7vw; font-weight:700; color:rgba(0,0,0,.45); text-transform:uppercase; margin-top:.3vh; }

/* Daftar pasien — auto-scroll via RAF */
.pasien-list { flex:1; overflow:hidden; padding:.3vh 0; min-height:0; position:relative; }
.pasien-inner { display:flex; flex-direction:column; }

.pasien-row {
    display:grid; grid-template-columns:5.8vw 1fr 5.5vw;
    gap:.6vw; align-items:center; padding:.7vh 1.2vw;
    border-bottom:1px solid rgba(0,0,0,.05);
}
.pasien-row:last-child { border-bottom:none; }
.pasien-row.active { background:rgba(251,191,36,.18); }
.pasien-row.done   { opacity:.45; background:rgba(0,200,83,.06); }
.pr-no { font-family:'Archivo Black',sans-serif; font-size:.85vw; color:var(--dark); white-space:nowrap; }
.pr-no.active { color:#b45309; }
.pr-nama { font-size:.88vw; font-weight:600; color:#1a2e44; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pr-status { font-size:.75vw; font-weight:700; text-align:right; text-transform:uppercase; letter-spacing:.03em; }
.pr-status.waiting { color:#ff5252; }
.pr-status.done    { color:#00c853; }
.pr-status.active  { color:#d97706; }

/* Fade bawah — isyarat masih ada data */
.pasien-list::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:2.5vh;
    background:linear-gradient(to bottom,transparent,rgba(255,255,255,.95));
    pointer-events:none; z-index:2;
}
.pasien-list.no-overflow::after { display:none; }
.no-pasien { text-align:center; padding:2vh 1vw; color:rgba(0,0,0,.35); font-size:.92vw; font-weight:600; }

/* ===== PAGE DOTS ===== */
.page-dots {
    position:fixed; bottom:11vh; left:50%; transform:translateX(-50%);
    display:flex; gap:.6vw; z-index:20; height:3vh; align-items:center;
}
.page-dot {
    width:.7vw; height:.7vw; min-width:8px; min-height:8px;
    border-radius:50%; background:rgba(255,255,255,.35);
    transition:background .3s, transform .3s;
}
.page-dot.active { background:var(--primary); transform:scale(1.4); }

/* ===== FOOTER 2 BARIS ===== */
.footer {
    position:fixed; bottom:0; left:0; right:0; z-index:10;
    background:rgba(10,25,41,.97);
    backdrop-filter:blur(20px);
    border-top:3px solid var(--primary);
    overflow:hidden;
    box-shadow:0 -4px 30px rgba(0,212,170,.2);
}

/* Baris 1 — ringkasan dokter */
.marquee-row-1 {
    border-bottom:1px solid rgba(255,255,255,.08);
    padding:.55vh 0; overflow:hidden;
}
/* Baris 2 — copyright */
.footer-copy {
    border-top:1px solid rgba(255,255,255,.06);
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
    font-size:.68vw; color:rgba(255,255,255,.35); font-weight:500;
    letter-spacing:.03em;
}
.footer-copy-right span { color:var(--primary); font-weight:700; }

/* Marquee konten */
.marquee-content {
    display:inline-flex; white-space:nowrap;
    font-size:.95vw; font-weight:600; color:#fff;
}
.marquee-content.row1 { animation:mq1 140s linear infinite; }
@keyframes mq1{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}

/* Baris 1 — item per dokter */
.mq-item { display:inline-flex; align-items:center; gap:.5vw; padding:0 2vw 0 0; }
.mq-item i { color:var(--primary); font-size:.85vw; }
.mq-dokter  { color:#fff; font-weight:800; }
.mq-poli    { color:var(--primary); font-weight:600; }
.mq-num     { color:var(--warning); font-family:'Archivo Black',sans-serif; }
.mq-tunggu  { color:#ff5252; font-weight:800; }
.mq-selesai { color:var(--success); font-weight:800; }
.mq-sep     { color:rgba(255,255,255,.2); margin:0 .15vw; }

/* sync dot */
.sync-dot {
    width:9px; height:9px; border-radius:50%;
    background:#10b981; display:inline-block; margin-left:8px;
    animation:syncBlink 2s ease-in-out infinite; vertical-align:middle;
}
@keyframes syncBlink{0%,100%{opacity:1}50%{opacity:.3}}
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<div class="header">
    <div class="brand-section">
        <div class="brand-icon"><i class="bi bi-display-fill"></i></div>
        <div class="brand-text">
            <h1>Display Antrian Poliklinik <span class="sync-dot" id="syncDot"></span></h1>
            <p>
                <?php if (!empty($poli) && !empty($dokter_data)): ?>
                    <?= htmlspecialchars($dokter_data[0]['nm_poli'] ?? '') ?>
                <?php else: ?>
                    Semua Poliklinik &mdash; RS Permata Hati
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="header-stats">
        <div class="header-stat-item">
            <div class="header-stat-icon total"><i class="bi bi-person-lines-fill"></i></div>
            <div>
                <div class="header-stat-label">Total Pasien</div>
                <div class="header-stat-value" id="globalTotal"><?= $globalTotal ?></div>
            </div>
        </div>
        <div class="header-stat-item">
            <div class="header-stat-icon avail"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="header-stat-label">Selesai</div>
                <div class="header-stat-value" id="globalSudah"><?= $globalSudah ?></div>
            </div>
        </div>
        <div class="header-stat-item">
            <div class="header-stat-icon waiting"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="header-stat-label">Menunggu</div>
                <div class="header-stat-value" id="globalMenunggu"><?= $globalMenunggu ?></div>
            </div>
        </div>
        <div class="header-stat-item">
            <div class="header-stat-icon occ"><i class="bi bi-person-badge-fill"></i></div>
            <div>
                <div class="header-stat-label">Dokter Aktif</div>
                <div class="header-stat-value"><?= count($dokter_data) ?></div>
            </div>
        </div>
    </div>

    <!-- Jam + WIB -->
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
    <div class="page-wrapper" id="pageWrapper">
        <?php
        $jml = count($dokter_data);
        if      ($jml <= 1) $cols = 1;
        elseif  ($jml <= 2) $cols = 2;
        else                $cols = 3;

        $per_page = 6;
        $pages    = array_chunk($dokter_data, $per_page);
        ?>

        <?php foreach ($pages as $pi => $page_dokter): ?>
        <div class="page-slide <?= $pi === 0 ? 'active' : '' ?>" id="slide-<?= $pi ?>">
            <div class="dokter-grid" style="--cols:<?= $cols ?>">
            <?php foreach ($page_dokter as $dok):
                $cc       = $dok['current_call'];
                $has_call = !empty($cc);
            ?>
                <div class="dokter-card <?= $has_call ? 'has-call' : '' ?>"
                     data-kd-dokter="<?= htmlspecialchars($dok['kd_dokter']) ?>"
                     data-kd-poli="<?= htmlspecialchars($dok['kd_poli']) ?>">

                    <div class="card-head">
                        <div class="dokter-avatar"><i class="bi bi-person-badge-fill"></i></div>
                        <div class="card-head-info">
                            <div class="nm-dokter"><?= htmlspecialchars($dok['nm_dokter']) ?></div>
                            <div class="nm-poli"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($dok['nm_poli']) ?></div>
                        </div>
                    </div>

                    <?php if ($has_call): ?>
                    <div class="now-serving">
                        <div class="ns-left">
                            <div class="ns-label"><i class="bi bi-megaphone-fill"></i> Sedang Dilayani</div>
                            <div class="ns-number"><?= htmlspecialchars($cc['no_antrian']) ?></div>
                        </div>
                        <div class="ns-right">
                            <div class="ns-name"><?= htmlspecialchars($cc['nm_pasien']) ?></div>
                            <?php if ($cc['jml_panggil'] > 1): ?>
                            <div class="ns-info"><i class="bi bi-arrow-repeat"></i> Dipanggil <?= $cc['jml_panggil'] ?>x</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="ns-empty"><i class="bi bi-hourglass-split"></i> Menunggu Panggilan...</div>
                    <?php endif; ?>

                    <div class="card-stats">
                        <div class="cs-item"><div class="cs-val total"><?= $dok['total'] ?></div><div class="cs-label">Total</div></div>
                        <div class="cs-item"><div class="cs-val tunggu"><?= $dok['menunggu'] ?></div><div class="cs-label">Menunggu</div></div>
                        <div class="cs-item"><div class="cs-val sudah"><?= $dok['sudah'] ?></div><div class="cs-label">Selesai</div></div>
                    </div>

                    <?php if (empty($dok['pasien'])): ?>
                    <div class="pasien-list no-overflow">
                        <div class="pasien-inner">
                            <div class="no-pasien"><i class="bi bi-inbox"></i> Belum ada pasien</div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="pasien-list">
                        <div class="pasien-inner">
                        <?php foreach ($dok['pasien'] as $ps):
                            $no_ant   = $dok['kd_poli'].'-'.str_pad($ps['no_reg'],2,'0',STR_PAD_LEFT);
                            $is_active= $cc && $cc['no_rawat'] === $ps['no_rawat'];
                            $is_done  = $ps['stts'] === 'Sudah';
                            $rowCls   = $is_active ? 'active' : ($is_done ? 'done' : '');
                            $stLabel  = $is_active ? 'Dipanggil' : ($is_done ? 'Selesai' : 'Menunggu');
                            $stCls    = $is_active ? 'active' : ($is_done ? 'done' : 'waiting');
                        ?>
                        <div class="pasien-row <?= $rowCls ?>" data-no-rawat="<?= htmlspecialchars($ps['no_rawat']) ?>">
                            <div class="pr-no <?= $is_active ? 'active' : '' ?>"><?= htmlspecialchars($no_ant) ?></div>
                            <div class="pr-nama"><?= htmlspecialchars($ps['nm_pasien']) ?></div>
                            <div class="pr-status <?= $stCls ?>"><?= $stLabel ?></div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Page Dots -->
<div class="page-dots" id="pageDots">
    <?php foreach ($pages as $pi => $_): ?>
    <div class="page-dot <?= $pi === 0 ? 'active' : '' ?>"></div>
    <?php endforeach; ?>
</div>

<!-- ===== FOOTER 2 BARIS + COPYRIGHT ===== -->
<div class="footer">

    <!-- Baris 1: Ringkasan per dokter -->
    <div class="marquee-row-1">
        <div class="marquee-content row1" id="marqueeRow1">
            <?php
            $r1 = '';
            foreach ($dokter_data as $dok) {
                $calling = $dok['current_call']
                    ? '<span class="mq-num">&#9654; No.'.htmlspecialchars($dok['current_call']['no_antrian']).'</span>'
                      .' <span style="color:rgba(255,255,255,.85);font-weight:600">'.htmlspecialchars($dok['current_call']['nm_pasien']).'</span>'
                    : '<span style="color:rgba(255,255,255,.3)">Menunggu&hellip;</span>';
                $r1 .= "<span class='mq-item'>"
                    ."<i class='bi bi-hospital-fill'></i>"
                    ."<span class='mq-dokter'>".htmlspecialchars($dok['nm_dokter'])."</span>"
                    ."<span class='mq-sep'>|</span>"
                    ."<span class='mq-poli'>".htmlspecialchars($dok['nm_poli'])."</span>"
                    ."<span class='mq-sep'>&middot;</span>"
                    ."Dilayani: $calling"
                    ."<span class='mq-sep'>&middot;</span>"
                    ."Menunggu: <span class='mq-tunggu'>{$dok['menunggu']}</span>"
                    ."<span class='mq-sep'>&middot;</span>"
                    ."Selesai: <span class='mq-selesai'>{$dok['sudah']}</span>"
                    ."<span class='mq-sep' style='margin-left:.8vw;opacity:.3'>&boxv;&boxv;</span>"
                    ."</span>";
            }
            echo $r1.$r1;
            ?>
        </div>
    </div>

    <!-- Baris 2: Copyright -->
    <div class="footer-copy">
        <div class="footer-copy-left">
            <i class="bi bi-shield-check-fill"></i>
            &copy; <?= date('Y') ?> <span style="color:var(--primary);font-weight:700">MediFix</span>
            &nbsp;&mdash;&nbsp;
            Anjungan Pasien Mandiri &amp; Sistem Antrian
            &nbsp;&nbsp;<span style="color:rgba(255,255,255,.2)">|</span>&nbsp;&nbsp;
            <i class="bi bi-person-fill"></i>
            <span style="color:var(--primary);font-weight:700">M. Wira Satria Buana</span>
        </div>
        <div class="footer-copy-right">
            Powered by <span>MediFix</span>
            &nbsp;&middot;&nbsp; v1.0
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

/* ===== PAGING — opacity, tidak kedip ===== */
var slides    = document.querySelectorAll('.page-slide');
var dots      = document.querySelectorAll('.page-dot');
var curPage   = 0;
var pageTimer = null;
var POLI      = '<?= addslashes($poli) ?>';
var DOKTER    = '<?= addslashes($dokter) ?>';

function calcPageDuration(slideEl){
    var maxRows = 0;
    slideEl.querySelectorAll('.pasien-list').forEach(function(list){
        var n = list.querySelectorAll('.pasien-row').length;
        if(n > maxRows) maxRows = n;
    });
    /* min 10 dtk, +0.7 dtk per baris, max 30 dtk */
    return Math.min(30000, Math.max(10000, maxRows * 700 + 6000));
}

function showSlide(idx){
    slides.forEach(function(s){ s.classList.remove('active'); });
    dots.forEach(function(d){   d.classList.remove('active'); });
    slides[idx].classList.add('active');
    if(dots[idx]) dots[idx].classList.add('active');
    curPage = idx;
    if(slides.length > 1){
        if(pageTimer) clearTimeout(pageTimer);
        pageTimer = setTimeout(function(){ showSlide((curPage+1) % slides.length); },
                               calcPageDuration(slides[idx]));
    }
}
if(slides.length > 1) showSlide(0);

/* ===== AUTO-SCROLL PASIEN — requestAnimationFrame ===== */
var SPEED_DN     = 22;
var SPEED_UP     = 250;
var PAUSE_TOP_MS = 3000;
var PAUSE_BOT_MS = 2500;
var scrollStates = [];

function buildScrollStates(){
    var oldMap = {};
    scrollStates.forEach(function(st){
        if(st.list.dataset.scrollKey)
            oldMap[st.list.dataset.scrollKey] = {pos:st.pos, phase:st.phase, pauseEnd:st.pauseEnd};
    });
    scrollStates = [];

    document.querySelectorAll('.pasien-list').forEach(function(list){
        var inner = list.querySelector('.pasien-inner');
        if(!inner) return;
        inner.style.animation = inner.style.transition = 'none';

        var maxScroll = Math.max(0, inner.scrollHeight - list.clientHeight);
        if(maxScroll <= 0){ list.classList.add('no-overflow'); inner.style.transform='translateY(0)'; return; }
        list.classList.remove('no-overflow');

        var card = list.closest('.dokter-card');
        var key  = card ? card.dataset.kdDokter+'_'+card.dataset.kdPoli : Math.random();
        list.dataset.scrollKey = key;
        var old  = oldMap[key];

        scrollStates.push({
            list:list, inner:inner, maxScroll:maxScroll,
            pos      : old ? Math.min(old.pos, maxScroll) : 0,
            phase    : old ? old.phase : 'PAUSE_TOP',
            pauseEnd : old ? old.pauseEnd : performance.now()+PAUSE_TOP_MS,
            lastTime : performance.now()
        });
    });
}

function rafLoop(now){
    scrollStates.forEach(function(st){
        var dt = Math.min((now-st.lastTime)/1000, 0.1);
        st.lastTime = now;
        switch(st.phase){
            case 'PAUSE_TOP':
                if(now>=st.pauseEnd) st.phase='SCROLL_DN'; break;
            case 'SCROLL_DN':
                st.pos += SPEED_DN*dt;
                if(st.pos>=st.maxScroll){ st.pos=st.maxScroll; st.phase='PAUSE_BOT'; st.pauseEnd=now+PAUSE_BOT_MS; }
                st.inner.style.transform='translateY(-'+st.pos.toFixed(1)+'px)'; break;
            case 'PAUSE_BOT':
                if(now>=st.pauseEnd) st.phase='SCROLL_UP'; break;
            case 'SCROLL_UP':
                st.pos -= SPEED_UP*dt;
                if(st.pos<=0){ st.pos=0; st.phase='PAUSE_TOP'; st.pauseEnd=now+PAUSE_TOP_MS; }
                st.inner.style.transform='translateY(-'+st.pos.toFixed(1)+'px)'; break;
        }
    });
    requestAnimationFrame(rafLoop);
}

window.addEventListener('load', function(){
    setTimeout(function(){ buildScrollStates(); requestAnimationFrame(rafLoop); }, 500);
});
window.addEventListener('resize', function(){ setTimeout(buildScrollStates,200); });

/* ===== POLLING LIVE — soft update tanpa reload ===== */
function pollCalls(){
    fetch('get_all_calls.php?poli='+encodeURIComponent(POLI)+'&dokter='+encodeURIComponent(DOKTER))
    .then(function(r){ return r.json(); })
    .then(function(data){
        document.getElementById('syncDot').style.background='#10b981';
        var tot=0, sdh=0, tgu=0;
        var r1Parts=[];

        data.forEach(function(item){
            tot+=(item.total||0); sdh+=(item.sudah||0); tgu+=(item.menunggu||0);

            var card=document.querySelector(
                '.dokter-card[data-kd-dokter="'+item.kd_dokter+'"][data-kd-poli="'+item.kd_poli+'"]');
            if(!card) return;

            card.classList.toggle('has-call',!!item.no_antrian);

            var nsEl=card.querySelector('.now-serving,.ns-empty');
            if(nsEl){
                if(item.no_antrian){
                    nsEl.outerHTML='<div class="now-serving">'
                        +'<div class="ns-left">'
                            +'<div class="ns-label"><i class="bi bi-megaphone-fill"></i> Sedang Dilayani</div>'
                            +'<div class="ns-number">'+item.no_antrian+'</div>'
                        +'</div>'
                        +'<div class="ns-right">'
                            +'<div class="ns-name">'+(item.nm_pasien||'')+'</div>'
                        +'</div>'
                    +'</div>';
                } else {
                    nsEl.outerHTML='<div class="ns-empty"><i class="bi bi-hourglass-split"></i> Menunggu Panggilan...</div>';
                }
            }

            var vals=card.querySelectorAll('.cs-val');
            if(vals.length>=3){ vals[0].textContent=item.total||0; vals[1].textContent=item.menunggu||0; vals[2].textContent=item.sudah||0; }

            card.querySelectorAll('.pasien-row').forEach(function(row){
                row.classList.toggle('active',!!(item.no_rawat && row.dataset.noRawat===item.no_rawat));
            });

            /* Baris 1 marquee */
            var calling = item.no_antrian
                ? '<span class="mq-num">&#9654; No.'+item.no_antrian+'</span> <span style="color:rgba(255,255,255,.85);font-weight:600">'+(item.nm_pasien||'')+'</span>'
                : '<span style="color:rgba(255,255,255,.3)">Menunggu&hellip;</span>';
            r1Parts.push('<span class="mq-item">'
                +'<i class="bi bi-hospital-fill"></i>'
                +'<span class="mq-dokter">'+(item.nm_dokter||'')+'</span>'
                +'<span class="mq-sep">|</span>'
                +'<span class="mq-poli">'+(item.nm_poli||'')+'</span>'
                +'<span class="mq-sep">&middot;</span>'
                +'Dilayani: '+calling
                +'<span class="mq-sep">&middot;</span>'
                +'Menunggu: <span class="mq-tunggu">'+(item.menunggu||0)+'</span>'
                +'<span class="mq-sep">&middot;</span>'
                +'Selesai: <span class="mq-selesai">'+(item.sudah||0)+'</span>'
                +'<span class="mq-sep" style="margin-left:.8vw;opacity:.3">&boxv;&boxv;</span>'
                +'</span>');
        });

        var elTot=document.getElementById('globalTotal');
        var elSdh=document.getElementById('globalSudah');
        var elTgu=document.getElementById('globalMenunggu');
        if(elTot) elTot.textContent=tot;
        if(elSdh) elSdh.textContent=sdh;
        if(elTgu) elTgu.textContent=tgu;

        var mr1=document.getElementById('marqueeRow1');
        if(mr1 && r1Parts.length){ var h=r1Parts.join(''); mr1.innerHTML=h+h; }

        setTimeout(buildScrollStates,150);
    })
    .catch(function(){ document.getElementById('syncDot').style.background='#ef4444'; });
}
setInterval(pollCalls, 4000);
</script>
</body>
</html>
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
    // === DATA SEMUA DOKTER + JAM POLI HARI INI ===
    $sqlDokter = "
        SELECT DISTINCT
            r.kd_dokter,
            r.kd_poli,
            d.nm_dokter,
            p.nm_poli,
            NULL AS jam_mulai,
            NULL AS jam_selesai
        FROM reg_periksa r
        LEFT JOIN dokter     d  ON r.kd_dokter = d.kd_dokter
        LEFT JOIN poliklinik p  ON r.kd_poli   = p.kd_poli
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

    // === DATA ANTRIAN PER DOKTER ===
    $dokter_data = [];
    foreach ($daftar_dokter as $dok) {
        $kd_d = $dok['kd_dokter'];
        $kd_p = $dok['kd_poli'];

        // Ambil antrian pasien
        $sqlQ = "
            SELECT r.no_reg, r.no_rawat, r.kd_poli, r.stts,
                   ps.nm_pasien
            FROM reg_periksa r
            LEFT JOIN pasien ps ON r.no_rkm_medis = ps.no_rkm_medis
            WHERE r.tgl_registrasi = ?
              AND r.kd_dokter = ?
              AND r.kd_poli = ?
            ORDER BY r.no_reg+0 ASC
        ";
        $stmtQ = $pdo_simrs->prepare($sqlQ);
        $stmtQ->execute([$today, $kd_d, $kd_p]);
        $pasien_list = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

        // Panggilan terakhir
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

    // Global summary
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
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

:root {
    --primary: #00d4aa;
    --secondary: #0088ff;
    --success: #00e676;
    --danger: #ff5252;
    --warning: #fbbf24;
    --dark: #0a1929;
    --card-bg: rgba(255,255,255,0.98);
    --shadow: rgba(10,25,41,0.10);
}

html, body {
    height: 100vh;
    overflow: hidden;
    font-family: 'DM Sans', sans-serif;
    background: linear-gradient(160deg, #0a1929 0%, #132f4c 50%, #1e4976 100%);
    position: relative;
}

body::before {
    content: '';
    position: absolute;
    top: -50%; right: -20%;
    width: 80%; height: 80%;
    background: radial-gradient(circle, rgba(0,212,170,0.13) 0%, transparent 70%);
    border-radius: 50%;
    animation: bgPulse 18s ease-in-out infinite;
    pointer-events: none;
}
body::after {
    content: '';
    position: absolute;
    bottom: -30%; left: -15%;
    width: 60%; height: 60%;
    background: radial-gradient(circle, rgba(0,136,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
    animation: bgPulse 22s ease-in-out infinite reverse;
    pointer-events: none;
}
@keyframes bgPulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.12);opacity:.7} }

/* =========== HEADER =========== */
.header {
    position: relative; z-index: 10;
    background: rgba(10,25,41,0.95);
    backdrop-filter: blur(20px);
    border-bottom: 3px solid var(--primary);
    padding: 1.4vh 3vw;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 2vw;
    align-items: center;
    box-shadow: 0 4px 30px rgba(0,212,170,0.2);
}

.brand-section { display: flex; align-items: center; gap: 1.2vw; }

.brand-icon {
    width: 4.5vw; height: 4.5vw;
    min-width: 52px; min-height: 52px; max-width: 72px; max-height: 72px;
    background: linear-gradient(135deg, var(--primary) 0%, #00aa88 100%);
    border-radius: 1vw;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 24px rgba(0,212,170,0.4);
}
.brand-icon i { font-size: 2.4vw; color: white; }

.brand-text h1 {
    font-family: 'Archivo Black', sans-serif;
    font-size: 2vw; color: white; margin: 0;
    line-height: 1; text-transform: uppercase; letter-spacing: -0.02em;
    background: linear-gradient(135deg, #fff 0%, var(--primary) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.brand-text p {
    font-size: .9vw; color: rgba(255,255,255,.7);
    margin: .3vh 0 0 0; font-weight: 600; letter-spacing: .05em;
}

.header-stats { display: flex; gap: 1.2vw; justify-content: center; }

.header-stat-item {
    display: flex; align-items: center; gap: .8vw;
    padding: .9vh 1.3vw;
    background: rgba(255,255,255,.08);
    border-radius: .8vw;
    border: 1px solid rgba(255,255,255,.1);
}
.header-stat-icon {
    width: 2.4vw; height: 2.4vw;
    min-width: 32px; min-height: 32px;
    border-radius: .5vw;
    display: flex; align-items: center; justify-content: center;
}
.header-stat-icon.total   { background: linear-gradient(135deg,var(--secondary),#0066cc); }
.header-stat-icon.avail   { background: linear-gradient(135deg,var(--success),#00c853); }
.header-stat-icon.occ     { background: linear-gradient(135deg,var(--danger),#d32f2f); }
.header-stat-icon.waiting { background: linear-gradient(135deg,var(--warning),#e09000); }
.header-stat-icon i { font-size: 1.2vw; color: white; }
.header-stat-label { font-size: .72vw; color: rgba(255,255,255,.6); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.header-stat-value { font-family: 'Archivo Black', sans-serif; font-size: 1.8vw; color: white; line-height: 1; }

.header-info { text-align: right; }
.live-time {
    font-family: 'Archivo Black', sans-serif;
    font-size: 2.8vw; color: var(--primary);
    line-height: 1; letter-spacing: -.02em;
    text-shadow: 0 0 30px rgba(0,212,170,.6);
}
.live-date { font-size: .9vw; color: rgba(255,255,255,.8); font-weight: 600; margin-top: .3vh; }

/* =========== MAIN =========== */
.main-content {
    position: relative; z-index: 1;
    padding: 2vh 3vw 9vh 3vw;
    height: calc(100vh - 12vh);
    overflow: hidden;
}

/* Paging container */
.page-wrapper { height: 100%; position: relative; }

.page-slide {
    display: none;
    height: 100%;
    animation: fadeSlide .5s ease;
}
.page-slide.active { display: flex; flex-direction: column; }
@keyframes fadeSlide {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Dokter grid auto layout */
.dokter-grid {
    display: grid;
    gap: 1.5vw;
    width: 100%;
    height: 100%;
    grid-template-columns: repeat(var(--cols, 3), 1fr);
    grid-auto-rows: 1fr;
}

/* =========== KARTU DOKTER =========== */
.dokter-card {
    background: var(--card-bg);
    border-radius: 1.2vw;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px var(--shadow);
    border: 2px solid transparent;
    transition: border-color .3s, box-shadow .3s;
    height: 100%;
    min-height: 0;
}
.dokter-card.has-call {
    border-color: var(--warning);
    box-shadow: 0 4px 30px rgba(251,191,36,.4);
    animation: cardGlow 2s ease-in-out infinite;
}
@keyframes cardGlow {
    0%,100%{box-shadow:0 4px 20px var(--shadow)}
    50%{box-shadow:0 6px 35px rgba(251,191,36,.5)}
}

/* Card header (dokter info) */
.card-head {
    padding: 1vh 1.2vw .8vh;
    background: linear-gradient(135deg, #0a1929 0%, #132f4c 100%);
    display: flex;
    align-items: flex-start;
    gap: .8vw;
}
.dokter-avatar {
    width: 2.8vw; height: 2.8vw;
    min-width: 36px; min-height: 36px; max-width: 48px; max-height: 48px;
    background: linear-gradient(135deg, var(--primary), #0088ff);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.dokter-avatar i { font-size: 1.4vw; color: white; }

.card-head-info { flex: 1; min-width: 0; }
.nm-dokter {
    font-family: 'Archivo Black', sans-serif;
    font-size: 1vw; color: white; line-height: 1.2;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.nm-poli { font-size: .78vw; color: var(--primary); font-weight: 600; margin-top: .2vh; }
.jam-poli {
    font-size: .74vw; color: rgba(255,255,255,.65);
    display: flex; align-items: center; gap: .3vw; margin-top: .2vh;
}
.jam-poli i { color: var(--warning); }

/* Now serving badge */
.now-serving {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    padding: .8vh 1.2vw;
    display: flex; align-items: center;
    gap: 1.2vw;
}
.ns-left { display: flex; flex-direction: column; flex-shrink: 0; }
.ns-label { font-size: .72vw; font-weight: 700; color: #78350f; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .1vh; }
.ns-number {
    font-family: 'Archivo Black', sans-serif;
    font-size: 2.2vw; color: #1c1400; line-height: 1;
    animation: numPulse 2s ease-in-out infinite;
}
@keyframes numPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.05)} }
.ns-right { flex: 1; min-width: 0; }
.ns-name { 
    font-size: .95vw; font-weight: 800; color: #78350f; 
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ns-info { font-size: .72vw; color: #92400e; margin-top: .2vh; }
.ns-empty {
    background: rgba(10,25,41,.06);
    padding: .7vh 1.2vw;
    display: flex; align-items: center; gap: .4vw;
    font-size: .78vw; color: rgba(0,0,0,.4); font-weight: 600;
}

/* Stats row */
.card-stats {
    display: flex;
    border-bottom: 1px solid rgba(0,0,0,.07);
}
.cs-item {
    flex: 1; text-align: center;
    padding: .6vh .5vw;
    border-right: 1px solid rgba(0,0,0,.07);
}
.cs-item:last-child { border-right: none; }
.cs-val {
    font-family: 'Archivo Black', sans-serif;
    font-size: 1.3vw; line-height: 1;
}
.cs-val.total   { color: #0088ff; }
.cs-val.sudah   { color: #00c853; }
.cs-val.tunggu  { color: #ff5252; }
.cs-label { font-size: .66vw; font-weight: 700; color: rgba(0,0,0,.45); text-transform: uppercase; margin-top: .2vh; }

/* Patient list */
.pasien-list { flex: 1; overflow-y: auto; padding: .4vh 0; }
.pasien-list::-webkit-scrollbar { width: 4px; }
.pasien-list::-webkit-scrollbar-track { background: transparent; }
.pasien-list::-webkit-scrollbar-thumb { background: rgba(0,0,0,.15); border-radius: 4px; }

.pasien-row {
    display: grid;
    grid-template-columns: 5.5vw 1fr 5.5vw;
    gap: .8vw;
    align-items: center;
    padding: .6vh 1vw;
    border-bottom: 1px solid rgba(0,0,0,.05);
    transition: background .2s;
}
.pasien-row:last-child { border-bottom: none; }
.pasien-row.active {
    background: rgba(251,191,36,.15);
}
.pasien-row.done {
    opacity: .45;
    background: rgba(0,200,83,.06);
}
.pr-no {
    font-family: 'Archivo Black', sans-serif;
    font-size: .78vw; color: var(--dark);
    white-space: nowrap;
}
.pr-no.active { color: #b45309; }
.pr-nama { font-size: .8vw; font-weight: 600; color: #1a2e44; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pr-status {
    font-size: .7vw; font-weight: 700;
    text-align: right; text-transform: uppercase; letter-spacing: .03em;
}
.pr-status.waiting { color: #ff5252; }
.pr-status.done    { color: #00c853; }
.pr-status.active  { color: #d97706; }

.no-pasien {
    text-align: center; padding: 2vh 1vw;
    color: rgba(0,0,0,.35); font-size: .85vw; font-weight: 600;
}

/* =========== PAGE DOT INDICATOR =========== */
.page-dots {
    position: fixed; bottom: 7.5vh; left: 50%; transform: translateX(-50%);
    display: flex; gap: .6vw; z-index: 20;
}
.page-dot {
    width: .7vw; height: .7vw; min-width: 8px; min-height: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,.35);
    transition: background .3s, transform .3s;
}
.page-dot.active { background: var(--primary); transform: scale(1.4); }

/* =========== FOOTER / MARQUEE =========== */
.footer {
    position: fixed; bottom: 0; left: 0; right: 0; z-index: 10;
    background: rgba(10,25,41,0.95);
    backdrop-filter: blur(20px);
    border-top: 3px solid var(--primary);
    padding: 1vh 0;
    overflow: hidden;
    box-shadow: 0 -4px 30px rgba(0,212,170,.2);
}
.marquee-content {
    display: inline-flex; white-space: nowrap;
    animation: marquee 50s linear infinite;
    font-size: 1vw; font-weight: 600; color: white; gap: 4vw;
}
@keyframes marquee { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
.mq-item { display: inline-flex; align-items: center; gap: .6vw; }
.mq-item i { color: var(--primary); }
.mq-dokter { color: #fff; font-weight: 700; }
.mq-poli { color: var(--primary); }
.mq-num { color: var(--warning); font-family: 'Archivo Black',sans-serif; }
.mq-tunggu { color: #ff5252; }
.mq-selesai { color: var(--success); }

/* sync dot */
.sync-dot {
    width: 9px; height: 9px; border-radius: 50%;
    background: #10b981; display: inline-block; margin-left: 8px;
    animation: syncBlink 2s ease-in-out infinite; vertical-align: middle;
}
@keyframes syncBlink { 0%,100%{opacity:1} 50%{opacity:.3} }
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
                    Semua Poliklinik — RS Permata Hati
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

    <div class="header-info">
        <div class="live-time" id="liveTime">00:00:00</div>
        <div class="live-date" id="liveDate">—</div>
    </div>
</div>

<!-- ===== MAIN ===== -->
<div class="main-content">
    <div class="page-wrapper" id="pageWrapper">
        <?php
        // Tentukan kolom berdasar jumlah dokter
        $jml = count($dokter_data);
        if      ($jml <= 2) $cols = $jml ?: 1;
        elseif  ($jml <= 4) $cols = 2;
        elseif  ($jml <= 6) $cols = 3;
        elseif  ($jml <= 9) $cols = 3;
        else                $cols = 4;

        $per_page = ($cols <= 2) ? $cols * 2 : $cols * 2;
        $pages    = array_chunk($dokter_data, $per_page);
        ?>

        <?php foreach ($pages as $pi => $page_dokter): ?>
        <div class="page-slide <?= $pi === 0 ? 'active' : '' ?>"
             style="grid-template-columns:1fr" id="slide-<?= $pi ?>">
            <div class="dokter-grid" style="--cols:<?= $cols ?>">
            <?php foreach ($page_dokter as $dok): ?>
                <?php
                    $cc         = $dok['current_call'];
                    $has_call   = !empty($cc);
                    $cardClass  = $has_call ? 'has-call' : '';
                ?>
                <div class="dokter-card <?= $cardClass ?>" data-kd-dokter="<?= htmlspecialchars($dok['kd_dokter']) ?>" data-kd-poli="<?= htmlspecialchars($dok['kd_poli']) ?>">

                    <!-- Header kartu -->
                    <div class="card-head">
                        <div class="dokter-avatar"><i class="bi bi-person-badge-fill"></i></div>
                        <div class="card-head-info">
                            <div class="nm-dokter"><?= htmlspecialchars($dok['nm_dokter']) ?></div>
                            <div class="nm-poli"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($dok['nm_poli']) ?></div>
                            <?php if (!empty($dok['jam_mulai'])): ?>
                            <div class="jam-poli">
                                <i class="bi bi-clock-fill"></i>
                                <?= htmlspecialchars(substr($dok['jam_mulai'],0,5)) ?> – <?= htmlspecialchars(substr($dok['jam_selesai'],0,5)) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Nomor dilayani -->
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
                    <div class="ns-empty">
                        <i class="bi bi-hourglass-split"></i> Menunggu Panggilan...
                    </div>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div class="card-stats">
                        <div class="cs-item">
                            <div class="cs-val total"><?= $dok['total'] ?></div>
                            <div class="cs-label">Total</div>
                        </div>
                        <div class="cs-item">
                            <div class="cs-val tunggu"><?= $dok['menunggu'] ?></div>
                            <div class="cs-label">Menunggu</div>
                        </div>
                        <div class="cs-item">
                            <div class="cs-val sudah"><?= $dok['sudah'] ?></div>
                            <div class="cs-label">Selesai</div>
                        </div>
                    </div>

                    <!-- Daftar pasien -->
                    <div class="pasien-list">
                        <?php if (empty($dok['pasien'])): ?>
                        <div class="no-pasien"><i class="bi bi-inbox"></i> Belum ada pasien</div>
                        <?php else: ?>
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
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div><!-- /page-wrapper -->
</div><!-- /main-content -->

<!-- Page Dots -->
<div class="page-dots" id="pageDots">
    <?php foreach ($pages as $pi => $_): ?>
    <div class="page-dot <?= $pi === 0 ? 'active' : '' ?>" id="dot-<?= $pi ?>"></div>
    <?php endforeach; ?>
</div>

<!-- Footer Marquee -->
<div class="footer">
    <div style="overflow:hidden">
        <div class="marquee-content" id="marqueeContent">
            <?php
            $mqs = '';
            foreach ($dokter_data as $dok) {
                $calling = $dok['current_call'] ? 'No. ' . htmlspecialchars($dok['current_call']['no_antrian']) : 'Menunggu';
                $mqs .= "<span class='mq-item'>
                    <i class='bi bi-caret-right-fill'></i>
                    <span class='mq-dokter'>" . htmlspecialchars($dok['nm_dokter']) . "</span>
                    — <span class='mq-poli'>" . htmlspecialchars($dok['nm_poli']) . "</span>
                    &nbsp;|&nbsp; Dilayani: <span class='mq-num'>$calling</span>
                    &nbsp;|&nbsp; Menunggu: <span class='mq-tunggu'>{$dok['menunggu']}</span>
                    &nbsp;|&nbsp; Selesai: <span class='mq-selesai'>{$dok['sudah']}</span>
                </span>";
            }
            echo $mqs . $mqs;
            ?>
        </div>
    </div>
</div>

<script>
/* ===== CLOCK ===== */
function updateClock() {
    const now = new Date();
    document.getElementById('liveTime').textContent = now.toLocaleTimeString('id-ID');
    document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID', {
        weekday:'long', day:'numeric', month:'long', year:'numeric'
    });
}
setInterval(updateClock, 1000);
updateClock();

/* ===== PAGING ===== */
const slides = document.querySelectorAll('.page-slide');
const dots   = document.querySelectorAll('.page-dot');
let curPage  = 0;
const POLI   = '<?= addslashes($poli) ?>';
const DOKTER = '<?= addslashes($dokter) ?>';

function showSlide(idx) {
    slides.forEach((s,i) => {
        s.classList.toggle('active', i === idx);
    });
    dots.forEach((d,i) => {
        d.classList.toggle('active', i === idx);
    });
    curPage = idx;
}

if (slides.length > 1) {
    setInterval(() => {
        showSlide((curPage + 1) % slides.length);
    }, 8000);
}

/* ===== POLLING LIVE CALL ===== */function pollCalls() {
    fetch(`get_all_calls.php?poli=${encodeURIComponent(POLI)}&dokter=${encodeURIComponent(DOKTER)}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('syncDot').style.background = '#10b981';

            data.forEach(item => {
                const card = document.querySelector(
                    `.dokter-card[data-kd-dokter="${item.kd_dokter}"][data-kd-poli="${item.kd_poli}"]`
                );
                if (!card) return;

                card.classList.toggle('has-call', !!item.no_antrian);

                // Update now-serving
                const nsEl = card.querySelector('.now-serving, .ns-empty');
                if (item.no_antrian) {
                    nsEl.outerHTML = `<div class="now-serving">
                        <div class="ns-left">
                            <div class="ns-label"><i class="bi bi-megaphone-fill"></i> Sedang Dilayani</div>
                            <div class="ns-number">${item.no_antrian}</div>
                        </div>
                        <div class="ns-right">
                            <div class="ns-name">${item.nm_pasien || ''}</div>
                        </div>
                    </div>`;
                } else {
                    nsEl.outerHTML = `<div class="ns-empty"><i class="bi bi-hourglass-split"></i> Menunggu Panggilan...</div>`;
                }

                // Update stats
                const vals = card.querySelectorAll('.cs-val');
                if (vals.length >= 3) {
                    vals[0].textContent = item.total || 0;
                    vals[1].textContent = item.menunggu || 0;
                    vals[2].textContent = item.sudah || 0;
                }

                // Highlight row aktif
                card.querySelectorAll('.pasien-row').forEach(row => {
                    if (item.no_rawat && row.dataset.noRawat === item.no_rawat) {
                        row.classList.add('active');
                        row.classList.remove('done');
                    }
                });
            });
        })
        .catch(() => {
            document.getElementById('syncDot').style.background = '#ef4444';
        });
}

setInterval(pollCalls, 3000);

// Full reload setiap 5 menit
setInterval(() => location.reload(), 300000);
</script>
</body>
</html>
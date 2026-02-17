<?php
/**
 * save_call.php
 * Menyimpan / update status panggilan pasien ke tabel panggil_log.
 * Dipanggil via fetch() dari data_antri_poli.php
 */
session_start();
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$no_rawat    = trim($input['no_rawat']    ?? '');
$no_rkm_medis= trim($input['no_rkm_medis']?? '');
$no_antrian  = trim($input['no_antrian']  ?? '');
$nm_poli     = trim($input['nm_poli']     ?? '');
$nm_pasien   = trim($input['nm_pasien']   ?? '');
$kd_poli     = trim($input['kd_poli']     ?? '');
$kd_dokter   = trim($input['kd_dokter']   ?? '');
$nm_dokter   = trim($input['nm_dokter']   ?? '');
$tgl         = date('Y-m-d');

if (!$no_rawat || !$no_antrian) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    // INSERT jika belum ada, UPDATE jml_panggil jika sudah ada
    $sql = "INSERT INTO simpan_antrian_poli_wira
                (no_rawat, no_rkm_medis, no_antrian, nm_poli, nm_pasien, kd_poli, kd_dokter, nm_dokter, tgl_panggil, jml_panggil)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                jml_panggil  = jml_panggil + 1,
                no_rkm_medis = VALUES(no_rkm_medis),
                nm_poli      = VALUES(nm_poli),
                nm_pasien    = VALUES(nm_pasien),
                nm_dokter    = VALUES(nm_dokter)";

    $stmt = $pdo_simrs->prepare($sql);
    $stmt->execute([$no_rawat, $no_rkm_medis, $no_antrian, $nm_poli, $nm_pasien, $kd_poli, $kd_dokter, $nm_dokter, $tgl]);

    // Ambil jumlah panggilan terkini
    $stmt2 = $pdo_simrs->prepare("SELECT jml_panggil FROM simpan_antrian_poli_wira WHERE no_rawat = ? AND tgl_panggil = ?");
    $stmt2->execute([$no_rawat, $tgl]);
    $jml = (int)$stmt2->fetchColumn();

    echo json_encode(['success' => true, 'jml_panggil' => $jml]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
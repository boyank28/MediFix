<?php
/**
 * get_all_calls.php
 * Digunakan oleh display_poli.php untuk polling live semua panggilan per dokter
 */
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

$today  = date('Y-m-d');
$poli   = $_GET['poli']   ?? '';
$dokter = $_GET['dokter'] ?? '';

$excluded_poli = ['IGDK','PL013','PL014','PL015','PL016','PL017','U0022','U0030'];
$excluded_list = "'" . implode("','", $excluded_poli) . "'";

try {
    // Ambil semua dokter yang ada antrian hari ini
    $sqlDokter = "
        SELECT DISTINCT r.kd_dokter, r.kd_poli
        FROM reg_periksa r
        WHERE r.tgl_registrasi = ?
          AND r.kd_poli NOT IN ($excluded_list)
    ";
    $params = [$today];
    if (!empty($poli))   { $sqlDokter .= " AND r.kd_poli = ?";   $params[] = $poli; }
    if (!empty($dokter)) { $sqlDokter .= " AND r.kd_dokter = ?"; $params[] = $dokter; }

    $stmtD = $pdo_simrs->prepare($sqlDokter);
    $stmtD->execute($params);
    $daftar = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($daftar as $d) {
        $kd_d = $d['kd_dokter'];
        $kd_p = $d['kd_poli'];

        // Panggilan terakhir
        $stmtC = $pdo_simrs->prepare("
            SELECT no_antrian, nm_pasien, no_rawat, jml_panggil
            FROM simpan_antrian_poli_wira
            WHERE tgl_panggil = ? AND kd_dokter = ? AND kd_poli = ?
            ORDER BY updated_at DESC LIMIT 1
        ");
        $stmtC->execute([$today, $kd_d, $kd_p]);
        $call = $stmtC->fetch(PDO::FETCH_ASSOC);

        // Hitung statistik
        $stmtS = $pdo_simrs->prepare("
            SELECT stts FROM reg_periksa
            WHERE tgl_registrasi = ? AND kd_dokter = ? AND kd_poli = ?
        ");
        $stmtS->execute([$today, $kd_d, $kd_p]);
        $rows = $stmtS->fetchAll(PDO::FETCH_COLUMN);

        $total    = count($rows);
        $sudah    = count(array_filter($rows, fn($s) => $s === 'Sudah'));
        $menunggu = count(array_filter($rows, fn($s) => in_array($s, ['Menunggu','Belum'])));

        $result[] = [
            'kd_dokter'  => $kd_d,
            'kd_poli'    => $kd_p,
            'no_antrian' => $call['no_antrian'] ?? null,
            'nm_pasien'  => $call['nm_pasien']  ?? null,
            'no_rawat'   => $call['no_rawat']   ?? null,
            'jml_panggil'=> $call['jml_panggil']?? 0,
            'total'      => $total,
            'sudah'      => $sudah,
            'menunggu'   => $menunggu,
        ];
    }

    echo json_encode($result);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
<?php
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

$hari_ini = strtoupper(date('l'));
$hari_map = [
    'SUNDAY' => 'MINGGU',
    'MONDAY' => 'SENIN',
    'TUESDAY' => 'SELASA',
    'WEDNESDAY' => 'RABU',
    'THURSDAY' => 'KAMIS',
    'FRIDAY' => 'JUMAT',
    'SATURDAY' => 'SABTU'
];
$hari_indo = $hari_map[$hari_ini] ?? 'SENIN';

$kd_poli = $_GET['kd_poli'] ?? '';

if (empty($kd_poli)) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo_simrs->prepare("
        SELECT DISTINCT j.kd_dokter, d.nm_dokter 
        FROM jadwal j 
        JOIN dokter d ON j.kd_dokter = d.kd_dokter 
        WHERE j.hari_kerja = ? AND j.kd_poli = ? 
        ORDER BY d.nm_dokter
    ");
    $stmt->execute([$hari_indo, $kd_poli]);
    $dokters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($dokters);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
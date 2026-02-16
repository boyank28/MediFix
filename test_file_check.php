<?php
// test_file_check.php - Helper untuk test_read.php
date_default_timezone_set('Asia/Jakarta');

$locations = [
    __DIR__ . '/data/last_farmasi.json',
    __DIR__ . '/last_farmasi.json',
    sys_get_temp_dir() . '/last_farmasi.json'
];

echo '<table border="1" cellpadding="8" style="border-collapse:collapse; width:100%; color:#0f0;">';
echo '<tr style="background:#222;">
        <th>Location</th>
        <th>Exists</th>
        <th>Readable</th>
        <th>Size</th>
        <th>Modified</th>
        <th>Content Preview</th>
      </tr>';

foreach ($locations as $file) {
    $exists = file_exists($file);
    $readable = $exists && is_readable($file);
    $size = $exists ? filesize($file) : 0;
    $modified = $exists ? date('Y-m-d H:i:s', filemtime($file)) : '-';
    
    $preview = '-';
    if ($readable) {
        $content = @file_get_contents($file);
        if ($content) {
            $data = json_decode($content, true);
            if ($data) {
                $preview = 'Resep: ' . ($data['no_resep'] ?? '-') . '<br>';
                $preview .= 'Pasien: ' . ($data['nm_pasien'] ?? '-') . '<br>';
                $preview .= 'Jenis: ' . ($data['jenis_resep'] ?? '-') . '<br>';
                $preview .= 'Waktu: ' . ($data['waktu'] ?? '-');
            } else {
                $preview = '<span style="color:#f00;">Invalid JSON</span>';
            }
        } else {
            $preview = '<span style="color:#f00;">Empty file</span>';
        }
    }
    
    $existsIcon = $exists ? '✓' : '✗';
    $readableIcon = $readable ? '✓' : '✗';
    $existsColor = $exists ? '#0f0' : '#f00';
    $readableColor = $readable ? '#0f0' : '#f00';
    
    echo '<tr>';
    echo '<td><code>' . htmlspecialchars($file) . '</code></td>';
    echo '<td style="color:' . $existsColor . '; text-align:center; font-weight:bold;">' . $existsIcon . '</td>';
    echo '<td style="color:' . $readableColor . '; text-align:center; font-weight:bold;">' . $readableIcon . '</td>';
    echo '<td>' . ($size > 0 ? number_format($size) . ' bytes' : '-') . '</td>';
    echo '<td>' . $modified . '</td>';
    echo '<td><small>' . $preview . '</small></td>';
    echo '</tr>';
}

echo '</table>';

echo '<div style="margin-top:20px; padding:15px; background:#111; border-left:4px solid #ff0;">';
echo '<strong style="color:#ff0;">💡 Tips:</strong><br>';
echo '1. At least ONE file should exist and be readable<br>';
echo '2. File size should be > 0 bytes<br>';
echo '3. Modified time should be recent (within last 2 hours)<br>';
echo '4. Content should show valid patient data<br>';
echo '</div>';
?>
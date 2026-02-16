<?php
// test_write.php - File untuk test apakah JSON bisa ditulis
date_default_timezone_set('Asia/Jakarta');

echo "<h2>Test Write File JSON</h2>";
echo "<pre>";

// Test data
$testData = [
    'no_resep' => 'RES001234',
    'nm_pasien' => 'Test Pasien',
    'nm_poli' => 'Poli Umum',
    'jenis_resep' => 'Non Racikan',
    'waktu' => date('Y-m-d H:i:s'),
    'timestamp' => time()
];

echo "Test Data:\n";
print_r($testData);
echo "\n\n";

// Test semua lokasi
$locations = [
    __DIR__ . '/data/last_farmasi.json',
    __DIR__ . '/last_farmasi.json',
    sys_get_temp_dir() . '/last_farmasi.json'
];

foreach ($locations as $file) {
    echo "Testing: $file\n";
    echo str_repeat('-', 80) . "\n";
    
    $dir = dirname($file);
    
    // Check directory exists
    if (!file_exists($dir)) {
        echo "  Directory NOT exists: $dir\n";
        echo "  Trying to create...\n";
        if (@mkdir($dir, 0777, true)) {
            echo "  ✓ Directory created successfully\n";
        } else {
            echo "  ✗ Failed to create directory\n";
            continue;
        }
    } else {
        echo "  ✓ Directory exists: $dir\n";
    }
    
    // Check if writable
    if (is_writable($dir)) {
        echo "  ✓ Directory is writable\n";
    } else {
        echo "  ✗ Directory is NOT writable\n";
        echo "  Current permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
        continue;
    }
    
    // Try to write
    $written = @file_put_contents(
        $file,
        json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
    
    if ($written !== false) {
        echo "  ✓ File written successfully ($written bytes)\n";
        @chmod($file, 0666);
        echo "  ✓ Permissions set to 0666\n";
        
        // Try to read back
        if (file_exists($file)) {
            echo "  ✓ File exists and can be read\n";
            $content = file_get_contents($file);
            $decoded = json_decode($content, true);
            if ($decoded) {
                echo "  ✓ JSON is valid\n";
                echo "  Content:\n";
                echo "  " . str_replace("\n", "\n  ", $content) . "\n";
            } else {
                echo "  ✗ JSON is INVALID\n";
            }
        }
        
        echo "  \n✅ SUCCESS - This location works!\n";
        echo "  Use this path in your code: $file\n\n";
        break;
    } else {
        echo "  ✗ Failed to write file\n\n";
    }
}

echo "\n";
echo str_repeat('=', 80) . "\n";
echo "RECOMMENDATION:\n";
echo "1. Create folder 'data' manually in your project root\n";
echo "2. Set permissions: chmod 777 data\n";
echo "3. Check if SELinux is blocking (if on Linux)\n";
echo "4. Check Apache/PHP user permissions\n";
echo str_repeat('=', 80) . "\n";

echo "</pre>";
?>
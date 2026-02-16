<?php
// test_read.php - File untuk test apakah API bisa dibaca
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Read API</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1a1a1a;
            color: #0f0;
        }
        .box {
            background: #000;
            border: 2px solid #0f0;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        h2 { color: #0ff; }
        pre {
            background: #111;
            padding: 15px;
            border-left: 4px solid #0f0;
            overflow-x: auto;
        }
        button {
            background: #0f0;
            color: #000;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
            margin: 5px;
        }
        button:hover {
            background: #0ff;
        }
        #log {
            max-height: 400px;
            overflow-y: auto;
        }
        .log-entry {
            padding: 5px;
            margin: 2px 0;
            border-left: 3px solid #555;
        }
        .log-entry.success { border-color: #0f0; }
        .log-entry.error { border-color: #f00; }
    </style>
</head>
<body>

<h1>🔍 API Display Farmasi - Debug Tool</h1>

<div class="box">
    <h2>📁 Step 1: Check File Locations</h2>
    <div id="fileCheck">Checking...</div>
</div>

<div class="box">
    <h2>🌐 Step 2: Test API Response</h2>
    <button onclick="testAPI()">Test API Now</button>
    <button onclick="clearLog()">Clear Log</button>
    <button onclick="location.reload()">Refresh Page</button>
    <div id="apiResult"></div>
</div>

<div class="box">
    <h2>📊 Step 3: Real-time Monitoring</h2>
    <button onclick="startMonitoring()">Start Auto-Refresh (2s)</button>
    <button onclick="stopMonitoring()">Stop</button>
    <div id="monitoring"></div>
</div>

<div class="box">
    <h2>📝 Activity Log</h2>
    <div id="log"></div>
</div>

<script>
let monitorInterval = null;
let logCount = 0;

function addLog(message, type = 'success') {
    const logDiv = document.getElementById('log');
    const time = new Date().toLocaleTimeString('id-ID');
    const entry = document.createElement('div');
    entry.className = `log-entry ${type}`;
    entry.innerHTML = `[${time}] ${message}`;
    logDiv.insertBefore(entry, logDiv.firstChild);
    
    logCount++;
    if (logCount > 50) {
        logDiv.removeChild(logDiv.lastChild);
    }
}

function clearLog() {
    document.getElementById('log').innerHTML = '';
    logCount = 0;
    addLog('Log cleared');
}

// Check file locations
fetch('test_file_check.php')
    .then(r => r.text())
    .then(html => {
        document.getElementById('fileCheck').innerHTML = html;
        addLog('File check completed');
    })
    .catch(err => {
        document.getElementById('fileCheck').innerHTML = '<span class="error">Error checking files</span>';
        addLog('Error checking files: ' + err.message, 'error');
    });

function testAPI() {
    addLog('Testing API...');
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<span class="warning">Loading...</span>';
    
    const timestamp = Date.now();
    
    fetch(`api_farmasi_display.php?t=${timestamp}`, {
        method: 'GET',
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        addLog(`Response status: ${response.status}`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        addLog('✓ API response received', 'success');
        
        let html = '<h3 class="success">✓ API Response:</h3>';
        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        
        // Check data
        if (data.success) {
            html += '<h3>Data Analysis:</h3>';
            html += '<ul>';
            
            if (data.non_racikan.has_data) {
                html += '<li class="success">✓ Non Racikan: ' + data.non_racikan.nomor + ' - ' + data.non_racikan.nama + '</li>';
                addLog('Non Racikan active: ' + data.non_racikan.nomor);
            } else {
                html += '<li class="warning">○ Non Racikan: No active queue</li>';
            }
            
            if (data.racikan.has_data) {
                html += '<li class="success">✓ Racikan: ' + data.racikan.nomor + ' - ' + data.racikan.nama + '</li>';
                addLog('Racikan active: ' + data.racikan.nomor);
            } else {
                html += '<li class="warning">○ Racikan: No active queue</li>';
            }
            
            html += '</ul>';
            
            // Debug info
            if (data.debug) {
                html += '<h3>Debug Info:</h3>';
                html += '<pre>' + JSON.stringify(data.debug, null, 2) + '</pre>';
            }
        } else {
            html += '<p class="error">API returned success: false</p>';
            addLog('API error: ' + (data.error || 'unknown'), 'error');
        }
        
        resultDiv.innerHTML = html;
    })
    .catch(err => {
        addLog('✗ API test failed: ' + err.message, 'error');
        resultDiv.innerHTML = '<p class="error">Error: ' + err.message + '</p>';
    });
}

function startMonitoring() {
    if (monitorInterval) {
        addLog('Monitoring already running', 'warning');
        return;
    }
    
    addLog('Starting auto-monitoring every 2 seconds');
    const monDiv = document.getElementById('monitoring');
    
    monitorInterval = setInterval(() => {
        const timestamp = Date.now();
        
        fetch(`api_farmasi_display.php?t=${timestamp}`, {
            method: 'GET',
            headers: { 'Cache-Control': 'no-cache' }
        })
        .then(r => r.json())
        .then(data => {
            const time = new Date().toLocaleTimeString('id-ID');
            let status = `<p><strong>${time}</strong> - `;
            
            if (data.non_racikan.has_data) {
                status += `<span class="success">NR: ${data.non_racikan.nomor}</span> `;
            } else {
                status += `<span class="warning">NR: -</span> `;
            }
            
            if (data.racikan.has_data) {
                status += `<span class="success">R: ${data.racikan.nomor}</span>`;
            } else {
                status += `<span class="warning">R: -</span>`;
            }
            
            status += '</p>';
            
            monDiv.innerHTML = status + monDiv.innerHTML;
            
            // Keep only last 10 entries
            const entries = monDiv.querySelectorAll('p');
            if (entries.length > 10) {
                monDiv.removeChild(entries[entries.length - 1]);
            }
        })
        .catch(err => {
            addLog('Monitor error: ' + err.message, 'error');
        });
    }, 2000);
}

function stopMonitoring() {
    if (monitorInterval) {
        clearInterval(monitorInterval);
        monitorInterval = null;
        addLog('Monitoring stopped');
    }
}

// Auto test on load
setTimeout(() => testAPI(), 500);
</script>

</body>
</html>
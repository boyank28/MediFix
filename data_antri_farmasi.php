<?php
session_start();
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

// === CEK LOGIN ===
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$nama = $_SESSION['nama'] ?? 'Pengguna';

// === Inisialisasi session panggilan ===
if (!isset($_SESSION['farmasi_called'])) $_SESSION['farmasi_called'] = [];

// === Ambil data resep hari ini - SESUAI KHANZA ASLI ===
try {
   $cari = $_GET['cari'] ?? '';
   
   // Pagination
   $items_per_page = 15;
   $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
   $current_page = max(1, $current_page);

$sql = "
    SELECT 
        ro.no_resep, ro.tgl_peresepan, ro.jam_peresepan, ro.status as status_resep,
        r.no_rkm_medis, p.nm_pasien, d.nm_dokter, pl.nm_poli, pl.kd_poli,
        r.status_lanjut,
        CASE 
            WHEN EXISTS (SELECT 1 FROM resep_dokter_racikan rr WHERE rr.no_resep = ro.no_resep)
            THEN 'Racikan'
            ELSE 'Non Racikan'
        END AS jenis_resep
    FROM resep_obat ro
    INNER JOIN reg_periksa r ON ro.no_rawat = r.no_rawat
    INNER JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
    INNER JOIN dokter d ON ro.kd_dokter = d.kd_dokter
    LEFT JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
    WHERE ro.tgl_peresepan = CURDATE()
      AND ro.status = 'ralan'
      AND ro.jam_peresepan <> '00:00:00'
";

if (!empty($cari)) {
    $sql .= " AND p.nm_pasien LIKE :cari";
}

$sql .= " ORDER BY ro.tgl_peresepan DESC, ro.jam_peresepan DESC";

// Hitung total data
$stmt_count = $pdo_simrs->prepare($sql);
if (!empty($cari)) {
    $stmt_count->bindValue(':cari', "%$cari%");
}
$stmt_count->execute();
$total_items = $stmt_count->rowCount();
$total_pages = ceil($total_items / $items_per_page);

// Query dengan LIMIT
$offset = ($current_page - 1) * $items_per_page;
$sql .= " LIMIT :limit OFFSET :offset";

$stmt = $pdo_simrs->prepare($sql);
if (!empty($cari)) {
    $stmt->bindValue(':cari', "%$cari%");
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$total_resep = $total_items;
$racikan = 0;
$non_racikan = 0;
foreach ($antrian as $r) {
    if ($r['jenis_resep'] === 'Racikan') {
        $racikan++;
    } else {
        $non_racikan++;
    }
}

} catch (PDOException $e) {
    die("Gagal mengambil data antrian: " . $e->getMessage());
}

// === AJAX Handler Pemanggilan ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'panggil') {
    $no_resep = $_POST['no_resep'] ?? '';

    if (!$no_resep) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Nomor resep kosong']);
        exit;
    }

    if (!in_array($no_resep, $_SESSION['farmasi_called'])) {
        $_SESSION['farmasi_called'][] = $no_resep;
    }

    $stmt = $pdo_simrs->prepare("
        SELECT ro.no_resep, p.nm_pasien, pl.nm_poli,
               CASE 
                   WHEN EXISTS (SELECT 1 FROM resep_dokter_racikan rr WHERE rr.no_resep = ro.no_resep)
                   THEN 'Racikan'
                   ELSE 'Non Racikan'
               END AS jenis_resep
        FROM resep_obat ro
        LEFT JOIN reg_periksa r ON ro.no_rawat = r.no_rawat
        LEFT JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
        LEFT JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
        WHERE ro.no_resep = ?
    ");
    $stmt->execute([$no_resep]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $dataDir = __DIR__ . '/data';
        if (!file_exists($dataDir)) {
            @mkdir($dataDir, 0777, true);
        }
        
        $file = $dataDir . '/last_farmasi.json';
        if (!is_writable($dataDir)) {
            $file = __DIR__ . '/last_farmasi.json';
        }
        
        $jsonData = [
            'no_resep' => $data['no_resep'],
            'nm_pasien' => $data['nm_pasien'],
            'nm_poli' => $data['nm_poli'] ?? '-',
            'jenis_resep' => $data['jenis_resep'],
            'waktu' => date('Y-m-d H:i:s')
        ];
        
        @file_put_contents($file, json_encode($jsonData, JSON_PRETTY_PRINT));
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'data' => $data]);
    exit;
}

// Set page title dan extra CSS
$page_title = 'Data Antrian Farmasi - MediFix';
$extra_css = '
/* Welcome Box */
.welcome-box {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 5px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.welcome-box h3 {
  margin: 0 0 10px 0;
  font-size: 24px;
  font-weight: 700;
}

.welcome-box p {
  margin: 0;
  opacity: 0.9;
  font-size: 14px;
}

.welcome-box .date-time {
  margin-top: 10px;
  font-size: 13px;
  opacity: 0.8;
}

/* Stats Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background: #fff;
  border-radius: 5px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
  transition: all 0.3s ease;
  border-top: 3px solid;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.stat-card-content {
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  color: white;
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 13px;
  color: #666;
  margin-bottom: 5px;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #333;
}

.stat-total { border-top-color: #f39c12; }
.stat-total .stat-icon { background: #f39c12; }
.stat-racikan { border-top-color: #dd4b39; }
.stat-racikan .stat-icon { background: #dd4b39; }
.stat-nonracikan { border-top-color: #00a65a; }
.stat-nonracikan .stat-icon { background: #00a65a; }

.row-called {
  background: #fff3e0 !important;
}

.btn-call {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.btn-call.called {
  background-color: #00a65a !important;
  border-color: #008d4c !important;
}

.call-counter {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #f39c12;
  color: white;
  font-size: 9px;
  font-weight: 800;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
}

.badge-called {
  background-color: #00a65a;
  color: white;
  padding: 3px 8px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 700;
  margin-left: 6px;
}

@media (max-width: 992px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 576px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
';

$extra_js = '
function updateClock() {
    const now = new Date();
    const options = { weekday: "long", year: "numeric", month: "long", day: "numeric" };
    const tanggal = now.toLocaleDateString("id-ID", options);
    const waktu = now.toLocaleTimeString("id-ID");
    
    document.getElementById("tanggalSekarang").innerHTML = tanggal;
    document.getElementById("clockDisplay").innerHTML = waktu;
}

setInterval(updateClock, 1000);
updateClock();

function angkaKeKata(n) {
  const satuan = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan"];
  const belasan = ["sepuluh", "sebelas", "dua belas", "tiga belas", "empat belas", "lima belas", 
                   "enam belas", "tujuh belas", "delapan belas", "sembilan belas"];
  
  if (n === 0) return "nol";
  if (n < 10) return satuan[n];
  if (n >= 10 && n < 20) return belasan[n - 10];
  if (n >= 20 && n < 100) {
    const puluhan = Math.floor(n / 10);
    const sisa = n % 10;
    return satuan[puluhan] + " puluh" + (sisa > 0 ? " " + satuan[sisa] : "");
  }
  if (n >= 100 && n < 200) {
    const sisa = n - 100;
    return "seratus" + (sisa > 0 ? " " + angkaKeKata(sisa) : "");
  }
  if (n >= 200 && n < 1000) {
    const ratusan = Math.floor(n / 100);
    const sisa = n % 100;
    return satuan[ratusan] + " ratus" + (sisa > 0 ? " " + angkaKeKata(sisa) : "");
  }
  if (n >= 1000 && n < 2000) {
    const sisa = n - 1000;
    return "seribu" + (sisa > 0 ? " " + angkaKeKata(sisa) : "");
  }
  if (n >= 2000 && n < 10000) {
    const ribuan = Math.floor(n / 1000);
    const sisa = n % 1000;
    return satuan[ribuan] + " ribu" + (sisa > 0 ? " " + angkaKeKata(sisa) : "");
  }
  return n.toString();
}

window.addEventListener("DOMContentLoaded", function() {
    const today = "'.date('Y-m-d').'";
    const calledPatients = JSON.parse(localStorage.getItem("calledFarmasi_" + today) || "{}");
    
    Object.keys(calledPatients).forEach(function(noResep) {
        markAsCalled(noResep, calledPatients[noResep]);
    });
});

function markAsCalled(noResep, count) {
    const button = document.querySelector(`[data-no-resep="${noResep}"]`);
    if (button) {
        button.classList.add("called");
        
        let counterEl = button.querySelector(".call-counter");
        if (count > 1) {
            if (!counterEl) {
                counterEl = document.createElement("span");
                counterEl.className = "call-counter";
                button.appendChild(counterEl);
            }
            counterEl.textContent = count;
        } else if (counterEl) {
            counterEl.remove();
        }
        
        const row = button.closest("tr");
        if (row) {
            row.classList.add("row-called");
        }
    }
}

function panggil(no_resep, nm_pasien, buttonElement) {
    buttonElement.disabled = true;
    const originalHTML = buttonElement.innerHTML;
    buttonElement.innerHTML = "<i class=\"fa fa-spinner fa-spin\"></i>";
    
    fetch(window.location.href, {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `action=panggil&no_resep=${encodeURIComponent(no_resep)}`
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.status !== "ok") {
            alert("Gagal memanggil: " + resp.message);
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalHTML;
            return;
        }

        const data = resp.data;
        const raw = data.no_resep.slice(-4);
        const angka = parseInt(raw, 10);
        const nomorKata = angkaKeKata(angka);
        const namaPasien = data.nm_pasien
            .toLowerCase()
            .split(" ")
            .map(kata => kata.charAt(0).toUpperCase() + kata.slice(1))
            .join(" ");

        const teks = `Nomor antrian farmasi, F ${nomorKata}. Atas nama, ${namaPasien}. Silakan menuju loket farmasi.`;

        function playSoundWithRetry(callback, retries = 3) {
            const bell = new Audio("sound/opening.mp3");
            bell.volume = 1;
            
            bell.play().then(() => {
                bell.addEventListener("ended", callback);
            }).catch(err => {
                if (retries > 0) {
                    setTimeout(() => playSoundWithRetry(callback, retries - 1), 500);
                } else {
                    callback();
                }
            });
        }

        playSoundWithRetry(() => {
            const utterance = new SpeechSynthesisUtterance(teks);
            utterance.lang = "id-ID";
            utterance.rate = 0.85;
            utterance.pitch = 1.1;
            utterance.volume = 1;
            
            const voices = window.speechSynthesis.getVoices();
            const indonesianVoice = voices.find(v => 
                v.lang === "id-ID" || 
                v.lang === "id_ID" || 
                v.name.includes("Indonesia")
            );
            
            if (indonesianVoice) {
                utterance.voice = indonesianVoice;
            }
            
            utterance.onend = () => {
                setTimeout(() => location.reload(), 1000);
            };
            
            utterance.onerror = () => {
                setTimeout(() => location.reload(), 1000);
            };
            
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utterance);
        });
        
        const today = "'.date('Y-m-d').'";
        let calledPatients = JSON.parse(localStorage.getItem("calledFarmasi_" + today) || "{}");
        
        if (calledPatients[no_resep]) {
            calledPatients[no_resep]++;
        } else {
            calledPatients[no_resep] = 1;
        }
        
        localStorage.setItem("calledFarmasi_" + today, JSON.stringify(calledPatients));
        markAsCalled(no_resep, calledPatients[no_resep]);
    })
    .catch(err => {
        alert("Koneksi gagal. Silakan coba lagi.");
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalHTML;
    });
}

if ("speechSynthesis" in window) {
    speechSynthesis.onvoiceschanged = () => {
        speechSynthesis.getVoices();
    };
    speechSynthesis.getVoices();
}

function cleanOldData() {
    const today = "'.date('Y-m-d').'";
    Object.keys(localStorage).forEach(key => {
        if (key.startsWith("calledFarmasi_") && !key.includes(today)) {
            localStorage.removeItem(key);
        }
    });
}
cleanOldData();
';

// Include header
include 'includes/header.php';

// Include sidebar
include 'includes/sidebar.php';
?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Data Antrian Farmasi</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Farmasi</a></li>
        <li class="active">Data Antrian</li>
      </ol>
    </section>

    <section class="content">
      
   

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card stat-total">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-list-ol"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total Resep</div>
              <div class="stat-value"><?= $total_resep ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-racikan">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-flask"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Racikan</div>
              <div class="stat-value"><?= $racikan ?></div>
            </div>
          </div>
        </div>

        <div class="stat-card stat-nonracikan">
          <div class="stat-card-content">
            <div class="stat-icon">
              <i class="fa fa-plus-square"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Non Racikan</div>
              <div class="stat-value"><?= $non_racikan ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Filter Data</h3>
            </div>
            <div class="box-body">
              <form method="get" class="form-inline">
                <div class="form-group">
                  <label>Cari Nama Pasien:</label>
                  <input type="text" name="cari" class="form-control" 
                         placeholder="Ketik nama pasien..." 
                         value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-search"></i> Cari Data
                </button>
              </form>
            </div>
          </div>

          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Daftar Antrian (<?= count($antrian) ?> dari <?= $total_items ?>)</h3>
            </div>
            <div class="box-body">
              
              <?php if ($total_items > 0): ?>
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                  <thead style="background: #f39c12; color: white;">
                    <tr>
                      <th width="50">No</th>
                      <th width="80">Panggil</th>
                      <th width="120">No. Antrian</th>
                      <th width="150">No. Resep</th>
                      <th width="120">No. RM</th>
                      <th>Nama Pasien</th>
                      <th width="150">Poli</th>
                      <th width="180">Dokter</th>
                      <th width="120">Jenis Resep</th>
                      <th width="100">Waktu</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $no = ($current_page - 1) * $items_per_page + 1;
                    foreach ($antrian as $r): 
                        $no_antrian = 'F' . str_pad(substr($r['no_resep'], -4), 4, '0', STR_PAD_LEFT);
                        $called = in_array($r['no_resep'], $_SESSION['farmasi_called']);
                        $rowId = 'row-' . md5($r['no_resep']);
                        $btnId = 'btn-' . md5($r['no_resep']);
                    ?>
                    <tr id="<?= $rowId ?>" class="<?= $called ? 'row-called' : ''; ?>">
                      <td><?= $no++; ?></td>
                      <td>
                        <button class="btn btn-warning btn-call <?= $called ? 'called' : ''; ?>" 
                                id="<?= $btnId ?>"
                                data-no-resep="<?= htmlspecialchars($r['no_resep']) ?>"
                                data-nm-pasien="<?= htmlspecialchars($r['nm_pasien']) ?>"
                                onclick="panggil('<?= addslashes($r['no_resep']) ?>', '<?= addslashes($r['nm_pasien']) ?>', this)">
                          <i class="fa fa-phone"></i>
                        </button>
                      </td>
                      <td>
                        <strong><?= htmlspecialchars($no_antrian) ?></strong>
                        <?php if ($called): ?>
                        <span class="badge-called">
                          <i class="fa fa-check-circle"></i> Dipanggil
                        </span>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($r['no_resep']) ?></td>
                      <td><?= htmlspecialchars($r['no_rkm_medis']) ?></td>
                      <td><strong><?= htmlspecialchars($r['nm_pasien']) ?></strong></td>
                      <td><?= htmlspecialchars($r['nm_poli'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($r['nm_dokter']) ?></td>
                      <td>
                        <?php if ($r['jenis_resep'] === 'Racikan'): ?>
                          <span class="label label-danger"><?= $r['jenis_resep'] ?></span>
                        <?php else: ?>
                          <span class="label label-success"><?= $r['jenis_resep'] ?></span>
                        <?php endif; ?>
                      </td>
                      <td><?= date('H:i', strtotime($r['jam_peresepan'])) ?> WIB</td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <?php if ($total_pages > 1): ?>
              <div class="box-footer clearfix">
                <ul class="pagination pagination-sm no-margin pull-right">
                  <li <?= ($current_page <= 1) ? 'class="disabled"' : '' ?>>
                    <a href="?page=<?= $current_page - 1 ?><?= !empty($cari) ? '&cari='.urlencode($cari) : '' ?>">«</a>
                  </li>
                  <?php 
                  $start_page = max(1, $current_page - 2);
                  $end_page = min($total_pages, $current_page + 2);
                  for ($i = $start_page; $i <= $end_page; $i++): 
                  ?>
                    <li <?= ($i == $current_page) ? 'class="active"' : '' ?>>
                      <a href="?page=<?= $i ?><?= !empty($cari) ? '&cari='.urlencode($cari) : '' ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>
                  <li <?= ($current_page >= $total_pages) ? 'class="disabled"' : '' ?>>
                    <a href="?page=<?= $current_page + 1 ?><?= !empty($cari) ? '&cari='.urlencode($cari) : '' ?>">»</a>
                  </li>
                </ul>
              </div>
              <?php endif; ?>

              <?php else: ?>
              <div class="callout callout-info">
                <h4><i class="fa fa-info"></i> Informasi</h4>
                <p>Tidak ada resep untuk hari ini</p>
              </div>
              <?php endif; ?>
              
            </div>
          </div>
        </div>
      </div>

    </section>
  </div>

<?php
// Include footer
include 'includes/footer.php';
?>
<?php
session_start();
include 'koneksi.php';
include 'koneksi2.php';
date_default_timezone_set('Asia/Jakarta');

$hari_ini = strtoupper(date('l'));
$hari_map = ['SUNDAY'=>'MINGGU','MONDAY'=>'SENIN','TUESDAY'=>'SELASA','WEDNESDAY'=>'RABU','THURSDAY'=>'KAMIS','FRIDAY'=>'JUMAT','SATURDAY'=>'SABTU'];
$hari_indo = $hari_map[$hari_ini] ?? 'SENIN';
$swal_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['no_rkm_medis'])) {
    try {
        $no_rkm_medis = trim($_POST['no_rkm_medis']);
        $kd_poli = trim($_POST['kd_poli']);
        $kd_dokter = trim($_POST['kd_dokter']);
        $kd_pj = trim($_POST['kd_pj']);

        if (!$no_rkm_medis || !$kd_poli || !$kd_dokter || !$kd_pj)
            throw new Exception("Data tidak lengkap!");

        $tgl = date('Y-m-d');
        $jam = date('H:i:s');

        $stmtCekDaftar = $pdo_simrs->prepare("SELECT COUNT(*) FROM reg_periksa WHERE no_rkm_medis=?");
        $stmtCekDaftar->execute([$no_rkm_medis]);
        $stts_daftar = ($stmtCekDaftar->fetchColumn() > 0) ? "Lama" : "Baru";

        $cekStatus = $pdo_simrs->prepare("SELECT COUNT(*) FROM reg_periksa WHERE no_rkm_medis=? AND kd_poli=?");
        $cekStatus->execute([$no_rkm_medis, $kd_poli]);
        $status_poli = ($cekStatus->fetchColumn() > 0) ? "Lama" : "Baru";

        $stmt_inap = $pdo_simrs->prepare("SELECT COUNT(*) FROM reg_periksa r JOIN kamar_inap k ON r.no_rawat = k.no_rawat WHERE r.no_rkm_medis=? AND k.stts_pulang='-'");
        $stmt_inap->execute([$no_rkm_medis]);
        if ($stmt_inap->fetchColumn() > 0)
            throw new Exception("Pasien sedang dalam perawatan inap!");

        $cek = $pdo_simrs->prepare("SELECT COUNT(*) FROM reg_periksa WHERE no_rkm_medis=? AND kd_poli=? AND kd_dokter=? AND tgl_registrasi=?");
        $cek->execute([$no_rkm_medis, $kd_poli, $kd_dokter, $tgl]);
        if ($cek->fetchColumn() > 0)
            throw new Exception("Pasien sudah terdaftar hari ini!");

        $stmt_no = $pdo_simrs->prepare("SELECT MAX(CAST(no_reg AS UNSIGNED)) FROM reg_periksa WHERE tgl_registrasi=?");
        $stmt_no->execute([$tgl]);
        $no_reg = str_pad((($stmt_no->fetchColumn() ?: 0) + 1), 3, '0', STR_PAD_LEFT);

        $stmt_rawat = $pdo_simrs->prepare("SELECT MAX(CAST(SUBSTRING(no_rawat, 12, 6) AS UNSIGNED)) FROM reg_periksa WHERE tgl_registrasi=?");
        $stmt_rawat->execute([$tgl]);
        $max_rawat_seq = $stmt_rawat->fetchColumn();
        $no_rawat = date('Y/m/d/') . str_pad((($max_rawat_seq ?: 0) + 1), 6, '0', STR_PAD_LEFT);

        $stmt_pasien = $pdo_simrs->prepare("SELECT nm_pasien, alamat, tgl_lahir, keluarga AS hubunganpj, namakeluarga AS p_jawab, alamatpj FROM pasien WHERE no_rkm_medis=?");
        $stmt_pasien->execute([$no_rkm_medis]);
        $pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

        if (!$pasien) throw new Exception("Data pasien tidak ditemukan!");
        if (empty($pasien['tgl_lahir'])) throw new Exception("Tanggal lahir belum diinput!");

        $p_jawab = $pasien['p_jawab'] ?: $pasien['nm_pasien'];
        $almt_pj = $pasien['alamatpj'] ?: $pasien['alamat'];
        $hubunganpj = $pasien['hubunganpj'] ?: "-";

        $lahir = new DateTime($pasien['tgl_lahir']);
        $today = new DateTime();
        $umur = $today->diff($lahir)->y;

        $stmt_biaya = $pdo_simrs->prepare("SELECT registrasi, registrasilama FROM poliklinik WHERE kd_poli=?");
        $stmt_biaya->execute([$kd_poli]);
        $biaya = $stmt_biaya->fetch(PDO::FETCH_ASSOC);
        $biaya_reg = ($stts_daftar == "Lama") ? $biaya['registrasilama'] : $biaya['registrasi'];

        $stmt = $pdo_simrs->prepare("INSERT INTO reg_periksa (no_reg,no_rawat,tgl_registrasi,jam_reg,kd_dokter,no_rkm_medis,kd_poli,p_jawab,almt_pj,hubunganpj,biaya_reg,stts,stts_daftar,status_lanjut,kd_pj,umurdaftar,sttsumur,status_bayar,status_poli) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$no_reg, $no_rawat, $tgl, $jam, $kd_dokter, $no_rkm_medis, $kd_poli, $p_jawab, $almt_pj, $hubunganpj, $biaya_reg, 'Belum', $stts_daftar, 'Ralan', $kd_pj, $umur, 'Th', 'Belum Bayar', $status_poli]);

        $printUrl = "print_antrian.php?no_reg={$no_reg}&no_rawat={$no_rawat}&nm_pasien={$pasien['nm_pasien']}";
        $swal_data = ['icon'=>'success','title'=>'Pendaftaran Berhasil!','html'=>"<strong>No. Rawat:</strong> {$no_rawat}<br><strong>No Antri:</strong> {$kd_poli}-{$no_reg}",'confirmText'=>'Cetak','cancelText'=>'Tutup','printUrl'=>$printUrl,'redirect'=>'daftar_poli.php'];

    } catch (Exception $e) {
        $swal_data = ['icon'=>'error','title'=>'Gagal!','text'=>$e->getMessage(),'confirmText'=>'OK','redirect'=>'daftar_poli.php'];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pendaftaran Poliklinik</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f0f4f8;min-height:100vh;padding:0}
.container-main{max-width:1200px;margin:0 auto;padding:20px}
.header-section{background:#fff;border-radius:12px;padding:24px 32px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border-left:4px solid #2563eb}
.header-section h3{font-size:22px;font-weight:700;color:#1e293b;margin:0 0 6px;display:flex;align-items:center;gap:12px}
.header-section h3 i{color:#2563eb;font-size:24px}
.header-section p{font-size:14px;color:#64748b;margin:0}
.card-section{background:#fff;border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
.search-box{position:relative;margin-bottom:20px}
.search-box input{height:54px;border-radius:10px;border:2px solid #e2e8f0;font-size:16px;font-weight:500;padding:0 100px 0 20px;background:#fff;transition:all 0.3s}
.search-box input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1);outline:0}
.search-box .btn-search{position:absolute;right:6px;top:6px;height:42px;padding:0 24px;border-radius:8px;background:#2563eb;border:0;color:#fff;font-weight:600;font-size:15px;transition:all 0.3s}
.search-box .btn-search:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 8px rgba(37,99,235,0.3)}
.btn-group-action{display:flex;gap:12px;flex-wrap:wrap}
.btn-action{height:46px;padding:0 22px;border-radius:10px;font-weight:600;font-size:15px;border:0;display:inline-flex;align-items:center;gap:10px;transition:all 0.3s}
.btn-action i{font-size:18px}
.btn-keyboard{background:#f59e0b;color:#fff;box-shadow:0 2px 6px rgba(245,158,11,0.2)}
.btn-keyboard:hover{background:#d97706;transform:translateY(-1px);box-shadow:0 4px 10px rgba(245,158,11,0.3)}
.btn-exit{background:#dc2626;color:#fff;box-shadow:0 2px 6px rgba(220,38,38,0.2)}
.btn-exit:hover{background:#b91c1c;transform:translateY(-1px);box-shadow:0 4px 10px rgba(220,38,38,0.3)}
.table{margin:0;font-size:14px}
.table thead th{background:#f8fafc;color:#475569;font-weight:700;font-size:13px;padding:14px 12px;border-bottom:2px solid #e2e8f0;text-transform:uppercase;letter-spacing:0.3px}
.table tbody td{padding:14px 12px;vertical-align:middle;color:#334155;font-weight:500;border-bottom:1px solid #f1f5f9}
.table tbody tr{transition:all 0.2s}
.table tbody tr:hover{background:#f8fafc}
.btn-select{background:#10b981;color:#fff;border:0;padding:8px 18px;border-radius:8px;font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:6px;transition:all 0.3s;box-shadow:0 2px 6px rgba(16,185,129,0.2)}
.btn-select:hover{background:#059669;transform:translateY(-1px);box-shadow:0 4px 10px rgba(16,185,129,0.3)}
.modal-content{border:0;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2)}
.modal-header{background:#2563eb;color:#fff;padding:20px 28px;border:0}
.modal-title{font-size:19px;font-weight:700;display:flex;align-items:center;gap:10px}
.modal-body{padding:28px;background:#f8fafc}
.form-label{font-weight:600;color:#334155;margin-bottom:8px;font-size:14px;display:flex;align-items:center;gap:8px}
.form-label i{color:#2563eb;font-size:16px}
.form-control,.form-select{height:48px;border-radius:10px;border:2px solid #e2e8f0;font-size:15px;font-weight:500;padding:0 16px;background:#fff;transition:all 0.3s}
.form-control:focus,.form-select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1);outline:0}
.form-control[readonly]{background:#f1f5f9;color:#64748b}
.modal-footer{padding:20px 28px;border:0;background:#fff;gap:12px}
.btn-modal{height:48px;padding:0 28px;border-radius:10px;font-weight:600;font-size:15px;border:0;display:inline-flex;align-items:center;gap:8px;transition:all 0.3s}
.btn-modal-success{background:#10b981;color:#fff;box-shadow:0 2px 8px rgba(16,185,129,0.2)}
.btn-modal-success:hover{background:#059669;transform:translateY(-1px);box-shadow:0 4px 12px rgba(16,185,129,0.3)}
.btn-modal-cancel{background:#e5e7eb;color:#4b5563;font-weight:600}
.btn-modal-cancel:hover{background:#d1d5db}
.virtual-keyboard{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.25);padding:22px;z-index:2000;display:none;width:96%;max-width:850px;border:1px solid #e5e7eb}
.kbd-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:16px;border-bottom:2px solid #e5e7eb}
.kbd-title{font-size:16px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:10px}
#closeKeyboard{width:38px;height:38px;border-radius:8px;background:#dc2626;color:#fff;border:0;font-size:20px;cursor:pointer;transition:all 0.3s;font-weight:700}
#closeKeyboard:hover{background:#b91c1c;transform:scale(1.08)}
.key-row{display:flex;justify-content:center;gap:8px;margin-bottom:8px}
.key{min-width:54px;height:50px;background:#fff;color:#1e293b;border:2px solid #e5e7eb;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:all 0.2s;box-shadow:0 2px 4px rgba(0,0,0,0.06)}
.key:hover{background:#f8fafc;border-color:#2563eb;transform:translateY(-2px);box-shadow:0 4px 8px rgba(37,99,235,0.15)}
.key:active{transform:translateY(0)}
.key.special{background:#2563eb;color:#fff;border-color:#2563eb;min-width:100px;font-size:14px}
.key.special:hover{background:#1d4ed8}
.alert-warning{background:#fef3c7;border:0;color:#92400e;border-left:4px solid #f59e0b;font-weight:600}
.alert-info{background:#dbeafe;border:0;color:#1e40af;border-left:4px solid #2563eb;font-weight:600}
@media (max-width:768px){
.header-section h3{font-size:18px}.header-section p{font-size:13px}.search-box input{height:50px;font-size:15px}.table{font-size:13px}.key{min-width:46px;height:46px;font-size:15px}
}
</style>
</head>
<body>

<div class="container-main">
  <div class="header-section">
    <h3><i class="bi bi-hospital"></i>ANJUNGAN PENDAFTARAN PASIEN MANDIRI</h3>
    <p>Silakan cari data pasien untuk mendaftar ke poliklinik tujuan</p>
  </div>

  <div class="card-section">
    <form method="get" id="formSearch">
      <div class="search-box">
        <input type="text" id="inputCari" name="cari" class="form-control" placeholder="Ketik No. RM atau Nama Pasien..." value="<?=htmlspecialchars($_GET['cari']??'')?>" autocomplete="off">
        <button class="btn-search" type="submit"><i class="bi bi-search"></i> CARI</button>
      </div>
    </form>
    <div class="btn-group-action">
      <button type="button" class="btn-action btn-keyboard" onclick="toggleKeyboard()"><i class="bi bi-keyboard-fill"></i>KEYBOARD VIRTUAL</button>
      <a href="anjungan.php" class="btn-action btn-exit"><i class="bi bi-box-arrow-left"></i>KELUAR</a>
    </div>
  </div>

  <?php
  $searchResultVoice = "";
  if(isset($_GET['cari'])){
      $keyword=trim($_GET['cari']);
      $stmt=$pdo_simrs->prepare("SELECT no_rkm_medis,nm_pasien,jk,tgl_lahir,alamat FROM pasien WHERE no_rkm_medis LIKE ? OR nm_pasien LIKE ? LIMIT 20");
      $stmt->execute(["%$keyword%","%$keyword%"]);
      $pasien=$stmt->fetchAll(PDO::FETCH_ASSOC);

      if(!$pasien){
          echo"<div class='card-section'><div class='alert alert-warning mb-0'><i class='bi bi-exclamation-triangle-fill me-2'></i><strong>Data tidak ditemukan.</strong> Silakan periksa kembali nomor RM atau nama pasien.</div></div>";
          $searchResultVoice = "notFound";
      }else{
          echo"<div class='card-section'><div class='table-responsive'><table class='table table-hover align-middle mb-0'>";
          echo"<thead><tr><th>NO. RM</th><th>NAMA PASIEN</th><th>JK</th><th>TGL LAHIR</th><th>ALAMAT</th><th>AKSI</th></tr></thead><tbody>";
          foreach($pasien as $p){
              $no=htmlspecialchars($p['no_rkm_medis']);
              $nm=htmlspecialchars($p['nm_pasien']);
              $jk=htmlspecialchars($p['jk']);
              $tgl_format=date('d/m/Y',strtotime($p['tgl_lahir']));
              $alamat=htmlspecialchars($p['alamat']);
              echo"<tr><td><strong>$no</strong></td><td><strong>$nm</strong></td><td class='text-center'><span class='badge bg-secondary'>$jk</span></td><td class='text-center'>$tgl_format</td><td>$alamat</td><td class='text-center'><button type='button'class='btn-select'data-bs-toggle='modal'data-bs-target='#modalDaftar'data-norm='$no'data-nama='$nm'><i class='bi bi-person-check-fill'></i>PILIH</button></td></tr>";
          }
          echo"</tbody></table></div></div>";
          $searchResultVoice = "found";
      }
  }
  ?>
</div>

<div class="modal fade"id="modalDaftar"tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="post"id="formDaftar">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-clipboard2-pulse-fill"></i>FORMULIR PENDAFTARAN POLIKLINIK</h5>
          <button type="button"class="btn-close btn-close-white"data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden"name="no_rkm_medis"id="no_rkm_medis">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-person-circle"></i>NAMA PASIEN</label>
              <input type="text"id="nama_pasien"class="form-control"readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-building-fill-add"></i>POLIKLINIK TUJUAN</label>
              <select name="kd_poli"id="kd_poli"class="form-select"required>
                <option value="">-- Pilih Poliklinik --</option>
                <?php
                $poli=$pdo_simrs->prepare("SELECT DISTINCT j.kd_poli,p.nm_poli FROM jadwal j JOIN poliklinik p ON j.kd_poli=p.kd_poli WHERE j.hari_kerja=? ORDER BY p.nm_poli");
                $poli->execute([$hari_indo]);
                foreach($poli as $pl){
                    $kd=htmlspecialchars($pl['kd_poli']);
                    $nm=htmlspecialchars($pl['nm_poli']);
                    echo"<option value='$kd'>$nm</option>";
                }
                ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-person-badge-fill"></i>DOKTER PEMERIKSA</label>
              <select name="kd_dokter"id="kd_dokter"class="form-select"required disabled>
                <option value="">-- Pilih Poli Terlebih Dahulu --</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-credit-card-fill"></i>CARA PEMBAYARAN</label>
              <select name="kd_pj"id="kd_pj"class="form-select"required>
                <option value="">-- Pilih Cara Bayar --</option>
                <?php
                $penjab=$pdo_simrs->query("SELECT kd_pj,png_jawab FROM penjab ORDER BY png_jawab");
                foreach($penjab as $pj){
                    $kd=htmlspecialchars($pj['kd_pj']);
                    $pn=htmlspecialchars($pj['png_jawab']);
                    echo"<option value='$kd'>$pn</option>";
                }
                ?>
              </select>
            </div>
            <div class="col-12">
              <div class="alert alert-info mb-0"><i class="bi bi-info-circle-fill me-2"></i><strong>Perhatian:</strong> Pastikan semua data sudah benar sebelum menyimpan pendaftaran.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit"class="btn-modal btn-modal-success"><i class="bi bi-check-circle-fill"></i>SIMPAN PENDAFTARAN</button>
          <button type="button"class="btn-modal btn-modal-cancel"data-bs-dismiss="modal"><i class="bi bi-x-circle-fill"></i>BATAL</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="keyboard"class="virtual-keyboard">
  <div class="kbd-header">
    <div class="kbd-title"><i class="bi bi-keyboard-fill"></i>KEYBOARD VIRTUAL</div>
    <button id="closeKeyboard">×</button>
  </div>
  <div class="key-row"id="row1"></div>
  <div class="key-row"id="row2"></div>
  <div class="key-row"id="row3"></div>
  <div class="key-row"id="row4"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchResultVoice="<?=$searchResultVoice?>";
let voiceFlags={welcome:false,keyboard:false,search:false,result:false,modal:false,poli:false,dokter:false,payment:false,submit:false};

function speak(text){
  if('speechSynthesis'in window){
    window.speechSynthesis.cancel();
    const u=new SpeechSynthesisUtterance(text);
    u.lang='id-ID';u.rate=0.9;u.pitch=1.0;u.volume=1.0;
    const v=window.speechSynthesis.getVoices();
    const idv=v.find(v=>v.lang.includes('id'));
    if(idv)u.voice=idv;
    window.speechSynthesis.speak(u);
  }
}

window.addEventListener('load',()=>{
  if(!voiceFlags.welcome && !searchResultVoice){
    setTimeout(()=>{
      speak("Selamat datang di anjungan pendaftaran pasien mandiri. Silakan cari nama pasien atau nomor rekam medis dengan mengetik atau klik tombol keyboard.");
      voiceFlags.welcome=true;
    },500);
  }
  inputCari.focus();
});

const inputCari=document.getElementById('inputCari');

if(searchResultVoice==="found" && !voiceFlags.result){
  setTimeout(()=>{
    speak("Data ditemukan. Silakan klik tombol pilih pada nama Anda.");
    voiceFlags.result=true;
  },600);
}else if(searchResultVoice==="notFound" && !voiceFlags.result){
  setTimeout(()=>{
    speak("Data tidak ditemukan. Silakan periksa kembali nomor rekam medis atau nama pasien Anda.");
    voiceFlags.result=true;
  },600);
}

const modal=document.getElementById('modalDaftar');
modal.addEventListener('show.bs.modal',e=>{
  const btn=e.relatedTarget;
  document.getElementById('no_rkm_medis').value=btn.dataset.norm;
  document.getElementById('nama_pasien').value=btn.dataset.nama;
  
  if(!voiceFlags.modal){
    setTimeout(()=>{
      speak("Silakan pilih poliklinik.");
      voiceFlags.modal=true;
    },700);
  }
});

const kdPoli=document.getElementById('kd_poli');
const kdDokter=document.getElementById('kd_dokter');

kdPoli.addEventListener('change',function(){
  const poliValue=this.value;
  const poliText=this.options[this.selectedIndex].text;
  
  if(poliValue){
    if(!voiceFlags.poli){
      speak(`Anda memilih ${poliText}. Silakan pilih dokter pemeriksa.`);
      voiceFlags.poli=true;
    }
    
    kdDokter.innerHTML='<option value="">-- Memuat data dokter... --</option>';
    kdDokter.disabled=true;
    
    fetch('get_dokter_by_poli.php?kd_poli='+encodeURIComponent(poliValue))
    .then(r=>r.json())
    .then(data=>{
      kdDokter.innerHTML='<option value="">-- Pilih Dokter --</option>';
      data.forEach(d=>{
        kdDokter.innerHTML+=`<option value="${d.kd_dokter}">${d.nm_dokter}</option>`;
      });
      kdDokter.disabled=false;
    })
    .catch(err=>{
      kdDokter.innerHTML='<option value="">-- Gagal memuat data --</option>';
      console.error(err);
    });
  }
});

kdDokter.addEventListener('change',function(){
  const dokterText=this.options[this.selectedIndex].text;
  if(this.value && !voiceFlags.dokter){
    speak(`Anda memilih ${dokterText}. Silakan pilih cara pembayaran.`);
    voiceFlags.dokter=true;
  }
});

const kdPj=document.getElementById('kd_pj');
kdPj.addEventListener('change',function(){
  const pjText=this.options[this.selectedIndex].text;
  if(this.value && !voiceFlags.payment){
    speak(`Anda memilih ${pjText}. Pastikan semua data sudah benar, kemudian klik simpan pendaftaran.`);
    voiceFlags.payment=true;
  }
});

document.getElementById('formDaftar').addEventListener('submit',()=>{
  if(!voiceFlags.submit){
    speak("Menyimpan data pendaftaran. Mohon tunggu sebentar.");
    voiceFlags.submit=true;
  }
});

const keyboard=document.getElementById('keyboard');
const keys1=['1','2','3','4','5','6','7','8','9','0'];
const keys2=['Q','W','E','R','T','Y','U','I','O','P'];
const keys3=['A','S','D','F','G','H','J','K','L'];
const keys4=['Z','X','C','V','B','N','M','Backspace','Space'];

function renderKeys(keys,rowId){
  const row=document.getElementById(rowId);
  keys.forEach(k=>{
    const btn=document.createElement('button');
    btn.type='button';
    btn.className='key'+(k==='Backspace'||k==='Space'?' special':'');
    if(k==='Space')btn.innerHTML='<i class="bi bi-space"></i> SPASI';
    else if(k==='Backspace')btn.innerHTML='<i class="bi bi-backspace"></i> HAPUS';
    else btn.textContent=k;
    btn.onclick=()=>pressKey(k);
    row.appendChild(btn);
  });
}

function pressKey(k){
  if(k==='Backspace')inputCari.value=inputCari.value.slice(0,-1);
  else if(k==='Space')inputCari.value+=' ';
  else inputCari.value+=k;
  inputCari.focus();
}

function toggleKeyboard(){
  if(keyboard.style.display==='block'){
    keyboard.style.display='none';
    speak("Keyboard ditutup.");
  }else{
    keyboard.style.display='block';
    inputCari.focus();
    if(!voiceFlags.keyboard){
      speak("Keyboard dibuka. Silakan ketik nomor rekam medis atau nama pasien, lalu klik tombol cari.");
      voiceFlags.keyboard=true;
    }
  }
}

document.getElementById('closeKeyboard').onclick=()=>{
  keyboard.style.display='none';
  speak("Keyboard ditutup.");
};

renderKeys(keys1,'row1');
renderKeys(keys2,'row2');
renderKeys(keys3,'row3');
renderKeys(keys4,'row4');

if('speechSynthesis'in window){
  speechSynthesis.onvoiceschanged=()=>speechSynthesis.getVoices();
  speechSynthesis.getVoices();
}
</script>

<?php if($swal_data):?>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const data=<?=json_encode($swal_data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
  
  if(data.icon==='success'){
    speak("Pendaftaran berhasil. Silakan ambil nomor antrian Anda dan cetak bukti pendaftaran.");
    Swal.fire({
      icon:data.icon,
      title:data.title,
      html:`<div style="font-size:16px;line-height:1.7;font-weight:600">${data.html||data.text||''}</div>`,
      showCancelButton:true,
      confirmButtonText:'<i class="bi bi-printer-fill me-2"></i>'+(data.confirmText||'Cetak'),
      cancelButtonText:'<i class="bi bi-x-circle me-2"></i>'+(data.cancelText||'Tutup'),
      allowOutsideClick:false,
      allowEscapeKey:false,
      customClass:{confirmButton:'btn-modal btn-modal-success',cancelButton:'btn-modal btn-modal-cancel'},
      buttonsStyling:false
    }).then((result)=>{
      if(result.isConfirmed){
        if(data.printUrl)window.open(data.printUrl,'_blank');
        window.location=data.redirect||'daftar_poli.php';
      }else{
        window.location=data.redirect||'daftar_poli.php';
      }
    });
  }else{
    speak("Maaf, terjadi kesalahan. "+data.text);
    Swal.fire({
      icon:data.icon||'error',
      title:data.title||'Perhatian',
      html:`<div style="font-size:16px;font-weight:600">${data.text||''}</div>`,
      confirmButtonText:'<i class="bi bi-check-circle me-2"></i>'+(data.confirmText||'OK'),
      allowOutsideClick:false,
      allowEscapeKey:false,
      customClass:{confirmButton:'btn-modal btn-modal-success'},
      buttonsStyling:false
    }).then(()=>{
      window.location=data.redirect||'daftar_poli.php';
    });
  }
});
</script>
<?php endif;?>
</body>
</html>
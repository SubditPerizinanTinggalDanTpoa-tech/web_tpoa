<?php
session_start();
if (!isset($_SESSION['user'])) $_SESSION['user'] = ['nama'=>'Ahmad Pratama','role'=>'ketua','label'=>'Ketua Tim (TPOA)'];
$user = $_SESSION['user'];
$page = $_GET['page'] ?? 'dashboard';
$pages = ['dashboard','daftar_ingo','daftar_yayasan','data_ingo','data_yayasan','verifikasi','users','laporan','pengaturan'];
if (!in_array($page, $pages, true)) $page = 'dashboard';
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function active($current, $target){ return $current === $target ? ' active' : ''; }
function status_label($s){ $map=['DRAFT'=>'Draft','DIAJUKAN'=>'Diajukan','DALAM_REVIEW'=>'Dalam Proses','PERLU_PERBAIKAN'=>'Perlu Perbaikan','DIVERIFIKASI'=>'Terverifikasi','DISETUJUI'=>'Disetujui','DITOLAK'=>'Ditolak']; return $map[$s] ?? $s; }
function status_class($s){ $map=['DRAFT'=>'status-review','DIAJUKAN'=>'status-process','DALAM_REVIEW'=>'status-process','PERLU_PERBAIKAN'=>'status-review','DIVERIFIKASI'=>'status-approved','DISETUJUI'=>'status-approved','DITOLAK'=>'status-rejected']; return $map[$s] ?? 'status-process'; }
function jenis_label($s){ return $s==='YAYASAN_ASING'?'Yayasan Asing':'INGO'; }
function tanggal_label($s){ return $s ? date('d M Y', strtotime($s)) : '—'; }
$pageTitles = ['dashboard'=>'Dashboard','daftar_ingo'=>'Pendaftaran INGO','daftar_yayasan'=>'Pendaftaran Yayasan Asing','data_ingo'=>'Data INGO','data_yayasan'=>'Data Yayasan Asing','verifikasi'=>'Verifikasi & Persetujuan','users'=>'Pengguna & Hak Akses','laporan'=>'Laporan Administrasi','pengaturan'=>'Pengaturan'];
$title = $pageTitles[$page];
$pdo = null; $dbError = '';
try { require __DIR__ . '/config/database.php'; } catch (Throwable $ex) { $dbError = 'Database belum terhubung. Periksa config/database.php dan pastikan database tpoa_db sudah diimpor.'; error_log($ex->getMessage()); }
$orgRows=[]; $stats=['ingo'=>0,'yayasan'=>0,'process'=>0,'approved'=>0,'rejected'=>0,'total'=>0]; $countryRows=[];
if ($pdo instanceof PDO) {
  try {
    $stats['ingo']=(int)$pdo->query("SELECT COUNT(*) FROM organisasi WHERE jenis='INGO'")->fetchColumn();
    $stats['yayasan']=(int)$pdo->query("SELECT COUNT(*) FROM organisasi WHERE jenis='YAYASAN_ASING'")->fetchColumn();
    $stats['process']=(int)$pdo->query("SELECT COUNT(*) FROM organisasi WHERE status IN ('DIAJUKAN','DALAM_REVIEW','PERLU_PERBAIKAN')")->fetchColumn();
    $stats['approved']=(int)$pdo->query("SELECT COUNT(*) FROM organisasi WHERE status='DISETUJUI'")->fetchColumn();
    $stats['rejected']=(int)$pdo->query("SELECT COUNT(*) FROM organisasi WHERE status='DITOLAK'")->fetchColumn();
    $stats['total']=(int)$pdo->query("SELECT COUNT(*) FROM organisasi")->fetchColumn();
    $orgRows=$pdo->query("SELECT id,nomor_registrasi,nama_resmi,jenis,negara_asal,tanggal_pengajuan,status FROM organisasi ORDER BY created_at DESC LIMIT 500")->fetchAll();
    $countryRows=$pdo->query("SELECT negara_asal,COUNT(*) total FROM organisasi GROUP BY negara_asal ORDER BY total DESC LIMIT 7")->fetchAll();
  } catch (Throwable $ex) { $dbError='Struktur database belum lengkap. Impor database/schema.sql dari paket prototype terlebih dahulu.'; error_log($ex->getMessage()); }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=e($title)?> | Sistem TPOA</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="index.php?page=dashboard"><span class="brand-emblem"><i class="bi bi-globe-asia-australia"></i></span><span class="brand-copy"><strong>KEMENTERIAN LUAR NEGERI</strong><b>REPUBLIK INDONESIA</b><small>Ministry of Foreign Affairs</small></span></a>
    <div class="nav-section-label">MENU UTAMA</div>
    <a class="nav-link-custom<?=active($page,'dashboard')?>" href="?page=dashboard"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a>
    <div class="nav-section-label">PENDAFTARAN</div>
    <a class="nav-link-custom<?=active($page,'daftar_ingo')?>" href="?page=daftar_ingo"><i class="bi bi-globe2"></i><span>INGO</span><i class="bi bi-chevron-right nav-chevron"></i></a>
    <a class="nav-link-custom<?=active($page,'daftar_yayasan')?>" href="?page=daftar_yayasan"><i class="bi bi-building"></i><span>Yayasan Asing</span><i class="bi bi-chevron-right nav-chevron"></i></a>
    <div class="nav-section-label">REKAM ADMINISTRASI</div>
    <a class="nav-link-custom<?=active($page,'data_ingo')?>" href="?page=data_ingo"><i class="bi bi-folder2-open"></i><span>Data INGO</span><i class="bi bi-chevron-right nav-chevron"></i></a>
    <a class="nav-link-custom<?=active($page,'data_yayasan')?>" href="?page=data_yayasan"><i class="bi bi-building-check"></i><span>Data Yayasan Asing</span><i class="bi bi-chevron-right nav-chevron"></i></a>
    <a class="nav-link-custom<?=active($page,'verifikasi')?>" href="?page=verifikasi"><i class="bi bi-clipboard2-check"></i><span>Monitoring & Evaluasi</span><span class="nav-badge">3</span></a>
    <div class="nav-section-label">MANAJEMEN</div>
    <a class="nav-link-custom<?=active($page,'users')?>" href="?page=users"><i class="bi bi-people"></i><span>User & Hak Akses</span><i class="bi bi-chevron-right nav-chevron"></i></a>
    <a class="nav-link-custom<?=active($page,'laporan')?>" href="?page=laporan"><i class="bi bi-file-earmark-bar-graph"></i><span>Laporan</span><i class="bi bi-chevron-right nav-chevron"></i></a>
    <a class="nav-link-custom<?=active($page,'pengaturan')?>" href="?page=pengaturan"><i class="bi bi-gear"></i><span>Pengaturan</span></a>
    <div class="sidebar-bottom"><div class="mini-emblem"><i class="bi bi-globe-asia-australia"></i></div><div><strong>PORTAL TPOA</strong><small>Prototype internal v0.2</small></div><span class="sidebar-version">v0.2</span></div>
  </aside>
  <main class="main-area">
    <header class="topbar">
      <button class="mobile-menu" id="menuToggle" aria-label="Buka navigasi"><i class="bi bi-list"></i></button>
      <div class="breadcrumb-line"><span>Direktorat Organisasi Internasional</span><i class="bi bi-chevron-right"></i><strong><?=e($title)?></strong></div>
      <div class="topbar-right"><button class="top-icon" id="notificationButton" title="Notifikasi"><i class="bi bi-bell"></i><span class="notification-dot"></span></button><div class="top-divider"></div><div class="avatar">AP</div><div class="user-info"><strong><?php echo e($user['nama']); ?></strong><small><?php echo e($user['role']); ?></small></div><button class="top-icon" id="profileButton" title="Profil"><i class="bi bi-chevron-down"></i></button></div>
    </header>

    <?php if ($page === 'dashboard'): ?>
    <section class="hero-banner"><div class="hero-shade"></div><div class="hero-copy"><div class="hero-kicker"><span></span> DIREKTORAT ORGANISASI INTERNASIONAL</div><h1>Sistem TPOA</h1><p>Pendaftaran dan Pengelolaan INGO &amp; Yayasan Asing</p><small>Administrasi terintegrasi • Transparan • Akuntabel</small></div><div class="hero-gold-line"></div></section>
    <div class="content-wrap dashboard-content">
      <?php if($dbError): ?><div class="alert alert-warning" role="alert"><strong><i class="bi bi-database-exclamation me-2"></i>Database belum siap.</strong> <?=e($dbError)?></div><?php endif; ?>
      <div class="welcome-row"><div><div class="eyebrow">RINGKASAN SISTEM</div><h2>Dashboard</h2><p>Pantau status pendaftaran dan rekam administrasi organisasi secara menyeluruh.</p></div><div class="date-chip"><i class="bi bi-calendar3"></i><?=date('d M Y')?></div></div>
      <div class="stats-grid">
        <article class="stat-card"><div class="stat-icon stat-blue"><i class="bi bi-globe2"></i></div><div class="stat-body"><span>Total INGO</span><strong id="totalIngo"><?=number_format($stats['ingo'])?></strong><small><i class="bi bi-arrow-up-right"></i> 12% <em>dari bulan lalu</em></small></div></article>
        <article class="stat-card"><div class="stat-icon stat-gold"><i class="bi bi-building"></i></div><div class="stat-body"><span>Total Yayasan Asing</span><strong id="totalYayasan"><?=number_format($stats['yayasan'])?></strong><small><i class="bi bi-arrow-up-right"></i> 8% <em>dari bulan lalu</em></small></div></article>
        <article class="stat-card"><div class="stat-icon stat-amber"><i class="bi bi-file-earmark-text"></i></div><div class="stat-body"><span>Dalam Proses</span><strong><?=number_format($stats['process'])?></strong><small class="muted-trend"><i class="bi bi-clock-history"></i> Perlu tindak lanjut</small></div></article>
        <article class="stat-card"><div class="stat-icon stat-green"><i class="bi bi-check-circle-fill"></i></div><div class="stat-body"><span>Disetujui</span><strong><?=number_format($stats['approved'])?></strong><small><i class="bi bi-arrow-up-right"></i> 15% <em>dari bulan lalu</em></small></div></article>
        <article class="stat-card"><div class="stat-icon stat-red"><i class="bi bi-x-circle-fill"></i></div><div class="stat-body"><span>Ditolak</span><strong><?=number_format($stats['rejected'])?></strong><small class="negative-trend"><i class="bi bi-arrow-down-right"></i> 3% <em>dari bulan lalu</em></small></div></article>
      </div>
      <div class="dashboard-grid charts-row">
        <section class="panel chart-panel"><div class="panel-heading"><div><h3>Statistik Pendaftaran</h3><p>Perbandingan pengajuan per bulan</p></div><select id="yearFilter" class="year-select" aria-label="Pilih tahun"><option value="2025">Tahun 2025</option><option value="2024">Tahun 2024</option><option value="2023">Tahun 2023</option></select></div>
          <div class="chart-legend"><span><i class="legend-square legend-blue"></i>INGO</span><span><i class="legend-square legend-gold"></i>Yayasan Asing</span></div>
          <div class="bar-chart" id="barChart" aria-label="Grafik pendaftaran bulanan"><div class="chart-grid-lines"><i></i><i></i><i></i><i></i><i></i></div>
          <?php $months=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; $ingo=[4,3,6,5,7,4,10,11,12,16,19,24]; $yay=[2,5,4,7,7,6,9,14,15,13,16,19]; foreach($months as $i=>$month): ?><div class="bar-pair" data-month="<?=e($month)?>"><div class="bar bar-ingo" style="height:<?=($ingo[$i]*4.3)?>px"></div><div class="bar bar-yayasan" style="height:<?=($yay[$i]*4.3)?>px"></div><small><?=e($month)?></small></div><?php endforeach; ?>
          </div>
        </section>
        <section class="panel status-panel"><div class="panel-heading"><div><h3>Status Permohonan</h3><p>Distribusi seluruh permohonan</p></div><button class="more-button" title="Info status"><i class="bi bi-three-dots"></i></button></div><div class="donut-layout"><div class="donut-chart"><div class="donut-hole"><strong><?=number_format($stats['total'])?></strong><span>Total</span></div></div><div class="status-legend"><div><span class="legend-dot dot-green"></span><span>Disetujui</span><strong><?=number_format($stats['approved'])?></strong></div><div><span class="legend-dot dot-blue"></span><span>Dalam Proses</span><strong><?=number_format($stats['process'])?></strong></div><div><span class="legend-dot dot-red"></span><span>Ditolak</span><strong><?=number_format($stats['rejected'])?></strong></div><div><span class="legend-dot dot-gray"></span><span>Draft</span><strong><?=max(0,$stats['total']-$stats['approved']-$stats['process']-$stats['rejected'])?></strong></div></div></div></section>
      </div>
      <div class="dashboard-grid records-row">
        <section class="panel records-panel"><div class="panel-heading"><div><h3>Rekam Administrasi Terbaru</h3><p>Aktivitas terbaru yang tercatat di sistem</p></div><a class="text-link" href="?page=data_ingo">Lihat Semua <i class="bi bi-arrow-up-right"></i></a></div><div class="table-responsive"><table class="table data-table"><thead><tr><th>No</th><th>Tanggal</th><th>Jenis</th><th>Nama Organisasi</th><th>Negara</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="dashboardRows">
          <?php if(!$orgRows): ?><tr><td colspan="7" class="text-center py-4">Belum ada data organisasi di database.</td></tr><?php else: foreach(array_slice($orgRows,0,5) as $i=>$org): ?><tr><td><?=($i+1)?></td><td><?=e(tanggal_label($org['tanggal_pengajuan']))?></td><td><span class="type-pill <?= $org['jenis']==='YAYASAN_ASING'?'type-foundation':'' ?>"><?=e(jenis_label($org['jenis']))?></span></td><td class="org-name"><?=e($org['nama_resmi'])?></td><td><?=e($org['negara_asal'])?></td><td><span class="status-pill <?=e(status_class($org['status']))?>"><?=e(status_label($org['status']))?></span></td>
          <td>
  <td>
<td>
    <a href="detail_rekam.php?id=<?= $org['id']; ?>" class="btn btn-sm btn-primary position-relative" style="z-index: 9999; pointer-events: auto;" title="Lihat Rekam Administrasi">
        <i class="bi bi-eye"></i> Detail Rekam
    </a>
</td>
    </tr><?php endforeach; endif; ?></tbody></table></div><div class="panel-footnote"><span><i class="bi bi-database-check"></i> Data organisasi dari MySQL.</span><span>Menampilkan <?=min(5,count($orgRows))?> dari <?=number_format($stats['total'])?> rekam</span></div></section>
        <section class="panel countries-panel"><div class="panel-heading"><div><h3>Sebaran Negara</h3><p>Organisasi berdasarkan negara asal</p></div><a href="?page=laporan" class="text-link">Detail</a></div><div class="country-list">
          <?php if(!$countryRows): ?><div class="text-muted small">Belum ada data negara.</div><?php else: $maxCountry=max(array_column($countryRows,'total')); foreach($countryRows as $c): ?><div class="country-item"><span class="country-flag"><i class="bi bi-globe2"></i></span><span class="country-name"><?=e($c['negara_asal'])?></span><div class="country-track"><i style="width:<?=max(4,round($c['total']/$maxCountry*100))?>%"></i></div><strong><?=$c['total']?></strong></div><?php endforeach; endif; ?>
        </div></section>
      </div>
      <div class="quick-actions"><a href="?page=daftar_ingo" class="quick-action"><span class="quick-icon quick-blue"><i class="bi bi-plus-lg"></i></span><span><strong>Pendaftaran INGO</strong><small>Ajukan permohonan baru</small></span><i class="bi bi-chevron-right quick-arrow"></i></a><a href="?page=daftar_yayasan" class="quick-action"><span class="quick-icon quick-gold"><i class="bi bi-building-add"></i></span><span><strong>Pendaftaran Yayasan Asing</strong><small>Ajukan permohonan baru</small></span><i class="bi bi-chevron-right quick-arrow"></i></a><a href="?page=data_ingo" class="quick-action"><span class="quick-icon quick-cyan"><i class="bi bi-folder2-open"></i></span><span><strong>Rekam Administrasi</strong><small>Lihat dan kelola data</small></span><i class="bi bi-chevron-right quick-arrow"></i></a><a href="?page=laporan" class="quick-action"><span class="quick-icon quick-slate"><i class="bi bi-file-earmark-bar-graph"></i></span><span><strong>Laporan</strong><small>Unduh laporan data</small></span><i class="bi bi-chevron-right quick-arrow"></i></a></div>
    <?php else: ?>
    <div class="content-wrap inner-content"><?php if($dbError): ?><div class="alert alert-warning" role="alert"><strong><i class="bi bi-database-exclamation me-2"></i>Database belum siap.</strong> <?=e($dbError)?></div><?php endif; ?><div class="inner-hero"><div><div class="eyebrow">SISTEM ADMINISTRASI TPOA</div><h1><?=e($title)?></h1><p><?= $page==='verifikasi'?'Tinjau kelengkapan dokumen dan kelola alur persetujuan.':($page==='users'?'Kelola peran pengguna dan gambaran hak akses.':'Kelola informasi pendaftaran, dokumen, dan riwayat organisasi.') ?></p></div><span class="inner-hero-icon"><i class="bi <?= $page==='users'?'bi-people':($page==='laporan'?'bi-file-earmark-bar-graph':($page==='pengaturan'?'bi-gear':'bi-folder2-open')) ?>"></i></span></div>
    <?php if ($page==='daftar_ingo' || $page==='daftar_yayasan'): $jenis=$page==='daftar_ingo'?'INGO':'Yayasan Asing'; ?>
      <div class="inner-heading"><div><h2>Formulir Pendaftaran</h2><p>Lengkapi informasi di bawah untuk membuat rekam administrasi baru.</p></div><span class="form-step"><i class="bi bi-shield-check"></i> Formulir prototype</span></div>
      <form class="panel registration-panel" id="registrationForm" enctype="multipart/form-data"><input type="hidden" name="jenis" value="<?=e($jenis)?>"><div class="form-section-title"><span>01</span><div><strong>Informasi organisasi</strong><small>Informasi dasar sesuai dokumen resmi</small></div></div><div class="row g-3"><div class="col-md-8"><label class="form-label">Nama resmi organisasi <b>*</b></label><input required name="nama" class="form-control" placeholder="Masukkan nama resmi organisasi"></div><div class="col-md-4"><label class="form-label">Negara asal <b>*</b></label><select required name="negara" class="form-select"><option value="">Pilih negara</option><option>Amerika Serikat</option><option>Australia</option><option>Belanda</option><option>Inggris</option><option>Jepang</option><option>Jerman</option><option>Prancis</option><option>Lainnya</option></select></div><div class="col-md-6"><label class="form-label">Nama perwakilan</label><input name="perwakilan" class="form-control" placeholder="Nama penanggung jawab"></div><div class="col-md-6"><label class="form-label">Email resmi</label><input type="email" name="email" class="form-control" placeholder="nama@organisasi.org"></div><div class="col-md-6"><label class="form-label">Tanggal pengajuan <b>*</b></label><input type="date" name="tanggal" required class="form-control" value="<?=date('Y-m-d')?>"></div><div class="col-md-6"><label class="form-label">Bidang kegiatan</label><select name="bidang" class="form-select"><option>Pendidikan</option><option>Kemanusiaan</option><option>Kesehatan</option><option>Lingkungan</option><option>Pembangunan</option><option>Lainnya</option></select></div><div class="col-12"><label class="form-label">Alamat kantor/perwakilan</label><textarea name="alamat" class="form-control" rows="3" placeholder="Alamat organisasi di Indonesia"></textarea></div></div><div class="form-section-title section-spaced"><span>02</span><div><strong>Dokumen pendukung</strong><small>Unggah berkas pendukung ke server lokal</small></div></div><label class="upload-zone"><i class="bi bi-cloud-arrow-up"></i><strong>Pilih dokumen untuk diunggah</strong><small>PDF, DOCX, JPG atau PNG • Maks. 5 MB</small><input type="file" name="dokumen" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-control upload-input"></label><div class="form-actions"><button type="reset" class="btn btn-light">Bersihkan formulir</button><button class="btn btn-navy" type="submit"><i class="bi bi-check2-circle me-2"></i>Simpan ke database</button></div><div class="prototype-notice"><i class="bi bi-database-check"></i><span>Data akan disimpan ke MySQL. Jika berkas dipilih, berkas akan diunggah ke server lokal.</span></div></form>
    <?php elseif (in_array($page,['data_ingo','data_yayasan','verifikasi','laporan'])): ?>
      <div class="inner-heading"><div><h2><?= $page==='verifikasi'?'Antrean Verifikasi':($page==='laporan'?'Ringkasan Laporan':'Daftar Organisasi') ?></h2><p>Gunakan pencarian dan filter untuk menemukan rekam administrasi.</p></div><?php if($page!=='laporan'): ?><a class="btn btn-navy" href="?page=<?=$page==='data_yayasan'?'daftar_yayasan':'daftar_ingo'?>"><i class="bi bi-plus-lg me-2"></i>Tambah pendaftaran</a><?php else: ?><button class="btn btn-navy" onclick="window.print()"><i class="bi bi-printer me-2"></i>Cetak laporan</button><?php endif; ?></div>
      <section class="panel list-panel"><div class="filter-toolbar"><div class="search-field"><i class="bi bi-search"></i><input id="recordSearch" placeholder="Cari nama organisasi, negara, atau nomor registrasi..."></div><select id="statusFilter" class="form-select"><option value="">Semua status</option><option>Disetujui</option><option>Dalam Proses</option><option>Perlu Perbaikan</option><option>Diajukan</option><option>Ditolak</option></select><select id="typeFilter" class="form-select"><option value="">Semua jenis</option><option>INGO</option><option>Yayasan Asing</option></select></div><div class="table-responsive"><table class="table data-table records-table" id="recordsTable"><thead><tr><th>No</th><th>No. Registrasi</th><th>Organisasi</th><th>Jenis</th><th>Negara</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
      <?php $shown=0; foreach($orgRows as $org): if($page==='data_ingo' && $org['jenis']!=='INGO') continue; if($page==='data_yayasan' && $org['jenis']!=='YAYASAN_ASING') continue; if($page==='verifikasi' && !in_array($org['status'],['DIAJUKAN','DALAM_REVIEW','PERLU_PERBAIKAN'],true)) continue; $shown++; ?><tr data-status="<?=e(status_label($org['status']))?>" data-type="<?=e(jenis_label($org['jenis']))?>"><td><?=str_pad((string)$shown,2,'0',STR_PAD_LEFT)?></td><td><?=e($org['nomor_registrasi'])?></td><td class="org-name"><?=e($org['nama_resmi'])?></td><td><span class="type-pill <?=$org['jenis']==='YAYASAN_ASING'?'type-foundation':''?>"><?=e(jenis_label($org['jenis']))?></span></td><td><?=e($org['negara_asal'])?></td><td><?=e(tanggal_label($org['tanggal_pengajuan']))?></td><td><span class="status-pill <?=e(status_class($org['status']))?>"><?=e(status_label($org['status']))?></span></td><td><button class="view-button detail-button" data-id="<?=$org['id']?>" data-name="<?=e($org['nama_resmi'])?>" aria-label="Lihat detail"><i class="bi bi-eye"></i></button></td></tr><?php endforeach; if($shown===0): ?><tr><td colspan="8" class="text-center py-4">Belum ada rekam organisasi untuk ditampilkan.</td></tr><?php endif; ?>
      </tbody></table></div><div class="panel-footnote"><span id="resultCount">Menampilkan <?=$shown?> rekam dari database</span><span><i class="bi bi-database-check"></i> Data dari MySQL</span></div></section>
    <?php elseif ($page==='users'): ?>
      <div class="inner-heading"><div><h2>Peran dan Tanggung Jawab</h2><p>Contoh struktur akses berdasarkan alur kerja administrasi TPOA.</p></div></div><div class="role-grid"><article class="panel role-card"><span class="role-icon role-navy"><i class="bi bi-person-check"></i></span><h3>Ketua</h3><p>Menetapkan keputusan akhir, menyetujui rekomendasi, dan memantau proses administrasi.</p><span class="status-pill status-approved">Persetujuan akhir</span></article><article class="panel role-card"><span class="role-icon role-gold"><i class="bi bi-file-earmark-text"></i></span><h3>Penata</h3><p>Memeriksa kelengkapan dokumen, melakukan pencatatan, dan menyiapkan rekomendasi.</p><span class="status-pill status-process">Pemeriksaan administrasi</span></article><article class="panel role-card"><span class="role-icon role-blue"><i class="bi bi-person-vcard"></i></span><h3>Kepala Subdit</h3><p>Melakukan verifikasi substantif dan meneruskan hasil pemeriksaan untuk keputusan.</p><span class="status-pill status-review">Verifikasi substantif</span></article></div><div class="prototype-notice"><i class="bi bi-exclamation-triangle"></i><span>Halaman peran ini bersifat informatif. Autentikasi dan otorisasi sebenarnya belum diterapkan pada prototipe.</span></div>
    <?php elseif ($page==='pengaturan'): ?>
      <section class="panel settings-panel"><h2>Preferensi tampilan</h2><p>Konfigurasi visual prototipe saat ini.</p><div class="settings-row"><div><strong>Tema antarmuka</strong><small>Navy, emas, dan putih formal</small></div><span class="setting-value"><i class="bi bi-palette"></i> Kemenlu Formal</span></div><div class="settings-row"><div><strong>Mode data</strong><small>Data demonstrasi di sisi browser</small></div><span class="setting-value"><i class="bi bi-database"></i> Prototype</span></div><div class="settings-row"><div><strong>Versi antarmuka</strong><small>Pembaruan desain dashboard</small></div><span class="setting-value">v0.2</span></div></section>
    <?php endif; ?>
    <?php endif; ?>
    <footer class="page-footer"><span>© <?=date('Y')?> Kementerian Luar Negeri Republik Indonesia • Prototype Sistem TPOA</span><span>Data pada sistem ini merupakan data simulasi.</span></footer>
    </div>
  </main>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="appToast" class="toast" role="status"><div class="toast-header"><i class="bi bi-info-circle-fill text-primary me-2"></i><strong class="me-auto">Sistem TPOA</strong><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button></div><div class="toast-body" id="toastMessage">Notifikasi.</div></div></div>
<div class="detail-modal" id="detailModal" aria-hidden="true"><div class="detail-modal-card"><button id="closeDetail" class="modal-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button><div class="eyebrow">DETAIL REKAM ADMINISTRASI</div><h2 id="detailTitle">Organisasi</h2><div id="detailContent" class="detail-content"><p>Pilih organisasi untuk melihat data.</p></div><div class="detail-modal-footer"><span id="detailStatus" class="status-pill status-process">Memuat...</span><button class="btn btn-navy" id="confirmDetail">Tutup</button></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="assets/js/app.js?v=3"></script>
</body></html>

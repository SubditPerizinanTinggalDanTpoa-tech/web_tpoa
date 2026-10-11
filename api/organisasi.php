<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function respond(int $status, array $payload): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function clean_text($value, int $max = 500): string { $value = trim((string)$value); return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max); }
try { require dirname(__DIR__) . '/config/database.php'; }
catch (Throwable $e) { error_log('TPOA DB: '.$e->getMessage()); respond(500, ['ok'=>false,'message'=>'Koneksi database gagal. Periksa config/database.php dan database tpoa_db.']); }
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) respond(400, ['ok'=>false,'message'=>'ID organisasi tidak valid.']);
    $stmt = $pdo->prepare('SELECT id, nomor_registrasi, nama_resmi, jenis, negara_asal, nama_perwakilan, email_resmi, alamat, bidang_kegiatan, tanggal_pengajuan, status, created_at FROM organisasi WHERE id = ?');
    $stmt->execute([$id]); $org = $stmt->fetch();
    if (!$org) respond(404, ['ok'=>false,'message'=>'Organisasi tidak ditemukan.']);
    $docs = $pdo->prepare('SELECT id,nama_dokumen,ukuran_bytes,created_at FROM dokumen WHERE organisasi_id=? ORDER BY created_at DESC');
    $docs->execute([$id]); $org['dokumen'] = $docs->fetchAll();
    $history = $pdo->prepare('SELECT aksi,status_sebelum,status_sesudah,catatan,created_at FROM riwayat_administrasi WHERE organisasi_id=? ORDER BY created_at DESC LIMIT 20');
    $history->execute([$id]); $org['riwayat'] = $history->fetchAll();
    respond(200, ['ok'=>true,'data'=>$org]);
}
if ($method !== 'POST') { header('Allow: GET, POST'); respond(405, ['ok'=>false,'message'=>'Metode tidak didukung.']); }
$jenisInput = clean_text($_POST['jenis'] ?? '', 30);
$jenis = $jenisInput === 'Yayasan Asing' || $jenisInput === 'YAYASAN_ASING' ? 'YAYASAN_ASING' : ($jenisInput === 'INGO' ? 'INGO' : '');
$nama = clean_text($_POST['nama'] ?? '', 200);
$negara = clean_text($_POST['negara'] ?? '', 100);
$tanggal = clean_text($_POST['tanggal'] ?? '', 10);
$perwakilan = clean_text($_POST['perwakilan'] ?? '', 150);
$email = clean_text($_POST['email'] ?? '', 190);
$bidang = clean_text($_POST['bidang'] ?? '', 120);
$alamat = clean_text($_POST['alamat'] ?? '', 4000);
if (!$jenis || $nama === '' || $negara === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) respond(422, ['ok'=>false,'message'=>'Jenis, nama organisasi, negara asal, dan tanggal pengajuan wajib diisi.']);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(422, ['ok'=>false,'message'=>'Format email tidak valid.']);
$uploadPath = null; $uploadName = null; $mime = null; $size = null;
if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['dokumen'];
    if ($file['error'] !== UPLOAD_ERR_OK) respond(422, ['ok'=>false,'message'=>'Unggahan dokumen gagal. Coba ulangi.']);
    if ($file['size'] > 5 * 1024 * 1024) respond(422, ['ok'=>false,'message'=>'Ukuran dokumen maksimal 5 MB.']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','doc','docx','jpg','jpeg','png'];
    if (!in_array($ext, $allowed, true)) respond(422, ['ok'=>false,'message'=>'Format dokumen tidak diizinkan.']);
    $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
    $allowedMime = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/jpeg','image/png','application/zip','application/x-zip-compressed','application/octet-stream'];
    if (!in_array($mime, $allowedMime, true)) respond(422, ['ok'=>false,'message'=>'Tipe file tidak sesuai dengan format yang diizinkan.']);
    $uploadDir = dirname(__DIR__) . '/uploads/dokumen';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true)) respond(500, ['ok'=>false,'message'=>'Folder penyimpanan dokumen tidak dapat dibuat.']);
    $stored = bin2hex(random_bytes(18)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $stored)) respond(500, ['ok'=>false,'message'=>'File gagal disimpan di server.']);
    $uploadPath = 'uploads/dokumen/' . $stored; $uploadName = basename($file['name']); $size = (int)$file['size'];
}
try {
    $pdo->beginTransaction();
    $prefix = $jenis === 'INGO' ? 'INGO' : 'YAS';
    $reg = $prefix . '-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $stmt = $pdo->prepare('INSERT INTO organisasi (nomor_registrasi,nama_resmi,jenis,negara_asal,nama_perwakilan,email_resmi,alamat,bidang_kegiatan,tanggal_pengajuan,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,\'DIAJUKAN\',NULL)');
    $stmt->execute([$reg,$nama,$jenis,$negara,$perwakilan ?: null,$email ?: null,$alamat ?: null,$bidang ?: null,$tanggal]);
    $id = (int)$pdo->lastInsertId();
    foreach (['PENATA','KASUBDIT','KETUA'] as $stage) { $a=$pdo->prepare('INSERT INTO alur_persetujuan (organisasi_id,tahap,keputusan) VALUES (?,?,\'MENUNGGU\')'); $a->execute([$id,$stage]); }
    $h=$pdo->prepare('INSERT INTO riwayat_administrasi (organisasi_id,aksi,status_sebelum,status_sesudah,catatan) VALUES (?,?,?,?,?)');
    $h->execute([$id,'Pendaftaran dibuat',null,'DIAJUKAN','Data pendaftaran disimpan melalui formulir website.']);
    if ($uploadPath) { $d=$pdo->prepare('INSERT INTO dokumen (organisasi_id,nama_dokumen,path_tersimpan,mime_type,ukuran_bytes) VALUES (?,?,?,?,?)'); $d->execute([$id,$uploadName,$uploadPath,$mime,$size]); }
    $pdo->commit();
    respond(201, ['ok'=>true,'message'=>'Pendaftaran berhasil disimpan ke database.','data'=>['id'=>$id,'nomor_registrasi'=>$reg,'nama_resmi'=>$nama,'status'=>'DIAJUKAN']]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($uploadPath && is_file(dirname(__DIR__) . '/' . $uploadPath)) @unlink(dirname(__DIR__) . '/' . $uploadPath);
    error_log('TPOA insert: '.$e->getMessage());
    respond(500, ['ok'=>false,'message'=>'Data belum tersimpan. Periksa struktur tabel database dan log PHP.']);
}

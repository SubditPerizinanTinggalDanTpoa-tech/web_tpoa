<?php
// Panggil koneksi database (sesuaikan path jika file database.php Anda ada di folder config)
require_once 'config/database.php'; 

// Catatan: Pastikan di file database.php koneksi PDO Anda disimpan dalam variabel $pdo atau $db. 
// Contoh di bawah menggunakan $pdo. Jika berbeda, silakan sesuaikan variabelnya.

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Ambil data organisasi menggunakan PDO prepared statement
    $stmt = $pdo->prepare("SELECT * FROM organisasi WHERE id = ?");
    $stmt->execute([$id]);
    $org = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$org) {
        echo "<div class='container mt-5'><div class='alert alert-danger'>Data organisasi tidak ditemukan. <a href='index.php'>Kembali</a></div></div>";
        exit;
    }

    // Ambil dokumen terkait
    $stmt_dok = $pdo->prepare("SELECT * FROM dokumen WHERE organisasi_id = ?");
    $stmt_dok->execute([$id]);
    $dokumen_list = $stmt_dok->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Koneksi/Query Error: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekam Administrasi & Verifikasi - <?= htmlspecialchars($org['nama_resmi']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container py-5">
    <a href="index.php" class="btn btn-secondary mb-4">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>

    <!-- Profil Organisasi -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-building"></i> <?= htmlspecialchars($org['nama_resmi']) ?></h4>
            <span class="badge bg-light text-dark fs-6"><?= htmlspecialchars($org['jenis']) ?></span>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Nomor Registrasi:</strong><br><?= htmlspecialchars($org['nomor_registrasi']) ?></p>
                    <p><strong>Negara Asal:</strong><br><?= htmlspecialchars($org['negara_asal']) ?></p>
                    <p><strong>Bidang Kegiatan:</strong><br><?= htmlspecialchars($org['bidang_kegiatan'] ?? '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Nama Perwakilan:</strong><br><?= htmlspecialchars($org['nama_perwakilan'] ?? '-') ?></p>
                    <p><strong>Email Resmi:</strong><br><?= htmlspecialchars($org['email_resmi'] ?? '-') ?></p>
                    <p><strong>Status Pengajuan:</strong><br><span class="badge bg-warning text-dark"><?= htmlspecialchars($org['status']) ?></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Dokumen Spesifik Berdasarkan Jenis Organisasi -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Dokumen Persyaratan & Terkait</h5>
        </div>
        <div class="card-body">
            <?php if ($org['jenis'] == 'INGO'): ?>
                <div class="alert alert-info py-2 small">
                    <strong>Dokumen Wajib INGO:</strong> Surat Izin Prinsip, MSP (Memorandum Sektoral/Perjanjian), dan Matriks Hasil Monev.
                </div>
            <?php elseif ($org['jenis'] == 'YAYASAN_ASING'): ?>
                <div class="alert alert-secondary py-2 small">
                    <strong>Dokumen Wajib Yayasan Asing:</strong> Surat Rekomendasi Pengesahan Yayasan Asing dan Akta Pendirian Yayasan Asing.
                </div>
            <?php endif; ?>

            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Dokumen / Jenis Berkas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($dokumen_list) > 0): ?>
                        <?php $no = 1; foreach ($dokumen_list as $dok): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($dok['nama_dokumen']); ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($dok['path_tersimpan']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Lihat Dokumen
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">Belum ada dokumen yang diunggah.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel Verifikasi Dokumen 2 Layer & Pertimbangan TPOA -->
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-check2-square"></i> Panel Verifikasi & Alur Persetujuan</h5>
        </div>
        <div class="card-body">
            <form action="proses_verifikasi.php" method="POST">
                <input type="hidden" name="organisasi_id" value="<?= $org['id']; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Pilih Tahapan Verifikasi:</strong></label>
                        <select name="tahap" class="form-select" required>
                            <option value="PENATA">Layer 1: Verifikasi Penata (Kelengkapan Dokumen)</option>
                            <option value="KASUBDIT">Layer 2: Verifikasi Pembina / Kasubdit</option>
                            <?php if ($org['jenis'] == 'INGO'): ?>
                                <option value="KETUA">Hasil Pertimbangan TPOA (Pimpinan Tertinggi)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Keputusan / Status:</strong></label>
                        <select name="keputusan" class="form-select" required>
                            <option value="DISETUJUI">Setuju / Memenuhi Syarat</option>
                            <option value="PERLU_PERBAIKAN">Perlu Perbaikan Dokumen</option>
                            <option value="DITOLAK">Ditolak</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><strong>Catatan Verifikasi / Catatan TPOA:</strong></label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Masukkan catatan atau alasan perbaikan berkas di sini..."></textarea>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-send"></i> Simpan & Kirim Hasil Verifikasi
                </button>
            </form>
        </div>
    </div>

</div>

</body>
</html>
<?php
// Hubungkan ke file koneksi database (menggunakan PDO)
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form POST
    $organisasi_id = isset($_POST['organisasi_id']) ? intval($_POST['organisasi_id']) : 0;
    $tahap = isset($_POST['tahap']) ? $_POST['tahap'] : '';
    $keputusan = isset($_POST['keputusan']) ? $_POST['keputusan'] : '';
    $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';

    if ($organisasi_id <= 0 || empty($tahap) || empty($keputusan)) {
        header("Location: index.php?error=datakurang");
        exit;
    }

    try {
        // Ambil status organisasi saat ini
        $stmt_org = $pdo->prepare("SELECT status FROM organisasi WHERE id = ?");
        $stmt_org->execute([$organisasi_id]);
        $org = $stmt_org->fetch(PDO::FETCH_ASSOC);

        if (!$org) {
            header("Location: index.php?error=notfound");
            exit;
        }

        $status_sebelum = $org['status'];
        
        // Tentukan perubahan status organisasi berdasarkan keputusan verifikator
        $status_sesudah = $status_sebelum;
        if ($keputusan === 'DISETUJUI') {
            if ($tahap === 'PENATA') {
                $status_sesudah = 'DALAM_REVIEW';
            } elseif ($tahap === 'KASUBDIT') {
                $status_sesudah = 'DIVERIFIKASI';
            } elseif ($tahap === 'KETUA') {
                $status_sesudah = 'DISETUJUI';
            }
        } elseif ($keputusan === 'PERLU_PERBAIKAN') {
            $status_sesudah = 'PERLU_PERBAIKAN';
        } elseif ($keputusan === 'DITOLAK') {
            $status_sesudah = 'DITOLAK';
        }

        // Mulai transaksi database (agar aman jika ada kegagalan salah satu query)
        $pdo->beginTransaction();

        // 1. Simpan ke tabel alur_persetujuan
        $stmt_alur = $pdo->prepare("INSERT INTO alur_persetujuan (organisasi_id, tahap, keputusan, catatan, diproses_pada) VALUES (?, ?, ?, ?, NOW())");
        $stmt_alur->execute([$organisasi_id, $tahap, $keputusan, $catatan]);

        // 2. Perbarui status pada tabel organisasi
        $stmt_update = $pdo->prepare("UPDATE organisasi SET status = ? WHERE id = ?");
        $stmt_update->execute([$status_sesudah, $organisasi_id]);

        // 3. Catat rekam jejak ke tabel riwayat_administrasi (audit trail)
        $aksi = "VERIFIKASI_" . $tahap;
        $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR']);
        $stmt_hist = $pdo->prepare("INSERT INTO riwayat_administrasi (organisasi_id, aksi, status_sebelum, status_sesudah, catatan, ip_hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_hist->execute([$organisasi_id, $aksi, $status_sebelum, $status_sesudah, $catatan, $ip_hash]);

        // Selesaikan transaksi
        $pdo->commit();

        // Kembalikan ke halaman detail dengan pesan sukses
        header("Location: detail.php?id=" . $organisasi_id . "&success=verifikasi");
        exit;

    } catch (Exception $e) {
        // Batalkan perubahan jika terjadi error
        $pdo->rollBack();
        echo "<div class='container mt-5'><div class='alert alert-danger'>Terjadi kesalahan sistem: " . htmlspecialchars($e->getMessage()) . " <a href='detail.php?id=$organisasi_id'>Kembali</a></div></div>";
    }
} else {
    header("Location: index.php");
    exit;
}
?>
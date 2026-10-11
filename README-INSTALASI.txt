PANDUAN TPOA REDESIGN + INTEGRASI MYSQL
======================================
Target: C:\xampp\htdocs\tpoa_web

YANG SUDAH TERHUBUNG
- Dashboard mengambil total INGO, Yayasan Asing, status, dan rekam terbaru dari MySQL.
- Halaman Data INGO, Data Yayasan Asing, Verifikasi, dan Laporan menampilkan rekam database.
- Tombol mata pada setiap organisasi membuka detail asli melalui api/organisasi.php?id=ID.
- Formulir pendaftaran menyimpan organisasi ke MySQL, membuat tiga tahap alur persetujuan, mencatat riwayat, dan (opsional) menyimpan dokumen ke server.
- Dokumen yang diterima: PDF, DOC, DOCX, JPG, PNG; ukuran maksimal 5 MB.

LANGKAH INSTALASI
1. Cadangkan folder C:\xampp\htdocs\tpoa_web terlebih dahulu.
2. Ekstrak ZIP ini.
3. Salin semua isi folder tpoa_web_redesign ke C:\xampp\htdocs\tpoa_web, dan pilih Replace/Timpa jika diminta.
   Pastikan ikut menyalin folder config, api, database, uploads, dan assets.
4. Buka XAMPP Control Panel. Jalankan Apache dan MySQL.
5. Buka http://localhost/phpmyadmin
6. Jika database tpoa_db belum ada, klik Import dan pilih:
   C:\xampp\htdocs\tpoa_web\database\schema.sql
   lalu klik Go/Kirim.
   Jika database dan tabel sudah ada, JANGAN impor ulang schema ini karena berisi data contoh dan pembuatan tabel awal.
7. Buka config\database.php. Default XAMPP biasanya:
   host 127.0.0.1, database tpoa_db, user root, password kosong.
   Jika MySQL kamu menggunakan password, isi variabel $pass.
8. Pastikan folder C:\xampp\htdocs\tpoa_web\uploads\dokumen bisa ditulisi oleh Apache.
9. Buka http://localhost/tpoa_web_baru/ Ctrl+F5.

UJI FITUR
- Masuk ke Pendaftaran INGO atau Pendaftaran Yayasan Asing.
- Isi Nama resmi organisasi, Negara asal, dan Tanggal pengajuan.
- Opsional: pilih dokumen dengan ukuran maksimal 5 MB.
- Klik Simpan ke database. Setelah sukses, halaman Data INGO/Yayasan akan terbuka.
- Klik ikon mata di kolom Aksi untuk melihat detail yang diambil dari database.

TROUBLESHOOTING
- “Database belum siap”: pastikan MySQL Running, database tpoa_db sudah diimpor, dan config/database.php benar.
- “Struktur database belum lengkap”: gunakan database/schema.sql yang ada di paket ini. Jika tabel lama berbeda, backup dahulu dan periksa struktur sebelum mengubahnya.
- Upload gagal: pastikan folder uploads/dokumen ada dan dapat ditulisi; periksa upload_max_filesize dan post_max_size di php.ini jika diperlukan.

BATASAN KEAMANAN
Ini masih prototype lokal. Login/hak akses belum benar-benar diterapkan; jangan dipasang langsung ke internet atau dipakai untuk data resmi/sensitif tanpa audit keamanan, autentikasi, CSRF protection, dan kebijakan dokumen.

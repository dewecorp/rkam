# SIRKAM v1.0 — PHP Native + MySQL + Tailwind

## 1. Syarat
- PHP 8.1+ (ext: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`)
- MySQL 5.7+ / MariaDB 10.2+, Apache + `mod_rewrite` (XAMPP / Laragon)

## 2. Instalasi
1. Copy folder ke `htdocs/rkam` (XAMPP) atau `www/rkam` (Laragon).
2. Buat database `rkam_db`, import `database/rkam.sql`.
3. Edit `config/database.php`: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
4. Edit `config/app.php`: `BASE_URL` (`/rkam/`), batas upload.
5. Pastikan folder writable: `db_backups/`, `uploads/bukti/`, `uploads/logo/`.
6. Buka `http://localhost/rkam/`. Fallback clean URL: `index.php?url=dashboard`.

## 3. Login awal (ganti setelah masuk)
| user | pass | role |
|---|---|---|
| superadmin | admin123 | superadmin |
| kepala | kepala123 | kepala_madrasah |
| bendahara | bendahara123 | bendahara |
| operator | operator123 | operator |
| viewer | viewer123 | viewer |

Reset darurat: `php tools/reset-password.php superadmin admin123`

## 4. Alur pakai
1. Pengaturan: identitas madrasah + upload logo (JPG/PNG/WebP max 2MB).
2. Master Data (lipatan): Tahun Anggaran aktif 1, Bidang, Sumber Dana, Rekening, Satuan, Kegiatan.
3. RKAM: modal item auto `volume×harga`, sumber wajib balance (tombol Samakan).
4. Detail: Ajukan → Verifikasi → Setujui → Kunci (+Tolak/Revisi wajib alasan).
5. Realisasi: overbudget tolak kecuali izin + alasan, bukti JPG/PNG/PDF max 5MB.
6. Monitoring: bar 80% kuning, 90% oranye, 100% merah.
7. Laporan (lipatan 6 anak): filter tahun, cetak kop + logo + TTD, export CSV.
8. Backup: konfirmasi → progress → sukses/gagal; tabel No/Nama/Ukuran/Aksi; restore `.sql` allowlist 18 tabel.
9. Notif bell: dropdown, bold → normal saat klik, badge hilang, redirect detail RKAM, auto-hapus 24 jam.

## 5. Keamanan bawaan
PDO prepared, `htmlspecialchars`, CSRF semua POST, `password_hash/verify`, `session_regenerate_id`, HttpOnly+Lax, lock login 5×/15 mnt, RBAC server-side, upload MIME + nama acak + `.htaccess` matikan PHP, transaction RKAM/realisasi/approval, error log server saja.

## 6. Troubleshooting
- RKAM "Terjadi kesalahan sistem": cek `php_errors.log`, alias `real` reserved → pakai `jml_real`.
- Backup kosong: folder fisik `backup/` tabrakan rute → pakai `db_backups/`.
- Simpan gagal FK sumber: id sumber kosong → dropdown valid + validasi ganda.
- Upload gagal: cek writable + `upload_max_filesize` php.ini.
- Clean URL 404: enable `mod_rewrite`, cek `RewriteBase /rkam/`.

## 7. Final check (2026-09-16)
- `php -l` semua file: OK.
- Query test 12/12: tahun aktif, list RKAM, total dashboard, bulan, sumber, bidang, 18 tabel, 5 user, prune notif, 3 folder writable.
- Security grep: tidak ada `alert/confirm/eval/mysql_query/concat SQL mentah`.
- `.htaccess` proteksi: `db_backups` deny all, `uploads/*` matikan PHP.

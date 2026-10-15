# Laporan Audit & Perbaikan — Sistem Buku Tamu Kantor

Tanggal: 15 Oktober 2026

## 1. Ringkasan kondisi awal

Proyek adalah aplikasi PHP + MySQL (PDO) untuk pencatatan kunjungan tamu kantor
dengan tiga role: `admin`, `resepsionis`, `keamanan`. Secara umum kualitas kode
sudah cukup baik — sudah memakai prepared statement (PDO), `password_hash()` /
`password_verify()`, dan escaping output dengan `htmlspecialchars()` secara
konsisten. Namun ditemukan beberapa bug nyata dan celah keamanan yang diperbaiki
pada audit ini.

## 2. Bug yang ditemukan dan diperbaiki

| # | File | Masalah | Perbaikan |
|---|------|---------|-----------|
| 1 | `logout.php` | Seluruh isi file terbungkus fence Markdown ```` ```php ... ``` ```` (bukan PHP murni, CRLF). Logout dari root **gagal total** — `header()` akan error karena output sudah terkirim duluan. | Fence dihapus, baris akhir dirapikan ke LF. |
| 2 | `resepsionis/tamu_masuk.php` | Ada sisa ```` ``` ```` nyasar di baris terakhir file (tercetak sebagai teks di bawah halaman). | Teks sisa dihapus. |
| 3 | `inc/koneksi.php` | Saat koneksi DB gagal: (a) pesan error PDO mentah di-`echo` ke pengguna → kebocoran info host/kredensial/struktur; (b) method mengembalikan `false`, padahal **semua** pemanggil langsung memanggil `->prepare()`/`->query()` pada hasilnya tanpa cek → fatal error tak tertangani ("Call to a member function on bool"). | Detail error hanya dicatat ke `error_log()`; pengguna melihat pesan umum lalu proses dihentikan (`die()`) dengan HTTP 500 — konsisten dengan asumsi semua pemanggil. |
| 4 | `inc/auth.php` | Redirect ke login di-hardcode `/buku-tamu/login.php` — rusak jika proyek di-deploy di folder lain atau di domain root. | Diganti fungsi `login_url()` yang menghitung path secara dinamis dari `DOCUMENT_ROOT` vs lokasi folder proyek. |
| 5 | `inc/logout.php` | File logout duplikat yang **tidak direferensikan di manapun** (ditelusuri dengan `grep -r` ke seluruh proyek — nol referensi). Logout sesungguhnya memakai `logout.php` di root. | Dihapus (dead file, duplikasi logika). |
| 6 | `inc/header.php` | Ada cabang menu sidebar untuk role `staf`/`petugas`/`pengunjung` yang menunjuk ke folder `staf/` dan `pengunjung/` yang **tidak pernah ada** di proyek — dead code. Kolom `role` di `tb_users` adalah `ENUM('admin','resepsionis','keamanan')`, jadi cabang ini tidak pernah bisa tereksekusi. | Dihapus. |
| 7 | `login_act.php` | Tidak ada regenerasi session ID setelah login berhasil → risiko *session fixation*. | Ditambahkan `session_regenerate_id(true)` tepat setelah password diverifikasi. |

## 3. Perbaikan keamanan

### 3.1 CSRF protection (celah utama yang ditemukan)

`admin/list_tamu.php` sudah menerapkan token CSRF dengan benar untuk aksi hapus
tamu. Tapi **hampir semua** form/aksi pengubah data lain **tidak punya token
CSRF sama sekali** — artinya situs lain bisa memaksa browser admin/resepsionis/
keamanan yang sedang login untuk menghapus staf, membuat user admin baru,
mengubah data tamu, atau check-out tamu, tanpa sepengetahuan korban.

Ditambahkan helper bersama di `inc/auth.php`: `csrf_token()`, `csrf_field()`,
`csrf_verify()`, `cek_csrf_atau_tolak()`. Diterapkan ke seluruh pasangan
form + handler yang sebelumnya tidak terlindungi:

- `admin/tambahstaf.php` ⇄ `tambahstaf_act.php`
- `admin/editstaf.php` ⇄ `editstaf_act.php`
- `admin/list_staf.php` (form hapus) ⇄ `del_staf.php`
- `admin/tambahuser.php` ⇄ `tambahuser_act.php`
- `admin/edituser.php` ⇄ `edituser_act.php`
- `admin/list_user.php` (form hapus) ⇄ `del_user.php`
- `resepsionis/tamu_masuk.php` ⇄ `tamu_masuk_act.php`
- `keamanan/edit_tamu.php` ⇄ `edit_tamu_act.php`
- `keamanan/index.php` (form check-out) ⇄ `checkout_act.php`

Token ditolak → pesan umum + redirect ke halaman aman, tidak ada perubahan
data yang terjadi.

### 3.2 Session hardening

Ditambahkan `inc/session_config.php` yang di-`require` sebelum setiap
`session_start()` di seluruh entry point (`login.php`, `login_act.php`,
`index.php`, `logout.php`, `inc/auth.php`, `inc/header.php`). Mengatur:

- `HttpOnly` → cookie session tidak bisa dibaca lewat JavaScript (mitigasi
  pencurian session via XSS).
- `SameSite=Lax` → mitigasi dasar lintas-situs.
- `Secure` otomatis aktif jika koneksi terdeteksi HTTPS (tidak memaksa HTTPS
  di lokal, karena lingkungan dev biasanya HTTP).
- `session.use_strict_mode = 1`.

### 3.3 Informasi & file sensitif

- `inc/koneksi.php` — lihat #3 di atas (tidak lagi membocorkan detail koneksi).
- Kredensial database sekarang bisa dioverride lewat environment variable
  (`DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`) untuk produksi, dengan
  default lokal (`root` tanpa password) tetap berfungsi seperti semula.
- Ditambahkan `.htaccess` di root: blokir akses langsung ke `*.sql`, `*.md`,
  `*.txt`, `*.log`, `*.env`; matikan directory listing; tambahkan header
  `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`.
- Ditambahkan `inc/.htaccess`: blokir akses web langsung ke folder `inc/`
  (semua filenya hanya dipakai lewat `require_once()`, bukan URL).
- Ditambahkan `uploads/.htaccess`: matikan eksekusi PHP/skrip di folder itu
  sebagai pencegahan. **Catatan:** fitur upload file **belum ada** di aplikasi
  ini (tidak ada form atau handler yang menulis ke `uploads/`), jadi ini murni
  pencegahan untuk masa depan, bukan menutup celah yang sedang aktif.

Catatan lain yang **tidak diubah** karena sudah aman:
- Seluruh query ke database sudah memakai PDO prepared statement.
- Password memakai `password_hash()` / `password_verify()` (hash seed di
  `buku_tamu.sql` berformat `$2b$10$...`, kompatibel dengan `password_verify()`).
- Validasi role per halaman (`cek_role()`) sudah konsisten di semua file admin/
  resepsionis/keamanan.
- Nama file upload/badge/identitas sudah divalidasi dengan whitelist regex di
  sisi server (bukan hanya di JavaScript).

## 4. File yang dihapus, dipindah, atau dipertahankan

| File | Tindakan | Alasan |
|------|----------|--------|
| `inc/logout.php` | **Dihapus** | Duplikat logout, nol referensi di seluruh proyek (dicek dengan grep). |
| Semua file lain | **Dipertahankan** | Masih direferensikan dan dipakai; tidak ditemukan file atau aset mati lain. Semua referensi `asset/css/*`, `asset/js/*` yang dipakai di kode PHP dicek cocok dengan file yang benar-benar ada di `asset/`. |

Tidak ditemukan kode SQL duplikat, fungsi duplikat antar file yang perlu
digabung di luar yang sudah memakai fungsi `e()` lokal per-file (fungsi ini
kecil dan dilindungi `function_exists()` sehingga aman dan tidak konflik).

## 5. Perubahan database

**Tidak ada migrasi destruktif yang dijalankan.** Struktur `buku_tamu.sql`
(tabel, foreign key, index, constraint unik pada `no_identitas`) sudah
konsisten dengan seluruh query PHP yang memakainya — tidak ditemukan kolom
yang dirujuk PHP tapi tidak ada di skema, atau sebaliknya.

Catatan untuk produksi (tidak diubah di file ini, perlu dilakukan manual saat
deploy): akun seed di `buku_tamu.sql` (`admin@kantor.com`, dst, password
`admin123`/`resepsionis123`/`satpam123`) **wajib diganti atau dihapus**
sebelum go-live — komentar ini sudah ada di file SQL aslinya.

## 6. Hasil pengujian

**Keterbatasan lingkungan:** sandbox audit ini tidak memiliki PHP CLI maupun
MySQL server terpasang, sehingga pengujian fungsional end-to-end (login
sungguhan, submit form, dsb.) **BELUM DIUJI** secara eksekusi nyata. Yang
dilakukan dan diverifikasi:

| Pemeriksaan | Status |
|---|---|
| Ekstraksi & audit seluruh file proyek | LULUS |
| Pencarian referensi aset (CSS/JS) vs file yang ada | LULUS — semua cocok |
| Pencarian referensi file PHP mati (`grep -r`) | LULUS — `inc/logout.php` ditemukan mati, sudah dihapus |
| Keseimbangan tag `<?php ... ?>` pada file yang diedit | LULUS (manual review per-file) |
| Validasi sintaks PHP otomatis (php -l) | BELUM DIUJI — PHP CLI tidak tersedia di sandbox ini |
| Pengujian fungsional login/CRUD/check-in/check-out terhadap MySQL nyata | BELUM DIUJI — perlu lingkungan lokal (XAMPP/Laragon) dengan `buku_tamu.sql` di-import |
| Smoke test setelah deploy ke server produksi | BELUM DIUJI — menunggu akses hosting |

**Tindakan yang disarankan sebelum dianggap selesai:** jalankan
`php -l` pada setiap file yang diedit (lihat daftar di bagian 7) di lingkungan
lokal, lalu uji manual checklist di bagian 8 di bawah.

## 7. Daftar lengkap file yang diubah

```
.htaccess                         (baru)
inc/.htaccess                     (baru)
inc/session_config.php            (baru)
uploads/.htaccess                 (baru)
LAPORAN_AUDIT.md                  (baru, laporan ini)
logout.php                        (diperbaiki — fence Markdown dihapus)
inc/logout.php                    (dihapus — duplikat mati)
inc/koneksi.php                   (diperbaiki — kebocoran info & fatal error)
inc/auth.php                      (diperbaiki — path login dinamis + helper CSRF)
inc/header.php                    (dibersihkan — dead code role, session_config)
resepsionis/tamu_masuk.php        (diperbaiki — sisa fence + token CSRF)
resepsionis/tamu_masuk_act.php    (ditambah verifikasi CSRF)
admin/tambahstaf.php              (ditambah token CSRF)
admin/tambahstaf_act.php          (ditambah verifikasi CSRF)
admin/editstaf.php                (ditambah token CSRF)
admin/editstaf_act.php            (ditambah verifikasi CSRF)
admin/list_staf.php               (ditambah token CSRF pada form hapus)
admin/del_staf.php                (ditambah verifikasi CSRF)
admin/tambahuser.php              (ditambah token CSRF)
admin/tambahuser_act.php          (ditambah verifikasi CSRF)
admin/edituser.php                (ditambah token CSRF)
admin/edituser_act.php            (ditambah verifikasi CSRF)
admin/list_user.php               (ditambah token CSRF pada form hapus)
admin/del_user.php                (ditambah verifikasi CSRF)
keamanan/edit_tamu.php            (ditambah token CSRF)
keamanan/edit_tamu_act.php        (ditambah verifikasi CSRF)
keamanan/index.php                (ditambah token CSRF pada form checkout)
keamanan/checkout_act.php         (ditambah verifikasi CSRF)
login.php                         (dikaitkan ke session_config.php)
login_act.php                     (session_regenerate_id + session_config.php)
index.php                         (dikaitkan ke session_config.php)
```

## 8. Checklist pengujian manual yang disarankan

Jalankan di lingkungan lokal (XAMPP/Laragon) setelah `buku_tamu.sql` di-import:

**Admin** — BELUM DIUJI (perlu eksekusi nyata): login/logout, dashboard,
tambah/edit/hapus staf (termasuk cek bahwa submit form tanpa token CSRF yang
valid ditolak), tambah/edit/hapus user, buka halaman admin tanpa login (harus
redirect ke login).

**Resepsionis** — BELUM DIUJI: login/logout, check-in tamu baru, validasi
badge yang sedang dipakai ditolak, filter & pencarian daftar tamu.

**Keamanan** — BELUM DIUJI: login/logout, daftar tamu di dalam gedung,
edit data tamu, check-out, cegah check-out ganda (klik dua kali cepat),
log harian per tanggal.

**Lintas-role** — BELUM DIUJI: akses halaman admin dari akun resepsionis
(harus 403), akses langsung ke `inc/*.php` lewat browser (harus diblokir oleh
`inc/.htaccess`), akses `buku_tamu.sql` lewat browser (harus diblokir oleh
`.htaccess` root).

## 9. Masalah yang belum selesai

- Validasi otomatis `php -l` belum dijalankan karena tidak ada PHP CLI di
  sandbox audit ini.
- Pengujian fungsional terhadap database MySQL sungguhan belum dilakukan
  (butuh lingkungan dengan MySQL/MariaDB berjalan).
- Deployment produksi **belum dijalankan** — menunggu akses hosting/server
  dari pengguna. Lihat bagian 10 untuk langkah yang perlu dilakukan saat itu.
- `asset/js/` hanya berisi `bootstrap.min.js` tanpa `init.js` yang disebut di
  `readme.md` lama — `readme.md` tidak diubah di audit ini karena bersifat
  dokumentasi, bukan kode; sebaiknya diperbarui saat dokumentasi proyek
  disinkronkan ulang (di luar lingkup perbaikan bug/keamanan audit ini).

## 10. Cara menjalankan proyek secara lokal

1. Salin folder `buku-tamu/` ke `htdocs/` (XAMPP) atau `www/` (Laragon).
2. Buat database dengan mengimpor `buku_tamu.sql` lewat phpMyAdmin atau:
   `mysql -u root -p < buku_tamu.sql`
3. Pastikan `inc/koneksi.php` cocok dengan kredensial lokal (default: host
   `localhost`, user `root`, password kosong — sudah sesuai default XAMPP).
   Untuk override tanpa mengubah kode, set environment variable `DB_HOST`,
   `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME` di web server.
4. Akses `http://localhost/buku-tamu/` lalu login dengan salah satu akun seed
   di `buku_tamu.sql` (ganti password-nya sebelum produksi).

## 11. Persyaratan & langkah deployment produksi

Belum dijalankan (menunggu akses hosting). Langkah yang perlu dilakukan saat
akses tersedia:

1. PHP ≥ 7.4 (direkomendasikan 8.x) dengan ekstensi `pdo_mysql`, `mbstring`.
2. MySQL/MariaDB dengan privilese untuk user aplikasi (bukan `root`) — buat
   user khusus dengan hak terbatas ke database `db_buku_tamu` saja.
3. Set environment variable `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`
   di konfigurasi hosting (bukan hardcode di kode).
4. Set `display_errors = Off` dan `log_errors = On` di `php.ini` produksi.
5. Aktifkan HTTPS; cookie session otomatis akan memakai flag `Secure` begitu
   `HTTPS` terdeteksi (lihat `inc/session_config.php`).
6. Pastikan modul Apache `mod_headers` aktif agar header keamanan di
   `.htaccess` berfungsi (atau sesuaikan untuk Nginx bila hosting berbeda).
7. Backup database sebelum migrasi apa pun.
8. Jalankan checklist pengujian manual (bagian 8) di staging sebelum go-live.
9. Ganti/hapus akun seed bawaan di `buku_tamu.sql`.

## 12. Langkah rollback

Karena semua perubahan bersifat non-destruktif terhadap data (tidak ada
migrasi skema yang dijalankan), rollback cukup dengan mengembalikan file kode
ke versi sebelumnya (backup proyek sebelum audit ini). Tidak ada perubahan
pada `buku_tamu.sql` yang memerlukan rollback skema.

## 13. Tindakan manual yang masih memerlukan akses/persetujuan pengguna

- Menjalankan `php -l` dan pengujian fungsional nyata di lingkungan dengan
  PHP + MySQL (tidak tersedia di sandbox audit ini).
- Mengganti kredensial database produksi dan akun seed bawaan.
- Menyediakan akses hosting untuk tahap deployment (bagian 11).
- Memutuskan apakah `inc/.htaccess` (deny all) perlu disesuaikan jika web
  server yang dipakai bukan Apache (butuh konfigurasi setara untuk Nginx/
  LiteSpeed).

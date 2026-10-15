<?php
// Letakkan kode ini di baris paling atas pada halaman yang butuh pengamanan login.
// Contoh penggunaan: require_once('../inc/auth.php'); cek_role(['keamanan']);

// Memanggil file konfigurasi sesi agar pengaturan session aman.
require_once(__DIR__ . '/session_config.php');

// Memulai sesi PHP jika sesi tersebut belum aktif.
if (session_status() === PHP_SESSION_NONE) session_start();

// Fungsi untuk menentukan alamat URL halaman login secara otomatis.
function login_url() {
    // Menghitung jalur URL login.php berdasarkan posisi folder proyek di dalam server (document root),
    // agar tidak kaku pada nama folder tertentu (tetap fleksibel jika dipindah ke subfolder atau domain utama).
    $projectRoot = dirname(__DIR__); // Mengambil folder utama proyek (induk dari folder inc/)
    $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $projectRootNormalized = str_replace('\\', '/', $projectRoot);

    // Jika folder proyek berada di dalam folder server utama, hitung sisa jalurnya.
    if ($documentRoot !== '' && strpos($projectRootNormalized, $documentRoot) === 0) {
        $webPath = substr($projectRootNormalized, strlen($documentRoot));
        return ($webPath !== '' ? $webPath : '') . '/login.php';
    }

    // Jika gagal dihitung, gunakan jalur bawaan (fallback) ke /login.php.
    return '/login.php';
}

// Fungsi untuk memeriksa apakah pengguna sudah login atau belum.
function cek_login() {
    // Jika data ID atau peran (role) di dalam sesi kosong, artinya pengguna belum login.
    if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
        // Alihkan (redirect) paksa ke halaman login.
        header('Location: ' . login_url());
        exit(); // Hentikan proses program selanjutnya.
    }
}

// Fungsi untuk memeriksa apakah pengguna memiliki hak akses (role) yang diizinkan membuka suatu halaman.
function cek_role(array $allowed_roles) {
    // Panggil fungsi cek_login dulu untuk memastikan pengguna sudah masuk.
    cek_login();
    // Jika peran pengguna saat ini tidak terdaftar di dalam daftar role yang diizinkan ($allowed_roles):
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Kirim status HTTP 403 (Akses Dilarang).
        http_response_code(403);
        // Hentikan program dan tampilkan pesan penolakan akses.
        die("Akses ditolak: role Anda (" . htmlspecialchars($_SESSION['role']) . ") tidak diizinkan membuka halaman ini.");
    }
}

// --- Kumpulan fungsi pembantu (helper) untuk keamanan CSRF (mencegah pengiriman data berbahaya dari luar). ---

// Fungsi untuk membuat atau mengambil token CSRF yang unik di dalam sesi.
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        // Membuat token acak yang aman sepanjang 32 byte dan diubah ke bentuk teks heksadesimal.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi untuk menyisipkan input tersembunyi (hidden input) berisi token CSRF ke dalam form HTML.
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

// Fungsi untuk mencocokkan token CSRF yang dikirimkan oleh pengguna dengan yang ada di sesi.
function csrf_verify($token) {
    // Menerima token hanya jika nilainya berupa teks dan sama dengan token sesi; hash_equals membandingkan dengan cara yang lebih aman terhadap pengukuran waktu.
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fungsi untuk memeriksa token CSRF pada data POST; jika tidak valid, tolak dan alihkan halaman.
function cek_csrf_atau_tolak($redirectTo) {
    $token = $_POST['csrf_token'] ?? '';
    // Jika verifikasi token gagal (tidak cocok atau kosong):
    if (!csrf_verify($token)) {
        // Simpan pesan peringatan ke sesi.
        $_SESSION['message'] = 'Permintaan tidak valid atau sudah kedaluwarsa. Silakan coba lagi.';
        // Alihkan kembali ke halaman tujuan yang ditentukan.
        header('Location: ' . $redirectTo);
        exit();
    }
}
?>
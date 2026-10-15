<?php
// Membuat sebuah kelas (class) bernama DatabaseConnection untuk mengelola koneksi database.
class DatabaseConnection {
    // Menyiapkan variabel (properti) untuk menyimpan informasi pengaturan server database.
    // Kredensial bisa diubah melalui environment variable (untuk server produksi),
    // atau menggunakan nilai bawaan (default) yang biasa dipakai untuk komputer lokal (XAMPP/Laragon).
    private $host;
    private $username;
    private $password;
    private $db_name;

    // Fungsi pembangun (constructor) yang otomatis dijalankan saat objek dari kelas ini dibuat.
    public function __construct() {
        // Mengambil nilai dari environment variable, atau pakai 'localhost' jika kosong.
        $this->host     = getenv('DB_HOST') ?: 'localhost';
        // Mengambil username database, atau pakai 'root' sebagai standar lokal.
        $this->username = getenv('DB_USERNAME') ?: 'root';
        // Mengambil password database, atau dikosongkan ('') untuk standar lokal.
        $this->password = getenv('DB_PASSWORD') ?: '';
        // Mengambil nama database, atau menggunakan 'db_buku_tamu' sebagai nama default.
        $this->db_name  = getenv('DB_NAME') ?: 'db_buku_tamu';
    }

    // Fungsi untuk menghubungkan PHP ke database MySQL dan mengembalikan objek koneksinya.
    public function getConnection() {
        // Blok try-catch digunakan untuk mencoba menghubungkan database dan menangani error jika gagal.
        try {
            // Membuat objek koneksi baru menggunakan PDO (PHP Data Objects) dengan setelan karakter utf8mb4.
            $conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            // Mengatur mode error PDO agar memunculkan Exception (peringatan kesalahan) jika query salah.
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Mengatur format hasil pengambilan data (fetch mode) default menjadi bentuk Array Asosiatif.
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Mengembalikan objek koneksi ($conn) agar bisa dipakai oleh file lain.
            return $conn;
        } catch (PDOException $e) {
            // Jika koneksi gagal, simpan pesan error asli ke log server (agar aman dari pengguna umum).
            error_log("Database connection failed: " . $e->getMessage());
            // Mengirimkan status kode HTTP 500 (Server Error).
            http_response_code(500);
            // Menghentikan program dan menampilkan pesan gangguan yang ramah kepada pengguna.
            die("Maaf, terjadi gangguan pada server. Silakan coba beberapa saat lagi atau hubungi administrator.");
        }
    }
}
?>
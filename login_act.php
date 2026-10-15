<?php
// Memanggil file konfigurasi sesi agar manajemen sesi berjalan aman.
require_once('inc/session_config.php');

// Memulai sesi PHP.
session_start();

// Memanggil file koneksi database untuk menghubungkan sistem ke database MySQL.
require_once('inc/koneksi.php');

// Membuat objek koneksi database baru dan mengambil objek koneksi PDO-nya.
$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Mengecek apakah tombol 'submit' pada form login telah ditekan atau belum.
if (isset($_POST['submit'])) {

    // Mengambil data email dari form, menghapus spasi di awal/akhir, lalu memvalidasi format emailnya.
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    // Mengambil data password dari form.
    $password = $_POST['password'] ?? '';

    // Jika format email kosong, tidak valid, atau password kosong:
    if (empty($email) || empty($password)) {
        // Simpan pesan peringatan ke dalam sesi.
        $_SESSION['message'] = "Harap isi email dan password dengan benar.";
        // Alihkan (redirect) kembali ke halaman login.
        header('Location: login.php');
        exit(); // Hentikan eksekusi program.
    }

    try {
        // Menyiapkan query SQL untuk mencari data pengguna berdasarkan alamat email (dibatasi 1 data).
        $query = "SELECT id, nama, email, password, role FROM tb_users WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($query);
        // Mengikat parameter email untuk mencegah celah keamanan SQL Injection.
        $stmt->bindParam(':email', $email);
        // Menjalankan query ke database.
        $stmt->execute();
        // Mengambil hasil data pengguna ke dalam bentuk array asosiatif.
        $user = $stmt->fetch();

        // Jika data pengguna ditemukan DAN password yang dimasukkan cocok dengan password terenkripsi di database:
        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil - memperbarui ID sesi (session_regenerate_id) untuk mencegah penculikan sesi (session fixation),
            // lalu menyimpan data penting pengguna ke dalam sesi.
            session_regenerate_id(true);

            $_SESSION['id']   = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            // Alihkan (redirect) ke halaman utama setelah berhasil login.
            header('Location: index.php');
            exit();
        } else {
            // Jika email tidak ditemukan atau password salah, berikan pesan error.
            $_SESSION['message'] = "Email atau password salah.";
            header('Location: login.php');
            exit();
        }

    } catch (PDOException $e) {
        // Jika terjadi kesalahan pada database, catat error ke log server demi keamanan.
        error_log("Login error: " . $e->getMessage());
        // Tampilkan pesan umum yang ramah kepada pengguna.
        $_SESSION['message'] = "Terjadi kesalahan pada server.";
        header('Location: login.php');
        exit();
    }
}

// Jika file diakses langsung tanpa melalui tombol submit form, kembalikan ke halaman login.
header('Location: login.php');
exit();
?>
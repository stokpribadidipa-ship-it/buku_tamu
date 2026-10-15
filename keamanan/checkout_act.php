<?php
// Memanggil file otentikasi agar halaman aman dari akses luar.
require_once('../inc/auth.php');
// Membatasi hak akses khusus untuk peran (role) keamanan (satpam).
cek_role(['keamanan']);

// Memanggil file koneksi database.
require_once('../inc/koneksi.php');

// Memastikan bahwa file ini hanya menerima permintaan data melalui metode POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Membuat objek koneksi database baru.
$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Mengambil dan memvalidasi ID tamu dari data POST agar bernilai angka bulat (integer).
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

// Jika ID tidak valid atau kurang dari 1, berikan pesan error lalu kembalikan ke halaman utama.
if ($id === false || $id === null || $id < 1) {
    $_SESSION['message'] = 'ID tamu tidak valid.';
    $_SESSION['message_type'] = 'danger';

    header('Location: index.php');
    exit();
}

// Memeriksa keabsahan token keamanan CSRF; jika gagal, batalkan proses.
cek_csrf_atau_tolak('index.php');

try {
    // Menyiapkan query untuk memastikan bahwa tamu dengan ID tersebut masih berstatus berada di dalam gedung ('di_dalam').
    $cek = $conn->prepare(
        "SELECT id
         FROM tb_tamu
         WHERE id = :id AND status = 'di_dalam'
         LIMIT 1"
    );

    $cek->execute([':id' => $id]);

    // Jika data tamu tidak ditemukan atau sudah terlanjur check-out, tolak proses.
    if (!$cek->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['message'] =
            'Tamu tidak ditemukan atau sudah check-out.';
        $_SESSION['message_type'] = 'warning';

        header('Location: index.php');
        exit();
    }

    // Menjalankan query untuk memperbarui status tamu menjadi sudah keluar, mencatat waktu keluar saat ini (NOW()),
    // serta merekam ID petugas keamanan yang memprosesnya.
    $query = "UPDATE tb_tamu
              SET waktu_checkout = NOW(),
                  status = 'sudah_keluar',
                  diproses_keluar_oleh = :uid
              WHERE id = :id
                AND status = 'di_dalam'";

    $stmt = $conn->prepare($query);

    $stmt->execute([
        ':uid' => $_SESSION['id'],
        ':id'  => $id
    ]);

    // Memeriksa apakah pembaruan data berhasil dilakukan.
    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = 'Tamu berhasil di-check-out.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] =
            'Check-out gagal atau data sudah berubah.';
        $_SESSION['message_type'] = 'warning';
    }

} catch (PDOException $e) {
    // Jika terjadi kesalahan pada database, catat ke log server demi keamanan.
    error_log('Checkout error: ' . $e->getMessage());

    $_SESSION['message'] =
        'Terjadi kesalahan saat memproses check-out.';
    $_SESSION['message_type'] = 'danger';
}

// Mengarahkan kembali halaman ke dashboard keamanan setelah selesai.
header('Location: index.php');
exit();
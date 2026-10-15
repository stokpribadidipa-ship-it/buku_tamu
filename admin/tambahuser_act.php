<?php
require_once('../inc/auth.php');
cek_role(['admin']);
require_once('../inc/koneksi.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tambahuser.php');
    exit;
}

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

cek_csrf_atau_tolak('tambahuser.php');

$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? '');

$allowed_roles = ['admin', 'resepsionis', 'keamanan'];

function kembaliTambahUser(string $pesan): void
{
    $_SESSION['message'] = $pesan;
    $_SESSION['old_user'] = [
        'nama' => trim($_POST['nama'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'role' => $_POST['role'] ?? 'resepsionis'
    ];

    header('Location: tambahuser.php');
    exit;
}

// Validasi nama
if (
    mb_strlen($nama, 'UTF-8') < 2 ||
    mb_strlen($nama, 'UTF-8') > 100 ||
    !preg_match("/\\A(?=.*\\p{L})[\\p{L}\\p{M} .'-]+\\z/u", $nama)
) {
    kembaliTambahUser(
        'Nama wajib 2–100 karakter dan harus mengandung huruf.'
    );
}

// Validasi email
if (
    $email === '' ||
    strlen($email) > 190 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    kembaliTambahUser('Format email tidak valid.');
}

// Validasi password
if (strlen($password) < 6 || strlen($password) > 72) {
    kembaliTambahUser('Password harus terdiri dari 6–72 karakter.');
}

// Validasi role
if (!in_array($role, $allowed_roles, true)) {
    kembaliTambahUser('Role yang dipilih tidak valid.');
}

try {
    // Cek email yang sudah terdaftar
    $cek = $conn->prepare(
        'SELECT id FROM tb_users WHERE email = :email LIMIT 1'
    );
    $cek->execute([':email' => $email]);

    if ($cek->fetch(PDO::FETCH_ASSOC)) {
        kembaliTambahUser('Email sudah digunakan. Gunakan email lain.');
    }

    // Simpan user
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        'INSERT INTO tb_users (nama, email, password, role)
         VALUES (:nama, :email, :password, :role)'
    );

    $stmt->execute([
        ':nama' => $nama,
        ':email' => $email,
        ':password' => $hash,
        ':role' => $role
    ]);

    unset($_SESSION['old_user']);

    $_SESSION['message'] = 'User berhasil ditambahkan.';

    header('Location: list_user.php');
    exit;

} catch (PDOException $e) {
    error_log('Tambah user gagal: ' . $e->getMessage());

    kembaliTambahUser(
        'Data gagal disimpan ke database. Periksa struktur tabel dan log error PHP.'
    );
}
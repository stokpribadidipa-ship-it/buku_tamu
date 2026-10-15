<?php
require_once('../inc/auth.php');
cek_role(['admin']);
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list_staf.php');
    exit();
}

cek_csrf_atau_tolak('list_staf.php');

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
$nama = trim($_POST['nama_staf'] ?? '');
$departemen = trim($_POST['departemen'] ?? '');

function kembaliKeDaftar(string $pesan): void
{
    $_SESSION['message'] = $pesan;
    header('Location: list_staf.php');
    exit();
}

function validasiTeksStaf(string $teks, string $pola): bool
{
    if (
        mb_strlen($teks, 'UTF-8') < 2 ||
        mb_strlen($teks, 'UTF-8') > 100
    ) {
        return false;
    }

    return preg_match($pola, $teks) === 1;
}

if (!$id || $id < 1) {
    kembaliKeDaftar('ID staf tidak valid.');
}

// Nama: huruf, tanda aksen, spasi, angka, titik, apostrof, dan tanda hubung.
$polaNama = "/\\A(?=.*\\p{L})[\\p{L}\\p{M}0-9 .'-]+\\z/u";

// Departemen juga dapat menggunakan &, misalnya Research & Development.
$polaDepartemen = "/\\A(?=.*\\p{L})[\\p{L}\\p{M}0-9 &.'-]+\\z/u";

if (!validasiTeksStaf($nama, $polaNama)) {
    kembaliKeDaftar(
        'Nama staf tidak valid. Gunakan 2–100 karakter dan wajib mengandung huruf.'
    );
}

if (!validasiTeksStaf($departemen, $polaDepartemen)) {
    kembaliKeDaftar(
        'Departemen tidak valid. Gunakan 2–100 karakter dan wajib mengandung huruf.'
    );
}

try {
    // Pastikan ID benar-benar ada.
    $cek = $conn->prepare(
        'SELECT id FROM tb_staf WHERE id = :id LIMIT 1'
    );
    $cek->bindValue(':id', $id, PDO::PARAM_INT);
    $cek->execute();

    if (!$cek->fetch(PDO::FETCH_ASSOC)) {
        kembaliKeDaftar('Data staf tidak ditemukan.');
    }

    $stmt = $conn->prepare(
        'UPDATE tb_staf
         SET nama_staf = :nama_staf,
             departemen = :departemen
         WHERE id = :id'
    );

    $stmt->bindValue(':nama_staf', $nama, PDO::PARAM_STR);
    $stmt->bindValue(':departemen', $departemen, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    kembaliKeDaftar('Data staf berhasil diperbarui.');

} catch (PDOException $e) {
    error_log('Update staf error: ' . $e->getMessage());
    kembaliKeDaftar(
        'Terjadi kesalahan saat memperbarui data staf. Silakan coba lagi.'
    );
}
?>
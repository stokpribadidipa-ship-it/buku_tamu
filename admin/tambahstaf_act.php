<?php
require_once('../inc/auth.php');
cek_role(['admin']);
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['submit'])
) {
    header('Location: tambahstaf.php');
    exit();
}

// Periksa token CSRF.
cek_csrf_atau_tolak('tambahstaf.php');

// Ambil input asli, jangan di-HTML-escape sebelum disimpan.
$nama_staf = trim($_POST['nama_staf'] ?? '');
$departemen = trim($_POST['departemen'] ?? '');

function gagalTambahStaf(string $pesan): void
{
    $_SESSION['message'] = $pesan;
    header('Location: tambahstaf.php');
    exit();
}

function validasiInputStaf(string $teks, string $pola): bool
{
    if (
        mb_strlen($teks, 'UTF-8') < 2 ||
        mb_strlen($teks, 'UTF-8') > 100
    ) {
        return false;
    }

    return preg_match($pola, $teks) === 1;
}

// Nama harus mengandung setidaknya satu huruf.
$polaNama = "/\\A(?=.*\\p{L})[\\p{L}\\p{M}0-9 .'-]+\\z/u";

// Departemen boleh berisi angka dan &, tetapi wajib mengandung huruf.
$polaDepartemen = "/\\A(?=.*\\p{L})[\\p{L}\\p{M}0-9 &.'-]+\\z/u";

if (!validasiInputStaf($nama_staf, $polaNama)) {
    gagalTambahStaf(
        'Nama staf tidak valid. Gunakan 2–100 karakter dan wajib mengandung huruf.'
    );
}

if (!validasiInputStaf($departemen, $polaDepartemen)) {
    gagalTambahStaf(
        'Departemen tidak valid. Gunakan 2–100 karakter dan wajib mengandung huruf.'
    );
}

try {
    $query = '
        INSERT INTO tb_staf (nama_staf, departemen)
        VALUES (:nama_staf, :departemen)
    ';

    $stmt = $conn->prepare($query);

    $stmt->bindValue(
        ':nama_staf',
        $nama_staf,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':departemen',
        $departemen,
        PDO::PARAM_STR
    );

    $stmt->execute();

    $_SESSION['message'] = 'Staf berhasil ditambahkan.';
    header('Location: list_staf.php');
    exit();

} catch (PDOException $e) {
    error_log('Insert staf error: ' . $e->getMessage());

    $_SESSION['message'] =
        'Terjadi kesalahan saat menambahkan staf. Silakan coba lagi.';

    header('Location: tambahstaf.php');
    exit();
}
?>
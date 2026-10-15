```php
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

if (!$id || $id < 1) {
    $_SESSION['message'] = 'ID staf tidak valid.';
    header('Location: list_staf.php');
    exit();
}

try {
    // Periksa apakah staf tersedia.
    $cek = $conn->prepare(
        'SELECT id FROM tb_staf WHERE id = :id LIMIT 1'
    );
    $cek->bindValue(':id', $id, PDO::PARAM_INT);
    $cek->execute();

    if (!$cek->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['message'] = 'Data staf tidak ditemukan.';
        header('Location: list_staf.php');
        exit();
    }

    // Prepared statement melindungi query dari SQL injection.
    $stmt = $conn->prepare(
        'DELETE FROM tb_staf WHERE id = :id'
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['message'] = $stmt->rowCount() > 0
        ? 'Data staf berhasil dihapus.'
        : 'Data staf tidak berhasil dihapus.';

} catch (PDOException $e) {
    error_log('Delete staf error: ' . $e->getMessage());

    // MySQL/MariaDB biasanya menggunakan SQLSTATE 23000
    // untuk pelanggaran constraint, termasuk foreign key.
    if ($e->getCode() === '23000') {
        $_SESSION['message'] =
            'Staf tidak dapat dihapus karena masih digunakan oleh data tamu atau riwayat terkait.';
    } else {
        $_SESSION['message'] =
            'Terjadi kesalahan saat menghapus data staf. Silakan coba lagi.';
    }
}

header('Location: list_staf.php');
exit();
?>
```
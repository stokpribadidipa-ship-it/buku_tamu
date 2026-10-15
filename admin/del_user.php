<?php
require_once('../inc/auth.php');
cek_role(['admin']);
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

if (isset($_POST['id'])) {
    cek_csrf_atau_tolak('list_user.php');

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    if (empty($id)) {
        $_SESSION['message'] = "ID user tidak valid.";
        header('Location: list_user.php');
        exit();
    }

    if ($id == $_SESSION['id']) {
        $_SESSION['message'] = "Tidak bisa menghapus akun Anda sendiri saat sedang login.";
        header('Location: list_user.php');
        exit();
    }

    try {
        $stmt = $conn->prepare("DELETE FROM tb_users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['message'] = "User berhasil dihapus.";

    } catch (PDOException $e) {
        // fk_tamu_dicatat_oleh pakai ON DELETE RESTRICT -> gagal kalau user pernah mencatat tamu
        error_log("Delete user error: " . $e->getMessage());
        $_SESSION['message'] = "Tidak bisa menghapus user ini karena masih ada riwayat tamu yang dicatat olehnya.";
    }
}

header('Location: list_user.php');
exit();
?>

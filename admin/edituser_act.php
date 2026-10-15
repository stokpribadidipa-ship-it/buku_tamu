<?php
require_once('../inc/auth.php');
cek_role(['admin']);
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

if (isset($_POST['submit'])) {
    cek_csrf_atau_tolak('list_user.php');

    $id       = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $nama     = trim($_POST['nama'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    $allowed_roles = ['admin', 'resepsionis', 'keamanan'];

    if (empty($id) || empty($nama) || empty($email) || !in_array($role, $allowed_roles)) {
        $_SESSION['message'] = "Harap isi semua kolom dengan benar.";
        header('Location: edituser.php?id=' . $id);
        exit();
    }

    if (!preg_match("/^[\p{L}\p{M} .'-]+$/u", $nama) || !preg_match('/\p{L}/u', $nama)) {
        $_SESSION['message'] = "Nama pengguna hanya boleh berisi huruf dan tanda baca umum, tanpa angka.";
        header('Location: edituser.php?id=' . $id);
        exit();
    }

    try {
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $_SESSION['message'] = "Password minimal 6 karakter.";
                header('Location: edituser.php?id=' . $id);
                exit();
            }
            $hash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]);
            $query = "UPDATE tb_users SET nama = :nama, email = :email, password = :password, role = :role WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':password', $hash);
        } else {
            $query = "UPDATE tb_users SET nama = :nama, email = :email, role = :role WHERE id = :id";
            $stmt = $conn->prepare($query);
        }

        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['message'] = "Data user berhasil diperbarui.";
        header('Location: list_user.php');
        exit();

    } catch (PDOException $e) {
        error_log("Update user error: " . $e->getMessage());
        $_SESSION['message'] = "Terjadi kesalahan pada server (mungkin email sudah dipakai).";
        header('Location: edituser.php?id=' . $id);
        exit();
    }
}

header('Location: list_user.php');
exit();
?>

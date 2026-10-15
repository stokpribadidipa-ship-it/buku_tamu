<?php
require_once('../inc/auth.php');
cek_role(['admin']);
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (empty($id)) { header('Location: list_user.php'); exit(); }

$stmt = $conn->prepare("SELECT id, nama, email, role FROM tb_users WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) { header('Location: list_user.php'); exit(); }

$base = '../';
$page_title = 'Edit User';
require_once('../inc/header.php');
?>

<h4 class="mb-3">Edit User</h4>

<?php if ($message !== ''): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="edituser_act.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <div class="mb-3">
                <label class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" minlength="2" maxlength="100"
                    pattern="[\p{L}\p{M} .'-]+" title="Nama hanya boleh berisi huruf dan tanda baca umum, tanpa angka."
                    value="<?= htmlspecialchars($user['nama']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                    required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="password" class="form-control" minlength="6"
                    placeholder="Kosongkan jika tidak ingin mengubah password">
            </div>
            <div class="mb-3">
                <label class="form-label">Role <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required>
                    <option value="resepsionis" <?= $user['role']==='resepsionis'?'selected':'' ?>>Resepsionis</option>
                    <option value="keamanan" <?= $user['role']==='keamanan'?'selected':'' ?>>Keamanan</option>
                    <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="submit" class="btn btn-dark">Simpan Perubahan</button>
                <a href="list_user.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once('../inc/footer.php'); ?>
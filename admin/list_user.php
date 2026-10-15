<?php
require_once('../inc/auth.php');
cek_role(['admin']);
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

$base = '../';
$page_title = 'Kelola User';

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$list_user = [];
$load_error = false;

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function roleLabel($role): string
{
    $roles = [
        'admin' => 'Admin',
        'keamanan' => 'Keamanan',
        'resepsionis' => 'Resepsionis',
    ];

    $role = strtolower(trim((string)$role));

    return $roles[$role] ?? ucwords(str_replace('_', ' ', $role));
}

function roleClass($role): string
{
    $roles = [
        'admin' => 'role-admin',
        'keamanan' => 'role-keamanan',
        'resepsionis' => 'role-resepsionis',
    ];

    return $roles[strtolower(trim((string)$role))] ?? 'role-default';
}

try {
    $query = $conn->query(
        "SELECT id, nama, email, role
         FROM tb_users
         ORDER BY nama ASC"
    );

    $list_user = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    error_log('Gagal memuat data pengguna: ' . $ex->getMessage());
    $load_error = true;
}

$currentUserId = $_SESSION['id'] ?? null;

require_once('../inc/header.php');
?>

<div class="user-page">

    <!-- Header halaman -->
    <div class="user-page-header">
        <div>
            <span class="user-eyebrow">ADMINISTRASI / PENGELOLAAN AKUN</span>
            <h1>Kelola User</h1>
            <p>Kelola akun pengguna dan hak akses sistem buku tamu.</p>
        </div>

        <a href="tambahuser.php" class="user-add-btn">
            <i class="fas fa-plus"></i>
            <span>Tambah User</span>
        </a>
    </div>

    <!-- Notifikasi -->
    <?php if ($message !== ''): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php endif; ?>

    <?php if ($load_error): ?>
    <div class="alert alert-danger" role="alert">
        Data pengguna gagal dimuat. Periksa koneksi database.
    </div>
    <?php else: ?>

    <!-- Statistik -->
    <div class="user-stats-grid">
        <div class="user-stat-card">
            <div class="user-stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <span>Total Pengguna</span>
                <strong><?= number_format(count($list_user), 0, ',', '.') ?></strong>
            </div>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon user-stat-admin">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <span>Admin</span>
                <strong>
                    <?php
                        echo count(array_filter($list_user, function ($user) {
                            return strtolower((string)($user['role'] ?? '')) === 'admin';
                        }));
                        ?>
                </strong>
            </div>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-icon user-stat-staff">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <span>Pengguna Non-admin</span>
                <strong>
                    <?php
                        echo count(array_filter($list_user, function ($user) {
                            return strtolower((string)($user['role'] ?? '')) !== 'admin';
                        }));
                        ?>
                </strong>
            </div>
        </div>
    </div>

    <!-- Panel daftar pengguna -->
    <section class="user-card">

        <div class="user-card-header">
            <div>
                <h2>
                    <i class="fas fa-address-book"></i>
                    Daftar Pengguna
                </h2>
                <p>Informasi akun, email, dan peran pengguna.</p>
            </div>

            <span class="user-count">
                <i class="fas fa-users"></i>
                <?= count($list_user) ?> pengguna
            </span>
        </div>

        <div class="table-responsive">
            <table class="table user-table mb-0">
                <thead>
                    <tr>
                        <th class="user-number">NO.</th>
                        <th>NAMA PENGGUNA</th>
                        <th>EMAIL</th>
                        <th>ROLE</th>
                        <th class="user-action-heading">AKSI</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($list_user)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="user-empty">
                                <i class="far fa-folder-open"></i>
                                <strong>Belum ada pengguna</strong>
                                <p>Tambahkan akun pengguna untuk mulai mengelola akses.</p>
                                <a href="tambahuser.php" class="user-add-btn">
                                    <i class="fas fa-plus"></i>
                                    Tambah User
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($list_user as $index => $u): ?>
                    <?php
                            $isCurrentUser = $currentUserId !== null
                                && (string)$u['id'] === (string)$currentUserId;
                            ?>
                    <tr>
                        <td class="user-number">
                            <?= $index + 1 ?>
                        </td>

                        <td>
                            <div class="user-name-cell">
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>

                                <div class="user-name-text">
                                    <strong><?= e($u['nama']) ?></strong>

                                    <?php if ($isCurrentUser): ?>
                                    <span class="user-current-label">Akun Anda</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="user-email">
                                <i class="far fa-envelope"></i>
                                <?= e($u['email']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="user-role <?= e(roleClass($u['role'] ?? '')) ?>">
                                <span class="user-role-dot"></span>
                                <?= e(roleLabel($u['role'] ?? '')) ?>
                            </span>
                        </td>

                        <td>
                            <div class="user-actions">
                                <a href="edituser.php?id=<?= rawurlencode((string)$u['id']) ?>" class="user-edit-btn"
                                    title="Edit pengguna" aria-label="Edit <?= e($u['nama']) ?>">
                                    <i class="fas fa-pen"></i>
                                    <span>Edit</span>
                                </a>

                                <?php if (!$isCurrentUser): ?>
                                <form action="del_user.php" method="POST" class="user-delete-form"
                                    data-user-name="<?= e($u['nama']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($u['id']) ?>">

                                    <button type="submit" class="user-delete-btn" title="Hapus pengguna"
                                        aria-label="Hapus <?= e($u['nama']) ?>">
                                        <i class="fas fa-trash-alt"></i>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="user-card-footer">
            <span>
                <i class="fas fa-info-circle"></i>
                Akun yang sedang digunakan tidak dapat dihapus dari halaman ini.
            </span>
            <span><?= count($list_user) ?> data ditampilkan</span>
        </div>
    </section>

    <?php endif; ?>
</div>

<!-- Modal konfirmasi hapus -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserTitle" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content user-delete-modal">
            <div class="modal-body">

                <div class="user-delete-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>

                <h2 id="deleteUserTitle">Hapus pengguna?</h2>

                <p>
                    Anda yakin ingin menghapus akun
                    <strong id="deleteUserName">pengguna ini</strong>?
                    Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="user-delete-modal-actions">
                    <button type="button" class="user-cancel-btn" data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="button" class="user-confirm-delete-btn" id="confirmDeleteUser">
                        Ya, Hapus
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('deleteUserModal');
    const nameElement = document.getElementById('deleteUserName');
    const confirmButton = document.getElementById('confirmDeleteUser');

    if (
        !modalElement ||
        !nameElement ||
        !confirmButton ||
        typeof bootstrap === 'undefined'
    ) {
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    let selectedForm = null;

    document.querySelectorAll('.user-delete-form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            selectedForm = form;
            nameElement.textContent = form.dataset.userName || 'pengguna ini';

            modal.show();
        });
    });

    confirmButton.addEventListener('click', function() {
        if (!selectedForm) {
            return;
        }

        confirmButton.disabled = true;
        selectedForm.submit();
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        selectedForm = null;
        confirmButton.disabled = false;
    });
});
</script>

<?php require_once('../inc/footer.php'); ?>
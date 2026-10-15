<?php
require_once('../inc/auth.php');
cek_role(['admin']);
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

$base = '../';
$page_title = 'Kelola Staf';

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$list_staf = [];
$load_error = false;

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    $stmt = $conn->query(
        "SELECT id, nama_staf, departemen
         FROM tb_staf
         ORDER BY nama_staf ASC"
    );

    $list_staf = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    error_log('Gagal memuat staf: ' . $ex->getMessage());
    $load_error = true;
}

require_once('../inc/header.php');
?>

<style>
/* ===== Halaman staf: tema hijau tua dan krem ===== */
.staff-page {
    max-width: 1400px;
    margin: 0 auto;
    color: #30382f;
}

.staff-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    margin-bottom: 25px;
}

.staff-eyebrow {
    display: block;
    margin-bottom: 8px;
    color: #8b8a7c;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
}

.staff-title {
    margin: 0 0 7px;
    font-family: 'Libre Baskerville', Georgia, serif;
    font-size: 27px;
}

.staff-subtitle {
    margin: 0;
    color: #858579;
    font-size: 13px;
}

.staff-page .btn {
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
}

.btn-olive {
    background: #303b30;
    border: 1px solid #303b30;
    color: #fffefa;
}

.btn-olive:hover {
    background: #414c3f;
    border-color: #414c3f;
    color: white;
}

.staff-stats {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 22px;
    padding: 20px 23px;
    background: #fffefa;
    border: 1px solid #e8e4da;
    border-radius: 7px;
}

.staff-stat-icon {
    display: grid;
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    place-items: center;
    background: #e9eee5;
    color: #40543c;
    border-radius: 7px;
    font-size: 20px;
}

.staff-stat-label {
    color: #858579;
    font-size: 11px;
}

.staff-stat-number {
    display: block;
    margin-top: 3px;
    color: #303b30;
    font-family: 'Libre Baskerville', Georgia, serif;
    font-size: 25px;
}

.staff-panel {
    overflow: hidden;
    background: #fffefa;
    border: 1px solid #e8e4da;
    border-radius: 7px;
    box-shadow: 0 3px 12px rgba(45, 51, 40, .04);
}

.staff-panel-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 20px 22px;
    border-bottom: 1px solid #e8e4da;
}

.staff-panel-heading h2 {
    margin: 0 0 5px;
    font-size: 15px;
    font-weight: 700;
}

.staff-panel-heading p {
    margin: 0;
    color: #89897e;
    font-size: 12px;
}

.staff-count {
    padding: 6px 10px;
    background: #eeeee6;
    border-radius: 5px;
    color: #555b4d;
    font-size: 11px;
    white-space: nowrap;
}

.staff-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 16px 22px;
}

.staff-search {
    position: relative;
    width: min(100%, 330px);
}

.staff-search i {
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    color: #8b8b7f;
}

.staff-search input {
    min-height: 39px;
    padding-left: 35px;
    border: 1px solid #e2dfd5;
    border-radius: 5px;
    font-size: 12px;
}

.staff-page .form-control:focus {
    border-color: #7e8d75;
    box-shadow: 0 0 0 3px rgba(76, 99, 69, .12);
}

.staff-table {
    min-width: 650px;
    margin: 0;
}

.staff-table thead th {
    padding: 14px 20px;
    background: #f0eee6;
    color: #717467;
    border-bottom: 1px solid #e5e1d6;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    white-space: nowrap;
}

.staff-table tbody td {
    padding: 15px 20px;
    border-color: #efede6;
    vertical-align: middle;
    font-size: 12px;
}

.staff-table tbody tr:hover {
    background: #faf9f4;
}

.staff-number {
    width: 65px;
    color: #929184;
}

.staff-name-wrap {
    display: flex;
    align-items: center;
    gap: 11px;
}

.staff-avatar {
    display: grid;
    width: 37px;
    height: 37px;
    flex-shrink: 0;
    place-items: center;
    background: #e9eee5;
    border-radius: 50%;
    color: #455b40;
}

.staff-name {
    font-weight: 700;
    color: #373e34;
}

.staff-department {
    display: inline-block;
    padding: 6px 10px;
    background: #f0eee6;
    border-radius: 4px;
    color: #65685b;
    font-size: 11px;
}

.staff-actions {
    display: flex;
    gap: 7px;
    align-items: center;
}

.staff-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 33px;
    padding: 0 10px;
    border: 1px solid;
    border-radius: 5px;
    background: transparent;
    font-size: 11px;
    font-weight: 600;
    transition: .2s;
}

.staff-edit-btn {
    border-color: #d9e2d3;
    background: #edf2e9;
    color: #425b3c;
}

.staff-edit-btn:hover {
    background: #dfe9d9;
}

.staff-delete-btn {
    border-color: #efd8d3;
    background: #fbefed;
    color: #ad4940;
}

.staff-delete-btn:hover {
    background: #f5dfdb;
}

.staff-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 22px;
    border-top: 1px solid #e8e4da;
    color: #89897e;
    font-size: 11px;
}

.staff-empty {
    padding: 45px 20px;
    text-align: center;
    color: #858579;
}

.staff-empty i {
    display: block;
    margin-bottom: 14px;
    color: #9b9b8c;
    font-size: 35px;
}

.staff-empty strong {
    display: block;
    margin-bottom: 6px;
    color: #42483d;
    font-size: 14px;
}

.staff-empty p {
    margin-bottom: 18px;
    font-size: 12px;
}

/* ===== Modal ===== */
.staff-modal .modal-content {
    overflow: hidden;
    border: 1px solid #e8e4da;
    border-radius: 10px;
    background: #fffefa;
    box-shadow: 0 18px 60px rgba(25, 32, 24, .17);
}

.staff-modal .modal-header {
    padding: 20px 23px;
    border-bottom: 1px solid #e8e4da;
}

.staff-modal .modal-title {
    font-family: 'Libre Baskerville', Georgia, serif;
    font-size: 18px;
}

.staff-modal .modal-header small {
    display: block;
    margin-top: 5px;
    color: #858579;
    font-size: 11px;
}

.staff-modal .modal-body {
    padding: 23px;
}

.staff-modal .modal-footer {
    padding: 15px 23px;
    background: #faf9f4;
    border-top: 1px solid #e8e4da;
}

.staff-modal .form-label {
    margin-bottom: 7px;
    color: #454a40;
    font-size: 12px;
    font-weight: 700;
}

.staff-modal .form-control {
    min-height: 43px;
    border-color: #deded3;
    border-radius: 5px;
    font-size: 13px;
}

.staff-modal .form-text {
    color: #89897e;
    font-size: 11px;
}

.staff-delete-symbol {
    display: grid;
    width: 62px;
    height: 62px;
    margin: 0 auto 17px;
    place-items: center;
    border-radius: 50%;
    background: #fae9e6;
    color: #bd4d42;
    font-size: 25px;
}

.staff-delete-title {
    margin-bottom: 10px;
    text-align: center;
    font-family: 'Libre Baskerville', Georgia, serif;
    font-size: 19px;
}

.staff-delete-description {
    margin-bottom: 18px;
    color: #77786d;
    text-align: center;
    font-size: 12px;
    line-height: 1.7;
}

.staff-delete-target {
    padding: 13px 15px;
    border: 1px solid #e8e4da;
    border-radius: 6px;
    background: #f6f4ed;
}

.staff-delete-target small {
    display: block;
    margin-bottom: 5px;
    color: #858579;
    font-size: 10px;
    letter-spacing: 1px;
}

.staff-delete-target strong {
    color: #303b30;
    font-size: 13px;
}

.staff-delete-target span {
    display: block;
    margin-top: 4px;
    color: #858579;
    font-size: 11px;
}

.staff-page .alert {
    font-size: 12px;
}

@media (max-width: 768px) {
    .staff-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .staff-title {
        font-size: 23px;
    }

    .staff-stats {
        padding: 16px;
    }

    .staff-panel-heading,
    .staff-toolbar,
    .staff-footer {
        padding-right: 15px;
        padding-left: 15px;
    }

    .staff-panel-heading {
        align-items: flex-start;
    }

    .staff-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .staff-search {
        width: 100%;
    }

    .staff-actions {
        flex-wrap: wrap;
    }

    .staff-action-btn span {
        display: none;
    }
}
</style>

<div class="staff-page">

    <div class="staff-heading">
        <div>
            <span class="staff-eyebrow">ADMINISTRASI / DATA MASTER</span>
            <h1 class="staff-title">Kelola Staf</h1>
            <p class="staff-subtitle">
                Kelola informasi staf dan departemen hotel.
            </p>
        </div>

        <a href="tambahstaf.php" class="btn btn-olive px-3 py-2">
            <i class="fas fa-plus me-2"></i>Tambah Staf
        </a>
    </div>

    <?php if ($message !== ''): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-2"></i><?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php endif; ?>

    <?php if ($load_error): ?>
    <div class="alert alert-danger">
        Data staf gagal dimuat. Periksa koneksi database.
    </div>
    <?php else: ?>

    <div class="staff-stats">
        <div class="staff-stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <span class="staff-stat-label">Total Staf Terdaftar</span>
            <strong class="staff-stat-number">
                <?= number_format(count($list_staf), 0, ',', '.') ?>
            </strong>
        </div>
    </div>

    <section class="staff-panel">
        <div class="staff-panel-heading">
            <div>
                <h2><i class="fas fa-address-card me-2"></i>Daftar Staf</h2>
                <p>Informasi nama dan departemen staf hotel.</p>
            </div>
            <span class="staff-count" id="staffCount">
                <?= count($list_staf) ?> staf
            </span>
        </div>

        <div class="staff-toolbar">
            <div class="staff-search">
                <i class="fas fa-search"></i>
                <input type="search" id="searchStaff" class="form-control" placeholder="Cari nama atau departemen..."
                    aria-label="Cari staf">
            </div>
            <small class="text-muted align-self-center">
                <i class="fas fa-sort-alpha-down me-1"></i>
                Diurutkan berdasarkan nama
            </small>
        </div>

        <div class="table-responsive">
            <table class="table staff-table">
                <thead>
                    <tr>
                        <th class="staff-number">NO.</th>
                        <th>NAMA STAF</th>
                        <th>DEPARTEMEN</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody id="staffTableBody">
                    <?php if (!$list_staf): ?>
                    <tr id="emptyStaffRow">
                        <td colspan="4">
                            <div class="staff-empty">
                                <i class="far fa-folder-open"></i>
                                <strong>Belum ada data staf</strong>
                                <p>Tambahkan staf untuk mulai mengelola daftar.</p>
                                <a href="tambahstaf.php" class="btn btn-olive">
                                    <i class="fas fa-plus me-1"></i>Tambah Staf
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($list_staf as $index => $s): ?>
                    <tr class="staff-row" data-name="<?= e($s['nama_staf']) ?>"
                        data-department="<?= e($s['departemen']) ?>">
                        <td class="staff-number"><?= $index + 1 ?></td>
                        <td>
                            <div class="staff-name-wrap">
                                <div class="staff-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <strong class="staff-name">
                                    <?= e($s['nama_staf']) ?>
                                </strong>
                            </div>
                        </td>
                        <td>
                            <span class="staff-department">
                                <?= e($s['departemen']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="staff-actions">
                                <button type="button" class="staff-action-btn staff-edit-btn btn-edit-staff"
                                    data-id="<?= (int)$s['id'] ?>" data-name="<?= e($s['nama_staf']) ?>"
                                    data-department="<?= e($s['departemen']) ?>"
                                    aria-label="Edit staf <?= e($s['nama_staf']) ?>">
                                    <i class="fas fa-pen"></i><span>Edit</span>
                                </button>

                                <button type="button" class="staff-action-btn staff-delete-btn btn-delete-staff"
                                    data-id="<?= (int)$s['id'] ?>" data-name="<?= e($s['nama_staf']) ?>"
                                    data-department="<?= e($s['departemen']) ?>"
                                    aria-label="Hapus staf <?= e($s['nama_staf']) ?>">
                                    <i class="fas fa-trash-alt"></i><span>Hapus</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="noSearchResult" hidden>
                        <td colspan="4">
                            <div class="staff-empty">
                                <i class="fas fa-search"></i>
                                <strong>Staf tidak ditemukan</strong>
                                <p>Coba gunakan kata kunci lain.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="staff-footer">
            <span><i class="fas fa-info-circle me-1"></i>
                Data staf terdaftar dalam sistem.
            </span>
            <span id="staffFooterCount">
                <?= count($list_staf) ?> data
            </span>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- Modal EDIT -->
<div class="modal fade staff-modal" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="editStaffForm" action="editstaf_act.php" method="POST">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="editStaffModalTitle">
                        <i class="fas fa-user-edit me-2"></i>Edit Staf
                    </h2>
                    <small>Perbarui informasi staf dengan benar.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editStaffId">

                <div class="mb-3">
                    <label for="editStaffName" class="form-label">
                        Nama Staf <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="editStaffName" name="nama_staf" minlength="2"
                        maxlength="100" pattern="(?=.*[A-Za-zÀ-ÖØ-öø-ÿ])[A-Za-zÀ-ÖØ-öø-ÿ0-9 .'-]+"
                        title="Nama harus mengandung huruf, bukan angka saja." autocomplete="off" required>
                    <div class="form-text">
                        Nama wajib mengandung huruf. Contoh: Andi Wijaya.
                    </div>
                </div>

                <div class="mb-1">
                    <label for="editStaffDepartment" class="form-label">
                        Departemen <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="editStaffDepartment" name="departemen" minlength="2"
                        maxlength="100" pattern="(?=.*[A-Za-zÀ-ÖØ-öø-ÿ])[A-Za-zÀ-ÖØ-öø-ÿ0-9 &.'-]+"
                        title="Departemen harus mengandung huruf." required>
                    <div class="form-text">
                        Contoh: Keuangan, IT Support, atau RPL 2.
                    </div>
                </div>

                <div class="alert alert-danger mt-3 mb-0 d-none" id="editStaffError" role="alert"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-olive" id="saveStaffButton">
                    <i class="fas fa-save me-1"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal KONFIRMASI HAPUS -->
<div class="modal fade staff-modal" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteStaffModalTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body p-4">
                <div class="staff-delete-symbol">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h2 class="staff-delete-title" id="deleteStaffModalTitle">
                    Hapus data staf?
                </h2>
                <p class="staff-delete-description">
                    Apakah kamu yakin ingin menghapus staf ini? Tindakan ini
                    tidak dapat dibatalkan.
                </p>

                <div class="staff-delete-target mb-4">
                    <small>STAF YANG DIPILIH</small>
                    <strong id="deleteStaffName">-</strong>
                    <span id="deleteStaffDepartment">-</span>
                </div>

                <form action="del_staf.php" method="POST" id="deleteStaffForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="deleteStaffId">

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-fill"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger flex-fill" id="confirmDeleteStaff">
                            <i class="fas fa-trash-alt me-1"></i>Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan Bootstrap JS sudah dimuat oleh inc/footer.php.
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JavaScript belum dimuat.');
        return;
    }

    const editModal = new bootstrap.Modal(
        document.getElementById('editStaffModal')
    );
    const deleteModal = new bootstrap.Modal(
        document.getElementById('deleteStaffModal')
    );

    const editForm = document.getElementById('editStaffForm');
    const editError = document.getElementById('editStaffError');
    const saveButton = document.getElementById('saveStaffButton');
    const deleteForm = document.getElementById('deleteStaffForm');
    const confirmDeleteButton = document.getElementById('confirmDeleteStaff');

    const nameInput = document.getElementById('editStaffName');
    const departmentInput = document.getElementById('editStaffDepartment');

    // Cari staf berdasarkan nama atau departemen.
    const searchInput = document.getElementById('searchStaff');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const keyword = this.value.trim().toLocaleLowerCase('id');
            const rows = [...document.querySelectorAll('.staff-row')];
            let visible = 0;

            rows.forEach(function(row) {
                const text = (
                    (row.dataset.name || '') + ' ' +
                    (row.dataset.department || '')
                ).toLocaleLowerCase('id');

                const match = text.includes(keyword);
                row.hidden = !match;

                if (match) visible++;
            });

            const noResult = document.getElementById('noSearchResult');
            if (noResult) noResult.hidden = visible !== 0;

            document.getElementById('staffFooterCount').textContent =
                visible + ' data ditampilkan';
        });
    }

    // Buka modal edit dan isi form menggunakan data tombol.
    document.querySelectorAll('.btn-edit-staff').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('editStaffId').value = this.dataset.id;
            nameInput.value = this.dataset.name || '';
            departmentInput.value = this.dataset.department || '';

            editError.textContent = '';
            editError.classList.add('d-none');
            editModal.show();
        });
    });

    // Validasi awal di browser; validasi server tetap wajib.
    const adaHuruf = /[A-Za-zÀ-ÖØ-öø-ÿ]/;

    editForm.addEventListener('submit', function(event) {
        const nama = nameInput.value.trim();
        const departemen = departmentInput.value.trim();

        if (!adaHuruf.test(nama) || !adaHuruf.test(departemen)) {
            event.preventDefault();

            editError.textContent =
                'Nama staf dan departemen wajib mengandung huruf.';
            editError.classList.remove('d-none');
            return;
        }

        nameInput.value = nama;
        departmentInput.value = departemen;

        saveButton.disabled = true;
        saveButton.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
    });

    // Buka modal hapus dan tampilkan staf yang dipilih.
    document.querySelectorAll('.btn-delete-staff').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('deleteStaffId').value = this.dataset.id;
            document.getElementById('deleteStaffName').textContent =
                this.dataset.name || 'Staf ini';
            document.getElementById('deleteStaffDepartment').textContent =
                this.dataset.department || '-';

            deleteModal.show();
        });
    });

    // Penghapusan hanya berjalan setelah tombol konfirmasi ditekan.
    deleteForm.addEventListener('submit', function() {
        confirmDeleteButton.disabled = true;
        confirmDeleteButton.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span>Menghapus...';
    });

    // Reset tombol bila pengguna menutup modal edit.
    document.getElementById('editStaffModal')
        .addEventListener('hidden.bs.modal', function() {
            saveButton.disabled = false;
            saveButton.innerHTML =
                '<i class="fas fa-save me-1"></i>Simpan Perubahan';
            editError.classList.add('d-none');
        });

    document.getElementById('deleteStaffModal')
        .addEventListener('hidden.bs.modal', function() {
            confirmDeleteButton.disabled = false;
            confirmDeleteButton.innerHTML =
                '<i class="fas fa-trash-alt me-1"></i>Hapus';
        });
});
</script>

<?php require_once('../inc/footer.php'); ?>
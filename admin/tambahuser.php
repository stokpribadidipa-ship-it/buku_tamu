<?php
require_once('../inc/auth.php');
cek_role(['admin']);

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$old = $_SESSION['old_user'] ?? [];
unset($_SESSION['old_user']);

$nama = $old['nama'] ?? '';
$email = $old['email'] ?? '';
$role = $old['role'] ?? 'resepsionis';

$base = '../';
$page_title = 'Tambah User';

require_once('../inc/header.php');
?>

<style>
.user-page {
    color: #30382f;
}

.user-heading {
    margin-bottom: 24px;
}

.user-heading h1 {
    font-size: 27px;
    font-weight: 700;
    color: #344735;
    margin-bottom: 6px;
}

.user-heading p {
    color: #777b72;
    font-size: 14px;
    margin: 0;
}

.user-card {
    background: #fff;
    border: 1px solid #e9e6dc;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 5px 18px rgba(40, 52, 40, .06);
}

.user-card-header {
    background: #f8f7f0;
    padding: 22px 26px;
    border-bottom: 1px solid #e9e6dc;
}

.user-card-header h2 {
    font-size: 18px;
    font-weight: 700;
    color: #344735;
    margin: 0 0 5px;
}

.user-card-header p {
    font-size: 13px;
    color: #777b72;
    margin: 0;
}

.user-card-body {
    padding: 26px;
}

.user-page .form-label {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 8px;
}

.user-page .form-control,
.user-page .form-select {
    min-height: 46px;
    border: 1px solid #dcded5;
    border-radius: 9px;
    padding: 10px 13px;
}

.user-page .form-control:focus,
.user-page .form-select:focus {
    border-color: #455b43;
    box-shadow: 0 0 0 3px rgba(53, 76, 51, .12);
}

.user-page .is-invalid {
    border-color: #bd5145 !important;
    background-image: none !important;
}

.user-error {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 16px;
    margin-bottom: 22px;
    border: 1px solid #f0c9c1;
    border-left: 4px solid #bd5145;
    border-radius: 10px;
    background: #fff5f2;
    color: #873b33;
}

.user-error strong {
    display: block;
    margin-bottom: 4px;
}

.user-error p {
    margin: 0;
    font-size: 13px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.user-field-error {
    margin-top: 7px;
    color: #b44439;
    font-size: 12px;
}

.user-help {
    display: block;
    color: #85887e;
    font-size: 12px;
    margin-top: 7px;
}

.user-btn-primary {
    color: #fff;
    background: #344735;
    border: 1px solid #344735;
    border-radius: 9px;
    padding: 11px 18px;
    font-weight: 600;
}

.user-btn-primary:hover {
    background: #263727;
    border-color: #263727;
    color: #fff;
}

.user-btn-secondary {
    border: 1px solid #d8d9d1;
    border-radius: 9px;
    padding: 11px 18px;
    background: #fff;
    color: #555b53;
}

.user-btn-secondary:hover {
    background: #f5f4ed;
    color: #30382f;
}

@media (max-width: 576px) {

    .user-card-header,
    .user-card-body {
        padding: 20px;
    }

    .user-heading h1 {
        font-size: 23px;
    }

    .user-actions {
        flex-direction: column;
    }

    .user-actions .btn {
        width: 100%;
    }
}
</style>

<div class="user-page">
    <div class="user-heading">
        <h1><i class="bi bi-person-plus me-2"></i>Tambah User</h1>
        <p>Buat akun baru dan tentukan hak aksesnya.</p>
    </div>

    <?php if ($message !== ''): ?>
    <div class="user-error" role="alert">
        <i class="bi bi-exclamation-circle-fill fs-5"></i>
        <div class="flex-grow-1">
            <strong>Data belum berhasil disimpan</strong>
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <button type="button" class="btn-close" aria-label="Tutup"
            onclick="this.closest('.user-error').remove()"></button>
    </div>
    <?php endif; ?>

    <div class="user-card">
        <div class="user-card-header">
            <h2><i class="bi bi-person-vcard me-2"></i>Informasi Akun</h2>
            <p>Kolom bertanda bintang (*) wajib diisi.</p>
        </div>

        <div class="user-card-body">
            <form action="tambahuser_act.php" method="POST" id="formTambahUser" novalidate>

                <?= csrf_field() ?>

                <div class="mb-4">
                    <label for="nama" class="form-label">
                        Nama <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nama" id="nama" class="form-control"
                        value="<?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Andi Wijaya"
                        minlength="2" maxlength="100" autocomplete="name" required>
                    <small class="user-help">
                        Nama harus mengandung huruf dan terdiri dari 2–100 karakter.
                    </small>
                </div>

                <div class="mb-4">
                    <label for="email" class="form-label">
                        Email <span class="text-danger">*</span>
                    </label>
                    <input type="email" name="email" id="email" class="form-control"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="nama@email.com"
                        maxlength="190" autocomplete="email" required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">
                        Password <span class="text-danger">*</span>
                    </label>
                    <input type="password" name="password" id="password" class="form-control" minlength="6"
                        maxlength="72" autocomplete="new-password" placeholder="Minimal 6 karakter" required>
                    <small class="user-help">
                        Gunakan minimal 6 karakter.
                    </small>
                </div>

                <div class="mb-4">
                    <label for="role" class="form-label">
                        Role <span class="text-danger">*</span>
                    </label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="resepsionis" <?= $role === 'resepsionis' ? 'selected' : '' ?>>
                            Resepsionis
                        </option>
                        <option value="keamanan" <?= $role === 'keamanan' ? 'selected' : '' ?>>
                            Keamanan
                        </option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>
                            Admin
                        </option>
                    </select>
                </div>

                <div class="d-flex gap-2 user-actions">
                    <button type="submit" name="submit" value="1" class="btn user-btn-primary" id="btnSimpan">
                        <i class="bi bi-check2-circle me-1"></i>Simpan User
                    </button>

                    <a href="list_user.php" class="btn user-btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formTambahUser');
    const nama = document.getElementById('nama');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const tombol = document.getElementById('btnSimpan');

    const polaNama = /^(?=.*\p{L})[\p{L}\p{M} .'-]+$/u;

    function error(input, pesan) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        let elemen = input.parentElement.querySelector('.user-field-error');

        if (!elemen) {
            elemen = document.createElement('div');
            elemen.className = 'user-field-error';
            elemen.setAttribute('role', 'alert');
            input.insertAdjacentElement('afterend', elemen);
        }

        elemen.textContent = pesan;
    }

    function bersihkan(input) {
        input.classList.remove('is-invalid', 'is-valid');
        const elemen = input.parentElement.querySelector('.user-field-error');
        if (elemen) elemen.remove();
    }

    function validasiNama() {
        bersihkan(nama);
        const nilai = nama.value.trim();

        if (!nilai) {
            error(nama, 'Nama wajib diisi.');
            return false;
        }

        if (nilai.length < 2 || nilai.length > 100 || !polaNama.test(nilai)) {
            error(nama, 'Masukkan nama yang valid, 2–100 karakter, dan mengandung huruf.');
            return false;
        }

        nama.classList.add('is-valid');
        return true;
    }

    function validasiEmail() {
        bersihkan(email);
        const nilai = email.value.trim();
        const polaEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!nilai) {
            error(email, 'Email wajib diisi.');
            return false;
        }

        if (nilai.length > 190 || !polaEmail.test(nilai)) {
            error(email, 'Format email belum benar.');
            return false;
        }

        email.classList.add('is-valid');
        return true;
    }

    function validasiPassword() {
        bersihkan(password);

        if (password.value.length < 6 || password.value.length > 72) {
            error(password, 'Password harus terdiri dari 6–72 karakter.');
            return false;
        }

        password.classList.add('is-valid');
        return true;
    }

    nama.addEventListener('blur', validasiNama);
    email.addEventListener('blur', validasiEmail);
    password.addEventListener('blur', validasiPassword);

    [nama, email, password].forEach(function(input) {
        input.addEventListener('input', function() {
            if (input.classList.contains('is-invalid')) {
                if (input === nama) validasiNama();
                if (input === email) validasiEmail();
                if (input === password) validasiPassword();
            }
        });
    });

    form.addEventListener('submit', function(event) {
        const validNama = validasiNama();
        const validEmail = validasiEmail();
        const validPassword = validasiPassword();

        if (!validNama || !validEmail || !validPassword) {
            event.preventDefault();

            const pertama = form.querySelector('.is-invalid');
            if (pertama) pertama.focus();
            return;
        }

        tombol.disabled = true;
        tombol.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Menyimpan...';
    });
});
</script>

<?php require_once('../inc/footer.php'); ?>
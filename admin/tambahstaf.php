<?php
require_once('../inc/auth.php');
cek_role(['admin']);

// Ambil notifikasi dan input lama sebelum dirender.
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$base = '../';
$page_title = 'Tambah Staf';

require_once('../inc/header.php');
?>

<style>
.staff-form-page {
    color: #30382f;
}

.staff-form-heading {
    margin-bottom: 24px;
}

.staff-form-heading h1 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 6px;
    color: #344735;
}

.staff-form-heading p {
    color: #777b72;
    margin: 0;
    font-size: 14px;
}

.staff-form-card {
    background: #fff;
    border: 1px solid #e9e6dc;
    border-radius: 14px;
    box-shadow: 0 5px 18px rgba(40, 52, 40, .06);
    overflow: hidden;
}

.staff-form-card-header {
    padding: 22px 26px;
    background: #f8f7f0;
    border-bottom: 1px solid #e9e6dc;
}

.staff-form-card-header h2 {
    font-size: 18px;
    font-weight: 700;
    color: #344735;
    margin: 0 0 5px;
}

.staff-form-card-header p {
    color: #777b72;
    font-size: 13px;
    margin: 0;
}

.staff-form-card-body {
    padding: 28px 26px;
}

.staff-form-page .form-label {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 9px;
    color: #343b32;
}

.staff-form-page .form-control {
    min-height: 46px;
    border: 1px solid #dcded5;
    border-radius: 9px;
    padding: 11px 14px;
    background-color: #fff;
    color: #30382f;
    transition: border-color .2s, box-shadow .2s;
}

.staff-form-page .form-control::placeholder {
    color: #a0a398;
}

.staff-form-page .form-control:focus {
    border-color: #455b43;
    box-shadow: 0 0 0 3px rgba(53, 76, 51, .12);
}

/* Input salah */
.staff-form-page .form-control.is-invalid {
    border-color: #bd5145;
    background-image: none;
    padding-right: 14px;
}

.staff-form-page .form-control.is-invalid:focus {
    border-color: #bd5145;
    box-shadow: 0 0 0 3px rgba(189, 81, 69, .12);
}

/* Input benar */
.staff-form-page .form-control.is-valid {
    border-color: #668568;
    background-image: none;
    padding-right: 14px;
}

.staff-form-help {
    display: block;
    margin-top: 8px;
    color: #7c8078;
    font-size: 12px;
    line-height: 1.6;
}

/* Pesan validasi di bawah input */
.staff-field-error {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    margin-top: 8px;
    color: #b44439;
    font-size: 12px;
    line-height: 1.6;
}

.staff-field-error i {
    margin-top: 2px;
}

/* Notifikasi server */
.staff-error-alert {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    padding: 17px 18px;
    margin-bottom: 22px;
    background: #fff5f2;
    border: 1px solid #f0c9c1;
    border-left: 4px solid #bd5145;
    border-radius: 10px;
    color: #873b33;
    box-shadow: 0 3px 10px rgba(80, 40, 30, .04);
    animation: errorSlideIn .25s ease-out;
}

.staff-error-icon {
    font-size: 21px;
    line-height: 1.4;
    color: #bd5145;
}

.staff-error-content {
    flex: 1;
    min-width: 0;
}

.staff-error-content strong {
    display: block;
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 700;
}

.staff-error-content p {
    margin: 0;
    font-size: 13px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.staff-error-close {
    background: transparent;
    border: 0;
    color: #873b33;
    padding: 3px 5px;
    cursor: pointer;
    opacity: .7;
}

.staff-error-close:hover {
    opacity: 1;
}

/* Tombol */
.staff-btn-primary {
    background: #344735;
    border: 1px solid #344735;
    color: #fff;
    border-radius: 9px;
    padding: 11px 18px;
    font-weight: 600;
    transition: background .2s;
}

.staff-btn-primary:hover {
    background: #263727;
    border-color: #263727;
    color: #fff;
}

.staff-btn-secondary {
    background: #fff;
    color: #555b53;
    border: 1px solid #d8d9d1;
    border-radius: 9px;
    padding: 11px 18px;
    font-weight: 500;
}

.staff-btn-secondary:hover {
    background: #f5f4ed;
    color: #30382f;
}

@keyframes errorSlideIn {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 576px) {

    .staff-form-card-body,
    .staff-form-card-header {
        padding: 20px;
    }

    .staff-form-heading h1 {
        font-size: 24px;
    }

    .staff-form-actions {
        display: flex;
        flex-direction: column;
    }

    .staff-form-actions .btn {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .staff-error-alert {
        animation: none;
    }
}
</style>

<div class="staff-form-page">

    <div class="staff-form-heading">
        <h1>
            <i class="bi bi-person-plus me-2"></i>
            Tambah Staf
        </h1>
        <p>Tambahkan informasi staf dan departemen hotel.</p>
    </div>

    <?php if ($message !== ''): ?>
    <div class="staff-error-alert" role="alert">
        <div class="staff-error-icon">
            <i class="bi bi-exclamation-circle-fill"></i>
        </div>

        <div class="staff-error-content">
            <strong>Input belum berhasil diproses</strong>
            <p><?= htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></p>
        </div>

        <button type="button" class="staff-error-close" aria-label="Tutup" onclick="this.parentElement.remove()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <?php endif; ?>

    <div class="staff-form-card">

        <div class="staff-form-card-header">
            <h2>
                <i class="bi bi-card-list me-2"></i>
                Informasi Staf
            </h2>
            <p>Lengkapi semua kolom yang bertanda bintang (*).</p>
        </div>

        <div class="staff-form-card-body">

            <form action="tambahstaf_act.php" method="POST" id="formTambahStaf" novalidate>

                <?= csrf_field() ?>

                <div class="mb-4">
                    <label for="nama_staf" class="form-label">
                        Nama Staf <span class="text-danger">*</span>
                    </label>

                    <input type="text" name="nama_staf" id="nama_staf" class="form-control"
                        placeholder="Contoh: Andi Wijaya" value="<?= htmlspecialchars(
                            $old_input['nama_staf'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>" minlength="2" maxlength="100" autocomplete="name" aria-describedby="namaHelp" required>

                    <small id="namaHelp" class="staff-form-help">
                        Masukkan nama yang mengandung huruf.
                        Angka saja tidak diperbolehkan.
                    </small>
                </div>

                <div class="mb-4">
                    <label for="departemen" class="form-label">
                        Departemen <span class="text-danger">*</span>
                    </label>

                    <input type="text" name="departemen" id="departemen" class="form-control"
                        placeholder="Contoh: IT Support" value="<?= htmlspecialchars(
                            $old_input['departemen'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>" minlength="2" maxlength="100" aria-describedby="departemenHelp" required>

                    <small id="departemenHelp" class="staff-form-help">
                        Contoh: Keuangan, IT Support, RPL 2,
                        atau Research &amp; Development.
                    </small>
                </div>

                <div class="d-flex gap-2 staff-form-actions">
                    <button type="submit" name="submit" value="1" class="btn staff-btn-primary" id="btnSimpan">

                        <i class="bi bi-check2-circle me-1"></i>
                        Simpan Staf
                    </button>

                    <a href="list_staf.php" class="btn staff-btn-secondary">

                        <i class="bi bi-arrow-left me-1"></i>
                        Batal
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formTambahStaf');
    const nama = document.getElementById('nama_staf');
    const departemen = document.getElementById('departemen');
    const tombolSimpan = document.getElementById('btnSimpan');

    /*
     * Karakter yang diperbolehkan harus sama
     * dengan validasi PHP di server.
     */
    const polaNama =
        /^(?=.*\p{L})[\p{L}\p{M}0-9 .'-]+$/u;

    const polaDepartemen =
        /^(?=.*\p{L})[\p{L}\p{M}0-9 &.'-]+$/u;

    function tampilkanError(input, pesan) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        input.setAttribute('aria-invalid', 'true');

        let error = input.parentElement.querySelector(
            '.staff-field-error'
        );

        if (!error) {
            error = document.createElement('div');
            error.className = 'staff-field-error';
            error.setAttribute('role', 'alert');

            input.insertAdjacentElement('afterend', error);
        }

        error.replaceChildren();

        const ikon = document.createElement('i');
        ikon.className = 'bi bi-exclamation-circle-fill';

        const teks = document.createElement('span');
        teks.textContent = pesan;

        error.append(ikon, teks);
    }

    function hapusError(input) {
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');

        const error = input.parentElement.querySelector(
            '.staff-field-error'
        );

        if (error) {
            error.remove();
        }
    }

    function validasi(input, pola, label) {
        const nilai = input.value.trim();

        hapusError(input);

        if (nilai.length === 0) {
            tampilkanError(
                input,
                label + ' wajib diisi.'
            );
            return false;
        }

        if (nilai.length < 2 || nilai.length > 100) {
            tampilkanError(
                input,
                label + ' harus terdiri dari 2–100 karakter.'
            );
            return false;
        }

        if (!pola.test(nilai)) {
            tampilkanError(
                input,
                label +
                ' harus mengandung huruf dan tidak boleh berisi karakter yang tidak diizinkan.'
            );
            return false;
        }

        input.classList.add('is-valid');
        return true;
    }

    // Validasi ketika pengguna meninggalkan kolom.
    nama.addEventListener('blur', function() {
        validasi(nama, polaNama, 'Nama staf');
    });

    departemen.addEventListener('blur', function() {
        validasi(
            departemen,
            polaDepartemen,
            'Departemen'
        );
    });

    // Perbarui pesan saat pengguna memperbaiki input.
    nama.addEventListener('input', function() {
        if (nama.classList.contains('is-invalid')) {
            validasi(nama, polaNama, 'Nama staf');
        }
    });

    departemen.addEventListener('input', function() {
        if (departemen.classList.contains('is-invalid')) {
            validasi(
                departemen,
                polaDepartemen,
                'Departemen'
            );
        }
    });

    form.addEventListener('submit', function(event) {
        const namaValid = validasi(
            nama,
            polaNama,
            'Nama staf'
        );

        const departemenValid = validasi(
            departemen,
            polaDepartemen,
            'Departemen'
        );

        if (!namaValid || !departemenValid) {
            event.preventDefault();

            if (!namaValid) {
                nama.focus();
            } else {
                departemen.focus();
            }

            return;
        }

        // Cegah klik berulang setelah validasi berhasil.
        tombolSimpan.disabled = true;
        tombolSimpan.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' +
            'Menyimpan...';
    });
});
</script>

<?php require_once('../inc/footer.php'); ?>
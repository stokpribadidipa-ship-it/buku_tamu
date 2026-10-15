<?php
// Memanggil file otentikasi dan membatasi akses khusus untuk resepsionis.
require_once('../inc/auth.php');
cek_role(['resepsionis']);
// Memanggil file koneksi database.
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Mengambil daftar data staf dari database untuk ditampilkan sebagai pilihan pada form.
$stmt = $conn->query(
    "SELECT id, nama_staf, departemen
     FROM tb_staf
     ORDER BY nama_staf ASC"
);
$list_staf = $stmt->fetchAll(PDO::FETCH_ASSOC);

$base = '../';
$page_title = 'Check-in Tamu';
// Menangkap pesan pemberitahuan dari sesi jika ada.
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Memanggil file header web.
require_once('../inc/header.php');
?>

<link rel="stylesheet" href="<?= $base ?>asset/css/form-tamu.css">

<div class="form-tamu-page">
    <div class="form-tamu-heading">
        <div>
            <span class="form-tamu-eyebrow">RESEPSIONIS / CHECK-IN</span>
            <h4>Form Check-in Tamu</h4>
            <p>Masukkan data pengunjung dengan benar sebelum menyimpan.</p>
        </div>
    </div>

    <!-- Menampilkan pesan pemberitahuan jika proses sebelumnya berhasil atau gagal -->
    <?php if ($message): ?>
    <div class="alert alert-<?=
            strpos(strtolower($message), 'berhasil') !== false
                ? 'success'
                : 'danger'
        ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php endif; ?>

    <!-- Peringatan jika data staf belum tersedia di database -->
    <?php if (empty($list_staf)): ?>
    <div class="alert alert-warning">
        Belum ada data staf. Hubungi admin untuk menambahkan staf.
    </div>
    <?php endif; ?>

    <div class="form-tamu-card">
        <div class="form-tamu-card-header">
            <div class="form-tamu-icon">
                <i class="bi bi-person-vcard"></i>
            </div>
            <div>
                <h5>Data Pengunjung</h5>
                <p>
                    Kolom bertanda <span class="text-danger">*</span>
                    wajib diisi.
                </p>
            </div>
        </div>

        <div class="form-tamu-card-body">
            <!-- Formulir pengisian data tamu baru yang dikirim via POST ke file tamu_masuk_act.php -->
            <form action="tamu_masuk_act.php" method="POST" id="formCheckin" accept-charset="UTF-8">
                <!-- Menyisipkan token keamanan CSRF -->
                <?= csrf_field() ?>

                <div class="form-tamu-grid">

                    <div class="form-tamu-field">
                        <label for="nama_tamu">
                            Nama Tamu <span>*</span>
                        </label>
                        <input type="text" id="nama_tamu" name="nama_tamu" class="form-control"
                            placeholder="Masukkan nama lengkap" minlength="2" maxlength="100"
                            pattern="[\p{L}\p{M} .'-]+"
                            title="Gunakan huruf, spasi, titik, apostrof, atau tanda hubung." autocomplete="name"
                            required>
                        <small>Nama lengkap sesuai identitas.</small>
                    </div>

                    <div class="form-tamu-field">
                        <label for="no_identitas">
                            No. Identitas (KTP/SIM) <span>*</span>
                        </label>
                        <input type="text" id="no_identitas" name="no_identitas" class="form-control"
                            placeholder="Masukkan nomor identitas" minlength="12" maxlength="20" pattern="[0-9]{12,20}"
                            inputmode="numeric" title="Nomor identitas harus berupa 12–20 digit angka." required>
                        <small>Masukkan angka tanpa spasi atau tanda baca.</small>
                    </div>

                    <div class="form-tamu-field">
                        <label for="no_telepon">
                            No. Telepon <span>*</span>
                        </label>
                        <input type="tel" id="no_telepon" name="no_telepon" class="form-control"
                            placeholder="Contoh: 081234567890" minlength="10" maxlength="16" pattern="\+?[0-9]{10,15}"
                            inputmode="tel" autocomplete="tel"
                            title="Masukkan 10–15 digit angka, boleh diawali tanda +." required>
                        <small>Masukkan nomor telepon yang aktif.</small>
                    </div>

                    <div class="form-tamu-field">
                        <label for="instansi">Instansi / Perusahaan</label>
                        <input type="text" id="instansi" name="instansi" class="form-control"
                            placeholder="Nama instansi (opsional)" maxlength="100"
                            pattern="[\p{L}\p{M}0-9 .,'()&amp;/\-]+"
                            title="Gunakan huruf, angka, spasi, dan tanda baca yang umum.">
                        <small>Boleh dikosongkan jika tidak ada.</small>
                    </div>

                    <div class="form-tamu-field">
                        <label for="staf_dituju_id">
                            Staf yang Dituju <span>*</span>
                        </label>
                        <select id="staf_dituju_id" name="staf_dituju_id" class="form-select" required
                            <?= empty($list_staf) ? 'disabled' : '' ?>>
                            <option value="">-- Pilih Staf --</option>
                            <?php foreach ($list_staf as $s): ?>
                            <option value="<?= (int) $s['id'] ?>">
                                <?= htmlspecialchars(
                                        $s['nama_staf'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                (<?= htmlspecialchars(
                                        $s['departemen'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Pilih staf yang akan dikunjungi.</small>
                    </div>

                    <div class="form-tamu-field">
                        <label for="no_badge">
                            No. Badge <span>*</span>
                        </label>
                        <input type="text" id="no_badge" name="no_badge" class="form-control" placeholder="Contoh: B-03"
                            minlength="1" maxlength="30" pattern="[A-Za-z0-9_-]+"
                            title="Gunakan huruf, angka, tanda hubung, atau garis bawah." required>
                        <small>Badge tidak boleh sedang digunakan tamu lain.</small>
                    </div>

                    <div class="form-tamu-field form-tamu-full">
                        <label for="keperluan">Keperluan</label>
                        <textarea id="keperluan" name="keperluan" class="form-control" rows="3" maxlength="500"
                            placeholder="Jelaskan keperluan kunjungan (opsional)"></textarea>
                        <small>Jelaskan tujuan kunjungan jika diperlukan.</small>
                    </div>

                </div>

                <div class="form-tamu-actions">
                    <button type="submit" name="submit" value="1" class="btn btn-dark"
                        <?= empty($list_staf) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle me-1"></i>
                        Simpan &amp; Check-in
                    </button>

                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>
                        Batal
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Skrip JavaScript untuk validasi formulir sisi klien (browser) -->
<script>
document.getElementById('formCheckin').addEventListener('submit', function(event) {
    const form = this;

    if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
    }

    const wajib = [
        'nama_tamu',
        'no_identitas',
        'no_telepon',
        'staf_dituju_id',
        'no_badge'
    ];

    for (const nama of wajib) {
        const input = form.elements[nama];

        if (!input.value.trim()) {
            event.preventDefault();
            input.setCustomValidity('Kolom ini wajib diisi.');
            input.reportValidity();
            input.setCustomValidity('');
            input.focus();
            return;
        }
    }
});

document.querySelectorAll(
    '#formCheckin input, #formCheckin textarea, #formCheckin select'
).forEach(function(input) {
    input.addEventListener('input', function() {
        this.setCustomValidity('');
    });

    input.addEventListener('change', function() {
        this.setCustomValidity('');
    });
});
</script>

<?php require_once('../inc/footer.php'); ?>
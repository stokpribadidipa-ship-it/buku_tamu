<?php
// Memanggil file otentikasi dan membatasi akses khusus untuk resepsionis.
require_once('../inc/auth.php');
cek_role(['resepsionis']);
// Memanggil file koneksi database.
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Memastikan halaman ini hanya menerima pengiriman data via metode POST dengan tombol submit.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header('Location: tamu_masuk.php');
    exit();
}

// Fungsi pembantu untuk menampilkan pesan kesalahan dan mengalihkan kembali ke form check-in.
function gagalValidasi($pesan)
{
    $_SESSION['message'] = $pesan;
    header('Location: tamu_masuk.php');
    exit();
}

// Memeriksa keabsahan token keamanan CSRF; jika tidak valid, tolak dan hentikan proses.
cek_csrf_atau_tolak('tamu_masuk.php');

// Mengambil data input mentah dari form POST dan membersihkan spasi berlebih.
$nama_tamu     = trim($_POST['nama_tamu'] ?? '');
$no_identitas  = trim($_POST['no_identitas'] ?? '');
$no_telepon    = trim($_POST['no_telepon'] ?? '');
$instansi      = trim($_POST['instansi'] ?? '');
$staf_dituju   = trim((string) ($_POST['staf_dituju_id'] ?? ''));
$no_badge      = trim($_POST['no_badge'] ?? '');
$keperluan     = trim($_POST['keperluan'] ?? '');

// Menentukan batas maksimal panjang karakter untuk mencegah input terlalu panjang.
$panjang = [
    'nama_tamu'    => 100,
    'no_identitas' => 20,
    'no_telepon'   => 16,
    'instansi'     => 100,
    'no_badge'     => 30,
    'keperluan'    => 500
];

foreach ($panjang as $field => $maksimal) {
    if (isset($_POST[$field]) &&
        strlen((string) $_POST[$field]) > $maksimal * 4) {
        gagalValidasi('Input terlalu panjang. Periksa kembali form.');
    }
}

// 1. Validasi kolom wajib terisi semua.
if (
    $nama_tamu === '' ||
    $no_identitas === '' ||
    $no_telepon === '' ||
    $staf_dituju === '' ||
    $no_badge === ''
) {
    gagalValidasi('Harap isi semua kolom wajib bertanda *.');
}

// 2. Validasi format dan panjang nama tamu.
if (strlen($nama_tamu) > 400) {
    gagalValidasi('Nama tamu maksimal 100 karakter.');
}

if (!preg_match("/^[\p{L}\p{M} .'-]+$/u", $nama_tamu)) {
    gagalValidasi(
        'Nama tamu hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.'
    );
}

if (!preg_match('/[\p{L}]/u', $nama_tamu)) {
    gagalValidasi('Nama tamu harus mengandung huruf.');
}


// 3. Validasi nomor KTP (minimal 16 digit, hanya angka).
if (!preg_match('/^[0-9]{16,}$/', $no_identitas)) {
    gagalValidasi(
        'Nomor KTP harus berupa angka dan minimal 16 digit.'
    );
}


// 4. Validasi nomor telepon (10–15 digit, boleh diawali tanda plus).
if (!preg_match('/^\+?[0-9]{10,13}$/', $no_telepon)) {
    gagalValidasi(
        'Nomor telepon harus berisi 10–13 digit dan boleh diawali tanda +.'
    );
}

// 5. Validasi instansi (opsional, jika diisi harus sesuai pola yang diizinkan).
if ($instansi !== '') {
    if (strlen($instansi) > 400) {
        gagalValidasi('Instansi maksimal 100 karakter.');
    }

    if (!preg_match("/^[\p{L}\p{M}0-9 .,'()&\/-]+$/u", $instansi)) {
        gagalValidasi(
            'Instansi mengandung karakter yang tidak diperbolehkan.'
        );
    }
}

// 6. Validasi ID staf yang dituju (harus berupa angka bulat positif).
if (!ctype_digit($staf_dituju) ||
    filter_var($staf_dituju, FILTER_VALIDATE_INT) === false ||
    (int) $staf_dituju < 1
) {
    gagalValidasi('Silakan pilih staf yang dituju dengan benar.');
}

$staf_dituju_id = (int) $staf_dituju;

// 7. Validasi nomor badge (huruf, angka, garis bawah, tanda hubung).
if (strlen($no_badge) > 120 ||
    !preg_match('/^[A-Za-z0-9_-]{1,30}$/', $no_badge)
) {
    gagalValidasi(
        'Nomor badge hanya boleh berisi huruf, angka, garis bawah, atau tanda hubung (maksimal 30 karakter).'
    );
}

// 8. Validasi keperluan (opsional, maksimal 500 karakter).
if ($keperluan !== '') {
    if (strlen($keperluan) > 2000) {
        gagalValidasi('Keperluan maksimal 500 karakter.');
    }

    if (!preg_match('/[\p{L}\p{N}]/u', $keperluan)) {
        gagalValidasi(
            'Keperluan harus mengandung huruf atau angka.'
        );
    }
}

// Mengubah string kosong menjadi NULL agar tersimpan bersih di database.
$instansi  = $instansi !== '' ? $instansi : null;
$keperluan = $keperluan !== '' ? $keperluan : null;

try {
    // Memeriksa apakah ID staf yang dipilih benar-benar ada di database.
    $cekStaf = $conn->prepare(
        'SELECT id FROM tb_staf WHERE id = :id LIMIT 1'
    );
    $cekStaf->execute([':id' => $staf_dituju_id]);

    if (!$cekStaf->fetch(PDO::FETCH_ASSOC)) {
        gagalValidasi(
            'Staf yang dipilih tidak ditemukan. Silakan pilih staf lain.'
        );
    }

    // Memeriksa apakah nomor identitas sudah pernah terdaftar sebelumnya.
    $cekIdentitas = $conn->prepare(
        'SELECT id FROM tb_tamu
         WHERE no_identitas = :no_identitas
         LIMIT 1'
    );
    $cekIdentitas->execute([
        ':no_identitas' => $no_identitas
    ]);

    if ($cekIdentitas->fetch(PDO::FETCH_ASSOC)) {
        gagalValidasi(
            'Nomor identitas sudah terdaftar. Periksa kembali data tamu.'
        );
    }

    // Memeriksa apakah nomor badge yang sama sedang digunakan oleh tamu lain yang statusnya masih 'di_dalam'.
    $cekBadge = $conn->prepare(
        "SELECT id FROM tb_tamu
         WHERE no_badge = :no_badge
         AND status = 'di_dalam'
         LIMIT 1"
    );
    $cekBadge->execute([':no_badge' => $no_badge]);

    if ($cekBadge->fetch(PDO::FETCH_ASSOC)) {
        gagalValidasi(
            'Nomor badge sedang digunakan tamu lain.'
        );
    }

    // Perintah SQL untuk menyimpan data tamu baru ke tabel tb_tamu.
    $query = "INSERT INTO tb_tamu (
        nama_tamu,
        no_identitas,
        no_telepon,
        instansi,
        staf_dituju_id,
        no_badge,
        keperluan,
        waktu_checkin,
        status,
        dicatat_oleh
    ) VALUES (
        :nama_tamu,
        :no_identitas,
        :no_telepon,
        :instansi,
        :staf_dituju_id,
        :no_badge,
        :keperluan,
        NOW(),
        'di_dalam',
        :dicatat_oleh
    )";

    $stmt = $conn->prepare($query);

    // Menjalankan query dengan mengikat data input aman parameter.
    $stmt->execute([
        ':nama_tamu'     => $nama_tamu,
        ':no_identitas'  => $no_identitas,
        ':no_telepon'    => $no_telepon,
        ':instansi'      => $instansi,
        ':staf_dituju_id'=> $staf_dituju_id,
        ':no_badge'      => $no_badge,
        ':keperluan'     => $keperluan,
        ':dicatat_oleh'  => (int) $_SESSION['id']
    ]);

    // Jika berhasil, simpan pesan sukses ke sesi lalu alihkan ke dashboard resepsionis.
    $_SESSION['message'] = 'Tamu berhasil di-check-in.';
    header('Location: index.php');
    exit();

} catch (PDOException $e) {
    // Jika terjadi error database, catat ke log server demi keamanan.
    error_log('Insert tamu error: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        $_SESSION['message'] =
            'Data bertabrakan dengan data yang sudah ada. Periksa identitas, badge, dan staf.';
    } else {
        $_SESSION['message'] =
            'Terjadi kesalahan pada server. Silakan coba kembali.';
    }

    header('Location: tamu_masuk.php');
    exit();
}
?>
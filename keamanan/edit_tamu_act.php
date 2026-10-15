<?php
// Memanggil file otentikasi dan membatasi akses khusus peran keamanan.
require_once('../inc/auth.php');
cek_role(['keamanan']);

// Memanggil file koneksi database.
require_once('../inc/koneksi.php');

// Memastikan permintaan data dilakukan melalui metode POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Mengambil dan memvalidasi ID tamu dari data POST.
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    $_SESSION['message'] = 'ID tamu tidak valid.';
    $_SESSION['message_type'] = 'danger';

    header('Location: index.php');
    exit();
}

// Memeriksa token keamanan CSRF.
cek_csrf_atau_tolak('index.php');

// Mengambil dan membersihkan input data dari form.
$nama_tamu = trim($_POST['nama_tamu'] ?? '');
$no_identitas = trim($_POST['no_identitas'] ?? '');
$no_telepon = trim($_POST['no_telepon'] ?? '');
$instansi = trim($_POST['instansi'] ?? '');
$no_badge = trim($_POST['no_badge'] ?? '');
$keperluan = trim($_POST['keperluan'] ?? '');

$staf_dituju_id = filter_var(
    $_POST['staf_dituju_id'] ?? '',
    FILTER_VALIDATE_INT
);

// Fungsi pembantu untuk menyimpan pesan error dan mengembalikan pengguna ke halaman edit.
function kembaliEdit($pesan, $jenis, $id)
{
    $_SESSION['message'] = $pesan;
    $_SESSION['message_type'] = $jenis;

    header('Location: edit_tamu.php?id=' . (int) $id);
    exit();
}

// Validasi agar kolom-kolom wajib terisi dengan benar.
if (
    $nama_tamu === '' ||
    $no_identitas === '' ||
    $no_telepon === '' ||
    $no_badge === '' ||
    $staf_dituju_id === false ||
    $staf_dituju_id === null ||
    $staf_dituju_id < 1
) {
    kembaliEdit(
        'Harap isi semua kolom wajib.',
        'danger',
        $id
    );
}

// Validasi nama tamu (hanya huruf, spasi, titik, apostrof, tanda hubung).
if (!preg_match("/^[\p{L}\s.'-]+$/u", $nama_tamu)) {
    kembaliEdit(
        'Nama tamu hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung. Angka tidak diperbolehkan.',
        'danger',
        $id
    );
}

// Batasan panjang karakter nama tamu (maksimal 150 karakter).
if (mb_strlen($nama_tamu, 'UTF-8') > 150) {
    kembaliEdit(
        'Nama tamu maksimal 150 karakter.',
        'danger',
        $id
    );
}

// Validasi nomor identitas (berupa angka 8 sampai 20 digit).
if (!preg_match('/^[0-9]{16,}$/', $no_identitas)) {
    kembaliEdit(
        'Nomor KTP harus berupa angka dan minimal 16 digit.',
        'danger',
        $id
    );
}

// Validasi nomor telepon (10 sampai 13 digit angka, tanda plus opsional di awal).
if (!preg_match('/^\+?[0-9]{10,13}$/', $no_telepon)) {
    kembaliEdit(
        'Nomor telepon harus terdiri dari 10–13 digit angka. Tanda + boleh digunakan di awal.',
        'danger',
        $id
    );
}

// Validasi nomor badge (huruf, angka, tanda hubung, maksimal 30 karakter).
if (!preg_match('/^[a-zA-Z0-9-]{1,30}$/', $no_badge)) {
    kembaliEdit(
        'Nomor badge hanya boleh berisi huruf, angka, dan tanda hubung, maksimal 30 karakter.',
        'danger',
        $id
    );
}

// Validasi batas panjang instansi dan keperluan.
if (
    mb_strlen($instansi, 'UTF-8') > 150 ||
    mb_strlen($keperluan, 'UTF-8') > 1000
) {
    kembaliEdit(
        'Instansi maksimal 150 karakter dan keperluan maksimal 1000 karakter.',
        'danger',
        $id
    );
}

try {
    // Memastikan data tamu yang akan diedit benar-benar ada dan statusnya masih di dalam gedung.
    $cekTamu = $conn->prepare(
        "SELECT id
         FROM tb_tamu
         WHERE id = :id
           AND status = 'di_dalam'
         LIMIT 1"
    );

    $cekTamu->execute([':id' => $id]);

    if (!$cekTamu->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['message'] =
            'Tamu tidak ditemukan atau sudah check-out.';
        $_SESSION['message_type'] = 'warning';

        header('Location: index.php');
        exit();
    }

    // Memastikan ID staf yang dipilih memang terdaftar di database.
    $cekStaf = $conn->prepare(
        "SELECT id
         FROM tb_staf
         WHERE id = :id
         LIMIT 1"
    );

    $cekStaf->execute([
        ':id' => $staf_dituju_id
    ]);

    if (!$cekStaf->fetch(PDO::FETCH_ASSOC)) {
        kembaliEdit(
            'Staf yang dituju tidak ditemukan.',
            'danger',
            $id
        );
    }

    // Mencegah nomor identitas bertabrakan (digunakan oleh tamu lain).
    $cekIdentitas = $conn->prepare(
        "SELECT id
         FROM tb_tamu
         WHERE no_identitas = :no_identitas
           AND id <> :id
         LIMIT 1"
    );

    $cekIdentitas->execute([
        ':no_identitas' => $no_identitas,
        ':id' => $id
    ]);

    if ($cekIdentitas->fetch(PDO::FETCH_ASSOC)) {
        kembaliEdit(
            'Nomor identitas sudah digunakan oleh tamu lain.',
            'danger',
            $id
        );
    }

    // Query untuk memperbarui data tamu di dalam database.
    $query = "UPDATE tb_tamu
              SET nama_tamu = :nama_tamu,
                  no_identitas = :no_identitas,
                  no_telepon = :no_telepon,
                  instansi = :instansi,
                  staf_dituju_id = :staf_dituju_id,
                  no_badge = :no_badge,
                  keperluan = :keperluan
              WHERE id = :id
                AND status = 'di_dalam'";

    $stmt = $conn->prepare($query);

    $stmt->execute([
        ':nama_tamu' => $nama_tamu,
        ':no_identitas' => $no_identitas,
        ':no_telepon' => $no_telepon,
        ':instansi' => $instansi !== '' ? $instansi : null,
        ':staf_dituju_id' => $staf_dituju_id,
        ':no_badge' => $no_badge,
        ':keperluan' => $keperluan !== '' ? $keperluan : null,
        ':id' => $id
    ]);

    $_SESSION['message'] = 'Data tamu berhasil diperbarui.';
    $_SESSION['message_type'] = 'success';

    header('Location: index.php');
    exit();

} catch (PDOException $e) {
    // Catat error database ke log server.
    error_log('Edit tamu error: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        $_SESSION['message'] =
            'Data bertentangan dengan aturan database. Periksa nomor identitas atau relasi staf.';
    } else {
        $_SESSION['message'] =
            'Terjadi kesalahan saat memperbarui data tamu.';
    }

    $_SESSION['message_type'] = 'danger';

    header('Location: edit_tamu.php?id=' . $id);
    exit();
}
<?php
// Memulai sesi PHP agar sistem dapat mengenali sesi pengguna yang sedang aktif.
session_start();

// Menghapus seluruh variabel data yang tersimpan di dalam sesi (variabel $_SESSION dikosongkan).
session_unset();

// Menghancurkan sesi yang sedang berjalan di server (menutup sesi pengguna secara total).
session_destroy();

// Mengalihkan (redirect) halaman web kembali ke halaman login (login.php).
header('Location: login.php');

// Menghentikan eksekusi skrip selanjutnya agar proses pengalihan berjalan lancar.
exit();
?>
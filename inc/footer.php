</div> <!-- Menutup elemen pembungkus konten utama (container) -->

<!-- Memuat berkas JavaScript Bootstrap agar komponen interaktif pada halaman web dapat berfungsi -->
<script src="<?= $base ?? '' ?>asset/js/bootstrap.min.js"></script>

</main> <!-- Menutup elemen utama halaman (<main>) -->
</div> <!-- Menutup elemen kerangka tata letak aplikasi (app-layout) -->

<!-- Bagian kaki halaman (footer) dengan kelas CSS untuk jarak margin dan padding -->
<footer class="main-footer mx-4 py-3">
    <!-- Menampilkan tahun secara otomatis menggunakan fungsi date('Y') serta teks hak cipta aplikasi -->
    &copy; <?= date('Y') ?> Hospitality — Sistem Informasi Buku Tamu
</footer>

</body> <!-- Menutup tag tubuh dokumen HTML -->

</html> <!-- Menutup dokumen HTML secara keseluruhan -->
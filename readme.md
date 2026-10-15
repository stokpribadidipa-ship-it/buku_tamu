```text
buku-tamu/
├── admin/
│   ├── index.php
│   ├── list_tamu.php
│   ├── list_staf.php
│   ├── tambahstaf.php
│   ├── tambahstaf_act.php
│   ├── editstaf.php
│   ├── editstaf_act.php
│   ├── del_staf.php
│   ├── list_user.php
│   ├── tambahuser.php
│   ├── tambahuser_act.php
│   ├── edituser.php
│   ├── edituser_act.php
│   └── del_user.php
├── asset/
│   ├── css/
│   │   ├── bootstrap*.css (varian Bootstrap dan source map)
│   │   └── style.css
│   ├── img/ (kosong)
│   ├── js/
│   │   ├── bootstrap*.js (varian Bootstrap dan source map)
│   │   └── init.js
│   └── README.txt
├── inc/
│   ├── auth.php
│   ├── koneksi.php
│   ├── header.php
│   ├── footer.php
│   └── logout.php
├── keamanan/
│   ├── index.php
│   ├── edit_tamu.php
│   ├── edit_tamu_act.php
│   ├── checkout_act.php
│   └── log_harian.php
├── resepsionis/
│   ├── index.php
│   ├── tamu_masuk.php
│   └── tamu_masuk_act.php
├── uploads/ (kosong)
├── buku_tamu.sql
├── index.php
├── login.php
├── login_act.php
├── logout.php
└── readme.md
```

`admin/`, `keamanan/`, dan `resepsionis/` berisi halaman sesuai peran pengguna. File bersama seperti autentikasi, koneksi database, header, dan footer berada di `inc/`. Aset lokal berada di `asset/`, sedangkan `uploads/` disiapkan untuk file unggahan.

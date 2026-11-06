# Laporan Progres OneClickStore

Laporan ini menjelaskan pekerjaan yang sudah dilakukan dan kondisi aplikasi agar pemilik proyek dapat mengikuti perkembangannya. Dokumen kebutuhan tetap berada di [Oneclick.md](Oneclick.md) dan [Rintis.md](Rintis.md). Fitur yang direncanakan tidak otomatis berarti sudah tersedia.

## Kondisi aplikasi saat ini

OneClickStore sudah memiliki halaman pengenalan Central, akun bersama, panel admin pemilik, dan fondasi aktivasi akses produk melalui web. Login mengikuti jumlah produk aktif. Rintis memiliki fondasi toko, pemisahan akses, kategori usaha, lokasi dan privasi alamat, serta pengelolaan kategori/produk dan katalog pelanggan. Rintis belum dapat digunakan untuk menerima pesanan atau pembayaran. Alur terbaru ada pada Tahap 5; alur pendaftaran langsung ke toko pada Tahap 3 merupakan riwayat sebelum aktivasi akses produk ditambahkan.

Hubungan bagian-bagiannya:

```text
OneClickStore — pusat produk milik Oneclick
└── Rintis — aplikasi untuk seller dan pelanggan toko
    ├── Wemeet Coffee — bisnis kopi milik pemilik OneClickStore
    └── Toko seller lain — masing-masing mempunyai akun pemilik sendiri
```

Wemeet Coffee adalah toko pertama yang direncanakan memakai aplikasi. Akun serta data tokonya tidak dibuat otomatis; pemilik memasukkannya sendiri melalui formulir. Panel administrasi OneClickStore dan hak khusus pemilik platform kini tersedia; rincian terbaru ada di Tahap 7. Pernyataan keterbatasan pada tahap sebelumnya adalah riwayat saat tahap tersebut selesai.

## Tahap 1 — Persiapan proyek dan database

Tanggal pengerjaan: 28 Oktober 2026.

### Tujuan dan hasil

- Menyiapkan Laravel dengan Vue, Inertia, dan Tailwind CSS untuk membangun aplikasi dalam satu proyek.
- Memasang PostgreSQL lokal, membuat database `oneclickstore`, menghubungkannya dengan Laravel, dan menjalankan migrasi bawaan.
- Memasang pgAdmin sebagai aplikasi visual untuk melihat dan mengelola database PostgreSQL.
- Memasang Laravel Boost untuk membantu pengembangan. Konfigurasi Codex dipertahankan; konfigurasi khusus Claude dihapus sesuai permintaan pemilik.
- Memisahkan dokumentasi merek, kebutuhan Rintis, dan aturan kerja asisten.

### Penjelasan teknologi

| Bagian | Tugas |
| --- | --- |
| Laravel | Mengatur URL, validasi, login, hak akses, dan pengolahan data |
| Vue | Menampilkan halaman dan interaksi di browser |
| Inertia | Menghubungkan respons Laravel dengan halaman Vue beserta datanya |
| Tailwind CSS | Mengatur warna, ukuran, jarak, dan susunan tampilan |
| PostgreSQL | Menyimpan data aplikasi |
| pgAdmin | Membantu melihat database melalui tampilan visual |

### Pemeriksaan yang dilakukan

Migrasi bawaan berhasil dijalankan di PostgreSQL. Pada tahap persiapan, build frontend berhasil dan 4 tes aplikasi lulus. Hasil pengujian yang lebih baru dicatat pada tahap berikutnya.

## Tahap 2 — Halaman awal Central

Tanggal pengerjaan: 28–29 Oktober 2026.

### Tujuan dan hasil

- Membuat halaman pengenalan OneClickStore menggunakan logo asli yang diberikan pemilik.
- Menampilkan pengenalan merek, informasi Rintis, dan bagian About Oneclick.
- Mengubah seluruh teks halaman Central ke bahasa Inggris.
- Membuat navbar tetap terlihat saat menggulir halaman.
- Membuat tautan Product, About Oneclick, dan Back to top berpindah dengan scroll halus. Pengaturan pengurangan animasi perangkat tetap dihormati.
- Menghapus simbol dekoratif hijau di kartu logo.

### File penting

| File | Fungsinya |
| --- | --- |
| `routes/web.php` | Menentukan halaman Central saat alamat utama dibuka |
| `resources/js/Pages/Central/Home.vue` | Isi halaman utama: judul, pengenalan, dan bagian Rintis |
| `resources/js/Layouts/Central/CentralLayout.vue` | Header, logo atas, navigasi, dan footer yang membungkus halaman Central |
| `resources/css/app.css` | Styling dasar dan pengaturan scroll halus |
| `public/images/central/oneclick-logo.png` | Salinan logo asli yang digunakan halaman |

### Cara mencoba dan batasan

Jalankan `composer run dev` dari folder proyek jika aplikasi belum berjalan. Buka alamat yang ditampilkan terminal. Coba navigasi Product, About Oneclick, dan Back to top.

Central saat ini adalah halaman pengenalan. Central belum mempunyai dashboard penghasilan, pengelolaan portofolio dari database, atau pengelolaan langganan.

### Pemeriksaan yang dilakukan

Build frontend berhasil setelah perubahan tampilan dan navigasi. Halaman utama juga tercakup dalam pengujian respons Laravel dan Inertia.

## Tahap 3 — Fondasi akun dan toko Rintis

Tanggal pengerjaan: 29 Oktober 2026.

### Tujuan

Menyiapkan Rintis agar dapat dipakai Wemeet Coffee dan nantinya seller lain, dengan satu toko per akun pemilik dan akses data yang terpisah. Rintis dibangun ulang dengan Laravel, Vue, dan Inertia; proyek Flutter menjadi acuan, bukan kode yang langsung dipindahkan.

### Hasil yang sudah dapat digunakan

| Fitur | Perilaku saat ini |
| --- | --- |
| Pendaftaran akun | Pengguna memasukkan nama, email, password, dan konfirmasi password |
| Login dan logout | Pengguna dapat masuk dan keluar; percobaan login dibatasi |
| Pembuatan toko | Akun dapat membuat satu toko dengan nama, alamat unik, dan tagline opsional |
| Pengaturan toko | Pemilik dapat mengubah identitas dan visibilitas tokonya |
| Overview toko | Menampilkan identitas toko, pemilik yang sedang login, dan tautan pengaturan/pratinjau |
| Direktori Rintis | Menampilkan identitas toko yang pemiliknya sudah aktifkan untuk publik |
| Halaman toko | Menampilkan identitas toko dan pemberitahuan bahwa katalog belum tersedia |
| Pratinjau privat | Pemilik dapat melihat toko yang belum dipublikasikan |
| Pemisahan hak akses | Akun lain ditolak ketika mencoba membuka atau mengubah admin toko tersebut |

### Alur yang sekarang tersedia

```text
Central → Explore Rintis → Set up your store
    → Daftar akun → Buat toko → Overview toko
    → Store settings → Simpan identitas dan visibilitas
    → Preview store / Visit store
```

Pelanggan publik dapat membuka Rintis, memilih toko yang sudah ditampilkan, lalu melihat halaman identitas toko. Belum ada proses pemesanan.

### Data yang disimpan

- `users`: akun pengguna. Password disimpan dalam bentuk hash, bukan teks biasa.
- `rintis_stores`: nama toko, alamat unik, tagline, status visibilitas, dan hubungan ke akun pemilik.
- Database membatasi satu toko per pemilik serta alamat toko yang unik.
- Kepemilikan ditentukan dari akun yang sedang login, bukan dari pilihan ID pemilik yang dikirim browser.
- Toko baru tersembunyi secara default. Menyimpan akun atau toko tidak otomatis memberikan hak pemilik platform atau membeli langganan.

### File dan folder penting

| File/folder | Fungsinya |
| --- | --- |
| `routes/web.php` | Route Central dan autentikasi akun |
| `routes/rintis.php` | Route direktori, halaman toko, dan area admin Rintis |
| `app/Http/Controllers/Auth/` | Memproses pendaftaran, login, dan logout |
| `app/Http/Controllers/Rintis/Admin/StoreController.php` | Membuat toko dan mengelola overview serta pengaturannya |
| `app/Http/Requests/` | Memeriksa input formulir sebelum diproses |
| `app/Models/Rintis/Store.php` | Model data toko dan hubungan ke pemilik |
| `app/Models/User.php` | Model akun dan hubungan ke toko miliknya |
| `app/Policies/Rintis/StorePolicy.php` | Memastikan hanya pemilik yang boleh mengelola toko |
| `database/migrations/2026_10_29_052557_create_stores_table.php` | Membuat tabel `rintis_stores` beserta batasan kepemilikan |
| `resources/js/Pages/Auth/` | Tampilan login dan pendaftaran |
| `resources/js/Pages/Rintis/Admin/` | Tampilan pembuatan toko, overview, dan pengaturan |
| `resources/js/Pages/Rintis/Directory.vue` | Tampilan daftar toko publik |
| `resources/js/Pages/Rintis/Storefront/Home.vue` | Tampilan awal toko bagi pelanggan |
| `resources/js/Layouts/Rintis/RintisLayout.vue` | Kerangka halaman direktori dan pengelola Rintis |
| `tests/Feature/Auth/` dan `tests/Feature/Rintis/` | Menguji alur akun, toko, validasi, dan pembatasan akses |

### Cara mencoba dengan Wemeet Coffee

1. Jalankan aplikasi dan buka halaman Central.
2. Klik **Explore Rintis**, lalu **Set up your store**.
3. Buat akun menggunakan identitas sendiri. Password minimal 12 karakter.
4. Isi nama toko **Wemeet Coffee** dan alamat **wemeet-coffee**, selama alamat tersebut belum digunakan.
5. Tagline boleh dikosongkan. Biarkan **Show my store publicly** tidak dicentang untuk menyiapkan toko secara privat.
6. Klik **Create store**. Halaman overview akan terbuka.
7. Gunakan **Store settings** untuk mengubah identitas dan **Preview store** untuk melihat halaman toko.
8. Jika ingin menampilkannya di direktori, aktifkan **Show my store publicly** dan simpan. Ini hanya mempublikasikan identitas; belum mengaktifkan pemesanan.

Nama Wemeet Coffee pada placeholder formulir adalah petunjuk pengisian, bukan bukti bahwa data toko sudah disimpan.

### Pemeriksaan yang benar-benar dilakukan

- Build frontend melalui `npm run build`: berhasil.
- Format PHP melalui Laravel Pint: selesai.
- Migrasi penambahan `rintis_stores` ke PostgreSQL: berhasil, tanpa menghapus data sebelumnya.
- Seluruh suite saat tahap ini selesai: **35 tes lulus, 212 assertions**. Sebanyak 31 tes berasal dari pengujian baru akun dan toko.
- Kasus yang diuji mencakup login salah, pembatasan percobaan login, normalisasi email, password ter-hash, duplikasi alamat toko, batas satu toko, penolakan akses antarpemilik, dan visibilitas publik/privat.
- Tes otomatis memakai database terisolasi sesuai `phpunit.xml`; keberhasilannya tidak berarti ada pesanan atau data bisnis nyata yang telah dibuat.
- Pemeriksaan visual interaktif di browser belum dilakukan pada tahap fondasi ini; build dan tes HTTP tidak membuktikan seluruh perilaku browser.

### Yang belum tersedia

- Dashboard pemilik platform OneClickStore dan pengelolaan seller dari pusat.
- Kategori, produk dagangan, foto produk, resep privat, dan HPP.
- Open order, kuota, checkout, serta riwayat pesanan.
- Pembayaran toko, verifikasi bukti transfer, pickup, QR, dan delivery.
- Penjualan paket Rintis, pembayaran langganan, aktivasi, serta perpanjangan.
- Pemulihan password dan verifikasi email.
- Keranjang lintas toko dan pembayaran gabungan.

### Langkah berikutnya yang disarankan

1. Pemilik mencoba pendaftaran, membuat Wemeet Coffee, dan memeriksa alur pengaturan/pratinjau.
2. Membangun kategori dan produk dengan kepemilikan toko yang selalu diperiksa di server.
3. Menampilkan produk yang diizinkan pada katalog pelanggan Wemeet Coffee.
4. Melanjutkan ke periode open order dan pesanan setelah alur produk bekerja.

## Tahap 4 — Kebiasaan laporan progres

Tanggal pengerjaan: 29 Oktober 2026.

Laporan bersama ini dibuat atas permintaan pemilik. `AGENTS.md` diperbarui agar setiap pekerjaan yang mengubah proyek disertai pembaruan pada file yang sama, dengan penjelasan sederhana, cara mencoba, hasil pemeriksaan, dan keterbatasan.

Perubahan tahap ini hanya dokumentasi. Isi dan referensi file diperiksa; build, migrasi, dan tes aplikasi tidak dijalankan ulang karena tidak ada perubahan kode aplikasi. Angka pengujian di atas adalah hasil tahap sebelumnya.


## Tahap 5 — Hak akses produk dan fondasi kategori/lokasi toko

Tanggal pengerjaan: 29 Oktober 2026.

### Tujuan

Satu pelanggan menggunakan satu akun OneClickStore untuk beberapa produk. Akses produk diaktifkan terpisah oleh pemilik platform. Rintis disiapkan untuk toko dari berbagai jenis usaha dan wilayah, tanpa membuat panel administrasi Central lengkap terlebih dahulu.

### Hasil yang sekarang bisa digunakan

| Bagian | Perilaku terbaru |
| --- | --- |
| Akun bersama | Pendaftaran hanya membuat akun; tidak otomatis mengaktifkan Rintis |
| Tanpa akses aktif | Setelah login, pengguna melihat My products dengan penjelasan bahwa akses perlu diaktifkan OneClickStore |
| Satu produk aktif | Pengguna diarahkan langsung ke workspace produk yang sudah tersedia |
| Beberapa produk aktif | Pengguna memilih produk melalui halaman My products |
| Workspace belum tersedia | Akses dapat tercatat, tetapi tidak menghasilkan tautan rusak atau aplikasi fiktif |
| Aktivasi manual | Pemilik server dapat mengaktifkan atau mencabut akses melalui perintah Artisan; belum ada tombol aktivasi di web |
| Keamanan Rintis | Semua route admin memerlukan login, akses Rintis aktif, dan kepemilikan toko sesuai tindakan |
| Kategori usaha | Pilihan kategori toko tersedia, terpisah dari kategori produk dagangan yang belum dibuat |
| Lokasi toko | Pemilik dapat mengisi provinsi, kota/kabupaten, kecamatan, alamat lengkap, dan koordinat opsional |
| Izin lokasi perangkat | Tombol Use my current location meminta izin browser; tidak dijalankan otomatis |
| Privasi lokasi | Alamat lengkap, kecamatan, dan koordinat dikecualikan dari data halaman publik jika tidak diizinkan pemilik |
| Informasi publik | Kategori toko, kota, dan provinsi tampil pada direktori dan halaman toko |

### Alur terbaru

```text
Daftar satu akun → Login → Pemeriksaan akses produk
    ├── Belum aktif → Halaman My products / menunggu aktivasi
    ├── Satu produk aktif → Workspace produk tersebut
    └── Beberapa produk aktif → Pilihan produk

Akses Rintis aktif → Admin Rintis
    ├── Belum punya toko → Buat toko
    └── Sudah punya toko → Overview → Store settings
```

Pemilik toko tidak otomatis menjadi pemilik platform OneClickStore. Pengunjung tetap bisa membuka direktori dan toko publik tanpa membeli akses admin Rintis.

### Cara mengaktifkan akses untuk mencoba

Setelah membuat akun, pemilik OneClickStore menjalankan perintah berikut dari folder proyek. Ganti `email-akun@example.com` dengan email akun yang benar-benar sudah terdaftar:

```bash
php artisan oneclick:product-access email-akun@example.com rintis
```

Kemudian buka kembali My products atau login ulang. Karena Rintis saat ini satu-satunya workspace produk yang tersedia, akun dengan akses tersebut akan diarahkan ke admin Rintis.

Untuk mencabut akses:

```bash
php artisan oneclick:product-access email-akun@example.com rintis --revoke
```

Perintah ini merupakan alat administrasi server, bukan fitur yang dapat dijalankan seller dari browser. Akses server/terminal harus tetap berada pada pihak yang berwenang. Perintah menolak akun atau produk yang tidak dikenal, tidak membuat akun baru, dan tidak menghapus data toko. Menjalankan aktivasi berulang tidak menggandakan akses.

**Akun yang dibuat sebelum tahap ini juga memerlukan aktivasi eksplisit. Tidak ada akses yang diaktifkan otomatis hanya karena akun sudah memiliki toko.** Migrasi mempertahankan data akun dan toko yang sudah ada. Pencabutan akses menutup workspace admin dan pratinjau privat; saat ini tidak otomatis mengubah visibilitas toko publik yang sudah tersimpan. Kebijakan publikasi, masa tenggang, retensi, dan langganan kedaluwarsa belum ditentukan.

### Cara melengkapi Wemeet Coffee

1. Pastikan akses Rintis akunmu sudah aktif.
2. Masuk ke toko, lalu buka Store settings.
3. Pilih Coffee & Drinks sebagai kategori usaha.
4. Isi alamat toko sebenarnya: provinsi, kota/kabupaten, kecamatan, dan alamat lengkap. Placeholder Singkawang bukan data yang dibuat atau dipastikan otomatis.
5. Koordinat boleh dikosongkan. Jika sedang berada di toko, gunakan Use my current location dan izinkan browser; periksa titiknya melalui tautan OpenStreetMap sebelum menyimpan. Koordinat juga dapat diisi manual.
6. Untuk usaha rumahan atau alamat privat, biarkan Show my full address and coordinates publicly tidak dicentang. Data publik hanya menampilkan wilayah kota/provinsi.
7. Simpan pengaturan. Toko yang masih disiapkan boleh disimpan dengan kategori/alamat belum lengkap. Untuk publikasi baru atau menyimpan kembali toko publik, kategori dan seluruh kolom alamat wajib diisi; koordinat tetap opsional.

Kategori awal: Coffee & Drinks, Food & Groceries, Fashion & Accessories, Home & Living, Hardware & Building Materials, Services, dan Other. Daftar ini adalah kategori usaha awal, bukan daftar kategori produk dagangan tiap seller.

### Data dan file penting

| Data/file | Tanggung jawab |
| --- | --- |
| `central_products` | Identitas produk aplikasi, dengan Rintis sebagai produk awal |
| `central_product_accesses` | Akses per akun dan produk, status aktif, waktu aktivasi/pencabutan; bukan catatan tagihan atau bukti pembayaran |
| `rintis_store_categories` | Referensi kategori usaha |
| Kolom tambahan `rintis_stores` | Kategori, wilayah, alamat, koordinat, dan pilihan privasi |
| `app/Models/Central/` | Model produk aplikasi dan akses akun |
| `config/products.php` | Pemetaan produk ke route workspace yang benar-benar tersedia |
| `app/Http/Controllers/Central/AccountController.php` | Memilih tujuan akun berdasarkan akses produk aktif |
| `app/Http/Middleware/EnsureProductAccess.php` | Memblokir pengelolaan produk tanpa akses aktif |
| `app/Console/Commands/SetProductAccess.php` | Aktivasi dan pencabutan akses melalui terminal pemilik |
| `resources/js/Pages/Central/Account.vue` | Halaman tanpa akses aktif atau pilihan beberapa produk |
| `app/Http/Requests/Rintis/StoreRequest.php` | Validasi kategori, lokasi, pasangan koordinat, dan privasi |
| `app/Models/Rintis/Store.php` | Hubungan kategori serta penyaringan data lokasi yang boleh tampil ke publik |
| `resources/js/Pages/Rintis/Admin/StoreForm.vue` | Formulir identitas, kategori, wilayah, koordinat, dan visibilitas |

### Pemeriksaan yang dilakukan

- Dua migration baru berhasil diterapkan di PostgreSQL tanpa menghapus akun atau toko sebelumnya.
- Build frontend melalui `npm run build`: berhasil.
- Laravel Pint pada PHP yang berubah: selesai.
- Seluruh pengujian: **65 tes lulus, 435 assertions**.
- Pengujian mencakup akun tanpa akses, satu/beberapa produk aktif, penolakan aktivasi mandiri melalui input pendaftaran, aktivasi/pencabutan berulang, serta perlindungan toko setelah akses dicabut.
- Pengujian lokasi mencakup kategori tidak valid, batas koordinat, koordinat yang harus berpasangan, nilai nol yang sah, kolom publikasi wajib, dan penghilangan alamat/koordinat privat dari respons publik.
- Izin geolokasi serta penampilan halaman belum diuji secara interaktif di browser; keberhasilan build dan tes HTTP tidak menggantikan pemeriksaan tersebut.

### Batasan dan langkah berikutnya

- Belum ada panel admin Central, pembayaran langganan, paket/harga, atau penghentian akses otomatis berdasarkan tanggal. Aktivasi saat ini bersifat manual dan tidak menyatakan adanya pembayaran.
- Baru Rintis yang memiliki workspace terpasang. Struktur akses mendukung banyak produk, tetapi produk lain belum dibangun atau diiklankan sebagai tersedia.
- Wilayah masih diisi sebagai teks terstruktur; belum ada referensi resmi wilayah atau validasi hubungan provinsi–kota–kecamatan.
- Belum ada peta pemilih titik di dalam aplikasi, pencarian terdekat, pengurutan jarak, filter kota/kategori, ongkir, atau integrasi rute jalan. Data lokasi adalah fondasi untuk fitur berikutnya.
- URL toko masih bisa diubah melalui pengaturan. QR promosi dan pengalihan slug lama belum dibuat.
- Berikutnya: coba alur aktivasi dan pengaturan Wemeet Coffee, lalu lanjutkan kategori produk dan katalog atau pencarian direktori sesuai prioritas pemilik.


## Tahap 6 — Panel admin pemilik OneClickStore

Tanggal pengerjaan: 29 Oktober 2026.

### Tujuan dan hasil

Pemilik ingin melakukan pengaturan melalui web tanpa menjalankan perintah terminal sehari-hari. Panel tersedia di path `/central/admin`, terpisah dari admin toko Rintis. Setelah login, akun pemilik platform diarahkan ke panel ini. Akun seller tetap mengikuti alur hak akses produk sebelumnya.

| Menu | Yang sudah dapat dilakukan |
| --- | --- |
| Overview | Melihat jumlah akun, produk aplikasi, akses produk aktif, dan toko Rintis dari database |
| Customers | Mencari akun berdasarkan nama/email, membuka detail, serta mengaktifkan atau mencabut akses tiap produk |
| Products | Mendaftarkan identitas produk aplikasi dan mengubah namanya; key produk tetap untuk menjaga pemetaan workspace |
| Rintis categories | Menambahkan dan mengubah kategori usaha yang dipilih seller saat mengatur toko |
| Settings | Mengubah nama, email, dan password akun pemilik setelah mengonfirmasi password saat ini |
| Recent access changes | Melihat 20 perubahan akses terbaru per akun, produk terkait, pelaku perubahan, dan waktu dalam WIB |

Angka Overview bukan jumlah transaksi atau penghasilan. Menambah identitas produk baru tidak membuat aplikasinya, menghubungkan workspace, atau menerbitkan penawaran berbayar. Workspace tetap dihubungkan melalui konfigurasi pengembangan saat aplikasinya tersedia.

### Akun pemilik dan keamanan

- Akun yang ditunjuk secara eksplisit oleh pemilik telah disiapkan sebagai pemilik platform. Kredensial tidak dicatat di laporan ini.
- Tidak ada aturan akun pertama otomatis menjadi admin. Registrasi biasa tidak dapat mengisi atau mengubah `is_platform_owner`.
- Semua route panel memerlukan login serta peran pemilik platform di server. Seller biasa ditolak meskipun mengetahui alamat panel.
- Peran pemilik platform berbeda dari akses Rintis. Pemilik dapat mengaktifkan akses Rintis pada akun sendiri melalui menu Customers jika ingin membuat atau mengelola tokonya dengan akun yang sama.
- Akun seller dan toko yang sudah ada tetap dipertahankan; toko tidak dipindahkan otomatis ke akun pemilik platform yang baru.
- Aktivasi akses web dan terminal menggunakan proses bisnis yang sama. Perubahan status disimpan bersama catatan pelaku dalam satu transaksi. Pengulangan status yang sama tidak menggandakan riwayat.
- Riwayat sebelum fitur pencatatan dibuat tidak diisi dengan data rekaan.
- Pengaturan akun tidak dapat mengubah peran platform atau hak akses produk melalui input tambahan. Perubahan email/password memerlukan password saat ini.

### Cara menggunakan tanpa terminal

1. Buka halaman Central dan klik **Sign in**.
2. Login memakai akun pemilik platform yang sudah disiapkan. Akun tersebut masuk ke Overview admin.
3. Pilih **Customers**, cari email akun yang ingin diaktifkan, lalu klik akun tersebut.
4. Pada produk Rintis, klik **Activate access**. Untuk pencabutan, klik **Deactivate access** dan konfirmasikan; data akun/toko tidak dihapus.
5. Untuk mengaktifkan Rintis pada akun pemilik sendiri, cari akun sendiri di Customers lalu aktifkan Rintis. Tautan **My Rintis store** tersedia di header setelah akses aktif.
6. Gunakan **Products** untuk daftar aplikasi, **Rintis categories** untuk kategori usaha, dan **Settings** untuk pengaturan akun pemilik.
7. Seller yang sudah diaktifkan dapat login dengan akun yang sama seperti sebelumnya dan masuk ke produk yang aktif. Jika beberapa produk aktif, seller memperoleh pilihan produk.

Tautan **Admin panel** pada navbar Central tampil untuk akun pemilik yang sudah login. Tautan itu tidak menjadikan halaman admin dapat diakses publik.

### File dan data penting

| File/data | Fungsi |
| --- | --- |
| Kolom `users.is_platform_owner` | Menandai akun yang berhak mengelola platform; nilai awal false |
| `central_access_events` | Riwayat perubahan aktivasi, akun yang dipengaruhi, produk, pelaku, dan sumber perubahan |
| `routes/central-admin.php` | Route khusus panel admin Central |
| `app/Http/Middleware/EnsurePlatformOwner.php` | Memeriksa peran pemilik platform sebelum membuka panel |
| `app/Http/Controllers/Central/Admin/` | Menangani overview, akun pelanggan, produk, kategori usaha, dan pengaturan pemilik |
| `app/Actions/Central/ChangeProductAccess.php` | Proses bersama untuk aktivasi/pencabutan dan pencatatan riwayat |
| `app/Models/Central/AccessEvent.php` | Model riwayat perubahan akses |
| `app/Http/Requests/Central/UpdateOwnerSettingsRequest.php` | Validasi pengaturan akun dan konfirmasi password saat ini |
| `resources/js/Layouts/Central/AdminLayout.vue` | Header serta navigasi panel pemilik |
| `resources/js/Pages/Central/Admin/` | Halaman panel admin |
| `resources/js/Components/Central/` | Formulir katalog/kategori dan tombol aktivasi akses |
| `app/Console/Commands/MakePlatformOwner.php` | Alat penetapan pemilik secara eksplisit saat setup; tidak dibuka sebagai route web |
| `tests/Feature/Central/AdminPanelTest.php` | Pengujian otorisasi dan perilaku panel pemilik |

### Pemeriksaan yang dilakukan

- Migration peran pemilik dan tabel riwayat berhasil dijalankan di PostgreSQL tanpa menghapus data sebelumnya.
- Akun pemilik yang dipilih pengguna berhasil disiapkan dengan input password melalui prompt tersembunyi.
- Laravel Pint: selesai.
- Build frontend: berhasil.
- Seluruh suite: **80 tes lulus, 584 assertions**.
- Tes mencakup penolakan seluruh endpoint admin bagi seller, pencarian akun, aktivasi/pencabutan dan riwayat pelaku, key produk yang tidak dapat diganti, pengubahan kategori tanpa memutus hubungan toko, konfirmasi password, penolakan duplikasi email, serta setup pemilik yang tidak memberikan hak otomatis ke akun biasa.
- Pengujian menemukan konfigurasi alias middleware yang saling menggantikan. Alias akses produk dan pemilik digabung dalam satu konfigurasi, lalu suite dijalankan ulang hingga lulus.
- Pemeriksaan visual interaktif di browser belum dilakukan; pemilik perlu mencoba login dan menu panel untuk menilai pengalaman tampilannya.

### Batasan dan langkah selanjutnya

- Belum tersedia transaksi pembayaran, tagihan, paket sewa, masa berlaku otomatis, refund, dan laporan penghasilan.
- Belum ada pemberian atau pencabutan peran pemilik platform lewat web, penghapusan akun/produk/kategori, serta reset password pelanggan oleh admin.
- Kategori yang sudah dipilih toko bisa diganti namanya melalui panel, tetapi tidak ada fitur penghapusan kategori pada tahap ini.
- Pengaturan merek global, unggah logo platform, dan integrasi email/pembayaran belum ditambahkan; Settings saat ini mengatur akun pemilik.
- Selanjutnya: coba alur pemilik mengaktifkan Rintis dan seller membuka tokonya, kemudian lanjutkan fitur katalog Rintis sesuai prioritas.


## Tahap 7 — Pengelolaan terpusat di detail produk

Tanggal pengerjaan: 29 Oktober 2026.

### Tujuan

Pemilik meminta daftar produk berbentuk tabel dan pengaturan khusus setiap produk ditempatkan di detail produk tersebut. Menu Rintis categories tidak lagi dipisahkan di sidebar Central.

### Hasil yang dapat digunakan

- **Products** kini berupa tabel: nama produk, key, jumlah pengguna aktif untuk produk tersebut, status ketersediaan workspace, dan tombol Manage.
- Klik nama produk atau Manage untuk membuka detail. Produk Rintis memiliki tab **Settings**, **Users**, dan **Store categories**.
- **Settings** mengubah nama produk; key tetap dipertahankan agar akses yang sudah tersimpan tidak terputus.
- **Users** berupa tabel akun, dengan pencarian nama/email serta filter All accounts, Active users, dan Not active. Status akses dihitung khusus untuk produk yang sedang dibuka.
- Aktivasi/pencabutan dapat dilakukan langsung pada baris pengguna. Pencabutan memerlukan konfirmasi, tetap mempertahankan data pengguna/toko, dan dicatat dalam riwayat akses yang sudah tersedia.
- **Store categories** milik Rintis berada di detail Rintis, dengan tabel kategori dan formulir tambah/edit yang dibuka saat diperlukan.
- Produk lain hanya mendapat tab yang relevan. Produk selain Rintis tidak dapat membaca atau mengubah konfigurasi kategori Rintis melalui route bersarangnya.
- Menu **Customers** tetap tersedia untuk melihat keseluruhan akun platform. Pengaturan akses juga bisa dilakukan dari sudut pandang produk melalui Products → produk → Users.
- Tautan lama halaman kategori mengarahkan ke detail Rintis pada tab Store categories. Tidak ada lagi menu kategori Rintis pada sidebar.

### Cara mencoba

```text
Login sebagai pemilik platform
    → Products (tabel)
    → Klik Rintis / Manage
        ├── Settings → ubah nama produk
        ├── Users → cari akun → Activate / Deactivate
        └── Store categories → Add category / Edit
```

Jumlah Active users pada tabel produk merupakan jumlah akun dengan akses aktif ke produk itu, bukan seluruh akun platform atau jumlah pembelian. Saat memilih All accounts di tab Users, akun yang belum memiliki akses juga ditampilkan agar pemilik dapat mengaktifkannya dari sana.

### File penting

| File | Perubahan |
| --- | --- |
| `resources/js/Pages/Central/Admin/Products.vue` | Daftar produk berbentuk tabel dan formulir tambah yang dapat dibuka |
| `resources/js/Pages/Central/Admin/ProductDetail.vue` | Halaman detail dengan tab pengaturan, pengguna, dan konfigurasi khusus produk |
| `resources/js/Components/Central/StoreCategoryTable.vue` | Tabel kategori Rintis dan formulir tambah/edit |
| `resources/js/Components/Central/AccessControl.vue` | Tombol aktivasi yang bisa digunakan dalam baris tabel maupun detail pelanggan |
| `app/Http/Controllers/Central/Admin/ProductController.php` | Data tabel, detail produk, filter pengguna, dan konfigurasi kategori Rintis |
| `app/Http/Controllers/Central/Admin/ProductAccessController.php` | Perubahan akses dari detail produk melalui action bersama |
| `app/Http/Controllers/Central/Admin/StoreCategoryController.php` | Perubahan kategori yang dibatasi ke konteks produk Rintis |
| `routes/central-admin.php` | Route detail produk serta pengguna/kategori di bawah produk |
| `resources/js/Layouts/Central/AdminLayout.vue` | Menghapus menu kategori Rintis dari sidebar |
| `tests/Feature/Central/ProductManagementTest.php` | Pengujian detail produk, isolasi konfigurasi, pengguna, dan filter |

Halaman kategori mandiri yang lama dihapus karena antarmukanya sudah digantikan tab dalam detail produk. Tabel database kategori dan hubungan toko tetap digunakan; tidak ada data yang dihapus.

### Pemeriksaan dan batasan

- Laravel Pint: selesai.
- Build frontend: berhasil.
- Seluruh suite: **91 tes lulus, 734 assertions**.
- Pengujian memeriksa jumlah pengguna per produk, filter status yang tidak mengambil akses produk lain, aktivasi/pencabutan yang tidak mengubah produk lain, penolakan konfigurasi kategori Rintis pada produk lain, serta penolakan endpoint baru bagi akun seller.
- Pencarian dan filter tetap dipertahankan sesudah perubahan akses dan saat berpindah halaman hasil.
- Tidak ada migration baru atau perubahan data akun/toko pada revisi tampilan ini.
- Pemeriksaan visual interaktif di browser belum dilakukan. Pemilik dapat refresh panel dan mengikuti alur di atas untuk menilai tampilannya.
- Perubahan ini menyederhanakan pengelompokan pengaturan. Harga, pembayaran, langganan otomatis, dan fitur katalog/pemesanan Rintis belum ditambahkan.


## Tahap 8 — Kategori produk dan katalog toko Rintis

Tanggal pengerjaan: 29 Oktober 2026.

### Tujuan

Membuat alur pengelola toko memasukkan kategori dan produk ke database, kemudian menampilkan produk yang diizinkan pada katalog pelanggan. Katalog toko berbeda dari daftar aplikasi pada panel Central dan dari kategori usaha Rintis.

### Hasil yang sudah dapat digunakan

| Fitur | Perilaku |
| --- | --- |
| Manage catalog | Tautan dari overview toko ke pengelolaan katalog |
| Categories | Menambahkan dan mengganti nama kategori produk milik toko, misalnya Coffee dan Non-Coffee |
| Products | Tabel produk toko dengan foto/pengganti gambar, kategori, harga dasar, visibilitas, dan tautan edit |
| Formulir produk | Nama, kategori, deskripsi opsional, harga rupiah utuh, foto opsional, dan pilihan Show in Our Products |
| Foto produk | Unggah, ganti, atau hapus foto; JPEG/PNG/WebP, maksimal 5 MB dan 4096 × 4096 piksel |
| Visibilitas | Produk baru tersembunyi sampai pengelola memilih untuk menampilkannya; produk tersembunyi tetap dapat dikelola |
| Katalog pelanggan | Menampilkan nama, kategori, deskripsi, dan foto/pengganti gambar untuk produk yang terlihat |
| Pencarian admin | Pencarian nama produk hanya dalam toko yang sedang dikelola |

Katalog Our Products tidak mengirim atau menampilkan harga kepada pengunjung, mengikuti kebutuhan Rintis. Harga disimpan untuk pengelolaan dan fondasi penjualan berikutnya. Belum ada tombol pesan atau open order aktif.

### Alur penggunaan

```text
Central → Admin panel → Products → Rintis → Users
    → Pastikan akun pengelola sudah memiliki akses aktif

My Rintis store → Buat toko jika belum ada → Overview
    → Manage catalog
        → Categories → Add category → simpan Coffee
        → Products → Add product → isi Iced Latte dan harga sebenarnya
            → foto opsional → pilih Show in Our Products → simpan
    → Preview store / Visit store → Our Products
```

Jika memakai akun seller terpisah, login dengan akun tersebut setelah diaktifkan oleh pemilik platform. Membuat akun pemilik Central tidak otomatis memindahkan toko dari akun lain.

Wemeet Coffee dan contoh nama produk di atas adalah petunjuk pengisian. Implementasi ini tidak membuat akun, toko, kategori, atau produk contoh secara otomatis. Masukkan data bisnis yang benar melalui formulir.

Toko yang belum dipublikasikan tetap dapat dilihat pemilik melalui pratinjau. Pengunjung lain hanya dapat mengakses toko yang sudah ditampilkan publik, dan hanya melihat produk yang visibilitasnya aktif.

### Data dan file penting

| File/data | Fungsi |
| --- | --- |
| `rintis_catalog_categories` | Kategori produk per toko, terpisah dari kategori usaha |
| `rintis_catalog_products` | Produk, harga dasar, foto, visibilitas, serta kepemilikan toko dan kategori |
| `app/Models/Rintis/CatalogCategory.php` | Model kategori produk toko |
| `app/Models/Rintis/CatalogProduct.php` | Model produk dagangan, bukan produk aplikasi Central |
| `app/Http/Controllers/Rintis/Admin/CatalogController.php` | Daftar, formulir, penyimpanan, pembaruan, dan pengelolaan foto produk |
| `app/Http/Controllers/Rintis/Admin/CatalogCategoryController.php` | Menambah dan mengganti nama kategori produk |
| `app/Http/Requests/Rintis/CatalogProductRequest.php` | Memvalidasi input produk dan kategori milik toko yang sama |
| `resources/js/Pages/Rintis/Admin/Catalog.vue` | Tabel produk dan kategori milik toko |
| `resources/js/Pages/Rintis/Admin/ProductForm.vue` | Formulir tambah/edit produk dengan pratinjau foto |
| `resources/js/Pages/Rintis/Storefront/Home.vue` | Katalog publik Our Products |
| `routes/rintis.php` | Route katalog per toko dengan scoped bindings dan data katalog publik |
| `tests/Feature/Rintis/CatalogTest.php` | Pengujian katalog, unggah, visibilitas, dan isolasi antartoko |

### Perlindungan data

- Seluruh pengelolaan memerlukan login, akses Rintis aktif, serta kepemilikan toko.
- Produk dan kategori pada URL harus menjadi milik toko pada URL tersebut. Mengganti ID produk atau kategori ke milik toko lain ditolak.
- Pemilik toko ditentukan server; input `store_id` dan `image_path` dari browser tidak digunakan untuk menetapkan kepemilikan atau jalur foto.
- Validasi kategori dibatasi pada toko yang sama. Database juga memiliki foreign key gabungan agar kategori produk tidak dapat berasal dari toko lain.
- Produk tersembunyi dan harga tidak disertakan dalam data katalog publik. Harga tetap tersedia untuk admin toko yang berwenang.
- Jika penyimpanan database gagal setelah foto baru ditulis, foto baru dibersihkan. Foto lama dihapus setelah penggantian berhasil atau ketika pengelola menghapus fotonya.

### Pemeriksaan yang dilakukan

- Migration dua tabel katalog berhasil diterapkan ke PostgreSQL tanpa menghapus data sebelumnya.
- Tautan penyimpanan publik untuk foto produk berhasil disiapkan melalui `storage:link`.
- Build frontend: berhasil.
- Laravel Pint: selesai.
- Seluruh suite: **110 tes lulus, 875 assertions**. Pengujian katalog baru mencakup 19 kasus.
- Tes meliputi kategori unik per toko, harga nol yang sah, input tidak valid, penggunaan kategori toko lain, penyusupan ID pada URL, penolakan pengelola tanpa akses, produk tersembunyi, penghilangan harga dari respons publik, dan pencarian yang terbatas pada toko sendiri.
- Pengujian foto memakai penyimpanan terisolasi: unggah/ganti/hapus, penolakan file non-gambar, ukuran/dimensi berlebih, serta pilihan ganti dan hapus yang bertentangan.
- Pengujian browser interaktif belum dilakukan; build dan tes HTTP tidak membuktikan seluruh tampilan atau interaksi pemilih file pada perangkat pengguna.

### Batasan dan langkah berikutnya

- Belum ada penghapusan produk atau kategori. Produk dapat disembunyikan dan kategori dapat diganti namanya. Penghapusan kategori dengan pengganti akan ditambahkan jika diperlukan sesuai aturan bisnis.
- Resep privat, HPP, pengelolaan varian, rating, dan inventaris belum dibuat.
- Katalog belum menerima pesanan. Open order, harga per periode, kuota, checkout, pembayaran, pickup, dan delivery masih tahap berikutnya.
- Pencarian pelanggan lintas toko, filter direktori, dan pencarian terdekat belum ditambahkan.
- Langkah berikutnya: pemilik mencoba kategori dan beberapa produk Wemeet Coffee. Setelah data katalog sesuai, lanjutkan periode open order beserta harga dan kuota per periode.


## Tahap 9 — Pengalaman setup, peta, wilayah, rekening, dan formulir yang lebih rapi

Tanggal pengerjaan: 29 Oktober 2026.

### Tujuan

Pemilik meminta lokasi yang bisa dipilih melalui pin, dropdown wilayah, tampilan setup yang interaktif, formulir tambah/edit yang tidak memanjang di bawah tabel, nominal rupiah otomatis, serta pengaturan rekening bank toko.

### Yang berubah

| Permintaan | Hasil implementasi |
| --- | --- |
| Peta di halaman settings | Leaflet menampilkan peta OpenStreetMap, tombol Use current location, pin yang dapat digeser, klik peta untuk memilih, dan tombol Place pin at map center |
| Tanpa mengetik koordinat | Latitude/longitude tidak lagi ditampilkan sebagai input pengguna; nilainya disimpan dari pilihan pin |
| Wilayah bertingkat | Dropdown Province → City / Regency → District melalui API Wilayah Indonesia, tanpa API key |
| Settings yang teratur | Identitas, lokasi, rekening, dan visibilitas menjadi section terpisah, dengan navigasi serta tombol simpan di samping pada layar lebar |
| Your starting point | Enam kartu yang bisa diklik, indikator kesiapan dari data sebenarnya, progress bar, dan tautan ke langkah berikutnya |
| Form tambah/edit rapi | Kategori katalog Rintis, kategori usaha pada detail produk, dan tambah aplikasi Central memakai dialog modal dengan fokus terkelola browser, Escape, dan backdrop |
| Rupiah otomatis | Mengetik 17000 menampilkan 17.000; nilai yang dikirim tetap angka 17000, bukan teks berformat |
| Rekening bank | Tambah, ubah, dan aktif/nonaktif rekening pada Store settings → Bank accounts |

### Cara memakai lokasi

1. Buka Store settings dan bagian Store location.
2. Pilih provinsi, kota/kabupaten, lalu kecamatan. Mengganti induk mengosongkan pilihan anak dan pin agar tidak memakai lokasi lama tanpa sengaja.
3. Isi alamat jalan secara lengkap.
4. Peta mengikuti area yang dipilih, tetapi titik pusat wilayah **tidak otomatis disimpan sebagai lokasi toko**.
5. Klik peta untuk menempatkan pin, geser pin untuk menyesuaikan, atau pilih Use current location jika perangkat sedang berada di toko.
6. Periksa pin sebelum menyimpan. Tombol Place pin at map center juga tersedia untuk memilih dari pusat peta, termasuk lewat navigasi keyboard peta.
7. Simpan settings. Pengaturan privasi alamat tetap berlaku: alamat lengkap dan koordinat tidak dikirim ke halaman publik saat opsi berbagi alamat dimatikan.

Lokasi perangkat membutuhkan izin browser dan dukungan konteks aman (HTTPS atau localhost). Menolak izin tidak menghalangi pemilihan pin secara manual. Tidak ada layanan geocoding atau perhitungan rute otomatis pada tahap ini. Pin tidak otomatis mengisi alamat atau menjamin titik berada di batas administratif yang dipilih.

### Sumber peta dan wilayah

- **Leaflet** adalah library peta yang dipasang sebagai dependensi frontend. Dokumentasi: https://leafletjs.com/examples/quick-start/
- **OpenStreetMap** menyediakan data peta bebas digunakan. Server tile standar memakai kapasitas donasi, memiliki aturan penggunaan, dan tidak menjamin layanan untuk trafik tak terbatas. Integrasi memakai URL tile HTTPS, atribusi terlihat, cache browser normal, tanpa unduhan massal/prefetch offline. Kebijakan: https://operations.osmfoundation.org/policies/tiles/
- URL penyedia tile disimpan di `config/rintis.php` dan dapat diganti melalui `RINTIS_MAP_TILES_URL`. Jika kelak memakai penyedia lain, perhatikan ketentuan serta atribusi penyedianya sebelum produksi berskala besar.
- **API Wilayah Indonesia** menyediakan referensi wilayah komunitas tanpa API key. Dokumentasi sumber: https://github.com/emsifa/api-wilayah-indonesia
- Aplikasi mengambil daftar melalui Laravel pada endpoint same-origin, dengan cache 24 jam. Browser tidak perlu menyimpan kredensial pihak ketiga.
- Data wilayah adalah referensi dari sumber komunitas tersebut, bukan klaim integrasi langsung ke database pemerintah. Ketersediaan dan perubahan data mengikuti penyedianya.
- Koneksi nyata berhasil diperiksa: 38 provinsi, 14 kota/kabupaten Kalimantan Barat, dan 5 kecamatan Singkawang saat pemeriksaan. Cache awal ketiga daftar tersebut sudah terisi.

Jika API wilayah gagal, UI memberi informasi dan tombol Retry. Data alamat lama tidak dihapus otomatis. Data toko lama yang masih berupa nama wilayah tetap dipertahankan; jika dapat dicocokkan ke hierarki sumber, pilihan dropdown mengisi kode wilayahnya saat diedit. Pilihan kode baru diverifikasi di server dan nama disimpan dari sumber, bukan dipercaya dari teks browser. Kode yang tidak berubah dapat disimpan kembali tanpa permintaan eksternal tambahan. Gagal memvalidasi pilihan wilayah baru mencegah penyimpanan parsial settings/rekening.

### Cara mengatur rekening

1. Buka Store settings → Bank accounts.
2. Klik Add account, isi bank, nama pemilik rekening, nomor rekening, dan instruksi opsional.
3. Nomor rekening disimpan sebagai teks agar nol di depan tidak hilang. Spasi dan tanda hubung pada input dibersihkan.
4. Gunakan Enable this bank account untuk mengaktifkan atau menonaktifkannya, lalu Save settings.
5. Bisa menyimpan sampai 10 rekening pada satu formulir. Baris baru yang belum disimpan dapat dibuang. Rekening tersimpan dinonaktifkan melalui checkbox; tidak dihapus lewat UI.

Rekening adalah data pengaturan toko, **bukan integrasi verifikasi bank atau penerimaan pembayaran**. Rekening hanya dikirim ke formulir admin toko yang berhak, belum ditampilkan pada direktori atau katalog publik. Snapshot rekening ke pesanan dan checkout transfer masih perlu dibangun pada tahap transaksi.

### Format nominal dan dialog

Harga dasar memakai komponen input rupiah bersama. Nilai kosong dan nol tetap dibedakan. Pasting Rp 17.000 atau 17.000,00 menghasilkan angka utuh 17000; nilai negatif dan pecahan rupiah tidak dikonversi diam-diam menjadi nominal lain. Batas harga server yang sudah ada tetap berlaku.

Tombol Add category/Edit membuka dialog di atas halaman sehingga tabel tidak bergeser memanjang. Simpan sukses menutup dialog dan memperbarui data tabel. Animasi dialog dan kartu menghormati pengaturan reduce motion perangkat. Form produk tetap berupa halaman khusus, dengan input nominal yang sudah diformat.

### File dan data penting

| File/data | Fungsi |
| --- | --- |
| `config/rintis.php` | Konfigurasi sumber wilayah dan URL tile peta |
| `app/Services/Rintis/RegionDirectory.php` | Mengambil, merapikan, dan menyimpan cache daftar wilayah |
| `app/Http/Controllers/Rintis/RegionController.php` | Endpoint wilayah untuk pengelola dengan validasi tingkat dan induk |
| Kolom `province_code`, `city_code`, `district_code` pada `rintis_stores` | Menyimpan identitas pilihan wilayah bersama labelnya |
| `rintis_bank_accounts` | Rekening bank per toko, dengan status aktif dan instruksi |
| `app/Http/Requests/Rintis/StoreRequest.php` | Validasi hierarki wilayah, rekening, dan kepemilikan rekening |
| `resources/js/Components/Rintis/LocationPicker.vue` | Peta, pin, lokasi perangkat, dan pemilihan titik |
| `resources/js/Components/Rintis/RegionFields.vue` | Dropdown bertingkat dan penanganan permintaan yang sudah kedaluwarsa |
| `resources/js/Components/Rintis/BankAccountsFields.vue` | Formulir rekening dengan nomor tetap berbentuk teks |
| `resources/js/Components/Shared/Dialog.vue` | Dialog modal bersama |
| `resources/js/Components/Shared/RupiahInput.vue` dan `resources/js/Support/rupiah.js` | Tampilan rupiah dan konversi nilai input ke angka |
| `resources/js/Pages/Rintis/Admin/Overview.vue` | Checklist setup interaktif berdasarkan data toko |
| `resources/js/Pages/Rintis/Admin/StoreForm.vue` | Susunan settings baru |
| `tests/Feature/Rintis/StoreExperienceTest.php` | Pengujian wilayah, rekening, privasi, dan data starting point |
| `tests/Frontend/rupiah.test.js` | Tes format/parse rupiah menggunakan test runner bawaan Node |

### Pemeriksaan yang dilakukan

- Leaflet berhasil dipasang; audit npm saat pemasangan tidak melaporkan kerentanan.
- Migration kode wilayah dan rekening berhasil diterapkan ke PostgreSQL tanpa menghapus data sebelumnya.
- Laravel Pint: selesai.
- Build frontend: berhasil.
- Seluruh suite Laravel: **124 tes lulus, 974 assertions**.
- Tes frontend rupiah: **3 tes lulus**, dijalankan dengan `node --test tests/Frontend/rupiah.test.js`.
- Tes backend mencakup cache API, parent wilayah tidak valid, kegagalan penyedia, normalisasi label di server, rekening toko lain, nomor rekening dengan nol di depan, batas rekening, update data lama, dan tidak bocornya rekening ke respons publik.
- API wilayah juga diperiksa langsung melalui service aplikasi dan berhasil, di luar respons tiruan pengujian.
- Belum tersedia alat pengujian browser interaktif pada sesi ini. Drag pin, izin lokasi browser, fokus dialog, serta caret input nominal belum diverifikasi di browser nyata; build dan pengujian fungsi/backend sudah berhasil.

### Batasan dan langkah berikutnya

- Peta dan pilihan wilayah baru memerlukan koneksi ke sumber terkait; tidak ada fitur peta offline.
- Tidak ada pencarian alamat otomatis, reverse geocoding, pencarian toko terdekat, atau kalkulasi ongkir/rute pada tahap ini.
- Pengaturan rekening belum mengaktifkan checkout, verifikasi transfer, atau pencatatan pembayaran.
- Checklist starting point adalah rekomendasi persiapan; rekening dan pin tidak dijadikan syarat baru publikasi toko tanpa keputusan bisnis.
- Berikutnya: pemilik mencoba settings, pin lokasi Wemeet Coffee, dropdown wilayah, rekening, dan pengelolaan kategori/produk. Setelah tampilan dan data sesuai, lanjutkan periode open order.


## Tahap 10 — Navigasi seller, persiapan ringkas, dan pengaturan pengiriman

Tanggal: 29 Oktober 2026.

### Tujuan dan hasil

- Direktori seller sekarang membuka **Open my store** ke admin Rintis untuk akun dengan akses aktif. Akun tanpa akses tetap diarahkan ke akun Oneclick. Navbar **My products** berubah menjadi **Oneclick account** agar tidak disangka katalog toko.
- Persiapan menjadi satu panel: progress bar, enam baris langkah berurutan, status Done, dan satu tombol langkah berikutnya. Kartu besar yang memenuhi halaman diganti daftar ringkas.
- Setelah semua langkah siap, tekan **Finish setup**. Server memeriksa kelengkapan serta kepemilikan dan menyimpan `setup_completed_at`. Panduan tidak muncul lagi setelah selesai, termasuk ketika produk kemudian disembunyikan. Settings dan katalog tetap tersedia. Bank dan pin tetap bagian checklist rekomendasi, bukan syarat baru untuk publikasi.
- Save settings berhasil mengarahkan kembali ke **Store overview** dengan pesan sukses. Validasi gagal tetap di formulir beserta error.
- Store settings → **Pickup & delivery** menyediakan pickup gratis, petunjuk pickup, delivery, pilihan ongkir tetap atau per kilometer jalan, biaya minimum, dan jangkauan maksimum dalam meter. Tidak ada angka tarif yang ditentukan otomatis untuk seller. Nol yang sengaja diisi merupakan tarif valid.
- Delivery memerlukan pin toko; nominal negatif, tarif wajib kosong, dan jangkauan nol ditolak server. Delivery dapat dimatikan tanpa menghapus pengaturan yang sudah tersimpan.

### Alur lokasi dan peta untuk pemilik yang sedang belajar

1. Route settings menjalankan `StoreController::edit`: policy memeriksa pemilik lalu controller mengirim data toko, URL wilayah, dan konfigurasi tile ke Vue StoreForm.
2. `RegionFields.vue` mengambil provinsi dari endpoint Laravel `/rintis/regions`. Memilih provinsi mengambil kota/kabupaten pada provinsi itu, lalu memilih kota mengambil kecamatannya. Server memakai RegionDirectory dan cache sumber wilayah.
3. Dropdown menyimpan **kode dan nama administratif**. Area pusat wilayah hanya mengarahkan tampilan peta, tidak menentukan titik toko. Mengganti induk wilayah mengosongkan anak dan pin agar lokasi lama tidak tersimpan tanpa sengaja.
4. `LocationPicker.vue` memakai Leaflet untuk interaksi peta, sedangkan gambar petanya dimuat dari OpenStreetMap. Klik atau geser pin menentukan titik toko. Use current location meminta izin perangkat, lalu seller memeriksa titiknya. Lokasi perangkat bisa berbeda dari lokasi toko.
5. Pin menyimpan latitude/longitude secara internal. Alamat jalan tetap ditulis seller: peta belum mengubah pin menjadi teks alamat, dan belum memeriksa batas administratif titik tersebut.
6. Save settings mengirim PATCH ke route update. StoreRequest memvalidasi input, hierarki kode baru, dan pengaturan delivery. Controller menyimpan toko/rekening dalam transaksi lalu redirect ke overview. Browser menampilkan Vue overview melalui Inertia.
7. Alamat lengkap dan pin tetap privat jika seller tidak mengaktifkan berbagi alamat publik. Titik asal internal delivery tidak memaksa koordinat tampil di direktori publik.

### Arti aturan pengiriman

- Pickup: pelanggan mengambil sendiri di toko; gratis ongkir.
- Delivery tetap: satu nominal tetap, tetap memiliki batas jangkauan.
- Delivery per kilometer: rencana ongkir = maksimum antara biaya minimum dan kilometer **rute jalan** × tarif; bulatkan ke rupiah utuh. Contoh 1.200 meter = 1,2 km, tarif Rp 5.000/km dan minimum Rp 5.000 menghasilkan Rp 6.000.
- Jangkauan disimpan dalam meter: 5.000 meter = 5 km. Tarif ditetapkan per km agar seller tidak perlu mengisi pecahan rupiah per meter.
- **Tahap ini menyimpan konfigurasi saja.** Belum ada checkout, panggilan penyedia routing, penghitungan ongkir transaksi, atau pengecekan tujuan pelanggan. Peta tidak otomatis mengetahui jarak jalan. Saat transaksi dibangun, tujuan di luar batas atau kegagalan layanan rute harus ditolak tanpa ongkir rekaan. Snapshot pengaturan periode/pesanan masih tahap berikutnya.

### File penting

- `resources/js/Layouts/Rintis/RintisLayout.vue` dan `Pages/Rintis/Directory.vue`: label dan tujuan navigasi seller.
- `resources/js/Pages/Rintis/Admin/Overview.vue`: progress ringkas dan penyelesaian setup.
- `resources/js/Pages/Rintis/Admin/StoreForm.vue`: formulir pengiriman serta redirect tanpa mempertahankan scroll lama.
- `app/Http/Requests/Rintis/StoreRequest.php`: validasi pickup/delivery dan pin.
- `app/Http/Controllers/Rintis/Admin/StoreController.php`: data settings, redirect sukses, dan penyelesaian setup yang diverifikasi server.
- `app/Models/Rintis/Store.php`, `routes/rintis.php`, migration `2026_10_29_080959_add_fulfillment_settings_to_rintis_stores.php`: penyimpanan pengaturan, casts, serta endpoint setup yang memakai auth, akses produk, dan policy pemilik.
- `tests/Feature/Rintis/StoreExperienceTest.php`: tambah cakupan konfigurasi, nol ongkir, kegagalan validasi, penyelesaian setup, dan isolasi pemilik.

### Cara mencoba dan pemeriksaan

1. Buka Rintis → Open my store. Perhatikan navigasi Oneclick account.
2. Buka Store settings → Pickup & delivery. Pilih pickup, delivery, tarif dan batas jarak, lalu Save settings; aplikasi kembali ke overview.
3. Lengkapi enam langkah awal. Tekan Finish setup untuk menutup panduan secara permanen.
4. Laravel Pint selesai. Build frontend berhasil. Seluruh **129 tes Laravel lulus, 1.003 assertions**.
5. Migration berhasil diterapkan ke PostgreSQL lokal tanpa menghapus data. Akses jaringan localhost memerlukan eksekusi di luar sandbox dan berhasil.
6. Belum dilakukan pengujian interaksi browser nyata. Langkah berikutnya: coba alur seller dan tentukan penyedia rute ketika checkout/open order mulai dibangun.


## Tahap 11 — Hapus rekening tersimpan dan label pickup

Tanggal: 29 Oktober 2026.

- Rekening baru maupun tersimpan sekarang memiliki tombol **Delete account**. Klik tombol untuk mengeluarkan rekening dari formulir, lalu **Save settings** untuk menghapusnya dari database. Keluar tanpa menyimpan tidak menghapus rekening tersimpan.
- Server memvalidasi bahwa semua ID penghapusan milik toko yang sedang dikelola. Penghapusan dilakukan dalam transaksi settings; rekening lain yang tidak dipilih tetap dipertahankan. Request lama tanpa daftar penghapusan tetap aman.
- Label **Enable free pickup** diubah menjadi **Enable pickup**. Perubahan ini hanya label; aturan ongkir pickup tidak berubah.
- File: `BankAccountsFields.vue` untuk tombol dan pemberitahuan simpan; `StoreForm.vue` untuk daftar penghapusan serta label pickup; `StoreRequest.php` untuk validasi kepemilikan; `StoreController.php` untuk penghapusan yang dibatasi relasi toko; `StoreExperienceTest.php` untuk keberhasilan penghapusan dan penolakan rekening toko lain.
- Pemeriksaan: Pint berhasil; 21 tes StoreExperienceTest lulus (137 assertions); build frontend berhasil. Interaksi browser belum diuji langsung. Tidak memerlukan migration.
- Cara mencoba: Store settings → Bank accounts → Delete account → Save settings; buka settings kembali untuk memastikan rekening hilang. Berikutnya lanjutkan alur open order sesuai rencana.


## Tahap 12 — Jarak delivery fleksibel dan penjelasan konfigurasi peta

Tanggal: 29 Oktober 2026.

### Perubahan yang dapat digunakan

- Menghapus **Minimum delivery fee** dari formulir dan validasi input; menggantinya dengan **Minimum delivery distance**.
- Minimum/maksimum jarak masing-masing memiliki pilihan **Meters / Kilometers**. Pilihan unit mengubah tampilan, bukan jarak fisiknya: 5 km sama dengan 5.000 m. Nilai disimpan sebagai integer meter, minimum positif dan tidak melebihi maksimum.
- Tarif jarak dapat memakai rupiah utuh per meter atau kilometer, dinormalisasi per kilometer di server. Pilihan meter tidak tersedia bila nominal per km menghasilkan pecahan rupiah per meter agar pergantian unit tidak mengubah nilai tarif diam-diam.
- Contoh konfigurasi: minimum 100 m, maksimum 5 km. Tujuan 10 m tidak memenuhi rentang; tujuan 2 km memenuhi rentang. Ini belum pengecekan checkout karena layanan rute/pemesanan belum dibangun.
- Open order ditegaskan sebagai alur default. Stok/manajemen stok merupakan rencana premium, belum dibangun. Master produk yang ada dipakai kembali saat periode open order dikembangkan. Pengaturan periode dan snapshot aturan belum diimplementasikan oleh perubahan ini.

### File dan pemeriksaan

- `resources/js/Components/Shared/DistanceInput.vue`: input jarak dan konversi tampilan meter/km.
- `resources/js/Pages/Rintis/Admin/StoreForm.vue`: rentang jarak, satuan tarif, dan keterangan konteks open order.
- `app/Http/Requests/Rintis/StoreRequest.php`: minimum wajib saat delivery aktif, batas nilai, validasi minimum tidak melebihi maksimum. Biaya minimum lama tidak lagi diterima.
- `app/Models/Rintis/Store.php` dan `StoreController.php`: penyimpanan serta pengiriman minimum jarak ke Vue.
- Migration `2026_10_29_083636_add_minimum_delivery_distance_to_rintis_stores.php`: kolom baru nullable agar tidak mengarang batas bagi toko lama; seller mengisinya saat menyimpan delivery aktif berikutnya.
- `tests/Feature/Rintis/StoreExperienceTest.php`: pembaruan kontrak input dan tes penolakan rentang terbalik tanpa perubahan data.
- Pint selesai; **132 tes Laravel lulus, 1.015 assertions**; build frontend berhasil. Migration diterapkan ke PostgreSQL lokal. Tidak ada pengujian interaksi browser nyata pada sesi ini.
- Cara mencoba: settings → delivery → pilih harga, isi minimum/maksimum beserta satuannya → Save settings → buka ulang untuk memeriksa jarak. Berikutnya membangun periode open order sebelum checkout atau stok premium.

### Panduan belajar: peran masing-masing bagian

| Bagian | Tanggung jawab |
| --- | --- |
| Laravel | Menyediakan konfigurasi, validasi, endpoint wilayah, dan menyimpan data toko |
| API Wilayah Indonesia (emsifa) | File JSON daftar nama/kode provinsi, kota/kabupaten, kecamatan dan titik referensi area |
| Leaflet | Library JavaScript untuk mengatur tampilan, zoom, pergeseran peta, layer gambar, dan marker |
| OpenStreetMap tile server | Mengirim potongan gambar peta PNG yang ditampilkan Leaflet |
| Browser Geolocation | Meminta izin perangkat untuk mendapatkan koordinat saat ini |
| PostgreSQL | Menyimpan wilayah, alamat, dan titik yang dipilih untuk toko |

Leaflet tidak menjadi database seluruh data OSM. Browser mengambil gambar tile sesuai area/zoom, kemudian Leaflet menyusunnya di dalam komponen. API wilayah terpisah dari tile peta: API memasok pilihan dropdown, tile memasok gambar peta. Aplikasi menyambungkan keduanya dengan mengarahkan peta ke titik referensi area saat pilihan berubah.

### Langkah 1 — Membaca `config/rintis.php`

```php
<?php

return [
    'regions_url' => env('RINTIS_REGIONS_URL', 'https://www.emsifa.com/api-wilayah-indonesia/v2'),
    'map_tiles_url' => env('RINTIS_MAP_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
];
```

- `<?php` membuka kode PHP.
- `return` mengembalikan nilai dari file kepada Laravel.
- `[...]` adalah array, di sini daftar pasangan nama konfigurasi dan nilainya.
- `'regions_url' => ...` berarti nama pengaturan `regions_url` memiliki nilai di sebelah kanan tanda panah. Tanda panah menghubungkan key dengan value, bukan route.
- `env('NAMA', 'cadangan')` membaca pengaturan environment. Jika variabel tidak tersedia, gunakan nilai cadangan. Dengan demikian URL tetap bekerja meski RINTIS_REGIONS_URL belum ditambahkan ke `.env`.
- `,` memisahkan isi array; `;` mengakhiri perintah return.
- Nama file `rintis.php` menjadi kelompok config. `config('rintis.regions_url')` berarti ambil key regions_url dari konfigurasi rintis.
- `.env` untuk override per lingkungan; `config/rintis.php` mendefinisikan nama dan default; kode aplikasi membaca `config()`, bukan membaca env langsung.

Contoh override opsional (tidak diperlukan jika menggunakan default):

```dotenv
RINTIS_REGIONS_URL=https://www.emsifa.com/api-wilayah-indonesia/v2
RINTIS_MAP_TILES_URL=https://tile.openstreetmap.org/{z}/{x}/{y}.png
```

Menulis URL di config belum mengambil data dari internet. Data baru diambil ketika service memanggil HTTP atau Leaflet meminta tile. Jika config pernah di-cache, perubahan environment memerlukan pembaruan cache konfigurasi, misalnya `php artisan config:clear` saat pengembangan. Ini berbeda dari cache daftar wilayah 24 jam.

### Langkah 2 — Route membuka settings

Browser membuka `/rintis/admin/stores/wemeet-coffee/settings`. `routes/rintis.php` mencocokkan GET tersebut ke StoreController::edit. Middleware memeriksa login/akses produk Rintis; policy memeriksa kepemilikan toko. Controller mengirim props ke `Rintis/Admin/StoreForm` melalui Inertia:

```php
'regionsUrl' => route('rintis.regions'),
'mapTilesUrl' => config('rintis.map_tiles_url'),
```

`route()` menghasilkan URL endpoint aplikasi kita; `config()` mengambil URL pola tile dari konfigurasi. Inertia membawa keduanya menjadi props Vue bersama data toko.

### Langkah 3 — Dropdown meminta daftar wilayah

StoreForm merender RegionFields dengan endpoint regionsUrl. Saat komponen dipasang, RegionFields meminta `/rintis/regions?level=provinces`. Endpoint ini milik aplikasi sendiri dan memakai session login; bukan API key.

RegionController memvalidasi level/parent, lalu RegionDirectory menyusun path:

- provinces → `provinces.json`
- cities, parent 61 → `regencies/61.json`
- districts, parent 61.72 → `districts/61.72.json`

Service menggabungkan base URL dari config dengan path. Contoh URL provinsi: `https://www.emsifa.com/api-wilayah-indonesia/v2/provinces.json`. Laravel mengambil JSON dan merapikan menjadi id/name/lat/lng. Daftar di-cache 24 jam; permintaan selanjutnya bisa memakai cache tanpa menghubungi sumber lagi. Bila sumber gagal dan cache tidak tersedia, UI menampilkan error/Retry, tidak membuat data palsu.

Repository GitHub adalah tempat kode/dokumentasi sumber, sedangkan URL emsifa pada config adalah endpoint JSON yang dipanggil aplikasi. Browser seller tidak perlu memasang repository tersebut.

### Langkah 4 — Hubungan dropdown dengan peta

Ketika memilih kota, RegionFields menyimpan kode/nama terpilih dan mengirim event `focus` berisi titik referensi area. StoreForm menerima lewat `@focus="focusArea = $event"`, lalu meneruskannya lewat `:focus-area="focusArea"` ke LocationPicker. Watcher pada LocationPicker menjalankan `map.setView(...)` agar peta berpindah ke area tersebut. Titik pusat kota belum merupakan lokasi toko. Mengganti wilayah mengosongkan pin lama untuk mencegah alamat baru memakai titik lama.

### Langkah 5 — Leaflet menggambar peta

Leaflet sudah terpasang melalui npm. LocationPicker mengimpor `leaflet` serta CSS-nya. Saat elemen peta siap, `onMounted` menjalankan `L.map(...)` untuk membuat map di container, lalu `L.tileLayer(props.tilesUrl, ...).addTo(map)` untuk menambahkan gambar dasar.

Pola URL `{z}/{x}/{y}` diisi Leaflet: z tingkat zoom, x kolom tile, y baris tile. Potongan PNG dari OSM diminta browser secara langsung, lalu disusun menjadi peta. Bergerak/zoom meminta potongan yang sesuai; browser mengelola cache HTTP. Teks atribusi OpenStreetMap contributors tetap tampil. Tidak ada pengunduhan seluruh peta atau penyimpanan gambar tile ke tabel toko.

### Langkah 6 — Memilih pin dan lokasi perangkat

Klik peta menghasilkan event `latlng`; LocationPicker menulis latitude/longitude ke model Vue. Dragend memindahkan nilai tersebut ke titik baru. `v-model:latitude` dan `v-model:longitude` menghubungkan nilai komponen dengan form StoreForm.

Use current location memanggil `navigator.geolocation.getCurrentPosition`. Browser meminta izin, memperoleh koordinat perangkat, lalu komponen memindahkan pin. Leaflet/OSM bukan yang menentukan posisi GPS. Izin ditolak tetap memungkinkan klik pin manual. Alamat jalan tetap diisi seller; belum ada reverse geocoding untuk mengubah koordinat menjadi alamat.

### Langkah 7 — Simpan ke database

Save settings menjalankan `form.patch(submitUrl)`. Route PATCH mengarah ke update. StoreRequest memeriksa nilai, hak pemilik, kode wilayah baru, rekening, pin dan aturan pengiriman. StoreController menyimpan dalam transaksi lalu redirect ke overview. Wilayah/alamat/koordinat masuk tabel `rintis_stores`; tile peta dan seluruh daftar wilayah tidak disalin ke tabel ini. Membuka settings lagi membaca titik tersimpan dan menampilkan marker di posisi tersebut.

Alamat penuh/koordinat tidak dikirim ke halaman publik jika izin berbagi alamat penuh dimatikan. Perhitungan jarak jalan membutuhkan integrasi routing tersendiri pada checkout; Leaflet, tile OSM, dan dropdown wilayah belum menyediakan perhitungan tersebut.

Referensi: https://leafletjs.com/examples/quick-start/ ; https://github.com/emsifa/api-wilayah-indonesia ; https://operations.osmfoundation.org/policies/tiles/


## Tahap 13 — Notifikasi simpan sementara

Tanggal: 29 Oktober 2026.

Pesan Store settings saved pada overview sebelumnya ditampilkan selama prop status masih ada di halaman, tanpa timer/tombol tutup. `resources/js/Pages/Rintis/Admin/Overview.vue` kini menampilkan notifikasi selama 5 detik dan menyediakan tombol tutup. Timer dibersihkan ketika halaman dilepas; pesan mengikuti respons halaman terbaru. Keterangan checklist di footer juga disembunyikan setelah setup selesai. Kartu Pickup & delivery tetap merupakan ringkasan pengaturan operasional yang bisa dibuka kembali, bukan langkah setup yang belum selesai.

Cara mencoba: simpan settings, perhatikan notifikasi menghilang setelah 5 detik atau klik ×. Build frontend berhasil; perilaku waktu/tombol belum diperiksa melalui browser nyata. Tidak ada perubahan backend/database. Penjelasan belajar selanjutnya disederhanakan menjadi satu contoh config dahulu sebelum membahas alur request lengkap.


## Tahap 14 — Memulihkan akun pemilik setelah migrate:fresh

Tanggal: 30 Oktober 2026.

Pengguna menjalankan migrate:fresh sehingga tabel dan data sebelumnya, termasuk akun pemilik, terhapus. Akun pemilik yang telah ditunjuk sebelumnya dibuat kembali melalui perintah bawaan `oneclick:make-owner` dengan opsi `--create`; password dimasukkan melalui prompt tersembunyi dan tidak disimpan dalam laporan. Peran pemilik platform berhasil diberikan dan diverifikasi di PostgreSQL lokal. Tidak ada perubahan kode, migration, atau seeder.

Cara mencoba: login kembali memakai akun OneClickStore dan password yang telah diberikan sebelumnya. Data toko, katalog, rekening, dan akses produk yang terhapus tidak dipulihkan oleh pembuatan akun ini. DatabaseSeeder saat ini hanya membuat akun Test User; tidak otomatis membuat pemilik platform. Gunakan `php artisan migrate` untuk menerapkan migration baru sambil mempertahankan data. `migrate:fresh` membangun ulang struktur dengan menghapus tabel lama. Login melalui browser belum diperiksa pada sesi ini.


## Tahap 15 — Pisahkan seller/pembeli dan sederhanakan persiapan toko

Tanggal selesai: 31 Oktober 2026.

### Tujuan dan hasil yang bisa dipakai

1. **Pintu masuk sesuai pengguna.** `/rintis` mengarahkan seller dengan akses aktif ke workspace, sedangkan pengunjung lain ke `/rintis/explore`. Tombol Rintis pada Home Central memakai pintu masuk ini. Seller tetap bisa memilih Shop as customer untuk melihat toko lain, dan kembali melalui Back to my store. Hak mengelola toko lain tetap ditolak policy.
2. **Pembeli boleh tanpa akun.** Direktori dan katalog publik tetap terbuka; CTA menjadi Browse stores, tidak mengajak membuat akun sebagai syarat melihat toko. Guest checkout belum ada, sehingga belum ada pengumpulan data pesanan tamu atau pemberian akses detail pickup.
3. **Nama unik dan URL rapi.** Slug dinormalisasi langsung saat input menjadi huruf kecil/angka/hyphen, dan diverifikasi ketersediaannya bersama nama setelah jeda 500 ms. Nama dianggap sama walaupun kapital/spasi berlebih berbeda. Database menyimpan name_key dengan unique index; backfill mengisi toko lama dan berhenti jika ada duplikasi tanpa mengubah nama otomatis. Permintaan bersamaan yang bertabrakan menghasilkan validasi, bukan penyimpanan nama ganda.
4. **Lokasi privat.** Checkbox alamat publik dihapus. Server tidak mengirim alamat lengkap, kecamatan atau koordinat ke halaman publik, meskipun flag lama aktif. Kota dan provinsi tetap terlihat. Rincian alamat pickup akan tersedia melalui pesanan terkait saat fitur transaksi dibangun.
5. **Rekening lebih sederhana.** Transfer instructions dihapus dari UI/props, perubahan instruksi lama dari request diabaikan; data historis tidak dihapus. Nomor rekening tetap berbentuk teks dan dapat dihapus melalui Delete account lalu Save settings.
6. **Halaman toko otomatis.** Pembuatan toko valid langsung menyimpan is_listed=true. Checkbox dan section Ready to be discovered dihapus; data identitas/kategori/alamat diwajibkan saat membuat toko. Payload lama yang meminta private/unlisted tidak mengganti aturan tersebut. Record lama yang belum listed tetap tersembunyi sampai disimpan melalui aturan baru; tidak dipublikasikan massal oleh migration.
7. **Setup dua tahap.** Store settings mencakup identitas/kategori, alamat/pin, dan setidaknya satu metode penerimaan yang valid; Catalog mencakup kategori dan produk yang terlihat. Rekening bukan syarat wajib penyelesaian. Finish setup tetap diperiksa server dan menyimpan timestamp. Kartu permanen Pickup & delivery pada overview dihapus; pengaturan tetap tersedia lewat Settings.

### File penting dan fungsinya

- `routes/rintis.php`, `routes/web.php`: route pintu masuk, explore publik, dan endpoint pengecekan ketersediaan seller dengan auth/akses produk/throttle.
- `app/Http/Controllers/Rintis/StoreAvailabilityController.php`: mengembalikan hanya boolean ketersediaan nama dan slug; pengecualian toko sendiri berasal dari pengguna login, bukan ID klien.
- `app/Http/Requests/Rintis/StoreRequest.php`: normalisasi input, validasi nama unik, aturan publikasi otomatis dan privasi.
- `app/Models/Rintis/Store.php`: name_key terkontrol saat model disimpan, penentuan kesiapan Settings, profil publik yang dibatasi.
- `database/migrations/2026_10_30_165759_add_unique_name_key_to_rintis_stores.php`: backfill dan unique index tanpa menghapus data.
- `app/Http/Controllers/Rintis/Admin/StoreController.php`: props baru, penyelesaian setup dua tahap, transaksi dan penanganan bentrok unique constraint.
- `app/Http/Controllers/Rintis/Admin/CatalogController.php`: URL navigasi workspace pada halaman katalog/produk.
- `resources/js/Layouts/Rintis/RintisLayout.vue`: navigasi berbeda untuk workspace dan halaman explore.
- `resources/js/Pages/Rintis/Directory.vue`, `Storefront/Home.vue`: penjelajahan tanpa syarat akun; tidak menampilkan alamat privat.
- `resources/js/Pages/Rintis/Admin/StoreForm.vue`, `Overview.vue`, `Components/Rintis/BankAccountsFields.vue`: formulir yang disederhanakan, verifikasi saat mengetik, setup dua tahap.
- `resources/js/Support/storeIdentity.js`: normalisasi slug untuk input/hasil akhir.
- `tests/Feature/Rintis/SellerWorkflowTest.php`, tes Rintis terkait dan `tests/Frontend/storeIdentity.test.js`: kontrak navigasi, privasi, keunikan, setup dan slug.

### Cara mencoba

1. Buka `/rintis` tanpa login: menuju Explore stores. Buka link toko tanpa membuat akun.
2. Login sebagai seller dengan akses aktif, buka `/rintis`: menuju toko sendiri. Pilih Shop as customer untuk explore toko lain; tombol edit toko lain tetap tidak bisa diakses.
3. Buat toko atau buka Settings. Ketik nama dan URL, tunggu hasil ketersediaan. Nama kapital/spasi berbeda dari nama yang sudah dipakai tetap ditolak.
4. Lengkapi Settings dan Catalog; klik Finish setup. Tidak ada checkbox publikasi/alamat publik atau kartu Pickup & delivery di overview.
5. Coba halaman toko publik: hanya wilayah umum tampil. Pengaturan lokasi lengkap tetap terlihat pada admin toko sendiri.

### Pemeriksaan

- Laravel Pint selesai pada seluruh PHP yang diubah.
- Seluruh suite: **142 tes Laravel lulus, 1.098 assertions**.
- Frontend: **5 tes JavaScript lulus** (rupiah dan slug).
- Build frontend berhasil; daftar route diperiksa.
- Migration berhasil diterapkan pada PostgreSQL lokal. Kolom name_key tersedia dan tidak ada record dengan name_key kosong/null.
- Pengujian lama disesuaikan secara eksplisit dengan keputusan terbaru: draft menjadi validasi wajib, slug yang dapat dirapikan menjadi uji normalisasi, alamat opt-in menjadi tetap privat, dan instruksi transfer tidak lagi diperbarui. Cakupan kepemilikan, kegagalan input dan pembatasan akses tetap dipertahankan.
- Belum ada pengujian interaksi browser nyata. Timer/debounce, caret slug, dan perpindahan navigasi perlu dicoba di browser pengguna.

### Batasan dan tahap berikutnya

- Pemeriksaan saat mengetik tidak mereservasi nama/URL. Jika jaringan gagal, UI memberi informasi dan validasi akhir tetap dilakukan di server saat Save. Request lama dibatalkan saat input berubah; pemeriksaan jaringan dibatasi 10 detik.
- Alamat pickup belum dapat diberikan ke pembeli karena pesanan/guest checkout belum dibangun. Tidak ada endpoint publik baru yang membocorkan lokasi.
- Open order tetap alur default yang disepakati. Pembuatan periode, snapshot aturan, kuota periode, guest checkout, pelacakan pesanan tamu, rating, dan direktori gabungan open order adalah pekerjaan berikutnya. Manajemen stok premium tetap ditunda.


## Tahap 16 — Setup otomatis dan navigasi seller yang lebih ringkas

Tanggal: 31 Oktober 2026.

### Hasil dan cara mencoba

- Tombol Finish setup dihapus. Setelah Store settings lengkap dan ada kategori serta minimal satu produk terlihat, penyimpanan otomatis mengisi setup_completed_at. Membuat produk tersembunyi belum menyelesaikan tahap Catalog.
- Urutan pengisian bebas: Settings lalu Catalog, atau Catalog lalu Settings. Penyimpanan terakhir yang melengkapi keduanya menyelesaikan setup. Timestamp hanya diisi sekali sehingga panduan tidak muncul lagi setelah produk disembunyikan.
- Navbar utama kini My store, Catalog, Settings. Klik nama pengguna untuk membuka Shop as customer, Sign out, serta Switch app hanya bagi akun dengan lebih dari satu produk aktif. Akses produk nonaktif dan akses milik akun lain tidak dihitung. Pemilik platform memiliki pintasan Platform admin khusus di menu tersebut.
- Menu dapat ditutup melalui klik di luar, Escape, atau perpindahan halaman. Tidak menambahkan dependensi frontend.
- Cara mencoba: lengkapi Settings → tambah kategori → tambah produk terlihat → kembali ke My store. Panduan hilang otomatis. Buka menu nama pengguna untuk berpindah ke area pembeli. Akun satu produk tidak memiliki pilihan Switch app.

### Alur untuk belajar

1. `CatalogController::persist()` menyimpan perubahan produk dalam transaksi.
2. Sebelum transaksi selesai, controller memanggil `Store::completeSetupIfReady()`.
3. Method membaca ulang toko dengan lock, memeriksa Settings dan produk terlihat, kemudian mengisi setup_completed_at bila belum terisi. StoreController memanggil method yang sama setelah menyimpan Settings. Pemeriksaan backend ini menjaga aturan konsisten untuk kedua urutan pengisian.
4. Saat membuka overview, StoreController mengirim setupCompleted berdasarkan timestamp. Vue menyembunyikan panduan jika nilainya true; tombol manual tidak diperlukan.
5. `HandleInertiaRequests` mengirim access.activeProductCount dari akses produk aktif milik pengguna login. `RintisLayout` menampilkan Switch app hanya jika jumlahnya lebih dari satu. Ini memakai shared props seperti navigation yang sudah dipelajari.

### File penting

- `app/Models/Rintis/Store.php`: completeSetupIfReady, pemeriksaan bersama dan penyimpanan idempoten dalam transaksi.
- `app/Http/Controllers/Rintis/Admin/StoreController.php`: memicu pemeriksaan setelah simpan settings dan tidak lagi mengirim URL tombol Finish.
- `app/Http/Controllers/Rintis/Admin/CatalogController.php`: memicu pemeriksaan setelah tambah/edit produk.
- `app/Http/Middleware/HandleInertiaRequests.php`: jumlah produk aktif untuk menentukan kebutuhan pemilih aplikasi.
- `resources/js/Pages/Rintis/Admin/Overview.vue`: tanpa tombol Finish setup.
- `resources/js/Layouts/Rintis/RintisLayout.vue`: menu pengguna dan navbar ringkas.
- `tests/Feature/Rintis/AutomaticSetupTest.php`: otomatis selesai, urutan settings/katalog, produk tersembunyi, timestamp tidak direset dan isolasi hitungan akses.
- `database/factories/Rintis/StoreFactory.php`: slug data uji menggunakan pola panjang tetap agar Faker tidak menghasilkan slug melampaui batas 80 karakter. Perbaikan ini dilakukan setelah satu tes gagal karena slug acak terlalu panjang.

### Pemeriksaan dan batasan

- Pint berhasil; seluruh **146 tes Laravel lulus, 1.160 assertions**; build frontend berhasil.
- Toko lokal dengan timestamp kosong telah diperiksa untuk rekonsiliasi kesiapan melalui method baru; tidak ada toko tambahan yang memenuhi syarat saat pemeriksaan (0 diubah). Tidak menjalankan migrate:fresh atau menghapus data.
- Tidak ada migration baru. Endpoint Finish lama dipertahankan agar tautan/klien lama tidak rusak, tetapi tidak dipakai UI.
- Interaksi menu/browser belum diuji langsung pada sesi ini. Open-order periods dan checkout masih pekerjaan berikutnya.


## Tahap 17 — Produk otomatis tampil dan kalkulator HPP privat

Tanggal: 31 Oktober 2026.

### Hasil yang dapat digunakan

- Checkbox Show in Our Products dihapus. Produk baru dan produk yang disimpan selalu is_visible=true; payload yang meminta false/tidak valid tidak bisa menyembunyikannya. Migration menetapkan default database true serta menampilkan produk lama yang tersembunyi. Tidak menghapus produk atau foto.
- Form tambah/edit produk menyediakan **Production cost (HPP)** → **Add HPP calculation**. HPP opsional; produk tetap dapat disimpan tanpa worksheet. Worksheet dapat diubah atau dihapus dengan tombol Remove HPP calculation when saving lalu Save product.
- Hingga 50 baris bahan/kemasan, masing-masing berisi nama, kuantitas/satuan beli, total harga beli, serta kuantitas/satuan yang digunakan dalam batch. Gram/kilogram, ml/liter dan pcs tersedia. Konversi berat-volume/pcs ditolak agar tidak memakai asumsi massa jenis atau isi kemasan yang tidak diketahui.
- Isi jumlah produk yang dihasilkan per batch dan biaya tambahan. Biaya bahan yang dipakai, total biaya batch dan HPP per produk diperbarui saat input berubah.
- Target gross margin opsional memberikan saran harga berdasarkan HPP. Tidak ada margin default yang ditetapkan aplikasi. Klik Use as base price untuk menyalin hasil; tidak mengubah harga tanpa tindakan seller. Jika harga dasar lebih kecil dari HPP, UI memberi informasi.
- Bahan, harga beli, margin dan HPP privat untuk pemilik toko yang berhak. Costing disembunyikan dari serialisasi model biasa dan hanya dimasukkan eksplisit pada props edit produk admin. Katalog publik tetap tidak memuat harga jual dasar maupun costing. Akun lain dan tamu tidak dapat membuka/mengubah worksheet.

### Contoh cara mencoba dan logika

1. Catalog → Add/Edit product → Add HPP calculation.
2. Baris kopi: beli 1 kg seharga Rp 200.000, gunakan 36 g. Biaya digunakan Rp 7.200.
3. Susu: beli 1 l Rp 20.000, gunakan 200 ml. Biaya Rp 4.000.
4. Cup: beli 50 pcs Rp 50.000, gunakan 2 pcs. Biaya Rp 2.000.
5. Biaya tambahan Rp 1.000, hasil batch 2 produk: total batch Rp 14.200, HPP per produk Rp 7.100.
6. Target gross margin 40% menghasilkan ceil(7100 / 0,60) = Rp 11.834. Ini memakai margin terhadap harga jual, bukan markup 40% terhadap biaya. Klik Use as base price jika sesuai, lalu Save product.
7. Buka ulang edit produk untuk memeriksa worksheet tersimpan. Pengunjung tetap hanya melihat produk/deskripsi/foto tanpa data privat.

### Alur untuk belajar

`ProductForm.vue` menyimpan input worksheet pada form.costing dan menampilkan `ProductCostingFields.vue`. Komponen menghitung preview dengan `Support/productCosting.js`. Save mengirim data produk; `CatalogProductRequest` memvalidasi struktur/nilai dan menjalankan kalkulator PHP untuk memeriksa hasil. `CatalogController` hanya menyimpan field yang lolos validasi melalui model CatalogProduct. Data mentah worksheet disimpan pada kolom JSON costing, bukan data stok atau transaksi. Penghitungan yang tersimpan tidak dipercaya dari browser; hasil turunan dihitung kembali dari input.

Kalkulator memakai integer berskala di PHP dan BigInt di JavaScript untuk menjaga pecahan kuantitas/konversi serta pembulatan yang konsisten tanpa aritmetika uang float. Tiap biaya bahan dibulatkan half-up ke rupiah; HPP per produk dibulatkan half-up; saran harga dibulatkan ke atas. Presisi ini adalah kebijakan perkiraan worksheet, bukan pembulatan transaksi checkout.

### File penting

- `database/migrations/2026_10_31_103815_add_private_costing_to_rintis_catalog_products.php`: kolom JSON privat dan default visibilitas baru; produk lama menjadi terlihat.
- `app/Models/Rintis/CatalogProduct.php`: casting JSON serta Hidden costing untuk serialisasi biasa.
- `app/Http/Requests/Rintis/CatalogProductRequest.php`: normalisasi tampil otomatis, struktur worksheet allowlist, validasi kuantitas/margin, dan pengecekan hitungan server.
- `app/Services/Rintis/ProductCostCalculator.php`: rumus konversi satuan, biaya batch, HPP dan saran harga.
- `app/Http/Controllers/Rintis/Admin/CatalogController.php`: hanya memasukkan worksheet ke form pemilik dan menyimpan field tervalidasi; request lama tanpa costing tidak menghapus worksheet tersimpan.
- `resources/js/Pages/Rintis/Admin/ProductForm.vue`, `Components/Rintis/ProductCostingFields.vue`, `Support/productCosting.js`: formulir dan preview perhitungan langsung.
- `resources/js/Pages/Rintis/Admin/Catalog.vue`: penjelasan katalog tampil otomatis.
- `tests/Feature/Rintis/ProductCostingTest.php`, tes katalog/setup terkait, dan `tests/Frontend/productCosting.test.js`: validasi, perhitungan, privasi dan kontrak visibilitas baru.

### Pemeriksaan dan batasan

- Pint berhasil. Seluruh **164 tes Laravel lulus, 1.243 assertions**. **9 tes JavaScript lulus**. Build frontend berhasil.
- Migration diterapkan ke PostgreSQL lokal; kolom costing tersedia dan tidak ada produk tersisa dengan is_visible=false. Kesiapan setup lama diperiksa kembali setelah perubahan visibilitas; tidak ada toko tambahan yang selesai (0).
- Cakupan mencakup konversi kg/g, l/ml, pcs; hasil batch; margin; nol biaya; pembulatan; jumlah/nilai invalid; satuan tidak cocok; batas jumlah item; computed field dari klien; akses toko lain; dan respons publik tanpa data privat.
- Belum ada pengujian interaksi browser nyata. Worksheet tidak mengelola stok, inventaris, supplier, pembelian atau histori HPP per transaksi. Resep langkah produksi/foto privat belum ditambahkan.
- Kuantitas 0,001–1.000 dalam satuan yang dipilih, hingga 3 desimal; bila jumlah dalam gram melebihi batas dapat gunakan kg. Hasil batch 1–1.000.000 produk utuh. Target margin 0–99,99%, harga dasar sampai Rp 999.999.999. Saran yang melebihi batas harga tidak dapat diterapkan melalui tombol.
- Saran hanya berdasarkan biaya yang dicatat. Biaya yang tidak dimasukkan tidak dihitung; belum ada harga pasar, pajak/fee otomatis atau jaminan laba bersih. Tahap berikutnya tetap periode open order dan snapshot harga/HPP saat transaksi sesuai keputusan bisnis berikutnya.


## Tahap 18 — Pilihan produk, tombol hapus, dan perbaikan preview HPP

Tanggal: 31 Oktober 2026.

### Temuan dan perbaikan

1. Preview HPP sebelumnya menghitung seluruh worksheet sekaligus. Satu baris baru yang belum lengkap atau satuan tidak cocok membuat semua biaya hilang. Kini setiap baris memiliki hasil/error sendiri; biaya baris lain tetap terlihat. Total resep belum dianggap lengkap jika masih ada baris salah. Target margin invalid tidak menghilangkan HPP yang sudah valid.
2. Dropdown satuan pemakaian hanya menyediakan satuan yang sesuai dengan pembelian. Misalnya pcs tidak bisa menggunakan gram. Mengubah kelompok satuan menyamakan satuan pemakaian dan mengosongkan jumlah pemakaian untuk diisi ulang; harga pembelian tidak disentuh. Key baris menggunakan identitas objek stabil agar menghapus item tidak memindahkan keadaan input rupiah ke baris lain.
3. Label batch menjadi recipe dan pertanyaan How many products does this recipe make? Ada contoh satu Americano memakai hasil 1, sedangkan bahan untuk 10 minuman memakai hasil 10. Biaya lain diberi keterangan bukan harga jual dan tidak mengulang bahan yang sudah dicatat.
4. Price suggestion memberi petunjuk mengisi target margin jika HPP sudah siap. Jika belum lengkap, terdapat pesan baris mana yang perlu dilengkapi, bukan semua angka kosong tanpa penjelasan.

### Pilihan produk yang disetujui

Pengguna menjawab bahwa tiap opsi dapat menambah harga dan HPP. Implementasi kelompok generik:

- Grup: Size, Strength, Sugar, Color, Extras atau nama lain yang dipilih seller.
- Pilihan tunggal atau jamak, wajib/opsional, maksimal 8 grup dan 20 pilihan per grup.
- Pilihan punya nama, tambahan harga jual, dan tambahan HPP dalam rupiah utuh nonnegatif. Standar/default yang sudah ada pada resep/harga dasar memakai tambahan 0.
- Preview privat memungkinkan seller memilih kombinasi. Contoh harga dasar Rp 17.000, HPP Rp 7.100, Large +Rp 3.000/+Rp 1.000 HPP dan Strong +Rp 2.000/+Rp 500 HPP menghasilkan harga Rp 22.000 dan HPP Rp 8.600. Selisih Rp 13.400 bukan klaim laba bersih.
- Halaman publik hanya menampilkan nama pilihan dan aturan kelompok. HPP, tambahan biaya dan tambahan harga tidak dikirim pada katalog pengenalan. Pemilihan opsi oleh pembeli serta penetapan harga transaksi masih menunggu open order/checkout.
- Setiap kombinasi tidak mempunyai resep tersendiri. Ini aturan tambahan terhadap resep/harga dasar, bukan sistem varian SKU atau stok. Mengurangi bahan/biaya dari resep dasar belum didukung; Less sugar/No sugar dapat menjadi pilihan tambahan 0, dengan perhitungan HPP dasar yang seller pilih.

### Tombol produk

- Baris katalog memiliki tombol Edit dan Delete berdampingan.
- Form edit memiliki Save product dan Delete product.
- Delete menampilkan dialog konfirmasi. Endpoint dilindungi auth, akses produk, policy toko dan scoped binding produk.
- Penghapusan memakai soft delete: hilang dari katalog admin/publik dan route edit, tetapi record/foto/worksheet dipertahankan. Tidak ada penghapusan permanen atau penghapusan kategori. Pemulihan melalui UI belum tersedia.

### Cara mencoba dan contoh HPP

1. Catalog → Edit, atau gunakan Delete dan konfirmasi bila ingin menghapus produk.
2. Pada resep satu Americano, isi coffee 500 g Rp 150.000, digunakan 18 g → Rp 5.400.
3. Cup + lid 250 pcs Rp 282.850, dipakai 1 pcs → Rp 1.131 setelah pembulatan. Paper bag 50 pcs Rp 33.040, dipakai 1 pcs → Rp 661. Sedotan 100 pcs Rp 15.000, dipakai 1 pcs → Rp 150.
4. Jika semuanya digunakan untuk satu minuman dan tidak ada biaya tambahan, hasil resep 1 memberikan HPP Rp 7.342. Jika tambahan Rp 12.000 memang biaya lain untuk resep itu, total menjadi Rp 19.342. Jangan masukkan harga jual pada Other costs.
5. Tambah satu baris baru kosong: biaya baris kopi tetap Rp 5.400; hanya total menunggu baris baru lengkap. Lengkapi jumlah pemakaian dan pilih satuan yang sesuai.
6. Isi target margin untuk melihat saran harga. Angka ini bukan harga pasar atau margin default dari aplikasi.
7. Product choices → Add option group → Size → Medium/Large; isi tambahan harga/HPP → pilih kombinasi pada preview privat → Save product. Buka ulang untuk memastikan tersimpan.

### File dan alur penting

- `resources/js/Support/productCosting.js`: kalkulasi tiap bahan terpisah dan preview worksheet yang membedakan error biaya dengan margin.
- `resources/js/Components/Rintis/ProductCostingFields.vue`: label resep, key stabil, penyesuaian satuan dan pesan per baris.
- `resources/js/Components/Rintis/ProductOptionsFields.vue`, `Support/productOptions.js`: editor opsi dan simulasi penambahan harga/HPP.
- `resources/js/Components/Rintis/ProductDeleteDialog.vue`: dialog konfirmasi hapus bersama.
- `resources/js/Pages/Rintis/Admin/ProductForm.vue`, `Catalog.vue`: editor pilihan, tombol Edit/Delete dan pengiriman pilihan sebagai JSON dalam multipart agar pengosongan seluruh grup benar-benar tersimpan.
- `app/Http/Requests/Rintis/CatalogProductRequest.php`: decoding JSON, allowlist struktur, ID unik, nama grup/pilihan unik, dan batas angka.
- `app/Http/Controllers/Rintis/Admin/CatalogController.php`: penyimpanan field tervalidasi dan penghapusan milik toko berhak.
- `app/Models/Rintis/CatalogProduct.php`: casting opsi privat, SoftDeletes dan projection publicOptions yang hanya memuat label/rule publik.
- `routes/rintis.php`: endpoint DELETE terikat toko/produk dan pemetaan pilihan publik tanpa harga/biaya.
- `database/migrations/2026_10_31_140003_add_options_and_soft_deletes_to_rintis_catalog_products.php`: kolom JSON options dan deleted_at, tanpa menghapus record lama.
- Tes frontend HPP/opsi dan `tests/Feature/Rintis/ProductOptionsTest.php`: regresi biaya per item, hitungan kombinasi, persistensi, privasi, validasi serta isolasi penghapusan.

### Pemeriksaan dan batasan

- Pint selesai. **171 tes Laravel lulus, 1.325 assertions**. **13 tes JavaScript lulus**. Build frontend berhasil.
- Migration diterapkan ke PostgreSQL lokal. Tidak ada produk pengguna yang dihapus untuk pengujian; tes memakai database terisolasi.
- Belum ada pengujian browser interaktif. Dialog, kestabilan input setelah menghapus baris, dan preview pilihan perlu dicoba pengguna pada browser.
- Open order/checkout belum dibangun. Opsi tidak menciptakan stok atau kuota kombinasi, tidak menetapkan diskon negatif atau resep per kombinasi, dan belum menghasilkan snapshot harga pesanan. Itu perlu ditangani pada alur transaksi berikutnya.


## Tahap 19 — Urutan formulir produk

Tanggal: 31 Oktober 2026.

Komponen Product choices pada `resources/js/Pages/Rintis/Admin/ProductForm.vue` dipindahkan tepat sesudah Base price, sebelum foto dan HPP. Cara mencoba: buka Add/Edit product, gulir ke Base price; Product choices langsung berada di bawahnya. Build frontend berhasil. Tidak ada perubahan database atau logika harga/HPP. Perubahan susunan ini tidak memerlukan tes backend baru; langkah berikutnya mengikuti pengembangan open order yang direncanakan.


## Tahap 20 — Hapus preview kombinasi yang tidak diperlukan

Tanggal: 31 Oktober 2026.

Bagian Preview a combination beserta state dan impor preview yang tidak lagi dipakai dihapus dari `resources/js/Components/Rintis/ProductOptionsFields.vue`, sesuai permintaan pengguna agar tidak menambah UI yang tidak diperlukan. Form pengaturan pilihan dan tambahan harga/HPP tetap tersedia. Build frontend berhasil; tidak ada perubahan database/backend. Cara mencoba: buka edit produk → Product choices; langsung lanjut ke foto produk setelah pengaturan opsi, tanpa panel preview kombinasi. Perubahan berikutnya harus tetap mengikuti kebutuhan eksplisit pengguna.


## Tahap 21 — Tab formulir produk dan tabel HPP ringkas

Tanggal: 31 Oktober 2026.

Form Add/Edit product dibagi menjadi tab Product details, Product choices, dan HPP. Details berisi identitas/kategori/deskripsi/harga/foto; Choices berisi kelompok pilihan; HPP berisi tabel bahan dan hasil biaya privat. Tombol Save/Delete tetap tersedia di bawah panel aktif dan menyimpan keseluruhan produk.

Panel memakai v-show, sehingga input, file terpilih dan state komponen tetap ada saat berpindah tab. Navigasi tab mendukung klik, panah kiri/kanan dan Home/End dengan atribut ARIA. Sebelum submit, validasi browser mencari kolom invalid, membuka tab dan details yang memuatnya lalu memfokuskan input. Error server juga mengarahkan ke tab yang sesuai, dan tab berisi error diberi indikator. Tidak ada perubahan backend, skema atau rumus HPP.

Bahan HPP yang sebelumnya berupa kartu panjang sekarang menjadi tabel: nama, jumlah beli, harga beli, jumlah dipakai, biaya pemakaian dan tombol hapus. Biaya per baris tetap independen. Tabel dapat digeser mendatar pada layar sempit. Total resep hanya ditampilkan jika hasil resep lebih dari satu, menghindari dua angka identik untuk resep satu produk. Pengaturan jumlah hasil/biaya tambahan dan saran harga opsional ditempatkan pada bagian yang dibuka saat diperlukan; nilai lama tetap dipertahankan dan tidak direset.

File: `resources/js/Pages/Rintis/Admin/ProductForm.vue` untuk tab dan pemilihan tab validasi; `resources/js/Components/Rintis/ProductCostingFields.vue` untuk tabel dan bagian opsional. Cara mencoba: buka Edit product, pindah ketiga tab, ubah input di satu tab lalu kembali; data tetap ada. Simpan dengan input wajib kosong pada tab lain untuk memastikan tab tersebut dibuka.

Build frontend berhasil. Tidak menambahkan tes backend karena hanya perubahan presentasi; pengujian browser interaktif belum dilakukan. Berikutnya mencoba tampilan di browser pengguna dan melanjutkan pekerjaan yang diminta tanpa menambah panel di luar kebutuhan.


## Tahap 22 — Rapikan tombol hapus bahan HPP

Tanggal: 1 November 2026.

Tombol silang hapus bahan pada `resources/js/Components/Rintis/ProductCostingFields.vue` memiliki kolom khusus lebar minimum, ikon SVG konsisten, area klik 36 px, border halus dan focus keyboard. Posisi tombol serta angka biaya disejajarkan vertikal; kolom aksi tetap di kanan ketika tabel digeser agar tidak terpotong/menempel pada tepi. Perhitungan dan perilaku hapus tidak berubah. Build frontend berhasil; posisi visual belum diperiksa di browser nyata. Cara mencoba: Edit product → HPP → periksa tombol × pada setiap bahan dan ketika tabel digeser mendatar.

Penjelasan hasil resep: gunakan 1 jika seluruh jumlah bahan yang dicatat untuk satu produk. Isi 10 hanya jika jumlah bahan di tabel memang untuk menghasilkan 10 produk dalam satu resep; HPP per produk adalah total biaya resep dibagi 10. Angka ini bukan jumlah pesanan, bukan jumlah kemasan yang dibeli, dan bukan target penjualan. Tidak ada perubahan logika backend atau database.


## Tahap 23 — Informasi kartu katalog publik

Tanggal: 1 November 2026.

Kartu pada `resources/js/Pages/Rintis/Storefront/Home.vue` menampilkan foto, nama produk dan deskripsi. Label kategori dan daftar pilihan seperti Level/Size dihapus dari tampilan kartu sesuai permintaan. Data pengaturan kategori/pilihan tetap tersimpan untuk pengelolaan. Rating belum diimplementasikan; nantinya hanya ditampilkan jika ada penilaian nyata, tanpa angka contoh atau bintang kosong. Cara mencoba: buka halaman toko publik, lihat kartu produk. Build frontend berhasil; tidak ada perubahan backend/database atau perhitungan. Pemeriksaan visual browser belum dilakukan pada sesi ini.


## Tahap 24 — Sesi open order pertama

Tanggal: 1 November 2026.

### Hasil yang dapat digunakan

- Navbar seller memiliki Open orders. Seller dapat membuat sesi dengan nama, tanggal/jam mulai dan tutup (WIB), memilih produk katalog, menetapkan harga per sesi dan kuota per produk atau No quota limit.
- Save draft menyimpan persiapan tanpa tampil publik. Open / schedule session menerbitkan menu; jadwal masa depan menjadi Scheduled, jadwal berjalan menjadi Open, dan melewati waktu tutup menjadi Ended tanpa membutuhkan cron untuk penentuan status.
- Hanya satu sesi published yang belum berakhir (open maupun scheduled) diizinkan per toko. Penyimpanan mengunci baris toko dalam transaksi sebelum memeriksa dan menerbitkan, supaya permintaan bersamaan mengikuti pemeriksaan yang sama.
- Seller dapat menutup sesi lebih awal atau membatalkan melalui dialog konfirmasi. Catatan sesi/menu tetap dipertahankan. Tidak ada penghapusan sesi pada tahap ini.
- Sesi terbit memerlukan minimal satu produk, waktu tutup di masa depan, minimal satu metode penerimaan yang aktif pada Store settings, alamat/pin lengkap, serta konfigurasi tarif/jarak delivery jika dipilih.
- Harga jual sesi boleh berbeda dari harga dasar katalog. Nama/deskripsi, pilihan produk beserta penyesuaian, resep HPP privat, alamat/pin, petunjuk pickup, serta tarif/jangkauan delivery disalin saat save. Perubahan master produk/settings berikutnya tidak mengubah sesi tersimpan.
- Form draft dapat diedit. Pada tahap awal ini **sesi yang sudah terbit tidak dapat diedit**, meskipun sistem pesanan belum ada. Tutup/batalkan lalu buat pengganti untuk perubahan menu/jadwal. Ini pembatasan implementasi awal yang lebih ketat daripada rencana penguncian setelah ada pesanan.
- Pelanggan tanpa akun dapat membuka tab Open Order di halaman toko untuk melihat jadwal, countdown, nama/deskripsi menu, harga sesi, kuota yang ditetapkan dan metode penerimaan. Menu draft/cancelled tidak dibuka publik; menu yang ditutup/berakhir disembunyikan.
- Alamat lengkap/pin, resep HPP dan tambahan HPP pilihan tidak dikirim ke halaman publik. Penyesuaian harga opsi yang disalin boleh dikirim pada data menu PO, tetapi pemilihan opsi/checkout belum dibuat. Katalog Our Products tetap tidak memuat harga.

### Cara mencoba

1. Lengkapi Store settings: alamat, pin dan metode pickup/delivery. Jika delivery, lengkapi tarif serta minimum/maksimum jaraknya.
2. Buka Open orders → New session.
3. Isi nama dan jadwal memakai pemilih tanggal/jam; seluruh waktu input ditafsirkan sebagai WIB (Asia/Jakarta), disimpan UTC dan ditampilkan kembali WIB.
4. Centang produk yang dijual. Harga awal diambil dari harga dasar tetapi dapat diubah khusus sesi. Kuota null berarti tanpa batas; jika terbatas gunakan bilangan bulat positif.
5. Pilih metode penerimaan yang tersedia, lalu Save draft atau Open / schedule session.
6. View public menu membuka tampilan pembeli tanpa login. Atau buka halaman toko → Open Order. Our Products tetap menampilkan master katalog.
7. Close/Cancel meminta konfirmasi; menutup sesi membolehkan menerbitkan sesi pengganti.

### Alur kode untuk belajar

`OpenOrders/Form.vue` mengumpulkan input dan mengirim POST/PATCH ke `routes/rintis.php`. Middleware memeriksa login/aktivasi produk; OpenOrderRequest memanggil policy pemilik dan memvalidasi input/produk milik toko. `OpenOrderController` meneruskan data tervalidasi ke `SaveOpenOrder`. Service mengunci toko, memeriksa konflik sesi, memvalidasi kesiapan metode dan menyalin snapshot dalam satu transaksi. Model OpenOrder menyimpan sesi; OpenOrderItem menyimpan menu per sesi. Halaman publik mengambil hanya projection yang aman, bukan seluruh snapshot.

Status Scheduled/Open/Ended dihitung dari status publikasi dan waktu server saat request. Countdown browser diinisialisasi dari serverNow untuk mengurangi pengaruh jam perangkat yang salah. Penutupan manual dari perangkat lain baru terlihat setelah halaman diperbarui; belum ada polling/realtime. Karena checkout belum tersedia, tidak ada tombol pemesanan yang mengandalkan countdown browser sebagai pemeriksaan keamanan.

### File penting

- `database/migrations/2026_10_31_171001_create_open_orders_table.php`: tabel rintis_open_orders dan rintis_open_order_items, relasi/indeks/keunikan menu per sesi.
- `app/Models/Rintis/OpenOrder.php`, `OpenOrderItem.php`, relasi Store::openOrders(): data sesi, status berdasarkan waktu dan projection publik.
- `app/Http/Requests/Rintis/OpenOrderRequest.php`: validasi nama, jadwal, metode, harga, kuota dan produk aktif milik toko.
- `app/Services/Rintis/SaveOpenOrder.php`: transaksi, lock, validasi publikasi dan snapshot.
- `app/Http/Controllers/Rintis/Admin/OpenOrderController.php`: daftar, form, create/update draft, close/cancel.
- `routes/rintis.php`: route admin dengan scoped binding sesi ke toko serta halaman menu PO publik.
- `config/rintis.php`: zona waktu Rintis Asia/Jakarta.
- `resources/js/Pages/Rintis/Admin/OpenOrders/Index.vue`, `Form.vue`: daftar sesi dan formulir seller.
- `resources/js/Pages/Rintis/Storefront/OpenOrder.vue`, `Storefront/Home.vue`: tab menu sesi dan katalog umum.
- `resources/js/Layouts/Rintis/RintisLayout.vue`, `app/Http/Middleware/HandleInertiaRequests.php`: tautan Open orders.
- `tests/Feature/Rintis/OpenOrderTest.php`: cakupan perilaku, privasi, status, snapshot dan akses.

### Pemeriksaan yang dilakukan

- Pint selesai. Seluruh **183 tes Laravel lulus, 1.457 assertions**. **13 tes JavaScript lulus**. Build frontend berhasil; route PO diperiksa.
- Migration berhasil diterapkan ke PostgreSQL lokal tanpa menghapus data sebelumnya. Tidak membuat atau menerbitkan sesi nyata atas nama seller; sesi pengujian hanya di database tes terisolasi.
- Tes mencakup draft privat, publish/schedule, roundtrip WIB/UTC, sesi bentrok tanpa perubahan parsial, close/cancel, waktu berakhir, produk toko lain/terhapus, kuota invalid/duplikat, metode/lokasi belum lengkap, tarif nol yang valid, session binding lintas toko, dan snapshot harga/resep/opsi tanpa kebocoran HPP/alamat publik.
- Interaksi browser nyata belum diuji: pemilih kalender, klik dua tombol intent, konfirmasi dan countdown perlu dicoba pengguna.

### Batasan dan langkah berikutnya

- **Belum ada checkout atau pesanan pelanggan.** Membuka sesi pada tahap ini berarti menerbitkan jadwal/menu; banner publik menjelaskan checkout belum tersedia.
- Kuota baru konfigurasi. Belum ada reservasi, pengurangan kuota, sold out akibat transaksi, pengembalian kuota karena pembayaran kedaluwarsa, atau kalkulasi kapasitas kombinasi opsi.
- Belum ada pembayaran cash/transfer sesi, snapshot rekening, bukti pembayaran, alamat pickup dalam pesanan, routing delivery atau ongkir transaksi.
- Sesi menyalin nama/deskripsi/harga/opsi/HPP, bukan foto. Menu sesi menggunakan tampilan teks; perubahan foto produk tidak memengaruhi gambar snapshot karena snapshot foto belum disediakan.
- Produk master yang kemudian dihapus tidak menghapus snapshot menu sesi yang sudah dibuat. Tutup/batalkan sesi jika menu tersebut tidak ingin lagi ditawarkan.
- Belum ada penghapusan/duplikasi sesi atau direktori gabungan sesi semua toko. Tahap berikutnya guest checkout dengan pilihan produk/opsi, kontak pembeli, penguncian kuota, rincian pickup privat dan pembayaran sesuai keputusan bisnis.


## Tahap 25 — Hapus tombol View public menu

Tanggal: 1 November 2026.

Tombol View public menu di halaman admin Open orders dihapus dari `resources/js/Pages/Rintis/Admin/OpenOrders/Index.vue`, beserta deklarasi prop yang tidak lagi dipakai komponen. Tombol New session tetap tersedia. Halaman publik dan alur sesi tidak berubah. Build frontend berhasil. Cara mencoba: buka Open orders; tombol View public menu tidak lagi tampil. Tidak ada perubahan database atau backend.

## Tahap 26 — Kalender PO, foto menu, pemilihan produk dan guest checkout

Tanggal: 1 November 2026.

### Tujuan dan hasil

Mengganti kalender bawaan browser yang terlihat generik serta membuat menu sesi benar-benar dapat dipilih dan diteruskan ke checkout.

- Form jadwal memakai kalender khusus dengan gaya warna Rintis, navigasi bulan/tahun, tanggal terpilih, Today, pilihan jam/menit WIB, dan Apply date & time. Nilai tetap dikirim dalam format lokal WIB yang diterima backend sebelumnya; penyimpanan tanggal tetap UTC.
- Menu PO menampilkan foto, nama, deskripsi singkat, harga sesi dan sisa kuota bila dibatasi. Select membuka dialog pilihan produk dan jumlah; pilihan wajib/tunggal/jamak diperiksa sebelum ditambahkan. Keranjang disimpan pada sessionStorage per sesi, sehingga pilihan tetap terbawa ke halaman checkout di tab yang sama. Data kontak tidak disimpan dalam keranjang browser.
- Pembeli dapat checkout tanpa akun: nama, telepon, pickup/delivery, dan ringkasan pilihan. Pembayaran tahap awal memakai **cash saat pickup/delivery** sebagai asumsi sementara yang telah disampaikan dalam percakapan; ini bukan implementasi pengaturan metode pembayaran per seller yang direncanakan dalam spesifikasi.
- Server menghitung harga dari snapshot menu dan opsi, memeriksa waktu/metode, memeriksa kuota gabungan semua baris, dan mencatat pesanan dalam transaksi dengan penguncian sesi/menu. Harga/total dari browser tidak dipercaya. Kunci pengiriman unik dan identitas sesi tamu mencegah pengulangan checkout yang sama mencatat dua pesanan. Kuota berkurang setelah pesanan berhasil tercatat.
- Bukti pesanan memakai tautan privat acak. Alamat/pin toko hanya dikirim pada bukti pesanan pickup; HPP dan biaya opsi tidak masuk props pembeli. Halaman bukti memakai no-store/no-referrer dan noindex. Tautan tersebut harus disimpan pembeli dan diperlakukan sebagai akses privat, bukan dibagikan ke publik.
- Seller membuka **Open orders → Orders (jumlah)** untuk membaca pesanan sesi milik tokonya. Akses seller lain/tamu ditolak. Belum ada tombol perubahan pembayaran atau penyelesaian pesanan.
- Foto menu disalin sebagai referensi snapshot. Migration mengisi foto sesi lama dari foto produk saat migration dijalankan. Mengganti/menghapus foto master tidak menghapus berkas yang masih direferensikan menu sesi.

### Delivery dan konfigurasi

`config/rintis.php` menambahkan `routing_url`, dibaca dari `RINTIS_ROUTING_URL`. Nilainya adalah base endpoint layanan yang kompatibel dengan OSRM Route API, misalnya format `https://HOST/route/v1/driving`, tanpa koordinat; bukan URL tile OpenStreetMap. Belum memilih/mengaktifkan penyedia layanan routing nyata. Tanpa konfigurasi ini, Calculate delivery memberi pesan bahwa routing belum tersedia; gunakan pickup bila sesi menyediakan pickup.

Alur delivery: pembeli memilih pin tujuan dengan LocationPicker → endpoint quote meminta rute dari pin toko ke tujuan melalui server → jarak jalan diperiksa terhadap batas minimum/maksimum snapshot → tarif tetap atau tarif/km dihitung → quote disimpan dalam sesi browser di server selama 15 menit. Saat checkout, server memeriksa bahwa quote milik sesi PO yang sama, belum kedaluwarsa, dan koordinat tujuan sama. Mengubah pin membatalkan quote di UI. Koordinat asal toko tidak dikirim ke props checkout atau respons quote. Kegagalan jaringan/layanan atau tujuan di luar jangkauan menolak delivery, tanpa mengarang ongkir. Pengujian layanan menggunakan HTTP fake, bukan panggilan routing nyata. Leaflet tetap hanya tampilan peta; tile peta tidak menghitung rute/ongkir.

### File penting dan alur

- `resources/js/Components/Shared/DateTimePicker.vue`, `resources/js/Support/calendar.js`: kalender khusus; dipakai `Pages/Rintis/Admin/OpenOrders/Form.vue`.
- `resources/js/Pages/Rintis/Storefront/OpenOrder.vue`, `Checkout.vue`, `Receipt.vue`: menu foto/pilihan → checkout tamu → bukti pesanan.
- `resources/js/Support/cart.js`: pembacaan keranjang, validasi pilihan dan pratinjau nominal; backend tetap sumber harga final.
- `resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue`: daftar pesanan untuk seller.
- `routes/rintis.php`: route checkout/quote terikat toko+sesi, route bukti privat, route daftar pesanan seller; rate limit untuk pengiriman/quote/bukti.
- `app/Http/Requests/Rintis/CheckoutRequest.php`: validasi struktur kontak, pilihan, jumlah dan tujuan.
- `app/Http/Controllers/Rintis/CheckoutController.php`: mengirim props checkout, quote, menerima checkout dan menampilkan bukti privat.
- `app/Services/Rintis/PlaceOrder.php`: transaksi pesanan, pemeriksaan snapshot/kuota dan pencegahan pengiriman ganda.
- `app/Models/Rintis/Order.php`: data pesanan dengan token akses terenkripsi dan projection bukti tanpa HPP.
- `app/Models/Rintis/OpenOrder.php`, `OpenOrderItem.php`: relasi pesanan, foto dan sisa kuota menu.
- `database/migrations/2026_10_31_172134_add_photo_to_rintis_open_order_items.php`, `2026_10_31_172324_create_orders_table.php`: kolom foto, ordered_quantity dan tabel rintis_orders.
- `app/Http/Controllers/Rintis/Admin/OpenOrderController.php`, `CatalogController.php`, `app/Services/Rintis/SaveOpenOrder.php`: navigasi/daftar pesanan, pelestarian foto dan snapshot foto menu.
- `resources/js/Components/Rintis/LocationPicker.vue`: judul bisa disesuaikan untuk pin tujuan checkout; pemakaian di Settings tetap tersedia.

### Cara mencoba

1. Seller membuka Open orders → New session. Pilih Opens/Closes untuk mencoba kalender baru, lalu isi menu dan buka/jadwalkan sesi. Sesi yang sudah diterbitkan tetap tidak bisa diedit; tidak perlu mengubah sesi lama untuk mendapatkan tampilan menu baru.
2. Saat sesi aktif, pembeli membuka halaman toko → Open Order → Select pada kartu berfoto. Lengkapi opsi wajib dan jumlah, lalu Add to order → Review & checkout.
3. Isi nama/telepon, pilih pickup, periksa total lalu Place order. Pesanan ini nyata dalam database lokal; jangan memakai alur ini sebagai simulasi penjualan tanpa mengetahui bahwa kuota ikut berkurang.
4. Simpan tautan bukti privat. Seller melihat pesanan melalui Open orders → Orders. Checkout delivery dicoba setelah layanan routing dikonfigurasi.

### Pemeriksaan

- **193 tes Laravel lulus, 1.605 assertions**; **18 tes JavaScript lulus**. Pint pada seluruh PHP yang berubah selesai; build frontend berhasil.
- Sepuluh tes checkout mencakup foto/privasi snapshot, pemesanan tamu, perhitungan opsi, total palsu, pilihan wajib/asing/duplikat, kuota gabungan dan kuota tanpa batas, idempotensi, sesi belum buka/berakhir/dibatalkan, binding lintas toko, toko tidak publik, metode tidak aktif, quote tujuan berubah/kedaluwarsa, routing gagal, jarak di luar batas dan tarif tetap nol, serta isolasi daftar pesanan seller.
- Dua migration berhasil diterapkan ke PostgreSQL lokal tanpa menghapus data lama. Tidak membuat pesanan atau sesi nyata atas nama pengguna selama pengerjaan; transaksi pengujian hanya dalam database tes.
- Kalender/keranjang diuji melalui pengujian logika dan kompilasi. Interaksi browser nyata, GPS perangkat, serta layanan routing nyata belum diuji.

### Batasan dan langkah berikutnya

- Belum ada transfer bank, bukti pembayaran, pengaturan pembayaran sesi, konfirmasi cash/uang diterima, kembalian, refund, pembatalan per pesanan atau penyelesaian fulfillment. Pesanan cash yang tercatat belum berarti uang diterima atau keuntungan.
- Menutup/membatalkan sesi menghentikan pesanan baru; tidak membatalkan atau mengembalikan kuota pesanan yang sudah masuk. Kuota yang terpakai tidak kedaluwarsa otomatis. Mekanisme pembatalan dan pengembalian kuota masih pekerjaan berikutnya.
- Belum ada realtime/polling. Perubahan kuota atau penutupan dari perangkat lain diperiksa ulang server saat kirim; refresh menu untuk melihat status terkini.
- Keranjang per tab/sesi dan dapat hilang jika browser storage dibersihkan; tidak ada keranjang tersinkron antarperangkat. Foto kosong memakai placeholder. Foto lama sebelum migration tidak dapat direkonstruksi bila sudah diganti/dihapus sebelumnya.
- Layanan routing perlu dipilih dan dikonfigurasi sebelum checkout delivery dapat digunakan. Tidak memasang layanan berbayar atau memakai demo routing publik sebagai layanan produksi secara otomatis.

## Tahap 27 — Pembeli memilih cash atau bank transfer

Tanggal: 1 November 2026.

### Tujuan dan hasil

Menindaklanjuti jawaban pengguna bahwa kedua metode harus tersedia. Keputusan ini menggantikan asumsi cash saja pada Tahap 26.

- Checkout menyediakan pilihan **Cash on pickup/delivery** dan **Bank transfer**. Cash tidak memerlukan rekening. Transfer memerlukan pilihan rekening aktif milik toko yang bersangkutan; jika tidak ada rekening aktif, pilihan transfer dinonaktifkan dengan penjelasan.
- Pilihan rekening pada checkout hanya menampilkan nama bank dan pemilik. Nomor rekening baru muncul pada bukti pesanan privat setelah pesanan dibuat, bersama nominal transfer.
- Server memeriksa kepemilikan/status aktif rekening dan menyimpan salinan nama bank, pemilik serta nomor rekening pada pesanan. Mengubah atau menghapus rekening di Settings tidak mengubah tujuan pembayaran historis pada bukti pesanan.
- Kedua metode dimulai dengan `payment_status = unpaid`; membuat pesanan tidak dianggap menerima uang. Daftar pesanan seller menampilkan metode dan status pembayaran. Pesanan cash lama mendapat default unpaid karena tahap sebelumnya belum memiliki pencatatan penerimaan uang.
- Pemeriksaan rekening berada dalam transaksi pesanan. Rekening tidak sah menolak pesanan tanpa mengurangi kuota. Pengiriman ulang checkout yang sudah berhasil tetap mengembalikan pesanan sebelumnya meski rekening kemudian dihapus.

### File penting

- `app/Http/Requests/Rintis/CheckoutRequest.php`: menerima cash/bank_transfer, rekening wajib untuk transfer.
- `app/Services/Rintis/PlaceOrder.php`: validasi rekening aktif milik toko dan snapshot rekening saat menyimpan pesanan.
- `app/Models/Rintis/Order.php`: cast rincian pembayaran dan projection rekening untuk bukti privat.
- `app/Http/Controllers/Rintis/CheckoutController.php`: daftar rekening aktif untuk dipilih pembeli.
- `resources/js/Pages/Rintis/Storefront/Checkout.vue`, `Receipt.vue`: pilihan metode/rekening dan rincian tujuan transfer.
- `resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue`: metode dan status pembayaran untuk seller.
- `database/migrations/2026_10_31_173536_add_payment_details_to_rintis_orders_table.php`: kolom payment_status dan payment_details.
- `tests/Feature/Rintis/CheckoutTest.php`: tes rekening asing/tidak aktif/kosong, snapshot setelah rekening diubah/dihapus, status belum dibayar, idempotensi transfer dan cash tanpa rekening.

### Cara mencoba dan pemeriksaan

Tambahkan rekening aktif melalui Settings toko. Buka menu PO aktif, pilih produk, masuk checkout lalu pilih Cash atau Bank transfer. Untuk transfer, pilih rekening dan buat pesanan; bukti privat menampilkan nomor rekening dan nominal. Jangan membuat pesanan percobaan pada data nyata tanpa memahami bahwa kuota ikut berkurang.

**13 tes checkout lulus, 187 assertions**; Pint selesai dan build frontend berhasil. Migration berhasil diterapkan ke PostgreSQL lokal tanpa menghapus data. Tidak membuat transaksi nyata selama pengerjaan. Interaksi browser nyata belum diuji.

### Batasan

Belum ada unggah bukti transfer, verifikasi bank otomatis, tombol konfirmasi pembayaran manual seller, batas waktu transfer atau pembatalan/pengembalian kuota. Dengan demikian pembayaran tetap unpaid; UI tidak mengklaim uang sudah diterima. Fitur-fitur tersebut merupakan tahap lanjutan, terpisah dari pilihan metode pembayaran yang diminta sekarang. Tidak ada instruksi transfer tambahan yang dimunculkan kembali pada pengaturan rekening. Batasan delivery/routing Tahap 26 tetap berlaku.

## Tahap 28 — Ringkasan keranjang, kejelasan harga opsi dan progres pesanan

Tanggal: 1 November 2026.

### Temuan harga Strong

Pemeriksaan terbatas pada data opsi Americano di PostgreSQL lokal menemukan Strong memiliki `price_adjustment = 0` dan `cost_adjustment = 3300`, baik pada master katalog maupun snapshot sesi PO yang ada. Jadi label Included sesuai tambahan harga jual yang tersimpan; Rp3.300 merupakan tambahan HPP privat dan tidak otomatis menjadi harga yang ditagih ke pembeli. Pertanyaan nominal tambahan harga jual dikirim kepada pengguna; sampai laporan ini ditulis, nominal tersebut belum ditetapkan. Tidak mengubah harga produk/sesi nyata secara sepihak.

Kolom opsi kini diberi label **Extra price charged to buyer** dan **Extra production cost · Private**, dengan keterangan bahwa hanya extra price yang menaikkan total pembeli. Dialog pemilihan menampilkan harga per item setelah opsi dipilih, selain total sesuai jumlah pada tombol Add to order. Rumus yang sudah ada tetap harga dasar sesi + total tambahan harga opsi, lalu dikalikan jumlah; server menghitung ulang dari snapshot, bukan total dari browser. Jika opsi katalog diubah setelah sesi diterbitkan, snapshot sesi lama tidak ikut berubah; ubah katalog sebelum membuka sesi baru untuk memakai harga opsi baru. Transaksi lama tetap menyimpan harga historisnya.

### Hasil yang bisa digunakan

- Halaman menu PO menampilkan ringkasan **Your order** berisi beberapa produk/kombinasi pilihan sekaligus, jumlah, total per baris dan tombol hapus. Pembeli tetap bisa menambah produk lain sebelum Review & checkout. Checkout menerima seluruh baris dalam satu pesanan.
- Seller membuka Open orders → Orders → **Start preparing** → **Mark ready for pickup**. Delivery memakai tahap Ready for delivery lalu Dispatch order.
- Pembeli melihat progres **Order received → Preparing → Ready for pickup**, atau tambahan Out for delivery untuk delivery. Saat pickup belum ready, pesan meminta pembeli menunggu; saat ready, pesan mengizinkan mengambil pesanan.
- Halaman bukti/pelacakan pesanan tamu memuat ulang props order setiap 15 detik melalui Inertia usePoll, serta menyediakan Refresh status. Tautan privat dari checkout tetap dipakai, tidak memerlukan akun pembeli. Ini polling saat halaman terbuka, bukan push notification di luar aplikasi.
- Status berubah hanya melalui tindakan seller pemilik toko dengan akses Rintis aktif. Server memeriksa tahap berikutnya dan status yang diharapkan, mengunci pesanan, serta mencatat waktu dan petugas dalam riwayat. Pengulangan tahap yang sama tidak menambah riwayat dua kali. Informasi petugas tidak dikirim ke pembeli.
- Progres pemenuhan terpisah dari pembayaran. Cash yang unpaid boleh disiapkan. Sesuai aturan produk yang sudah tertulis, transfer harus paid sebelum persiapan; karena kontrol verifikasi pembayaran belum tersedia, tombol progres transfer unpaid dinonaktifkan dan penjelasannya ditampilkan. Tidak menandai uang diterima secara otomatis.

### File dan alur penting

- `resources/js/Components/Rintis/ProductOptionsFields.vue`: label harga pembeli versus biaya internal.
- `resources/js/Pages/Rintis/Storefront/OpenOrder.vue`: ringkasan keranjang multi produk serta harga per item dengan pilihan.
- `resources/js/Components/Rintis/OrderProgress.vue`: tahapan dan waktu progres bersama untuk seller/pembeli.
- `resources/js/Pages/Rintis/Storefront/Receipt.vue`: pesan kesiapan, polling dan refresh status privat.
- `resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue`: tombol tahap berikutnya dan dialog konfirmasi untuk seller.
- `routes/rintis.php`: PATCH status dengan scoped binding toko → sesi → pesanan, dalam middleware admin yang sudah ada.
- `app/Http/Requests/Rintis/UpdateOrderStatusRequest.php`: policy pemilik dan validasi status/expected_status.
- `app/Services/Rintis/UpdateOrderStatus.php`: transaksi, urutan tahap, pembayaran transfer, pencegahan update usang dan riwayat audit.
- `app/Models/Rintis/Order.php`: tahap berikutnya dan projection progres yang menghapus identitas petugas.
- `app/Http/Controllers/Rintis/Admin/OpenOrderController.php`: URL/tindakan progres seller dan respons pembaruan.
- `database/migrations/2026_10_31_175757_add_fulfillment_history_to_rintis_orders_table.php`: penyimpanan riwayat. Pesanan lama yang belum memiliki riwayat memakai created_at sebagai waktu diterima.
- `tests/Feature/Rintis/CheckoutTest.php`: progres berurutan/idempotent, pembaruan usang, scope/akses, transfer unpaid, polling privat dan total beberapa produk dengan opsi berharga.

### Cara mencoba

1. Pada sesi aktif, pilih satu produk beserta opsinya, Add to order, lalu pilih produk lain. Periksa daftar Your order dan lanjut checkout setelah semua pilihan lengkap.
2. Untuk menguji harga opsi tanpa mengubah transaksi lama, isi Extra price charged to buyer pada master sebelum membuat sesi baru. HPP tetap merupakan biaya internal. Harga sesi dasar ditambah extra price tersebut.
3. Buat pesanan cash, simpan tautan privat pelacakan. Seller membuka Open orders → Orders, Start preparing dan kemudian Mark ready for pickup ketika barang benar-benar siap. Pada tab pembeli, tunggu maksimal sekitar 15 detik atau tekan Refresh status.
4. Pemesanan melalui data lokal merupakan transaksi nyata dan mengurangi kuota. Tidak membuat pesanan nyata otomatis selama pengerjaan.

### Pemeriksaan dan batasan

**200 tes Laravel lulus, 1.688 assertions**; **18 tes JavaScript lulus**; Pint pada PHP yang berubah selesai dan build berhasil. Migration berhasil diterapkan ke PostgreSQL lokal tanpa menghapus data. Cakupan tambahan memeriksa total beberapa produk dengan opsi, urutan/stale/idempotensi tahap, scope toko/sesi, guest tanpa izin, larangan dispatch pickup, pemisahan pembayaran serta pembacaan progres tamu melalui partial Inertia request tanpa actor_id. Interaksi browser nyata dan pembaruan dua perangkat belum diuji.

Belum ada tahap Completed, validasi penyerahan/QR, konfirmasi penerimaan uang cash, verifikasi transfer, pembatalan pesanan/refund, atau notifikasi di luar halaman. Pesanan ready tidak otomatis dianggap sudah diambil, sudah dibayar atau selesai. Pengaturan routing delivery dan batasan tahap sebelumnya tetap berlaku. Nominal tambahan harga jual Strong masih menunggu keputusan pengguna; implementasi tidak menebak harga dari HPP.

## Tahap 29 — Menu produk langsung di My store

Tanggal: 1 November 2026.

Sesuai klarifikasi pengguna, daftar produk toko sekarang langsung ditampilkan pada halaman My store, di bawah ringkasan toko. Bagian Your menu berisi foto, nama, deskripsi singkat, tautan Edit product dan tombol Add product. Foto kosong menggunakan placeholder. Tampilan menyesuaikan layar: satu, dua atau tiga kolom. Daftar dibatasi 12 produk per halaman dengan Previous/Next agar tetap ringan saat jumlah produk bertambah. Panel setup yang belum lengkap tetap tersedia setelah bagian menu. Teks lama yang menyebut checkout sebagai tahap mendatang diperbarui sesuai kondisi fitur.

Data diambil melalui relasi catalogProducts milik toko setelah policy pemilik diperiksa, tidak termasuk produk yang sudah dihapus. Props hanya memuat identitas/nama/deskripsi/foto dan URL edit, tanpa mengirim resep/HPP atau opsi privat yang tidak dipakai halaman ini. Ini menu master seller, bukan daftar pesanan atau hanya produk sesi PO.

File penting: `app/Http/Controllers/Rintis/Admin/StoreController.php` mengirim data menu dengan pagination; `resources/js/Pages/Rintis/Admin/Overview.vue` menampilkan kartu menu dan navigasinya; `tests/Feature/Rintis/StoreAccessTest.php` memeriksa isi, pagination, pembatasan toko dan projection data.

Cara mencoba: masuk sebagai seller dan buka My store. Produk yang sudah ditambahkan melalui Catalog langsung terlihat; Edit product membuka formulir yang sudah ada. Menu kosong menampilkan petunjuk penambahan produk pertama. Tidak ada perubahan database atau data produk nyata.

Pemeriksaan: **26 tes terkait akses toko/setup lulus, 224 assertions**; Pint selesai dan build frontend berhasil. Interaksi browser nyata belum diuji. Bagian ini belum memiliki pencarian/filter langsung; pengaturan katalog lengkap tetap berada di Catalog.

## Tahap 30 — Sederhanakan harga tambahan opsi

Tanggal: 1 November 2026.

Formulir Product choices di `resources/js/Components/Rintis/ProductOptionsFields.vue` kini hanya menampilkan **Extra price charge** untuk nominal tambahan pilihan. Input Extra production cost dan penjelasan berulang dihapus dari tampilan; petunjuk akhir dipersingkat menjadi penggunaan 0 dan penambahan ke harga dasar. Data HPP opsi yang sudah tersimpan tetap dipertahankan saat mengedit, tidak dihapus otomatis; opsi baru tetap menggunakan biaya internal default 0. Rumus harga checkout tidak berubah.

Cara mencoba: Catalog → Edit product → Product choices. Setiap pilihan hanya memiliki satu kolom nominal yang terlihat. Build frontend berhasil. Tidak ada perubahan database/backend dan tidak menambah tes untuk perubahan tampilan ini.

## Tahap 31 — Target keuntungan dihitung dari HPP (markup)

Tanggal: 1 November 2026.

Atas permintaan pengguna, Price suggestion kini memakai **Target profit from HPP (%)**, yaitu persentase tambahan atas HPP, menggantikan margin dari harga jual pada formulir. Rumus: harga saran = HPP + (HPP × persentase / 100). Contoh HPP Rp8.000: 50% → Rp12.000, 100% → Rp16.000, 200% → Rp24.000. Nilai kosong tidak membuat saran; 0% menghasilkan harga sama dengan HPP. UI menampilkan contoh langsung dan menerima nilai 100% maupun lebih tinggi. Persentase mendukung dua angka desimal, dibatasi 0 sampai 1.000.000%; saran melampaui batas harga aplikasi tidak dapat diterapkan. Hasil pecahan saran dibulatkan ke atas ke rupiah utuh.

Target baru disimpan sebagai `target_markup` dalam JSON costing, sehingga tidak menyalahartikan data `target_margin` lama. Saat formulir produk lama dibuka, target margin dikonversi ke markup yang setara dan dibulatkan dua desimal (misalnya margin 50% menjadi markup 100%). Setelah pengguna menyimpan, costing memakai target_markup. Kalkulator masih dapat membaca data legacy untuk snapshot/klien lama. Konversi dua desimal dapat menghasilkan sedikit perbedaan saran untuk persentase tertentu; **harga dasar yang sudah tersimpan tidak diubah otomatis**. Snapshot sesi dan transaksi lama tidak diubah. Tidak memerlukan migration karena costing sudah berupa JSON.

File penting: `resources/js/Components/Rintis/ProductCostingFields.vue` untuk label, input dan contoh; `resources/js/Support/productCosting.js` untuk pratinjau markup dan konversi target lama; `resources/js/Pages/Rintis/Admin/ProductForm.vue` untuk membuka data lama dengan format baru; `app/Services/Rintis/ProductCostCalculator.php` untuk rumus server dengan pembagian integer agar perkalian tidak overflow; `app/Http/Requests/Rintis/CatalogProductRequest.php` untuk validasi target baru dan kompatibilitas lama. Tes ditambahkan pada `tests/Frontend/productCosting.test.js` dan `tests/Feature/Rintis/ProductCostingTest.php`.

Cara mencoba: Edit product → HPP → Price suggestion, masukkan 100. Jika HPP Rp8.000, saran menjadi Rp16.000. Use as base price hanya menyalin saran ke kolom harga; perubahan produk masih perlu disimpan. Persentase ini adalah selisih atas HPP yang dimasukkan, bukan jaminan laba bersih jika ada biaya lain yang belum dihitung.

Pemeriksaan: **202 tes Laravel lulus, 1.735 assertions; 21 tes JavaScript lulus**. Pint selesai dan build berhasil. Cakupan mencakup 0/50/100/200%, nilai kosong/tidak valid, batas harga, konversi margin lama tanpa mutasi data, kelangsungan HPP ketika target invalid, serta harga dasar tidak berubah otomatis. Interaksi browser nyata belum diuji. Tidak mengubah harga jual produk nyata, sesi PO atau transaksi dalam database lokal selama pengerjaan.

## Tahap 32 — Countdown PO, kendali pembayaran seller dan lokasi pickup setelah Ready

Tanggal: 1 November 2026.

### Hasil

- Ringkasan sesi PO kini memisahkan informasi sesi dan countdown. Jam/menit/detik ditampilkan dalam kotak berwarna gelap, angka tabular dengan nol di depan; sesi lebih dari sehari juga menampilkan hari. Badge Open now/Coming soon/Closed, jadwal WIB dan label pickup/delivery dibuat lebih terstruktur. Countdown tetap mengikuti waktu server yang diterima halaman, bukan jam perangkat semata.
- **Settings → Payment methods** menyediakan checkbox Cash dan Bank transfer. Seller dapat memilih salah satu/keduanya. Menonaktifkan keduanya menghentikan checkout baru; UI memberi penjelasan. Transfer memerlukan setidaknya satu rekening aktif. Pengaturan bank tetap dapat diedit walaupun transfer dimatikan.
- Pengaturan berlaku langsung untuk checkout baru, termasuk sesi PO yang sudah dibuka. Metode tidak aktif disembunyikan di checkout; server memeriksa ulang pengaturan saat submit untuk mengatasi halaman pembeli yang sudah terbuka sebelum seller mematikannya. Pembayaran pesanan yang sudah tercatat tetap mempertahankan metode/rekening historisnya. Pengiriman ulang pesanan yang sama tetap idempotent.
- **Alamat lengkap, koordinat dan petunjuk pickup baru dikirim ketika pesanan pickup berstatus ready** (atau completed jika tahap tersebut kelak dipakai). Ini menggantikan keputusan sebelumnya yang menampilkan lokasi setelah membuat pesanan. Pada received/preparing, props order.pickup bernilai null; bukan sekadar menyembunyikan elemen Vue. Perubahan berlaku juga untuk pembacaan ulang pesanan lama.
- Setelah siap, halaman privat pembeli menampilkan kartu Ready for pickup, nama toko, alamat terstruktur, petunjuk, peta Leaflet dengan pin tetap dan tombol Open map. Sebelum siap ada keterangan bahwa lokasi akan muncul setelah seller menandai ready. Peta hanya dimuat ketika lokasi tersedia; kegagalan tile menampilkan pesan dan tetap menyisakan alamat serta tautan peta. Polling progres 15 detik yang sudah ada akan memperbarui kartu lokasi.

### File penting

- `resources/js/Pages/Rintis/Storefront/OpenOrder.vue`: layout sesi dan countdown.
- `resources/js/Pages/Rintis/Admin/StoreForm.vue`: kontrol metode pembayaran seller.
- `app/Models/Rintis/Store.php`, `app/Http/Requests/Rintis/StoreRequest.php`, `app/Http/Controllers/Rintis/Admin/StoreController.php`: pengaturan boolean tervalidasi dan data formulir.
- `database/migrations/2026_11_01_054634_add_payment_methods_to_rintis_stores_table.php`: cash_enabled/bank_transfer_enabled; default true mempertahankan perilaku metode sebelumnya pada toko lama, transfer tetap memerlukan rekening aktif.
- `app/Http/Controllers/Rintis/CheckoutController.php`, `app/Services/Rintis/PlaceOrder.php`, `resources/js/Pages/Rintis/Storefront/Checkout.vue`: ketersediaan metode, pilihan default yang valid serta pemeriksaan server dalam transaksi. Lock toko diambil sebelum sesi agar urutannya konsisten dengan alur perubahan sesi.
- `app/Models/Rintis/Order.php`: pembatasan projection lokasi berdasarkan status.
- `resources/js/Pages/Rintis/Storefront/Receipt.vue`, `resources/js/Components/Rintis/PickupMap.vue`: kartu dan peta pickup khusus halaman pesanan privat. Leaflet dimuat dinamis setelah komponen terpasang.
- `tests/Feature/Rintis/CheckoutTest.php`, `StoreExperienceTest.php`: status lokasi, kebijakan pembayaran, penyimpanan/validasi pengaturan dan akses seller.

### Cara mencoba

Seller membuka Settings → Payment methods, memilih Cash/Bank transfer dan Save settings. Coba checkout pada sesi aktif untuk melihat metode yang tersedia. Mematikan keduanya menonaktifkan pemesanan baru; transfer tanpa rekening aktif tidak tersedia.

Pada pesanan pickup, buka tautan privat pembeli saat received/preparing: alamat/pin belum muncul. Seller membuka Open orders → Orders, Start preparing lalu Mark ready for pickup. Halaman pembeli memperbarui status otomatis atau melalui Refresh status; alamat dan kartu peta baru muncul setelah ready.

### Pemeriksaan dan batasan

**204 tes Laravel lulus, 1.805 assertions**. Pint pada PHP yang berubah selesai dan build frontend berhasil. Migration diterapkan ke PostgreSQL lokal tanpa menghapus data atau membuat pesanan nyata. Tes mencakup metode dimatikan setelah halaman dibuka, cash saja/transfer saja/keduanya mati, rekening aktif, penolakan tanpa pengurangan kuota, pengaturan tetap ketika klien lama tidak mengirimnya, validasi boolean/akses toko lain, lokasi null sebelum ready dan lokasi tersedia melalui partial reload setelah ready. Interaksi browser nyata, tile jaringan dan tampilan di dua perangkat belum diuji.

Kontrol verifikasi pembayaran transfer, konfirmasi penerimaan cash, Completed/QR dan pengembalian kuota masih belum tersedia. Transfer unpaid tetap tidak bisa disiapkan sesuai aturan sebelumnya; pengaturan metode tidak menandai pembayaran lunas. Ketersediaan routing delivery dan batasan layanan peta dari tahap sebelumnya tetap berlaku.

## Tahap 33 — Antrean ringkas, pemberitahuan halaman, salin rekening dan perbaikan referer peta

Tanggal: 1 November 2026.

Menindaklanjuti keluhan kartu pesanan terlalu besar, akses tile OSM diblokir, kebutuhan salin rekening, serta pertanyaan persiapan gateway dan QR pengambilan.

### Perubahan yang sudah tersedia

- `resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue` sekarang memakai tabel antrean: nomor pesanan/pelanggan, pickup/delivery, metode/status pembayaran, progres, total, Details dan tindakan berikutnya. Rincian produk, telepon, tujuan delivery dan timeline berada dalam dialog Details. Pagination 20 pesanan per halaman tetap digunakan.
- Antrean melakukan partial reload orders setiap 15 detik. Setelah pemuatan awal, kenaikan total pesanan memunculkan pemberitahuan pesanan baru. Perubahan status pembayaran dari status yang sudah pernah dilihat menjadi paid juga dapat memunculkan pemberitahuan pembayaran diterima. Ini indikator berbasis polling halaman sesi, bukan pusat notifikasi persisten: hanya bekerja saat halaman tersebut terbuka; pendeteksian paid terbatas pada baris yang pernah dibaca di halaman. Belum gateway/webhook, push/email/WhatsApp, suara atau notifikasi di seluruh halaman seller.
- `resources/js/Pages/Rintis/Storefront/Receipt.vue` menyediakan Copy account number. Berhasil/gagal diberitahukan; nomor tetap dapat diseleksi untuk disalin manual. Clipboard memerlukan konteks browser yang mendukungnya, umumnya HTTPS atau localhost.
- `resources/js/Components/Rintis/PickupMap.vue` dan `LocationPicker.vue` menetapkan referrerPolicy origin pada gambar tile. Sebelumnya header no-referrer halaman bukti privat menghilangkan identitas situs yang diminta kebijakan tile OSM. Pengaturan baru mengirim origin/domain saja kepada layanan tile, tidak path maupun token pesanan. Header no-referrer untuk halaman privat dan tautan eksternal tetap dipertahankan. Kondisi ready-only untuk lokasi tidak berubah.

Pemeriksaan: build frontend berhasil. Opsi referrerPolicy diperiksa pada source TileLayer paket Leaflet yang terpasang. Tidak mengubah backend/database; tidak menjalankan transaksi/pembayaran nyata. Browser nyata, keberhasilan clipboard, notifikasi dengan dua perangkat dan pemulihan blokir tile jaringan belum diuji. Konfigurasi referrer memperbaiki pelanggaran yang ditemukan; tidak menjamin ketersediaan layanan tile publik atau mengatasi pemblokiran jaringan/provider lain. Tidak melakukan bypass, proxy penyamaran atau cache busting tile.

Cara mencoba: refresh halaman bukti yang sudah Ready for pickup untuk mencoba peta, Copy account number pada bukti transfer, dan buka antrean di satu tab saat ada pesanan baru dari perangkat/tab pembeli. Notifikasi baru terlihat setelah polling saat halaman antrean terbuka. Pesanan uji lewat aplikasi tetap memakai data/kuota nyata.

### Rencana gateway dan pengambilan — belum diimplementasikan

Pengguna menginginkan gateway untuk transfer/QR, pemberitahuan seller ketika dibayar, dana ke seller masing-masing, dan barcode/QR pengambilan baik cash maupun sudah dibayar. Dua jenis QR harus dipisahkan: QRIS untuk pembayaran, QR pengambilan untuk verifikasi penyerahan pesanan.

Alur yang perlu dibangun: order + payment attempt → pembayaran gateway → webhook tervalidasi → payment_status paid → notifikasi seller → persiapan → Ready for pickup → QR pengambilan privat → seller scan → verifikasi toko/status → bila cash unpaid, input uang diterima dan hitung kembalian → konfirmasi bayar/serahkan secara atomik → Completed. Pesanan sudah paid tetap memiliki QR pengambilan; tidak ditagih ulang. Token QR pengambilan harus acak, terikat pesanan/toko, hanya tersedia saat ready dan satu kali pakai; scan saja tidak boleh menyelesaikan pesanan atau menerima uang. Kamera butuh HTTPS dan perlu alternatif kode manual. Ini rancangan, bukan tombol yang sudah tersedia.

Untuk gateway perlu memutuskan penyedia yang mendukung platform multi seller/submerchant, bukan menganggap satu akun merchant otomatis menyetor semua pembayaran ke rekening seller berbeda. Evaluasi Xendit xenPlatform: dokumentasi menyebut sub-account merchant terpisah, penerimaan/split/routing dana serta payout rekening merchant. Aktivasi komersial, dukungan channel Indonesia, onboarding/KYC seller, biaya, jadwal settlement/payout dan siapa menanggung biaya harus dikonfirmasi dengan penyedia. Paid pelanggan, saldo gateway settled dan payout masuk rekening seller adalah peristiwa berbeda; jangan menjanjikan dana langsung tiba di rekening setelah scan.

Persiapan pemilik: akun sandbox penyedia, identitas dan dokumen usaha/rekening sesuai tipe merchant dan permintaan penyedia (Oneclick dan tiap seller yang diwajibkan), keputusan channel awal seperti QRIS dinamis/VA, biaya/komisi, alur refund/expired, alamat aplikasi HTTPS publik untuk webhook. API key/webhook secret disimpan di server/environment, tidak diminta dikirim melalui chat. Untuk pengembangan dapat memakai tunnel HTTPS sementara yang dikonfigurasi khusus; belum menyalakan tunnel/deploy pada tahap ini.

Persiapan kode setelah provider ditentukan: pemetaan store ke merchant/subaccount; payment attempts dan identitas transaksi unik; nominal/channel/rekening payout tersnapshot; webhook memverifikasi identitas/signature/token sesuai provider, merchant, mata uang, jumlah dan payment/order ID; proses idempotent dan tahan callback ganda/terlambat/tidak berurutan; tidak menandai paid dari redirect frontend, tombol pembeli atau screenshot. Catat penerimaan event dan notifikasi persisten setelah transaksi database commit, kemudian antrian worker untuk pengiriman. Notifikasi order baru dan paid merupakan event berbeda. Pisahkan payment status, fulfillment status dan payout status. Transfer manual ke nomor rekening biasa yang sekarang tersedia belum bisa diketahui otomatis tanpa integrasi bank/gateway yang sesuai.

Uji sandbox meliputi pending/paid/expired, signature salah, jumlah/merchant salah, callback ganda/terlambat, klik ulang, kuota/refund, scan QR salah toko dan scan ulang, cash kurang/cukup/kembalian, serta pemisahan dana seller. Setelah lolos dan akun live/channel diaktifkan, lakukan uji transaksi kecil terotorisasi sebelum membuka pembayaran produksi. Belum membuat akun provider, menyimpan secret, menerima dana, atau menghubungkan API gateway pada tahap ini.

Sumber resmi yang diperiksa 1 November 2026:
- OSM tile usage policy: https://operations.osmfoundation.org/policies/tiles/ — valid Referer, attribution, caching dan larangan referrer policy yang meniadakan Referer.
- Xendit xenPlatform overview: https://docs.xendit.co/docs/xenplatform-overview — model platform/sub-account dan payout merchant.
- Xendit sub-account setup: https://docs.xendit.co/docs/xenplatform-global-accounts-setup — aktivasi platform, verifikasi perwakilan usaha dan pengaturan webhook subaccount.
- Xendit split payments: https://docs.xendit.co/docs/split-payments — routing/split mengikuti settlement dana.
- Midtrans webhook: https://docs.midtrans.com/docs/https-notification-webhooks — notifikasi perubahan status transaksi ke backend.

## Tahap 34 — Sesi berpesanan hanya dapat dihentikan, bukan dibatalkan

Tanggal: 1 November 2026.

Aturan baru: Cancel hanya tersedia untuk sesi yang belum mempunyai pesanan pelanggan. Begitu ada satu pesanan, baik unpaid maupun paid, tombol Cancel disembunyikan dan endpoint server menolak pembatalan. Pemeriksaan dilakukan di dalam transaksi setelah lock toko dan sesi, dengan urutan yang sama seperti checkout, sehingga konfirmasi lama tidak dapat melewati aturan ketika pesanan sudah masuk.

Tombol Close diganti **Stop orders**, label sesi yang dihentikan menjadi **Stopped** (nilai status database tetap closed). Dialog menjelaskan bahwa tindakan ini hanya menghentikan pesanan baru. Pesanan, status pembayaran, rekening/harga historis dan kuota yang sudah terpakai tidak dibatalkan, dihapus atau diubah. Seller tetap dapat melihat dan menyiapkan pesanan pada sesi yang sudah dihentikan. Stop berulang tidak mengubah pesanan dua kali. Penolakan akibat pesanan baru yang masuk ketika dialog Cancel masih terbuka ditampilkan di dalam dialog.

File: `app/Http/Controllers/Rintis/Admin/OpenOrderController.php` untuk ketersediaan Cancel dan pemeriksaan transaksi; `resources/js/Pages/Rintis/Admin/OpenOrders/Index.vue` untuk label/dialog; `tests/Feature/Rintis/CheckoutTest.php` untuk kasus unpaid/paid, Cancel langsung lewat endpoint, Stop berulang, tidak ada perubahan transaksi, pesanan baru ditolak setelah Stop, serta pesanan lama tetap bisa diproses. Tes sesi tanpa pesanan pada `OpenOrderTest.php` tetap lulus.

Cara mencoba: sesi tanpa pesanan masih memiliki Cancel. Setelah pembeli membuat pesanan, refresh daftar sesi: hanya Stop orders tersedia untuk penghentian. Stop lalu buka Orders untuk melanjutkan persiapan pesanan lama. Tidak membuat transaksi nyata atau mengubah sesi lokal selama pengerjaan.

Pemeriksaan: **32 tes checkout/sesi lulus, 464 assertions**; Pint selesai dan build frontend berhasil. Tidak ada perubahan skema database. Interaksi browser nyata dan pengujian request simultan PostgreSQL belum dilakukan; jalur penulisan menggunakan lock toko/sesi yang sama. Pembatalan/refund individual pesanan tetap belum diimplementasikan; Stop tidak menjalankan refund atau menganggap kewajiban pesanan selesai.

## Tahap 35 — Akun admin OCS tersedia melalui seeding

Tanggal: 1 November 2026.

Sesuai penunjukan eksplisit pengguna, seeding utama menyiapkan akun bernama **OCS**, email **oneclickstore@gmail.com**, dengan is_platform_owner aktif. Password sesuai yang diberikan pengguna disimpan pada konfigurasi environment lokal yang diabaikan repositori, bukan ditulis di seeder, laporan atau contoh environment. Model User menyimpan password sebagai hash.

`php artisan db:seed` memanggil PlatformOwnerSeeder. Akun dicari berdasarkan email: bila sudah ada, identitas/id dan relasinya tetap dipertahankan; nama, password dan hak owner dikembalikan ke konfigurasi seed. Jika belum ada, dibuat satu akun. Seeding berulang tidak membuat duplikat dan tidak mengubah akun lain. Contoh Test User bawaan tidak lagi dibuat oleh DatabaseSeeder. Pada database baru, `php artisan migrate --seed` juga menjalankan seeder yang sama. Tidak perlu menjalankan migrate:fresh untuk memakai akun ini.

File penting: `database/seeders/DatabaseSeeder.php`, `PlatformOwnerSeeder.php`, `config/central.php`, `.env.example` (nama variabel kosong), environment lokal (`ONECLICK_SEED_OWNER_PASSWORD`, nilai tidak dicatat di laporan), dan `tests/Feature/PlatformOwnerSeederTest.php`. Environment baru harus mengisi variabel password tersebut sebelum seeding; bila kosong/kurang dari 12 karakter, seeder berhenti dengan pesan konfigurasi tanpa membuat akun. Seeding ini memang mengembalikan password akun OCS ke nilai konfigurasi setiap dijalankan; bukan mekanisme pendaftaran otomatis atau pemberian owner ke akun pertama.

Pemeriksaan: **3 tes lulus, 12 assertions**, meliputi dua kali seeding tanpa duplikat, update akun yang sudah ada tanpa mengganti id/akun lain, hash password dan konfigurasi yang belum lengkap. Pint selesai. Cache konfigurasi lokal dibersihkan agar nilai environment dibaca. `php artisan db:seed --no-interaction` berhasil dijalankan pada PostgreSQL lokal untuk menyiapkan akun yang diminta, tanpa menghapus data lain. Tidak ada perubahan frontend/skema database. Cara mencoba: login dengan email OCS dan password yang telah diberikan pengguna, kemudian buka panel admin Central.

## Tahap 36 — Umpan balik harga dasar dan QR pengambilan dengan pembayaran cash

Tanggal: 1 November 2026.

### Hasil yang bisa digunakan

- Klik Use as base price sekarang mengisi harga, membuka tab Product details, menggulir/fokus ke kolom Base price dan menampilkan pesan berhasil beserta nominal. Pesan menjelaskan bahwa Save product masih diperlukan; tombol ini tidak menyimpan perubahan lain di formulir secara otomatis.
- Halaman privat pembeli menampilkan **pickup QR dan kode manual 16 karakter** hanya pada pesanan pickup Ready. QR dibuat lokal di browser tanpa mengirim kode ke situs pembuat QR eksternal. QR ini untuk pengambilan, bukan QRIS pembayaran, dan berbeda dari token tautan bukti pesanan.
- Seller memiliki menu **Scan pickup**. Pemindai dapat memakai kamera laptop atau ponsel; fallback input kode manual tersedia, termasuk kode dengan spasi/tanda hubung. Pemindaian hanya mencari dan menampilkan informasi pembeli, produk/opsi, total dan status pembayaran. Scan tidak menyelesaikan pesanan.
- Cash unpaid: seller memasukkan Cash received; UI menampilkan kekurangan atau kembalian. Setelah dialog konfirmasi, server memeriksa kembali uang yang diterima minimal sebesar total, mencatat cash_received/cash_change, menetapkan paid dan completed, serta waktu dan petugas penyerahan dalam satu transaksi.
- Sudah paid: konfirmasi penyerahan tanpa memasukkan cash atau menagih ulang. Transfer yang masih unpaid ditolak; pemindaian tidak memalsukan verifikasi bank.
- Scan/submit ulang setelah collected tidak mencatat uang atau penyerahan lagi; hasil lama dikembalikan. Halaman pembeli memperlihatkan Collected dan rincian cash/kembalian bila ada. QR disembunyikan setelah completed. Pengambilan tetap bisa dilakukan walaupun sesi PO sudah di-Stop/berakhir.

### Data dan pengamanan

Migration menambahkan pickup_code terenkripsi, pickup_code_hash unik untuk pencarian, collected_at, collected_by, cash_received dan cash_change. Pesanan pickup lama juga mendapat kode privat; pesanan pickup baru membuat kode melalui event creating model. Data pembayaran/status pesanan nyata tidak diubah oleh migration. Hash bukan kode yang ditampilkan pada QR. Kode tidak dikirim pada projection antrean biasa; kode pengambilan hanya ditambahkan pada props halaman bukti pembeli yang ready. Identitas petugas dan hash tidak dikirim kepada pembeli.

Route scan dan penyerahan memerlukan login, akses Rintis aktif dan policy pemilik toko. Pencarian dibatasi store_id dan method pickup. Endpoint diberi rate limit dan dilindungi CSRF; respons JSON lookup/complete menggunakan private no-store. Service memeriksa otorisasi, mengunci order dan melakukan pengecekan ulang ready/payment/cash sebelum menyimpan. Kode QR hanya mengidentifikasi pesanan untuk penyerahan setelah pemeriksaan seller; bukan izin publik untuk menerima pembayaran. Scan salah toko, delivery, belum ready, uang kurang/invalid dan transfer belum dibayar tidak bisa menyelesaikan pesanan.

### File penting

- `resources/js/Components/Rintis/ProductCostingFields.vue`, `resources/js/Pages/Rintis/Admin/ProductForm.vue`: event penerapan harga dan umpan balik/fokus tab.
- `database/migrations/2026_11_01_063303_add_pickup_collection_to_rintis_orders_table.php`: struktur dan backfill kode pickup lama.
- `app/Models/Rintis/Order.php`: pembuatan kode, enkripsi/hash, pickupPass ready-only dan projection hasil pengambilan.
- `app/Http/Controllers/Rintis/CheckoutController.php`, `resources/js/Pages/Rintis/Storefront/Receipt.vue`, `resources/js/Components/Rintis/PickupPass.vue`: QR dan status Collected pada bukti privat.
- `app/Http/Controllers/Rintis/Admin/PickupController.php`, `app/Http/Requests/Rintis/CompletePickupRequest.php`, `app/Services/Rintis/CompletePickup.php`: lookup dan transaksi penyerahan/cash.
- `resources/js/Pages/Rintis/Admin/Pickup.vue`: kamera, input manual, informasi pesanan, nominal/kembalian dan dialog konfirmasi.
- `resources/js/Support/request.js`: JSON POST dengan header XSRF dan penanganan kegagalan.
- `resources/js/Components/Rintis/OrderProgress.vue`: tahap Collected/Completed.
- `routes/rintis.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/Layouts/Rintis/RintisLayout.vue`: route/navigasi Scan pickup.
- `package.json`/`package-lock.json`: qrcode untuk pembuatan QR dan @zxing/browser versi 0.1.5 untuk kamera; versi dipilih agar sesuai Node 22 proyek. Library scanner dimuat saat Start camera, bukan saat setiap halaman dibuka.
- `tests/Feature/Rintis/PickupCollectionTest.php`: pengujian akses, pembayaran dan penyerahan.

### Cara mencoba

1. Pada HPP, klik Use as base price: tab pindah ke Product details, harga terisi dan pesan berhasil muncul. Save product untuk menyimpannya.
2. Pada pesanan pickup, seller menandai Preparing lalu Ready. Pembeli membuka tautan privat/Refresh status; QR dan kode manual muncul.
3. Seller memilih Scan pickup → Start camera lalu arahkan kamera ke QR di perangkat pembeli. Alternatif: masukkan kode manual.
4. Periksa pembeli/produk. Untuk cash, masukkan uang yang benar-benar diterima; periksa kembalian lalu Confirm cash & collection dan dialog konfirmasi. Untuk paid, Confirm collection tanpa meminta uang lagi.
5. Pesanan menjadi Completed; browser pembeli memperbarui status Collected melalui polling. Coba scan ulang untuk melihat hasil penyerahan yang sudah tercatat, bukan menerima uang ulang.

### Pemeriksaan dan batasan

**214 tes Laravel lulus, 1.967 assertions; 21 tes JavaScript lulus**. Pint selesai, build berhasil dan npm audit saat pemasangan melaporkan 0 vulnerabilities. Migration berhasil diterapkan ke PostgreSQL lokal tanpa menghapus data. Pemeriksaan agregat memastikan seluruh kode pickup hasil backfill dapat didekripsi dan sesuai hash, tanpa menampilkan kode atau data pembeli.

Lima tes fitur baru mencakup QR ready-only dan hilang setelah collected, kode terenkripsi/projection aman, lookup tidak membocorkan HPP/token bukti, cash kurang ditolak tanpa mutasi, cash/kembalian dan petugas tercatat, kirim ulang tidak mengubah hasil, prepaid tanpa pembayaran kedua, received/transfer unpaid ditolak, guest/foreign seller/wrong store/delivery ditolak, normalisasi kode dan nominal invalid. Uji prepaid memakai data terisolasi yang ditandai paid, bukan transaksi bank sungguhan. Tidak mengonfirmasi atau menyelesaikan pesanan nyata selama pengerjaan.

Kamera nyata, pemindaian QR dari layar dan alur fokus/scroll browser belum diuji langsung. Kamera membutuhkan izin pengguna dan HTTPS atau localhost; akses dari alamat IP LAN HTTP umumnya tidak memenuhi syarat, gunakan kode manual atau HTTPS. Tidak ada upload foto QR atau integrasi scanner hardware khusus; scanner yang bertindak sebagai keyboard dapat mengisi input manual. Gateway/verifikasi transfer tetap belum terhubung, jadi paid transfer harus berasal dari proses verifikasi yang sah pada tahap berikutnya. Penyerahan delivery, refund dan rekonsiliasi gateway tetap belum ditambahkan.

Referensi paket resmi: https://github.com/soldair/node-qrcode dan https://github.com/zxing-js/browser . API yang dipakai juga diperiksa dari paket yang terpasang.

## Tahap 37 — My orders untuk pembeli tamu dan akun

Tanggal: 1 November 2026.

### Hasil

Pembeli dapat membuka **My orders** di `/rintis/my-orders`. Tautan tersedia pada halaman toko, menu PO, checkout, bukti pesanan, direktori Rintis serta menu akun seller. Riwayat berisi toko, tanggal WIB, nomor pesanan, nama/jumlah produk, total, pickup/delivery, metode/status pembayaran, progres dan View order. View order membuka kembali halaman privat yang sudah memiliki pelacakan, QR pickup ketika ready dan hasil collected. Riwayat mencakup toko berbeda milik pembeli yang sama, dengan pagination 12 per halaman, bukan semua pesanan dari toko tersebut.

Pembeli tanpa akun tetap didukung. Identitas browser memakai cookie `rintis_order_history` acak yang dienkripsi middleware Laravel, HttpOnly dan SameSite Lax; Secure pada HTTPS. Cookie berlaku 30 hari dan diperbarui ketika checkout atau halaman My orders dibuka. Key yang sudah ada pada sesi tamu sebelumnya dipakai kembali agar pesanan lama dari sesi yang masih tersedia dapat muncul. Cookie ini terpisah dari session cookie, sehingga berakhirnya sesi login biasa tidak langsung menghilangkan identitas tamu. Daftar history tidak disimpan pada localStorage dan tidak dibuka menggunakan nomor telepon/nama/email.

Pesanan baru yang dibuat saat login mendapat customer_id dari user request yang terautentikasi, bukan input browser. Akun dapat melihat pesanan terikat akunnya dari perangkat lain. Saat login, daftar juga mencakup pesanan tamu yang belum terikat akun dari browser saat ini. Tidak mengklaim/mengubah pemilik pesanan tamu otomatis saat login, dan tidak mencocokkan pesanan lama berdasarkan nama/nomor telepon. Saat logout atau berganti akun, pesanan yang sudah terikat akun lain tidak ikut terlihat meski memakai cookie browser yang sama.

### Privasi dan file penting

- `database/migrations/2026_11_01_070422_add_customer_to_rintis_orders_table.php`: customer_id nullable serta indeks pencarian riwayat akun/tamu. Pesanan lama tidak diberi customer_id secara spekulatif.
- `app/Services/Rintis/GuestOrderIdentity.php`: mengambil/membuat identitas tamu dan cookie terenkripsi persisten.
- `app/Services/Rintis/PlaceOrder.php`, `app/Http/Controllers/Rintis/CheckoutController.php`: mengikat pesanan baru pada user login, mempertahankan identitas tamu dan mencegah pengulangan idempotency milik akun lain pada browser yang sama.
- `app/Http/Controllers/Rintis/OrderHistoryController.php`: query terbatas akun yang sedang login atau guest_key browser dengan customer_id null; projection ringkas dan respons private no-store/no-referrer.
- `app/Models/Rintis/Order.php`: customer_id pada data pesanan.
- `resources/js/Pages/Rintis/Storefront/OrderHistory.vue`: daftar riwayat, status, halaman kosong dan navigasi pagination.
- `routes/rintis.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/Layouts/Rintis/RintisLayout.vue`, halaman storefront Home/OpenOrder/Checkout/Receipt: route serta tautan My orders.
- `tests/Feature/Rintis/OrderHistoryTest.php`: cakupan isolasi browser/akun, cookie, checkout dan pagination.

Riwayat hanya mengirim ringkasan dan tautan privat milik pembeli tersebut, bukan nomor telepon, alamat lengkap, koordinat, resep/HPP, key browser, hash atau QR pickup. Mengunjungi tautan bukti orang lain tidak otomatis memasukkan pesanan atau seluruh riwayat mereka ke browser pengunjung. Parameter query customer_id/phone tidak dipakai sebagai otorisasi. Login tetap tidak diwajibkan untuk checkout atau riwayat browser.

### Cara mencoba

1. Buat pesanan dari halaman publik sebagai tamu, lalu klik My orders pada bukti pesanan. Pesanan muncul dengan tombol View order.
2. Buka toko lain dan pesan dari browser yang sama: keduanya masuk riwayat tamu tersebut. Browser/incognito lain memiliki riwayat tersendiri.
3. Buat pesanan saat login. Login dengan akun yang sama pada perangkat lain untuk melihat pesanan terikat akun tersebut.
4. Gunakan View order untuk mengecek progres/QR yang sudah ada. Riwayat daftar dibaca ketika halaman dibuka; progres langsung tetap berada pada halaman detail pesanan.

### Pemeriksaan dan batasan

**219 tes Laravel lulus, 2.091 assertions; 21 tes JavaScript lulus**, Pint selesai dan build frontend berhasil. Migration berhasil diterapkan ke PostgreSQL lokal tanpa menghapus data atau mengubah pemilik pesanan lama. Lima tes riwayat memeriksa cookie terenkripsi/HttpOnly/masa berlaku dan pemulihan setelah sesi hilang, browser asing tidak melihat riwayat, akun dapat melihat pesanan antarbrowser, akun lain pada browser yang sama tidak bocor, query palsu diabaikan, riwayat tidak memuat PII/HPP/pickup code, pagination, kunjungan bearer tidak mengimpor riwayat, customer_id checkout tidak dapat dipalsukan dan replay idempotency lintas akun ditolak.

Tidak membuat pesanan nyata atau menghubungkan pesanan lama ke akun tertentu selama pengerjaan. Interaksi browser nyata lintas perangkat belum diuji. Menghapus cookie, cookie kedaluwarsa, incognito ditutup atau pindah browser dapat menghilangkan akses daftar tamu; pesanan di database tidak dihapus. Simpan tautan privat order sebagai akses cadangan. Pesanan tamu lama yang identitas browser/sesinya sudah hilang tidak dapat dipulihkan melalui nomor telepon saja. Penggabungan riwayat tamu ke akun melalui klaim terverifikasi belum tersedia; riwayat akun otomatis berlaku untuk pesanan baru yang dibuat saat login.

## Tahap 38 — Bayar transfer lebih dulu, satu pesanan aktif dan routing lokal

Tanggal: 1 November 2026.

### Keputusan pengguna dan hasil

Pengguna menegaskan checkout baru dibatasi **selama masih ada pesanan yang belum Completed, termasuk cash**. Batas ini bukan hanya transfer unpaid: pesanan paid yang masih preparing/ready juga memblokir pesanan baru, termasuk ke toko lain.

- `ActiveCheckout` mencari pesanan belum completed berdasarkan identitas tamu browser (hanya pesanan belum terikat akun) dan/atau customer_id akun login. Membuka checkout saat ada pesanan aktif mengarahkan kembali ke halaman privat pesanan tersebut. POST langsung atau halaman lama tetap ditolak di server sebelum membuat pesanan/mengurangi kuota. Retry dengan idempotency key pesanan yang sama tetap mengembalikan pesanan sebelumnya.
- Migration `2026_11_01_071532_create_rintis_checkout_guards_table.php` menambahkan baris penguncian identitas berbentuk hash. Semua jalur pembuatan pesanan mengambil lock identitas dalam urutan yang konsisten sebelum lock toko/sesi, sehingga permintaan checkout pembeli yang sama ke toko berbeda tetap diserialisasi. Completed membuka kesempatan checkout berikutnya; data pesanan lama tidak dihapus atau diselesaikan secara otomatis.
- Transfer belum paid sekarang tampil sebagai **Complete your payment / Awaiting payment**, instruksi rekening ditempatkan di bagian atas dan timeline memiliki langkah Payment sebelum Order received/Preparing. Ini perubahan urutan informasi; backend tetap memisahkan payment_status dan status fulfillment. Cash tetap membayar saat pickup/delivery sesuai metode yang dipilih, tetapi ikut batas satu pesanan aktif.
- Halaman detail pesanan hanya memiliki satu navigasi **Back to menu**, menuju menu PO toko. Tautan Back to store dan My orders di bagian itu dihapus. Checkout juga hanya menyisakan Back to menu di bagian navigasi atas. Riwayat tetap dapat dibuka dari menu toko/direktori yang lain.

### Routing delivery lokal

Error sebelumnya terjadi karena RINTIS_ROUTING_URL belum terisi, bukan karena pin Leaflet gagal. Environment lokal kini memakai endpoint demo OSRM `https://router.project-osrm.org/route/v1/driving` dan RINTIS_ROUTING_DEMO=true. Mode demo dibatasi global maksimal satu request per detik, memakai User-Agent aplikasi dan atribusi rute/OSM, dan ditolak bila app environment produksi. `.env.example` tetap berisi endpoint kosong dan demo false agar tidak menjadikan server demo publik sebagai layanan produksi otomatis.

`CheckoutController::quote` tetap menghitung jarak jalan di server, memeriksa batas jarak dan tarif snapshot, serta menyimpan quote berumur 15 menit. Tidak memakai jarak garis lurus atau ongkir buatan. Endpoint demo diuji dengan koordinat contoh publik Berlin dan mengembalikan code Ok serta jarak 1.888 meter; tidak mengirim alamat/pin toko atau pembeli nyata saat pengerjaan. Routing wilayah toko sebenarnya, ketepatan jalan dan alur browser belum diuji langsung; demo tidak memiliki jaminan ketersediaan untuk produksi.

### File dan pemeriksaan

File utama: `app/Services/Rintis/ActiveCheckout.php`, `PlaceOrder.php`, `app/Http/Controllers/Rintis/CheckoutController.php`, `config/rintis.php`, migration checkout guards, environment lokal/contoh, `resources/js/Components/Rintis/OrderProgress.vue`, halaman storefront Receipt/Checkout/OrderHistory, halaman admin OpenOrders/Orders, `tests/Feature/Rintis/ActiveCheckoutTest.php` dan penyesuaian isolasi konfigurasi routing pada CheckoutTest.

**222 tes Laravel lulus, 2.127 assertions; 21 tes JavaScript lulus**. Pint selesai, build berhasil dan migration berhasil diterapkan ke PostgreSQL lokal. Tes tambahan mencakup pemblokiran lintas toko, semua tahap sebelum completed termasuk paid, pembukaan kembali setelah completed, akun lintas browser, akun lain tidak terblokir oleh pesanan akun sebelumnya, rate limit demo dan penolakan mode demo di production. Pengujian rate limit/produksi menggunakan HTTP fake; pengecualian CSRF hanya digunakan dalam tes yang menyimulasikan environment produksi, bukan pada kode aplikasi. Pengujian konkurensi nyata PostgreSQL belum dilakukan.

Tidak mengubah status/payment pesanan nyata, membatalkan transfer lama atau melepas kuotanya. Jika sudah ada beberapa pesanan aktif dari tahap sebelumnya, semuanya tetap tersimpan dan checkout baru tetap dibatasi sampai tidak ada lagi yang belum completed. Untuk tamu, batas terkait identitas browser/cookie, bukan identitas orang terverifikasi; menghapus cookie/pindah browser tidak bisa dicegah tanpa akun/verifikasi tambahan. Pembatalan pesanan individual dan expiry pembayaran belum tersedia. Transfer manual saat ini tetap memerlukan verifikasi yang sah; belum ada gateway untuk melunaskannya otomatis dan tidak menambahkan tombol pembeli yang mengklaim sudah bayar.

### Langkah pertama gateway dan biaya

Pengguna belum memilih penyedia dan meminta penjelasan istilah banyak seller. Sudah dijelaskan bahwa uang Wemeet milik Wemeet dan uang toko pihak lain harus masuk ke merchant/rekening mereka masing-masing. Arah belajar yang disarankan adalah Midtrans Sandbox untuk Wemeet terlebih dahulu; belum menghubungkan akun/provider, membuat pembayaran, menambahkan secret atau mengaktifkan fitur berbayar.

Dokumentasi resmi Midtrans menyatakan sandbox gratis, memakai simulator dan tidak membutuhkan uang nyata. Produksi memiliki biaya transaksi; halaman harga saat diperiksa menampilkan VA/bank transfer Rp4.000 dan QRIS 0,7%, dengan pajak/ketentuan sesuai halaman/merchant. Xendit xenPlatform berbeda dari akun payment merchant biasa dan halaman resminya memuat biaya aktivasi/platform/onboarding. Karena itu saran lama untuk mengevaluasi xenPlatform bukan persetujuan mengaktifkan produk berbayar; tidak merekomendasikan mengaktifkannya untuk belajar sebelum menilai biaya/kontrak.

Langkah pemilik berikutnya: daftar akun Midtrans, masuk Sandbox/Test environment, lalu siapkan Server Key dan Client Key melalui environment server lokal (jangan dikirim ke chat). Setelah akun tersedia, implementasi dapat dilanjutkan bertahap ke pembuatan payment attempt, halaman bayar Snap, HTTPS webhook tervalidasi/idempotent, simulator paid/expired dan notifikasi seller. Pembayaran produksi untuk banyak toko perlu keputusan merchant/account per seller tersendiri sebelum memakai uang nyata; satu akun Wemeet tidak otomatis dapat mencairkan uang semua seller lain.

Sumber resmi diperiksa 1 November 2026:
- https://docs.midtrans.com/docs/testing-payment-on-sandbox — sandbox gratis/simulasi, bukan pembayaran nyata.
- https://midtrans.com/pricing — biaya produksi/channel dan ketentuan pajak.
- https://www.xendit.co/id/biaya — biaya layanan/platform, bukan diasumsikan gratis.
- https://github.com/Project-OSRM/osrm-backend/wiki/Api-usage-policy dan https://routing.openstreetmap.de/about.html — atribusi, identitas aplikasi, batas satu request/detik dan larangan penggunaan berat.

## Tahap 39 — Persiapan konfigurasi Midtrans Sandbox

Tanggal: 1 November 2026.

Pengguna telah masuk dashboard Midtrans Sandbox dan meminta penjelasan routing demo serta panduan langkah berikutnya. Menyiapkan `config/midtrans.php` dengan Merchant ID/Server Key/Client Key yang dibaca dari environment, dan is_production false untuk tahap awal sandbox saja. Menambahkan nama variabel kosong ke environment lokal dan `.env.example` bila belum ada, tanpa menimpa nilai yang mungkin sudah diisi. Tidak membaca/menampilkan nilai kredensial, tidak memasang SDK, tidak membuat pembayaran atau mengubah status paid.

Langkah pengguna: pada lingkungan Sandbox di dashboard, buka Settings → Access Keys; salin Merchant ID, Server Key dan Client Key ke variabel MIDTRANS_* di `.env` lokal. Jangan menaruh Server Key pada Vue, chat, screenshot, atau `.env.example`. Setelah tersimpan, integrasi token transaksi/webhook akan dikerjakan sebagai tahap selanjutnya. Variabel saja belum mengaktifkan gateway. Config khusus provider tetap dikunci sandbox pada tahap persiapan ini.

Routing dijelaskan sebagai layanan pencari jalan/jarak dari pin toko ke pembeli, terpisah dari gambar peta. Local berarti aplikasi di laptop/127.0.0.1; production berarti aplikasi publik untuk pelanggan nyata. Demo routing adalah layanan bersama untuk belajar/tes ringan, sehingga konfigurasi saat ini menolaknya di production agar tidak menjadi ketergantungan produksi tanpa persiapan. Bukan larangan memakai maps ketika website dipublikasikan; produksi memerlukan layanan routing yang sesuai atau server sendiri.

Pemeriksaan: Pint config/midtrans.php selesai. Ini persiapan konfigurasi tanpa perubahan runtime checkout/frontend/database, sehingga tidak menjalankan ulang suite/build. Dokumentasi resmi diperiksa: https://docs.midtrans.com/docs/midtrans-account dan https://docs.midtrans.com/docs/api-authorization-headers untuk Settings → Access Keys dan kerahasiaan Server Key; https://docs.midtrans.com/docs/testing-payment-on-sandbox untuk simulasi tanpa dana nyata.


## Tahap 40 — Virtual Account otomatis Midtrans Sandbox

Tanggal: 1 November 2026.

Tujuan: pembeli tidak perlu menekan Pay now atau mengaku sudah bayar. Pada checkout baru Wemeet Coffee, pilihan Bank transfer menampilkan bank BCA/BNI/BRI. Setelah Place order, backend meminta nomor VA melalui Core API Sandbox dan halaman receipt menampilkan nomor serta tombol Copy. Cash tetap menggunakan alur sebelumnya. Gateway dibatasi lingkungan local/testing, is_production false, dan slug toko wemeet-coffee; bukan aktivasi merchant bagi seluruh seller. Tidak memasang SDK/dependensi baru, tidak mengubah skema atau nilai key.

File penting:
- `config/midtrans.php`: konfigurasi secret server dan batas toko Sandbox. Core API memakai Server Key; Client Key tidak diperlukan untuk alur VA langsung ini.
- `app/Http/Requests/Rintis/CheckoutRequest.php` dan `app/Services/Rintis/PlaceOrder.php`: validasi bank, pembayaran yang diaktifkan seller, serta snapshot intent VA dengan provider_order_id stabil dari idempotency key. Transfer manual lama tetap didukung; permintaan tanpa payment_bank tidak dikonversi diam-diam.
- `app/Services/Rintis/MidtransPayment.php`: GET status sebelum membuat charge; menggunakan kembali provider order ID setelah timeout. Memeriksa ID transaksi, merchant, mata uang, nominal total server, tipe pembayaran dan settlement sebelum paid. Lock order dan pengecekan paid mencegah proses ulang. Payload/secret provider tidak ditulis ke log atau props. Status expire/cancel/deny tetap unpaid; VA tidak ditawarkan untuk disalin ketika terminal gagal.
- `CheckoutController.php`: pembuatan VA setelah checkout tersimpan, sinkronisasi status pada receipt dengan jeda minimal 15 detik. Gagal koneksi tidak membatalkan order atau mengulang pengurangan kuota. Tombol retry hanya muncul bila VA belum didapat dan memakai transaksi yang sama.
- `MidtransController.php`, `routes/rintis.php`, `bootstrap/app.php`: endpoint retry memakai token privat + CSRF + throttle; webhook hanya mengecualikan satu path dari CSRF. Webhook tidak mempercayai status kiriman; mencari intent dikenal lalu meminta status langsung ke Midtrans dengan autentikasi server. Status paid tidak diturunkan oleh notifikasi berikutnya.
- `resources/js/Pages/Rintis/Storefront/Checkout.vue` dan `Receipt.vue`: pilihan bank dan tampilan VA/copy/expiry/retry Sandbox. Tidak menambahkan tombol konfirmasi bayar.

Cara mencoba:
1. Settings → Access Keys → General Credentials → server key Sandbox: pastikan izin Core API **Write** karena kini perlu membuat VA, bukan hanya membaca status. Snap Checkout tidak dipakai pada alur ini.
2. Buat checkout baru di Wemeet Coffee, pilih Bank transfer dan BCA, lalu Place order. Untuk menghindari pesanan aktif lama yang memblokir checkout, gunakan browser incognito sebagai pembeli uji baru. Pesanan transfer manual lama tidak berubah menjadi VA dan tidak ditandai lunas.
3. Salin VA yang muncul, buka simulator resmi BCA `https://simulator.sandbox.midtrans.com/bca/va/index`, masukkan nomor tersebut dan selesaikan simulasi pembayaran. Tidak mengirim uang nyata.
4. Biarkan receipt terbuka. Setiap 15 detik aplikasi memeriksa status, kemudian menampilkan Paid setelah Midtrans mengonfirmasi settlement. Seller yang membuka tabel Orders menerima pembaruan/notifikasi melalui polling yang sudah ada; belum berupa push ketika halaman tertutup.
5. Endpoint webhook tersedia di `/rintis/payments/midtrans/notification`. Untuk menerima notifikasi ketika receipt tertutup, perlu URL HTTPS publik dan pengisian Payment Notification URL di dashboard. Belum membuat tunnel/deploy atau mengubah dashboard. Midtrans tidak dapat menghubungi 127.0.0.1; polling receipt memungkinkan latihan lokal sebelum HTTPS disiapkan.

Pemeriksaan: **229 tes Laravel lulus, 2.187 assertions**, termasuk tujuh tes baru checkout otomatis/idempotensi, status palsu, nominal/merchant salah, timeout/pemulihan, bank/toko lain, cash, expiry dan polling settlement. Pint selesai dan build Vite berhasil. Pemeriksaan jaringan nyata hanya GET status ID pemeriksaan yang sengaja tidak ada: HTTP 200 dengan provider status 404, membuktikan autentikasi baca Sandbox diterima. Tidak membuat charge atau mengubah pesanan nyata dalam pemeriksaan tersebut. Pembuatan VA dengan key pengguna, simulator pembayaran, kamera/browser dan webhook HTTPS belum diuji langsung.

Keterbatasan: tahap ini khusus VA bank, belum QRIS, refund, penggantian transaksi expired atau pencairan banyak seller. Order expired tetap tersimpan dan tidak membuka checkout baru karena kebijakan satu order belum Completed belum diubah. Jeda polling memerlukan halaman receipt terbuka; webhook HTTPS belum dikonfigurasi. Perubahan slug Wemeet harus diikuti konfigurasi toko gateway yang sesuai. Nilai dana bukan langsung masuk rekening seller; ini Sandbox dan konfigurasi pencairan produksi belum diaktifkan.

Sumber resmi:
- https://docs.midtrans.com/docs/coreapi-core-api-bank-transfer-integration — Charge API bank transfer dan respons VA.
- https://docs.midtrans.com/docs/https-notification-webhooks — verifikasi notifikasi dengan GET Status API, bukan mempercayai kiriman browser/webhook.
- https://docs.midtrans.com/docs/testing-payment-on-sandbox — simulator bank dan pembayaran percobaan.

## Tahap 41 — Percobaan VA Sandbox langsung

Tanggal: 1 November 2026.

Pengguna menunjukkan izin Core API Write dan receipt pesanan yang belum memperoleh VA. Pemeriksaan GET status untuk intent Sandbox terakhir mengembalikan provider 404 (transaksi belum ada). Percobaan ulang Charge API memakai ID intent, bank dan total server yang sama berhasil: HTTP 200, provider status 201, VA tersedia. Kemudian memanggil MidtransPayment::sync melalui GET status; sinkronisasi berhasil dan nomor VA tersimpan pada pesanan gateway terbaru. Tidak mencetak key, VA, nomor telepon, tautan privat atau rincian pembeli ke output. Tidak membuat order aplikasi baru, tidak mengurangi kuota ulang, dan tidak menandai paid: status tetap unpaid sampai simulator menyelesaikan pembayaran.

Tidak mengubah kode aplikasi pada tahap ini. Keberhasilan retry sudah terverifikasi langsung, tetapi penyebab kegagalan request awal belum dipastikan; tidak menyimpulkan izin lama sebagai sebab tanpa bukti. Langkah pengguna: refresh receipt, salin VA, masukkan ke simulator resmi BCA, selesaikan simulasi lalu biarkan receipt terbuka untuk sinkronisasi. Belum menjalankan simulator, penyerahan pickup atau webhook HTTPS. Tidak menjalankan ulang suite karena kode tidak berubah. Signature Secret Key tampak pada screenshot pengguna; menyarankan regenerasi lewat dashboard tanpa mengirim nilai pengganti ke chat.

## Tahap 42 — Sinkronisasi setelah simulator dan modal pembayaran sukses

Tanggal: 1 November 2026.

Pengguna telah membayar melalui simulator tetapi receipt masih Unpaid. Pemeriksaan langsung GET status intent gateway terbaru membuktikan settlement, merchant/nominal/mata uang cocok, fraud accept. Menjalankan service sinkronisasi yang sudah ada berhasil menyimpan Paid berdasarkan respons tersebut; tidak memalsukan status, membuat charge atau pesanan baru. Penyebab browser tidak memperbarui belum direproduksi langsung, tetapi kode Inertia yang terpasang memperlambat polling tab tersembunyi hingga satu dari sepuluh interval.

Mengubah `resources/js/Pages/Rintis/Storefront/Receipt.vue`: polling keepAlive dengan mode rest; saat tab kembali terlihat, window fokus atau koneksi online kembali, segera reload props order. Listener dilepas saat unmount, refresh fokus yang sedang berlangsung tidak ditumpuk. Status gagal validasi diberi pesan. Browser/OS masih dapat menangguhkan tab sepenuhnya; webhook HTTPS tetap diperlukan untuk pembaruan saat halaman tertutup.

Menambahkan modal Shared/Dialog **Payment successful** yang hanya muncul untuk bank_transfer setelah props server menunjukkan paid. Menampilkan jumlah yang terverifikasi dan tombol Track my order. Penutupan dicatat dalam sessionStorage menggunakan referensi pesanan, sehingga polling/reload dalam tab yang sama tidak membuka ulang modal yang sudah ditutup; tidak menyimpan token/PII. Pending menggunakan ikon jam, bukan centang sukses. Modal bukan bukti pembayaran; server tetap memverifikasi status Midtrans terlebih dahulu.

Cara mencoba: refresh sekali untuk memuat aset frontend terbaru; pesanan yang telah diverifikasi menampilkan modal sukses. Pada transaksi berikutnya, buka simulator untuk membayar lalu kembali ke tab receipt; pemeriksaan status segera dijalankan. Seller tetap memakai tabel Orders dengan polling yang sudah ada. Belum menguji interaksi browser/modal secara langsung; build Vite berhasil. Tidak mengubah backend, skema atau key dan tidak mengaktifkan webhook publik.

## Tahap 43 — Sembunyikan instruksi pembayaran setelah lunas

Tanggal: 1 November 2026.

Mengubah kondisi tampilan kartu rekening/VA di `resources/js/Pages/Rintis/Storefront/Receipt.vue` agar hanya muncul selama payment_status belum paid. Ketika polling mengonfirmasi Paid, nomor VA, tombol salin dan instruksi transfer otomatis menghilang; progress pesanan dan ringkasan Paid amount tetap tersedia. Data pembayaran historis tidak dihapus. Bagian expiry/retry sudah dibatasi unpaid pada implementasi sebelumnya.

Cara mencoba: buka receipt pesanan yang sudah paid; kartu VA tidak tampil. Receipt unpaid tetap menampilkan VA yang tersedia. Build Vite berhasil. Tidak mengubah backend/database dan tidak menjalankan ulang tes backend untuk perubahan kondisi tampilan ini; pemeriksaan browser langsung belum dilakukan.

## Tahap 44 — Antrean pengantaran seller dan persiapan bersama

Tanggal: 1 November 2026.

Tujuan: seller dapat menyiapkan banyak pesanan bersama dan mengikuti tujuan pengantaran berurutan, bukan membuka alamat satu per satu tanpa antrean.

Hasil yang dapat digunakan:
- Menu seller **Deliveries**, tombol **Delivery queue** di daftar pesanan sesi, dan tautan **Navigate** langsung pada pesanan delivery. Halaman pengantaran mengumpulkan delivery belum completed dari semua sesi toko, 30 pesanan per halaman. Seller dapat memilih maksimal 10 di halaman yang sama.
- Pilih pesanan Received lalu **Prepare together**; setelah siap pilih yang Preparing lalu **Mark all ready**. Transaksi bulk bersifat atomik: status campur, pesanan toko lain, pickup atau transfer unpaid ditolak tanpa pembaruan parsial.
- Pilih delivery Ready atau yang sudah Out for delivery lalu **Plan delivery route**. Server meminta satu matriks jarak jalan OSRM Table, mulai dari pin toko dan memilih tujuan terdekat dari titik sebelumnya. Contoh tes: toko→A 100m, A→C 150m, C→B 100m, bukan mengurutkan semua jarak hanya dari toko. Ini nearest-neighbor heuristic, bukan jaminan rute global paling pendek, tidak mempertimbangkan lalu lintas atau waktu janji pelanggan.
- Antrean bernomor disimpan di tabel `rintis_delivery_runs` dan ditampilkan di Leaflet. Pin S menunjukkan toko; pin nomor sesuai daftar; tujuan berikutnya dan yang dipilih disorot. Peta menunjukkan lokasi tujuan, bukan garis rute jalan/turn-by-turn. Tombol Navigate membuka Google Maps untuk tujuan yang dipilih; Google dapat memilih rute jalan berbeda dari OSRM.
- **Start delivery trip** memperbarui pesanan Ready menjadi Out for delivery bersama. Seller mengikuti tujuan berikutnya, menyerahkan pesanan, lalu **Mark delivered**. Pesanan paid cukup konfirmasi; cash unpaid memerlukan nominal diterima yang cukup, mencatat kembalian dan paid bersama status completed. Pengulangan konfirmasi tidak menggandakan penerimaan. Hanya stop berikutnya yang dapat diselesaikan melalui antrean. Setelah semua selesai, perjalanan ditandai completed dan perjalanan baru dapat dibuat.
- Satu perjalanan planned/active per toko untuk tahap pertama. Planned dapat di-clear tanpa mengubah pesanan; active tidak dapat dihapus melalui Clear. Urutan tersimpan tersedia ketika seller pindah perangkat. Tidak mengubah pesanan nyata saat pengerjaan.

File penting:
- Migration `2026_11_01_141618_create_delivery_runs_table.php`, model `app/Models/Rintis/DeliveryRun.php`, relasi orders pada Store: penyimpanan status perjalanan, pin awal, urutan ID pesanan, estimasi jarak.
- `app/Services/Rintis/PlanDelivery.php`: validasi pin/metode/payment/status, OSRM Table dan pemilihan tujuan terdekat, penguncian toko/order sebelum menyimpan. Rute gagal, pin hilang atau jalan tidak terhubung tidak menghasilkan antrean palsu. Routing demo memakai batas global satu request/detik yang sama dengan checkout dan ditolak di luar local/testing.
- `app/Http/Controllers/Rintis/Admin/DeliveryController.php`, `DeliveryRequest.php`, route deliveries: otorisasi owner/access Rintis, antrean, bulk preparation, start/clear/complete, pembayaran cash dan penyelesaian berulang. Semua mutasi memakai CSRF dan transaksi sesuai kebutuhan; halaman privat no-store/no-referrer. Lokasi pembeli tidak diberikan ke seller toko lain atau receipt pembeli lain. Props seller membuang private_costing dan token akses pesanan.
- `resources/js/Pages/Rintis/Admin/Deliveries.vue`, `Components/Rintis/DeliveryMap.vue`: tabel pemilihan, urutan tujuan bernomor, dialog konfirmasi, navigasi, cash RupiahInput, fallback ketika tiles gagal.
- Layout Rintis dan shared navigation: menu Deliveries. Halaman admin OpenOrders/Orders: akses antrean/navigasi dan menghilangkan tombol transisi completed yang sebelumnya tidak didukung endpoint status umum. Receipt pembeli kini memakai teks Delivered untuk delivery yang completed, bukan Collected.

Cara mencoba:
1. Refresh aplikasi, buka seller **Deliveries** atau **Open orders → Orders → Delivery queue**.
2. Untuk menyiapkan bersama, pilih beberapa delivery dengan status sama lalu Prepare together / Mark all ready. Transfer harus paid; cash boleh disiapkan sebelum dibayar.
3. Pilih delivery Ready/Out for delivery, klik Plan delivery route, periksa nomor dan alamat, lalu Start delivery trip.
4. Klik Navigate pada tujuan pertama. Setelah barang diserahkan, Mark delivered; isi cash jika perlu. Antrean berikutnya otomatis menjadi Next stop. Status pelanggan diperbarui lewat polling receipt yang sudah ada.

Pemeriksaan: **235 tes Laravel lulus, 2.250 assertions**, termasuk enam tes fitur pengantaran untuk urutan jalan dari titik sebelumnya/persistensi, persiapan atomik dan transfer unpaid, dispatch/penyelesaian berurutan/cash/idempotensi, akses guest/toko lain, rute/pin gagal, dan satu perjalanan/clear plan. Pint selesai, build Vite berhasil, route terdaftar, migration berhasil diterapkan ke PostgreSQL lokal. Pengujian routing memakai HTTP fake dan belum mengirim pin pembeli nyata ke OSRM dalam pengerjaan ini. Interaksi Leaflet/Google Maps dan perjalanan dengan 10 pelanggan nyata belum diuji langsung.

Keterbatasan: belum ada optimasi global, pengurutan manual, banyak kurir/perjalanan serentak, pelacakan GPS kurir, estimasi traffic, auto-detection kedatangan atau histori perjalanan khusus di UI. Pin tujuan dan urutan persisten; peta tidak menggambar garis jalan. Waktu/jarak adalah estimasi perencanaan dari toko, bukan jarak aktual yang terus dihitung dari posisi pengantar. OSRM demo tetap hanya pengujian ringan lokal; produksi memerlukan layanan routing yang sesuai.

Sumber resmi: https://project-osrm.org/docs/v5.24.0/api/ — layanan Table untuk matriks jarak jalan; layanan Trip juga bersifat pendekatan dan tidak menjamin rute tercepat. Implementasi ini memakai Table + nearest-neighbor sendiri, bukan Trip.

## Tahap 45 — Rapikan posisi tombol daftar pesanan

Tanggal: 1 November 2026.

Mengubah header `resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue`: judul/keterangan di kiri dan Refresh orders + Delivery queue dalam satu kelompok di kanan pada desktop. Sebelumnya ketiga elemen menjadi anak langsung justify-between sehingga tombol tersebar. Pada layar kecil, kelompok tombol turun di bawah judul; tinggi, padding, jarak dan perataan tombol disamakan. Tidak mengubah proses pesanan/pengantaran.

Cara mencoba: refresh halaman Orders suatu sesi; dua tombol berdampingan dan tidak lagi terpisah di tengah/ujung halaman. Build Vite berhasil. Tidak menjalankan ulang tes backend untuk perubahan layout saja; belum memeriksa tampilan langsung di browser.

## Tahap 46 — Navigasi seller lebih rapi dan responsif

Tanggal: 1 November 2026.

Mengubah `resources/js/Layouts/Rintis/RintisLayout.vue`: menu utama My store, Catalog, Open orders, Settings memakai padding/tinggi konsisten dan penanda halaman aktif berdasarkan komponen Inertia, termasuk halaman form/detail. Deliveries dan Scan pickup dikelompokkan dalam dropdown **Delivery & pickup** agar header tidak dipenuhi tautan setara. Menu akun tetap memuat fungsi yang sudah ada.

Di bawah breakpoint xl, navigasi memakai tombol Menu/Close dan panel vertikal; dropdown pengantaran tampil di dalam alur panel. Menu desktop tetap mendatar. Klik di luar, Escape dan perpindahan halaman menutup menu; aria-expanded/controls serta aria-current membantu navigasi aksesibel. Identitas merek dan label workspace dipisahkan dengan garis kecil.

Cara mencoba: refresh halaman seller, cek penanda aktif dan buka Delivery & pickup; perkecil layar untuk tombol Menu. Tidak mengubah route, hak akses atau proses transaksi. Build Vite berhasil. Tidak menambah tes untuk perubahan layout ini; pengujian visual/interaksi langsung di browser belum dilakukan.

## Tahap 47 — Pemulihan otomatis VA dan diagnosis kegagalan aman

Tanggal: 2 November 2026.

Pengguna kembali mendapat receipt tanpa VA. GET intent gateway terbaru membuktikan transaksi belum ada (provider 404). Percobaan ulang charge dengan ID, bank dan nominal server yang sama berhasil (provider 201, VA tersedia), lalu sync berhasil menyimpan VA lokal; status tetap unpaid. Tidak membuat order baru, tidak menggandakan pengurangan kuota dan tidak melakukan simulasi pembayaran. Penyebab awal belum dapat dipastikan karena implementasi sebelumnya menelan exception/respons gagal tanpa diagnosis. Keberhasilan retry bukan bukti bahwa semua kegagalan awal disebabkan jaringan atau Midtrans.

Perubahan:
- `app/Services/Rintis/MidtransPayment.php`: menormalisasi status_code numerik/string, memeriksa kode respons provider meskipun HTTP 200, menyimpan last_error berisi kategori tetap, kode tiga digit dan waktu saja. Kategori membedakan koneksi, credentials/permission, provider, respons tidak cocok dan internal. Tidak menyimpan pesan mentah provider, exception, request, header/key atau data pembeli pada error. Error dibersihkan setelah sinkronisasi berhasil dan tidak menurunkan paid. Respons 404 pada polling baca tidak menghapus diagnosis pembuatan sebelumnya.
- `resources/js/Pages/Rintis/Storefront/Receipt.vue`: saat VA belum tersedia, otomatis POST retry setelah 3, 10 dan 25 detik (maksimal tiga percobaan per pemasangan halaman), selalu intent yang sama. Tidak membuat charge melalui GET. Timer dihentikan saat VA tersedia/komponen dilepas; polling tidak dimulai ketika retry aktif. Setelah batas tercapai, tombol manual tersedia. Credentials/respons tidak cocok tidak dipaksa retry otomatis; pembeli mendapat informasi bahwa pengaturan memerlukan perhatian toko. Pesanan tetap tersimpan.
- `tests/Feature/Rintis/MidtransPaymentTest.php`: fixture respons memakai status_code seperti kontrak API; menambah kasus provider error HTTP 200, numeric 404, pemulihan error dan kegagalan koneksi tanpa data sensitif.

Cara mencoba: refresh receipt satu kali untuk memuat perubahan. VA pesanan terbaru sudah dipulihkan. Pada kegagalan berikutnya, biarkan halaman terbuka untuk retry otomatis; jika masih gagal, laporkan pesanan agar kategori error dapat diperiksa tanpa mengirim key. Pembeli tetap tidak perlu tombol konfirmasi sudah bayar.

Pemeriksaan: sembilan tes Midtrans lulus (74 assertions), build Vite berhasil. Sinkronisasi jaringan nyata terakhir berhasil menyimpan VA tanpa paid. Suite Laravel lengkap dijalankan setelah perubahan; hasil dicatat pada baris berikut. Interaksi timer/retry browser langsung belum diuji. Otomatisasi browser memerlukan halaman tetap terbuka; ini bukan background worker dan tidak menggantikan webhook HTTPS untuk notifikasi pembayaran.

Hasil suite lengkap Tahap 47: **237 tes Laravel lulus, 2.264 assertions**.

## Tahap 48 — Ringkas informasi pembayaran pembeli

Tanggal: 2 November 2026.

Sesuai permintaan pengguna, menghapus label Sandbox, penjelasan uang simulasi dan instruksi simulator dari `resources/js/Pages/Rintis/Storefront/Receipt.vue` serta `Checkout.vue`. Kartu VA cukup menampilkan bank, nomor/tombol salin, nominal transfer dan batas waktu. Checkout mempertahankan petunjuk singkat bahwa VA tersedia setelah checkout. Menghapus kalimat teknis tambahan yang tidak membantu pembeli menyelesaikan pembayaran.

Ini perubahan teks UI saja; konfigurasi gateway tetap Sandbox dan tidak mengaktifkan pembayaran produksi. Build Vite berhasil. Cara mencoba: refresh checkout/receipt unpaid; tidak ada lagi label Sandbox atau penjelasan simulasi. Tidak menjalankan ulang tes backend karena logika pembayaran tidak diubah; tampilan browser langsung belum diuji.

## Tahap 49 — Hapus data percobaan pesanan dan sesi PO

Tanggal: 2 November 2026.

Atas instruksi eksplisit pemilik, menghapus seluruh data pesanan dan sesi PO Rintis dari database aplikasi lokal dalam satu transaksi: 4 pesanan, 4 sesi open order, 4 item menu sesi dan 1 antrean pengantaran. Mengunci baris toko sebelum operasi dan menghapus sesuai urutan dependensi; tidak menjalankan migrate:fresh atau mereset ID.

Verifikasi langsung: rintis_orders, rintis_open_orders, rintis_open_order_items dan rintis_delivery_runs masing-masing tersisa 0 baris. Jumlah toko, katalog produk dan akun sebelum/sesudah sama. Tidak mengubah pengaturan toko, kredensial, katalog atau struktur database. Tidak menghapus/membatalkan riwayat transaksi di dashboard Midtrans Sandbox. Checkout guards tetap merupakan baris penguncian identitas, bukan pesanan aktif; tidak menghalangi checkout setelah tabel orders kosong.

Cara mencoba: refresh Open orders / Deliveries / My orders; data yang dihapus tidak lagi muncul, dan seller dapat membuat sesi PO baru. Tautan privat pesanan lama akan 404 karena pesanan memang sudah dihapus. Tidak menjalankan ulang suite karena tidak mengubah kode; pemeriksaan memakai jumlah baris database nyata.

## Tahap 50 — Akar kegagalan VA: key lama pada server web; refresh manual nyata

Tanggal: 2 November 2026.

Pengguna melaporkan VA selalu gagal dan Refresh status terasa tidak bekerja. Pemeriksaan terbaru membuktikan error provider non-JSON HTTP 401 melalui server web. Panggilan yang sama dari terminal berhasil. Perbandingan aman environment proses PHP built-in server dengan konfigurasi CLI menghasilkan Server Key inherited=true, matches_current_config=false, sedangkan Merchant ID cocok. Nilai key tidak dicetak/disimpan. Akar masalah kali ini terkonfirmasi: proses server web masih mewarisi Server Key lama. Retry sebelumnya tidak dapat menyelesaikan penolakan kredensial yang sama.

Menghentikan hanya proses php artisan serve dan child PHP server proyek ini, lalu menjalankannya kembali di 127.0.0.1:8000 dengan MIDTRANS_* lama dihapus dari environment induk agar .env terbaru digunakan. Server pengganti berjalan sebagai sesi proses lokal, memakai --no-reload; Vite/queue/multiplexer lain tidak dihentikan. Perubahan kredensial selanjutnya memerlukan restart proses development dari environment terbaru, bukan hanya refresh browser. Tidak mengubah nilai key, menghapus data, atau menandai pesanan paid.

Pemeriksaan melalui HTTP ke server web (bukan hanya memanggil service via CLI) setelah restart: receipt HTTP 200, nomor VA tersedia, last_error kosong; partial reload Inertia dengan versi aset yang benar juga HTTP 200 dan VA tersedia. Selama diagnosis, charge Sandbox intent terbaru berhasil memakai ID dan nominal server yang sama, tanpa membuat order aplikasi baru atau menggandakan kuota. Status tetap unpaid.

Perubahan kode:
- MidtransPayment mencatat http_status aman dan mengenali HTTP 401/403 sebagai credentials walaupun body bukan JSON, sehingga tidak terus menganggapnya gangguan sementara. RequestException juga menyertakan kode HTTP tanpa pesan/body sensitif.
- Receipt.vue: Refresh status kini tombol jelas dengan Checking…/disabled saat permintaan aktif dan pesan Status updated. Memakai ref reaktif untuk loading; menunggu retry yang sedang berjalan agar tidak bertabrakan.
- Menambahkan POST privat `/rintis/orders/{token}/status` (CSRF + throttle 12/menit), MidtransController::refresh dan URL props pada receipt. Klik manual memaksa GET status ke provider segera, melewati cache 15 detik, lalu kembali ke receipt. Tidak membuat charge melalui tombol refresh. Polling/focus otomatis tetap memakai reload biasa agar tidak membanjiri provider.
- Tes baru mencakup HTTP 401 non-JSON tanpa charge, dan manual refresh yang langsung mengonfirmasi settlement tanpa menunggu interval/cache atau membuat charge kedua.

Pemeriksaan: 11 tes fitur Midtrans lulus, 87 assertions; Pint dan build Vite berhasil. Suite lengkap dijalankan, hasil berikut dicatat setelah selesai. Interaksi klik browser langsung belum diuji, tetapi HTTP receipt/partial refresh server nyata telah diuji. Cara mencoba: refresh browser sekali untuk memuat aset terbaru; VA pesanan terbaru sudah tersedia. Tombol Refresh status kini menampilkan proses dan hasil pemeriksaan. Tidak mengubah konfigurasi sandbox/produksi atau memulai pembayaran nyata.

Hasil suite lengkap Tahap 50: **239 tes Laravel lulus, 2.277 assertions**.

## Tahap 51 — Countdown pembayaran, produk terkunci dan persiapan rombongan

Tanggal: 2 November 2026.

Permintaan pengguna: alternatif VA untuk transaksi kecil; expiry bergerak; produk sesi berjalan tidak dapat diubah; persiapan rombongan sesuai pilihan seller; navigasi pengantaran baru saat siap.

Hasil:
- Receipt menggunakan countdown HH:MM:SS yang diperbarui setiap detik. `resources/js/Support/paymentExpiry.js` membaca tanggal provider tanpa offset sebagai WIB dan menghormati offset eksplisit. Waktu dikoreksi dengan serverNow dari CheckoutController dan diperbarui bersama polling. Countdown berhenti di 00:00:00; VA tidak lagi ditampilkan untuk disalin saat waktu lokal habis. Perubahan payment_status tetap berdasarkan Midtrans, tidak menganggap timer sebagai bukti provider expire/paid, tidak otomatis membatalkan order atau melepas kuota.
- CatalogProduct::sellingItems membatasi item sesi published dengan closes_at masih di masa depan, termasuk sesi scheduled. Catalog menampilkan Locked during open order dan tidak menawarkan Edit/Delete. GET edit diarahkan ke katalog dengan pesan. PATCH dan DELETE tetap memeriksa aturan di server dalam transaksi dengan lock toko/produk yang konsisten dengan proses publikasi PO, sehingga formulir lama/POST langsung tidak melewati kunci. Setelah sesi stopped/closed, cancelled atau berakhir waktunya, produk dapat diedit kembali. Snapshot harga/opsi pesanan dan sesi lama tetap tidak berubah.
- Tabel Session orders sekarang memiliki checkbox untuk pesanan Received/Preparing yang boleh diproses. Seller memilih maksimal 20 (sesuai halaman) lalu Prepare selected / Mark selected ready. Berlaku untuk pickup dan delivery, cash maupun transfer paid. Endpoint bulk memakai Form Request, scope toko/sesi dan update transaksi atomik melalui UpdateOrderStatus. Unpaid transfer, status tidak sama, ID sesi/toko lain ditolak tanpa pembaruan parsial.
- Delivery queue di header Orders hanya tersedia ketika sesi mempunyai delivery Ready/Out for delivery, diperbarui melalui polling juga. Navigate per baris hanya tersedia pada status tersebut, baik tabel Orders maupun halaman Deliveries. Rute perencanaan di backend sebelumnya juga tetap mensyaratkan Ready/Out for delivery. Menu workspace Delivery & pickup tetap merupakan akses umum alat seller, bukan tombol dispatch pesanan Received.

File utama: CatalogProduct.php, Admin/CatalogController.php dan Catalog.vue; Admin/OpenOrderController.php, BulkOrderStatusRequest.php, routes/rintis.php dan OpenOrders/Orders.vue; Storefront/Receipt.vue, CheckoutController.php, Support/paymentExpiry.js; Admin/Deliveries.vue untuk pembatasan tombol. Tidak mengubah skema/data transaksi nyata atau metode pembayaran yang diaktifkan.

Cara mencoba: refresh aplikasi. Receipt unpaid menampilkan Pay within dengan detik bergerak. Buka Catalog saat PO berjalan: produk menu sesi menunjukkan locked. Buka Orders, centang pesanan yang sama-sama Received, Prepare selected, kemudian centang yang Preparing dan Mark selected ready. Setelah ada delivery Ready, tombol antrean/navigasi muncul.

Pemeriksaan: **243 tes Laravel lulus, 2.337 assertions**. Tes baru memeriksa edit/hapus terkunci termasuk scheduled, pembukaan kunci setelah closed/ended, snapshot historis, bulk pickup+delivery, unpaid transfer/foreign session atomik dan visibilitas URL antrean. Dua tes JS baru memeriksa zona WIB/offset dan hitungan detik/batas nol. Pint dan build Vite berhasil. Suite JS lengkap dijalankan; hasil dicatat di bawah. Browser interaktif/countdown langsung belum diuji.

### Alternatif biaya pembayaran yang diteliti

Biaya VA pada halaman harga Midtrans adalah Rp4.000 per transaksi, bukan per cup; screenshot pengguna menunjukkan potongan Rp4.440. Tarif pajak/ketentuan akun aktual harus mengikuti invoice/penyedia, tidak diasumsikan dihitung dari UI aplikasi. Untuk 1 cup Rp20.000 dengan HPP Rp5.000 dan potongan Rp4.440, sisa Rp10.560 sebelum biaya lain, bukan laba bersih.

Rekomendasi untuk transaksi kecil adalah QRIS (idealnya dinamis jika ingin pencocokan order otomatis), dengan cash sebagai pilihan. Halaman harga Midtrans saat diperiksa mencantumkan QRIS 0,7%, yaitu Rp140 dari Rp20.000 sebelum ketentuan/biaya lain. Kebijakan BI terbaru efektif 1 Oktober 2026 mencantumkan UMI sampai Rp500.000 MDR 0%, UMI di atasnya 0,3%; UKE/UME/UBE sampai Rp100.000 MDR 0%, di atasnya 0,7%. Karena harga publik gateway dan ketentuan MDR tidak boleh dianggap identik tanpa verifikasi kategori/kontrak akun, tarif yang benar-benar berlaku dan biaya layanan perlu dikonfirmasi ke Midtrans/PJP. Tidak menjanjikan semua akun gateway otomatis gratis. QRIS statis milik merchant tidak otomatis memberi webhook per pesanan. Belum mengintegrasikan/mengaktifkan QRIS atau mengubah transaksi VA yang sudah ada karena pengguna baru meminta alternatif.

Sumber resmi diperiksa 2 November 2026:
- https://midtrans.com/pricing — VA Rp4.000/transaksi, harga publik QRIS 0,7%.
- https://www.bi.go.id/id/publikasi/ruang-media/cerita-bi/Pages/mdr-qris.aspx — tabel MDR efektif 1 Oktober 2026; tarif berdasarkan kategori/nilai transaksi. Informasi ini memperbarui generalisasi tarif reguler pada catatan tahap sebelumnya.

Hasil suite JavaScript lengkap Tahap 51: **23 tes lulus**.

## Tahap 52 — Select all untuk persiapan pesanan lintas halaman

Tanggal: 2 November 2026.

Menambahkan checkbox Select all pada header tabel Orders dan tombol Select all (jumlah) di atasnya. Memilih semua pesanan yang memenuhi syarat dalam sesi, termasuk di luar halaman tabel yang sedang tampil; bukan hanya 20 baris terlihat. Klik ulang membatalkan seluruh pilihan. Checkbox mendukung keadaan sebagian dipilih, dan pilihan dipertahankan saat berpindah halaman tabel.

OpenOrderController mengirim metadata id/status semua pesanan Received/Preparing yang cash atau transfer paid melalui selectableOrders. Tidak mengirim data pembeli tambahan untuk pilihan lintas halaman. Polling memperbarui metadata dan membuang pilihan yang tidak lagi valid. Bila pilihan mencakup Received dan Preparing, masing-masing tombol menampilkan jumlah kelompoknya dan hanya mengirim ID untuk tahap tersebut; dialog mengonfirmasi jumlah yang benar. Tidak mengirim semua status campur lalu gagal. Transfer unpaid dan pesanan tahap lain tidak ikut Select all.

Batas request BulkOrderStatusRequest dinaikkan dari 20 menjadi 1.000 ID per aksi. Scope toko/sesi, validasi status/payment, transaksi atomik dan pemeriksaan server tetap berlaku. Tidak mengubah batas 10 tujuan per perjalanan pengantaran; Select all ini untuk persiapan/ready pesanan, bukan otomatis menggabungkan 100 alamat dalam satu perjalanan.

File: Admin/OpenOrderController.php, BulkOrderStatusRequest.php, OpenOrders/Orders.vue, tests/Feature/Rintis/DeliveryTest.php. Cara mencoba: buka Orders, klik Select all atau checkbox header, lalu Prepare selected / Mark selected ready. Angka tombol menunjukkan jumlah pesanan yang akan diproses lintas halaman.

Pemeriksaan: menambah pengujian 100 pesanan eligible + 1 transfer unpaid. Respons tabel tetap 20 baris, metadata selectable berisi 100, aksi bulk berhasil memindahkan semua 100 ke Preparing dan unpaid tetap Received. Tes fitur pengantaran dijalankan dan build Vite berhasil. Tidak membuat pesanan uji pada database pengguna; data tes terisolasi. Browser interaktif belum diuji langsung.

## Tahap 53 — Select all cukup melalui checkbox tabel

Tanggal: 2 November 2026.

Sesuai revisi pengguna, menghapus tombol Select all dan keterangan tambahannya di atas tabel Session orders pada `resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue`. Checkbox header tetap memilih/membatalkan seluruh pesanan eligible lintas halaman dan mempertahankan indikator sebagian dipilih. Tombol proses rombongan tetap muncul setelah ada pilihan.

Cara mencoba: refresh Orders, centang checkbox paling atas untuk memilih semua. Build Vite berhasil. Tidak mengubah logika/backend; tidak menjalankan ulang tes untuk penghapusan elemen UI ini.

## Tahap 54 — Centang Ready for delivery dan navigasi khusus Deliveries

Tanggal: 2 November 2026.

Sesuai permintaan, checkbox Session orders sekarang juga aktif untuk pesanan delivery Ready yang cash atau transfer paid. Checkbox header Select all ikut memilih kelompok tersebut lintas halaman. Menambahkan Dispatch selected dengan jumlah kelompok Ready; ketika pilihan berisi beberapa tahap, setiap aksi tetap mengirim hanya kelompok tahapnya. Pesanan pickup Ready tidak ditawarkan untuk dispatch, dan backend menolak pengiriman pickup atau status yang tidak sesuai secara atomik.

Menghapus tombol Navigate dari tabel Session orders sepenuhnya. Navigasi lokasi tetap tersedia di halaman Deliveries. Tombol Delivery queue tetap menjadi akses menuju pengelolaan antrean ketika ada delivery siap/berjalan.

File: Admin/OpenOrderController.php, BulkOrderStatusRequest.php, OpenOrders/Orders.vue, DeliveryTest.php. Cara mencoba: refresh Orders, centang Ready for delivery atau checkbox header, lalu Dispatch selected; buka Delivery queue/Deliveries untuk navigasi tujuan.

Pemeriksaan: tes fitur pengantaran dijalankan, termasuk kasus baru pilihan Ready+dispatch rombongan dan penolakan pickup tanpa perubahan parsial. Pint dan build Vite berhasil. Tidak mengubah status pesanan nyata selama pengerjaan; interaksi browser langsung belum diuji.

## Tahap 55 — Checkbox pilih bersama pada Deliveries

Tanggal: 2 November 2026.

Menambahkan checkbox header pada tabel halaman `resources/js/Pages/Rintis/Admin/Deliveries.vue`, tanpa tombol Select all tambahan. Sekali centang memilih kelompok pesanan eligible yang sama tahapnya pada halaman tersebut, maksimal 10 sesuai batas perjalanan; Ready dan Out for delivery dapat dikelompokkan bersama untuk perencanaan. Klik ulang membatalkan pilihan. Checkbox baris tetap dapat dipakai untuk menyesuaikan pilihan.

Pesanan transfer unpaid, completed atau yang sudah masuk perjalanan planned/active tidak ikut dipilih. Polling membersihkan pilihan yang tidak lagi valid. Batas 10 perjalanan/permintaan pengantaran yang sudah ada tidak dinaikkan; untuk lebih banyak pesanan, seller membuat kelompok perjalanan berikutnya setelah perjalanan aktif selesai.

Cara mencoba: refresh Deliveries, centang checkbox paling atas untuk memilih beberapa pesanan sekaligus, lalu Plan delivery route. Build Vite berhasil. Tidak mengubah backend/database dan tidak menjalankan ulang tes backend untuk perubahan UI ini; interaksi browser langsung belum diuji.

## Tahap 56 — Kelompok pekerjaan pesanan dan notifikasi seller

Tanggal: 2 November 2026.

Halaman Session orders sekarang membuka kelompok To process secara default: pesanan Received yang sudah paid atau memakai cash. Pesanan transfer unpaid dipisahkan ke Awaiting payment. Kelompok Preparing & pickup, In Deliveries, Completed dan All orders tersedia dengan jumlah masing-masing. Antrean pekerjaan diurutkan dari pesanan paling lama agar tidak terlewat; All orders menampilkan yang terbaru dahulu. Klik notifikasi membuka pesanan terkait secara khusus, dengan pilihan kembali melihat kelompok lengkap.

Pesanan delivery Ready/Out for delivery tetap tercatat di Orders dan otomatis tersedia di Deliveries berdasarkan statusnya. Baris yang sudah Out for delivery tidak lagi menampilkan checkbox persiapan yang nonaktif; ada Open in Deliveries dan keterangan tahap pengantaran. Pesanan yang sudah masuk perjalanan pada halaman Deliveries diberi label In current trip, karena sudah dikelola dalam perjalanan tersebut. Pilihan Ready untuk dispatch rombongan tetap tersedia. Navigasi peta tetap berada di Deliveries.

Menambahkan notifikasi database untuk pesanan baru dan pembayaran transfer yang berubah menjadi paid. Lonceng pada seller workspace menampilkan jumlah belum dibaca, daftar terbaru, tandai dibaca dan tautan ke pesanan. Pemeriksaan berjalan setiap 15 detik saat halaman terlihat; aktivitas baru setelah pemuatan awal memunculkan pemberitahuan kecil. Notifikasi tersimpan walaupun halaman ditutup, tetapi belum ada push saat browser tertutup, suara, email atau WhatsApp. Tidak membuat notifikasi ulang untuk pesanan lama. Perubahan paid berulang tidak menggandakan notifikasi pembayaran.

Akses feed dan penandaan dibaca dibatasi ke pemilik toko dan entitlement Rintis. Payload notifikasi hanya identitas internal pesanan/toko, referensi, jenis dan nominal; tidak menyimpan alamat, nomor telepon, token receipt atau HPP. Notifikasi ikut transaksi database sehingga rollback pesanan tidak meninggalkan pemberitahuan palsu. Migration notifications sudah diterapkan tanpa menghapus data.

File utama: OpenOrderController.php, NotificationController.php, app/Notifications/Rintis/OrderActivity.php, Order.php, HandleInertiaRequests.php, routes/rintis.php, SellerNotifications.vue, RintisLayout.vue, OpenOrders/Orders.vue dan Deliveries.vue.

Cara mencoba: refresh seller workspace, buka Orders untuk melihat tab pekerjaan. Buka In Deliveries lalu Open in Deliveries untuk pengantaran. Pesanan baru berikutnya atau pembayaran transfer yang terkonfirmasi akan masuk lonceng; klik pemberitahuan untuk membuka pesanan terkait.

Pemeriksaan: **249 tes Laravel lulus, 2.443 assertions**, termasuk isolasi notifikasi antar seller, mark read, rollback, pembayaran berulang dan pemisahan kelompok pesanan. Pint dan build Vite berhasil. Tidak mengubah status pesanan nyata atau menghapus data. Tampilan/interaksi browser belum diuji langsung.

## Tahap 57 — Pengantaran fokus pada perjalanan dan rating menu setelah selesai

Tanggal: 2 November 2026.

Menghapus tombol Refresh queue. Pembaruan otomatis tetap berjalan setiap 15 detik. Saat ada rencana/perjalanan pengantaran, tabel Orders to prepare & deliver di bawah peta disembunyikan agar informasi tujuan tidak berulang. Tabel pemilihan tetap tersedia ketika belum ada perjalanan untuk memilih tujuan perjalanan berikutnya.

Setelah seller menekan Mark delivered dan mengonfirmasi penyerahan, status Completed tersimpan melalui alur pengantaran yang sudah ada. Halaman receipt pelanggan yang terbuka memperoleh status terbaru melalui polling 15 detik atau saat kembali fokus. Form rating muncul di dekat bagian atas receipt setelah Completed dan Paid, termasuk untuk pickup yang sudah diserahkan. Tidak memaksa pelanggan memberi rating atau memindahkan halaman secara mendadak.

Rating berupa 1–5 bintang per baris menu yang dibeli, beserta label varian pada form. Satu baris pesanan hanya dapat dinilai sekali; beberapa cup pada baris yang sama tidak menggandakan rating. Pengiriman ulang tidak mengganti/menggandakan nilai. Rating opsional dan tidak memerlukan akun; menggunakan tautan receipt privat yang sama. Orang yang memegang tautan tersebut dapat mengakses/memberi rating seperti akses detail pesanan yang sudah berlaku. Belum menambahkan komentar teks, foto review, perubahan rating atau moderasi.

Server memeriksa token, status Completed/Paid dan indeks menu dalam snapshot pesanan. ID produk berasal dari menu sesi yang dimiliki pesanan/toko, bukan input pembeli. Transaksi, row lock dan unique order_id+line_index mencegah rating ganda. Katalog Our Products menampilkan rata-rata bintang dan jumlah rating hanya bila sudah ada; tidak membocorkan identitas pembeli atau detail pesanan. Rating belum ditambahkan pada kartu menu PO yang memakai snapshot sesi.

File penting: Deliveries.vue (menghapus kontrol/duplikasi), Receipt.vue dan ProductRating.vue (form rating), ReviewController.php dan ReviewRequest.php (validasi/penyimpanan), ProductReview.php dan migration rintis_product_reviews (data rating), CatalogProduct.php dan routes/rintis.php (relasi/agregat publik), Storefront/Home.vue (bintang katalog), CheckoutController.php (data review privat).

Cara mencoba: refresh halaman seller, buat/mulai perjalanan; tabel pilihan tidak muncul di bawah peta. Buka receipt pelanggan pada tab lain, selesaikan penyerahan dari seller, tunggu maksimal sekitar 15 detik saat halaman terbuka. Pilih bintang per menu lalu Save rating; buka Our Products untuk melihat rata-rata. Tidak perlu membuat ulang order atau akun.

Pemeriksaan: **252 tes Laravel lulus, 2.501 assertions**; mencakup rating tamu, per menu/varian, idempotensi, penolakan token/menu asing, nilai tidak valid, status belum selesai/unpaid, agregat publik dan respons polling Completed. Pint dan build Vite berhasil. Migration diterapkan pada PostgreSQL lokal tanpa penghapusan data. Tidak mengubah status pesanan pengguna untuk demonstrasi; browser interaktif belum diuji langsung.

## Tahap 58 — Perbaikan PostgreSQL, rating sekaligus, batas sesi dan QRIS

Tanggal: 2 November 2026.

Tujuan: memperbaiki error saat seller membuka/menyelesaikan pengantaran, menyederhanakan receipt, membatasi PO bertumpuk dan menyediakan pembayaran lebih sesuai untuk transaksi kecil.

Error pada screenshot sebenarnya berasal dari GET /rintis/admin/notifications, bukan kegagalan penyerahan pada endpoint delivery. Kolom data pada tabel notifications masih text dari migration standar; PostgreSQL tidak mendukung operator JSON ->> pada text. Menambahkan migration baru yang mengubah text menjadi jsonb dengan USING data::jsonb untuk PostgreSQL, mempertahankan isinya. SQLite tetap memakai perilaku JSON pada text. Migration telah diterapkan. Memanggil NotificationController langsung memakai pengguna pemilik toko pada database PostgreSQL lokal menghasilkan HTTP 200 dan bentuk feed valid, tanpa mencetak isi notifikasi/identitas pelanggan. Tidak mengubah status pesanan nyata untuk pengecekan.

Rating sekarang satu formulir untuk seluruh baris menu, satu tombol Save ratings. Setiap menu/varian tetap punya bintang masing-masing, dapat melewati menu yang tidak ingin dinilai. Backend menerima batch maksimal 50, memeriksa seluruh nilai dan menyimpan secara atomik. Indeks menu invalid menggagalkan seluruh batch; pengiriman ulang tidak menggandakan/mengganti rating lama. Endpoint masih menerima bentuk satu rating untuk kompatibilitas. Setelah Completed, receipt menonjolkan rating; progres, kontak, rincian barang, nominal dan alamat tersembunyi dalam View order details. Modal pembayaran sukses tidak dibuka lagi saat pertama membuka receipt yang sudah Completed. Rata-rata/jumlah rating kini tampil juga pada kartu menu PO dari produk katalog terkait dengan eager loading, tanpa menampilkan identitas penilai. Menghapus banner Choose your favorites. No account needed.

SaveOpenOrder memblokir publikasi berikutnya ketika masih ada sesi published yang belum tutup (termasuk scheduled), atau sesi mana pun masih memiliki pesanan non-Completed. Ini mencakup sesi ended/stopped yang pengantarannya belum selesai. Lock toko mempertahankan pemeriksaan serial. Tombol New session disembunyikan dan diberi penjelasan selama kondisi tersebut; validasi server tetap berlaku bila mengakses URL langsung. Draft belum dipublikasikan tidak memesan slot sesi.

QRIS Midtrans ditambahkan untuk checkout baru Wemeet yang koneksi gateway-nya tersedia, bersama pilihan cash sesuai pengaturan seller. Pilihan VA tidak ditawarkan pada UI checkout baru. VA/transfer manual yang sudah tercatat dan endpoint kompatibilitas tidak dikonversi, dihapus atau dilunaskan. Pengaturan seller berlabel Digital payment (menggunakan field bank_transfer_enabled yang sudah ada). QRIS tidak memerlukan memasukkan rekening seller pada checkout. Provider mengeluarkan QR per transaksi/nominal melalui Core API payment_type=qris, acquirer=gopay. Gambar hanya diterima dari URL HTTPS api.sandbox.midtrans.com dengan pola endpoint QRIS yang diizinkan. Respons/status divalidasi order ID, merchant, nominal, currency dan jenis pembayaran; paid hanya dari settlement server Midtrans. Webhook/polling memakai verifikasi yang sama. Semua pembayaran non-cash termasuk QRIS harus paid sebelum Preparing. Notifikasi pembayaran juga mencakup QRIS. Rincian biaya tidak dipasang di UI pelanggan.

Batas waktu berdasarkan jawaban eksplisit pengguna: pembayaran digital berakhir 15 menit sebelum PO tutup, bukan 15 menit sejak checkout. Misalnya closesAt 18.00, deadline 17.45. Checkout digital baru ditolak pada/setelah batas itu; pilihan QRIS tidak ditawarkan ketika sudah lewat. Intent baru menyimpan batas tersebut dan charge mengirim custom_expiry dengan unit second sesuai sisa waktu; retry sesudah batas tidak membuat charge baru. Expiry provider yang lebih awal tetap dihormati; expiry yang lebih lama tidak memperpanjang timer aplikasi. QRIS provider memiliki batas maksimum tujuh hari, sehingga request yang sangat panjang dibatasi maksimum tersebut. Cash tetap dibayar saat menerima pesanan dan mengikuti waktu tutup pemesanan biasa. VA lama yang sudah terbit tidak diubah tenggatnya. Timer tidak dianggap bukti paid, tidak otomatis menghapus pesanan/merilis kuota/membatalkan order; kebijakan satu pesanan aktif hingga Completed tetap berlaku sesuai keputusan sebelumnya. Penyelesaian order digital expired membutuhkan alur pembatalan/resolusi lanjutan, belum ditetapkan sepihak.

OrderProgress diurutkan Order placed → Awaiting payment/Payment confirmed (digital) → Preparing → Ready for pickup/delivery → On the way (delivery) → Completed. Cash melewati tahap pembayaran awal. Garis penghubung dan indikator tahap aktif diberi gerakan ringan, mendukung prefers-reduced-motion. Saat status Out for delivery, animasi kendaraan menandai sedang diantar. Ini animasi berdasarkan status, bukan live GPS, bukan estimasi posisi/ETA kurir; belum mengirim lokasi kurir atau menampilkan alamat pelanggan lain.

File penting: migration change_notification_data_to_json; ReviewController/ReviewRequest dan ProductRating.vue (batch review); Receipt.vue (rincian terlipat/QR pembayaran); SaveOpenOrder dan OpenOrderController/Index.vue (satu sesi operasional); OpenOrderItem/routes/rintis.php/OpenOrder.vue (rating menu); MidtransPayment/PlaceOrder/CheckoutRequest/CheckoutController/Checkout.vue (QRIS dan cutoff); Order/UpdateOrderStatus/Orders.vue/OrderHistory.vue (verifikasi digital dan label); OrderProgress.vue (alur/animasi).

Cara mencoba: refresh aplikasi, buka notifikasi dan lanjutkan pengantaran. Pada receipt Completed, beri bintang beberapa menu lalu Save ratings sekali; View order details dapat dibuka terpisah. Coba New session ketika masih ada pesanan berjalan: tidak bisa dipublikasikan. Untuk QRIS, gunakan checkout baru sebelum cutoff dan pilih QRIS, lalu simulasi pembayaran QRIS di Midtrans Sandbox. Transaksi VA lama tetap memakai VA lama; tidak perlu menghapusnya. Setelah status seller berubah, receipt yang terbuka memperbarui diri setiap 15 detik.

Pemeriksaan: **257 tes Laravel lulus, 2.560 assertions**. Tes baru memeriksa batch rating/rollback, rating menu publik, blocking sesi ended dengan order belum selesai, QRIS charge/expiry/settlement, penolakan cutoff dan unpaid QRIS tidak bisa disiapkan; response jenis pembayaran yang salah tidak dianggap paid. Pint dan build Vite berhasil. Pemeriksaan PostgreSQL notifikasi HTTP 200 dilakukan langsung pada database pengguna tanpa mutasi pesanan. Integrasi QRIS diuji dengan HTTP fake sesuai kontrak resmi; belum membuat transaksi QRIS langsung pada akun Sandbox pengguna dan belum diuji lewat browser interaktif. Produksi serta banyak merchant masih belum diaktifkan.

Sumber resmi diperiksa 2 November 2026:
- https://docs.midtrans.com/reference/qris — Core API QRIS, tindakan generate QR, status settlement.
- https://docs.midtrans.com/reference/charge-transactions-1 dan https://docs.midtrans.com/reference/custom-expiry-object — custom_expiry untuk QRIS, unit second, batas provider.
- https://midtrans.com/pricing — harga publik QRIS 0,7% dan VA Rp4.000/transaksi; Rp20.000 × 0,7% = Rp140. Tarif aktual tergantung kategori/ketentuan merchant dan pajak yang berlaku; tidak menjanjikan semua merchant identik. Lihat juga catatan kebijakan MDR BI pada Tahap 51.

## Tahap 59 — Countdown QRIS dan pesanan hangus pada batas pembayaran

Tanggal: 2 November 2026.

Klarifikasi pengguna: pembeli boleh langsung membayar sejak checkout; 15 menit sebelum PO tutup adalah batas terakhir pembayaran, bukan waktu mulai bayar atau durasi tetap 15 menit. Contoh closesAt 17.00 WIB → cutoff 16.45 WIB. Checkout 15.00 memberi waktu tersisa 1 jam 45 menit. Khusus QRIS unpaid menjadi hangus/Expired setelah cutoff; cash tidak ikut hangus dan boleh dibuat/dibayar menurut alur cash sampai PO tutup.

Checkout sekarang menampilkan Pay now · Complete before … beserta Time left to pay HH:MM:SS yang bergerak setiap detik. Menggunakan waktu server dan helper countdown yang sama dengan receipt. Di cutoff pilihan QRIS ditutup, tombol submit QRIS nonaktif dan server menolak request baru walaupun halaman lama tetap terbuka. Cash tetap dapat dipilih bila seller mengaktifkan. Pembatasan cutoff checkout yang sebelumnya diterapkan ke seluruh non-cash kini secara khusus untuk QRIS; transaksi transfer/VA lama tidak diubah menjadi expired oleh fitur ini.

Menambah status operasional expired untuk QRIS yang masih Received dan belum Paid ketika cutoff tercapai. Service ExpireQrisOrder memeriksa status provider terlebih dahulu agar konfirmasi pembayaran yang sudah dilakukan tidak hilang karena terlambat datang. Kemudian lock order, periksa ulang paid/status, catat expired_at dan event history sekali saja. Tidak menghapus order, tidak menandai paid sebagai expired dan tidak menyentuh cash. Expired tidak bisa Preparing/dispatch/pickup/review, tidak lagi masuk Awaiting payment atau daftar Deliveries. Seller memiliki tab Expired dan riwayat pembeli tetap mencatatnya. Receipt menampilkan Order expired dan menyembunyikan QR, retry pembayaran serta progres pemenuhan yang tidak berlaku.

Expired dilepas dari penghalang satu checkout aktif dan penghalang pesanan lama saat publikasi sesi berikutnya. Sesi published yang belum tutup tetap menghalangi sesi baru sampai stopped/ended sesuai aturan sebelumnya. Checkout key browser direset secara terotorisasi bila ada order expired pada sesi yang sama, agar percobaan baru tidak mengembalikan order hangus melalui idempotency key lama. Cash baru pada sesi yang masih terbuka dapat menggantikan QRIS expired. Harga/rincian historis dan kuota menu yang tercatat tidak dihapus atau diubah otomatis; pengembalian reservasi kuota belum diterapkan pada tahap ini agar konfirmasi provider yang terlambat tidak menimbulkan pemesanan berlebih.

Retry untuk status expired hanya dapat memeriksa status provider, tidak membuat charge/QR baru. Jika konfirmasi settlement sah baru diterima setelah aplikasi menganggap expired, pembayaran tetap dihormati dan status dikembalikan Received untuk diproses; uang yang benar-benar diterima tidak dianggap hangus. Detail verifikasi nominal/merchant/jenis pembayaran tetap berlaku. Provider custom_expiry dari tahap sebelumnya tetap membatasi QR di cutoff.

Pemeriksaan expiry berjalan saat receipt/riwayat/checkout/halaman seller terkait diakses, serta command rintis:expire-qris yang dijadwalkan setiap menit tanpa overlap. Command dibatasi anggaran kerja sekitar 50 detik per siklus; item berikutnya diteruskan siklus berikutnya. Scheduler lokal telah dijalankan dan schedule:list menunjukkan jadwal setiap menit. Browser menutup QR tepat pada timer nol; pencatatan otomatis server tanpa request dapat berselisih sampai siklus scheduler berikutnya. Proses scheduler perlu tetap berjalan; penerapan scheduler produksi masih merupakan bagian deployment, belum dikerjakan.

File penting: ExpireQrisOrder.php (transisi expired), Console/Commands/Rintis/ExpireQrisOrders.php dan routes/console.php (otomatisasi), ActiveCheckout/SaveOpenOrder/OpenOrderController (penghalang yang mengabaikan terminal expired), MidtransPayment (retry aman/rekonsiliasi), CheckoutController/Checkout.vue (timer dan key baru), Receipt.vue/OrderHistory.vue/Orders.vue/OrderProgress.vue (status), DeliveryController (expired tidak masuk antrean).

Cara mencoba: buat QRIS sebelum cutoff; timer langsung bergerak dan pembayaran bisa dilakukan saat itu juga. Saat timer habis, order unpaid menjadi Expired, QR hilang dan tidak dapat disiapkan. Bila cash aktif dan PO belum tutup, kembali ke menu dan checkout baru dengan cash tetap tersedia. Jangan mengubah waktu/tanggal mesin atau melakukan pembayaran sungguhan untuk demonstrasi; pengujian waktu dilakukan terisolasi.

Pemeriksaan: **262 tes Laravel lulus, 2.635 assertions**. Lima tes baru mencakup detik sebelum/tepat cutoff, command idempoten, paid/cash tidak tersentuh, receipt/history/tab Expired, penghalang checkout/sesi dilepas, verifikasi settlement sebelum expired, retry tidak mengirim POST charge, dan penggantian QRIS expired dengan cash memakai checkout key baru. Pint dan build Vite berhasil. Scheduler lokal aktif dan jadwal diverifikasi; belum ada uji visual browser langsung. Tidak menghapus transaksi atau menjalankan migrate:fresh.

## Tahap 60 — Keterangan nomor WhatsApp aktif pada checkout

Tanggal: 2 November 2026.

Mengubah label Phone number menjadi WhatsApp number pada resources/js/Pages/Rintis/Storefront/Checkout.vue. Menambahkan keterangan: “An active WhatsApp number is required so the seller can contact you about your order.” Nomor tetap wajib diisi. Keterangan dihubungkan ke input menggunakan aria-describedby agar terbaca pembaca layar.

Cara mencoba: refresh checkout dan lihat bagian Contact details. Perubahan ini memperjelas persyaratan kepada pembeli; aplikasi belum memverifikasi keaktifan WhatsApp melalui OTP/API. Tidak mengirim pesan atau menambahkan layanan eksternal. Build Vite berhasil. Tidak menambah/menjalankan tes backend karena hanya perubahan teks dan atribut aksesibilitas.

## Tahap 61 — Ikon jenis notifikasi seller

Tanggal: 2 November 2026.

Menambahkan ikon uang dalam latar hijau untuk Payment received (kind paid) dan ikon tas belanja dalam latar biru untuk New order pada daftar notifikasi. Ikon diletakkan di kiri dengan ukuran tetap; judul, referensi, nominal, waktu dan penanda belum dibaca tetap rapi di kanan. SVG dekoratif menggunakan aria-hidden; jenis notifikasi tetap dijelaskan melalui judul teks sehingga tidak mengandalkan warna saja.

File: resources/js/Components/Rintis/SellerNotifications.vue. Cara mencoba: refresh seller workspace lalu buka lonceng notifikasi. Build Vite berhasil; tidak mengubah backend/data atau menambah tes untuk perubahan tampilan ini. Belum diuji interaktif melalui browser.

## Tahap 62 — Countdown pada halaman pesanan seller

Tanggal: 2 November 2026.

Menambahkan dua ringkasan waktu pada Session orders: Ordering opens/closes in dan QRIS payment time left. Keduanya menampilkan HH:MM:SS bergerak setiap detik serta jam tujuan WIB. Setelah batas masing-masing tercapai, tampil Closed; sesi cancelled diberi label Cancelled. Sisa pembayaran QRIS juga terlihat pada baris pesanan QRIS unpaid, dengan Payment deadline reached saat nol sebelum status Expired diterima dari server. Cash/Paid tidak diberi timer pembayaran.

Timer memakai helper paymentCountdown yang sudah dipakai pelanggan, dengan offset waktu server. Polling 15 detik menyinkronkan ulang waktu dan status sesi; interval satu detik dibersihkan ketika halaman ditutup. Menghentikan pemesanan lewat Stop orders tidak memajukan tenggat pembayaran pesanan yang sudah ada. Tidak menambah request setiap detik atau mengubah aturan expiry/pembayaran.

File: OpenOrderController.php (metadata waktu saja, tanpa fulfillment privat), OpenOrders/Orders.vue (ringkasan dan timer baris), OpenOrderTest.php (kontrak jam PO/cutoff dan tidak mengirim fulfillment).

Cara mencoba: refresh halaman Orders seller. Lihat dua countdown di bawah judul; pada Awaiting payment, QRIS unpaid juga menunjukkan sisa waktunya. Pint, 16 tes fitur terkait dan build Vite berhasil. Belum diuji visual melalui browser langsung.

## Tahap 63 — Details tertutup saat pembayaran

Tanggal: 2 November 2026.

Bagian View order details pada Receipt.vue kini tertutup secara default ketika menunggu pembayaran, expired dan completed. Ringkasan tetap terlihat agar pelanggan bisa membuka detail secara manual. QRIS, nominal pembayaran dan countdown tetap terlihat di luar bagian tersebut. Setelah pembayaran terverifikasi dan order masih diproses, progres mengikuti tampilan proses yang sudah ada.

Cara mencoba: refresh receipt unpaid; bagian Details tertutup dan bisa diklik untuk dibuka. Build Vite berhasil. Perubahan hanya kondisi tampilan; tidak mengubah data atau aturan pembayaran dan tidak menambah tes backend. Belum diuji visual melalui browser langsung.

## Tahap 64 — Menghapus tombol Open in Deliveries pada baris pesanan

Tanggal: 2 November 2026.

Menghapus tombol Open in Deliveries dari kolom Actions di resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue sesuai permintaan pengguna. Akses pengantaran melalui navigasi yang sudah tersedia. Tidak mengubah status, dispatch atau data pesanan.

Cara mencoba: refresh Session orders dan lihat kolom Actions. Build Vite berhasil. Tidak menjalankan ulang tes backend untuk penghapusan tombol tampilan ini; belum diuji interaktif di browser.

## Tahap 65 — Akses Deliveries setelah Dispatch

Tanggal: 2 November 2026.

Sesuai klarifikasi pengguna, tombol Open in Deliveries muncul di sebelah kanan Details hanya untuk pesanan delivery berstatus out_for_delivery, setelah Dispatch order. Tombol tidak tampil pada Ready for delivery atau tahap lainnya. Menggunakan URL pengantaran yang sudah dikirim server; tidak mengubah alur dispatch atau status pesanan.

File: resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue. Cara mencoba: lihat pesanan Ready, lalu Dispatch; tombol muncul setelah status Out for delivery. Build Vite berhasil. Perubahan kondisi tampilan saja, tanpa tes backend tambahan; interaksi browser belum diuji langsung. Ketentuan ini menggantikan penghapusan menyeluruh pada Tahap 64.

## Tahap 66 — Perbaikan 429 saat Plan delivery route

Tanggal: 2 November 2026.

Penyebab ditemukan pada middleware Laravel yang terpasang: limiter numerik throttle:10,1 tanpa prefix memakai signature user yang sama dengan limiter numerik route lain. Akibatnya request notifikasi, pencarian, receipt atau aksi lain dapat memakai penghitung yang sama; batas yang lebih rendah pada Plan delivery route bisa tercapai meski seller tidak membuat rute berkali-kali. Bukan bukti bahwa OSRM menolak rute atau pesanan berubah.

Plan delivery route kini memakai named limiter rintis-delivery-plan, tetap 10 percobaan per menit per seller, terpisah dari fungsi lain. Semua limiter numerik di routes/rintis.php juga diberi prefix deskriptif tersendiri agar tidak saling memakan jatah. Tidak menaikkan/menghapus proteksi layanan. Ketika batas perencanaan benar-benar tercapai, permintaan halaman/Inertia diarahkan kembali ke Deliveries dengan status 303 dan pesan inline orders yang menyebut jumlah detik menunggu. Header Retry-After dipertahankan. Request JSON tetap mendapat respons 429 berbentuk JSON dengan pesan yang sama. Tidak membuat route atau mengubah status pesanan pada penolakan.

Frontend Deliveries mencegah perform dipanggil kembali saat busy, dan polling tidak berjalan selama aksi/form cash sedang dikirim agar tidak mengganggu permintaan perencanaan. Tidak menambah tombol baru.

File: app/Providers/AppServiceProvider.php (named limiter/respons error), routes/rintis.php (isolasi limiter), Deliveries.vue (penjagaan request), DeliveryTest.php (regresi). Cara mencoba: refresh aplikasi lalu pilih pesanan dan Plan delivery route. Request notifikasi rutin tidak lagi menyebabkan batas pembuatan route tercapai. Bila melakukan banyak percobaan dalam satu menit, pesan menunggu tampil di halaman tanpa modal putih.

Pemeriksaan: **264 tes Laravel lulus, 2.696 assertions**. Tes baru membuktikan 12 request notifikasi tetap membolehkan perencanaan pertama, percobaan ke-11 menghasilkan redirect/error inline tanpa request routing atau mutasi delivery run, serta batas seller pertama tidak memblokir seller lain. Pint dan build Vite berhasil. Pengujian menggunakan HTTP fake untuk provider routing; tidak membuat/mengubah perjalanan pengguna untuk demonstrasi dan belum diuji interaktif melalui browser.

### Tahap 67 — Rating per produk dan redirect notifikasi (2 November 2026)

- Tujuan: Americano cukup dinilai sekali dalam satu pesanan, meskipun pembeli memilih beberapa ukuran/level.
- `resources/js/Components/Rintis/ProductRating.vue` menggabungkan baris dengan menu produk yang sama, hanya menampilkan nama dan satu pilihan bintang; Save ratings tetap satu. Rincian varian tetap tersedia pada detail transaksi.
- `app/Http/Controllers/Rintis/ReviewController.php` memeriksa rating produk yang sudah tersimpan dalam pesanan dengan lock transaksi. Pengiriman ulang melalui varian lain tidak menambah rating atau mengganti rating pertama; identitas produk tetap berasal dari menu yang dibeli di server.
- `resources/js/Components/Rintis/SellerNotifications.vue` menandai fetch sebagai XMLHttpRequest. Sebelumnya polling GET dapat mengganti previous URL dalam sesi Laravel sehingga redirect back setelah aksi seller membuka endpoint JSON notifikasi dan menghasilkan modal error Inertia.
- Cara mencoba: selesaikan pesanan dengan Americano dua varian; halaman rating menampilkan satu Americano. Simpan sekali. Di workspace seller, biarkan notifikasi memperbarui lalu lakukan aksi delivery; redirect tetap ke halaman operasional.
- Pemeriksaan: 9 tes ProductReviewTest/SellerNotificationTest lulus (148 assertions), Pint lulus, npm run build lulus. Belum diuji visual pada browser.
- Batasan: rating historis yang sudah tersimpan tidak dihapus/digabung secara destruktif; UI mengenali rating lama dari varian produk yang sama dan mencegah rating baru berulang. Pengelompokan hanya per produk dalam satu pesanan, bukan lintas pembelian.

### Tahap 68 — Pesanan pelanggan langsung dan jadwal mingguan (2 November 2026)

Tujuan: seller bisa membantu pelanggan yang datang ke toko serta mengatur jam buka/tutup sekali untuk Senin–Minggu.

Hasil yang dapat digunakan:
- Open orders → sesi berstatus Open → Create order. Seller memilih beberapa produk, jumlah dan varian; tambahan harga ikut dihitung dan harga/kuota tetap divalidasi server.
- Nama dan WhatsApp opsional khusus pesanan langsung. Jika nama kosong dicatat Walk-in customer. Pesanan tidak terikat akun belanja seller, sehingga seller dapat membantu beberapa pelanggan berturut-turut.
- Cash memerlukan nominal diterima yang cukup, mencatat Paid dan kembalian secara atomik tanpa menggandakan transaksi saat submit ulang. Status pesanan tetap Received, kemudian disiapkan dan diserahkan melalui alur pickup yang ada. QRIS menampilkan halaman pembayaran yang sama dan tetap menunggu verifikasi provider; cutoff 15 menit sebelum PO tutup tetap berlaku. Receipt milik pesanan langsung menyediakan Back to orders untuk seller.
- Open orders → Weekly schedule. Atur aktif/tutup per hari, jam WIB, pilih menu dan aktifkan. Sesi otomatis memakai harga produk saat sesi dibuat serta konfigurasi pickup/delivery toko, dengan snapshot historis tetap terjaga.
- Scheduler memeriksa setiap menit, satu sesi otomatis per toko per tanggal. Tidak membuka ulang sesi yang sengaja dihentikan hari itu. Sesi lain/pesanan belum selesai tetap menghalangi pembukaan; alasan terakhir dapat dilihat di Weekly schedule dan akan dicoba lagi selama masih dalam jam buka.
- Skip automatic opening today mencegah pembukaan otomatis hari itu. Jika sesi sudah terbuka, gunakan Stop orders; tombol skip tidak menghentikan sesi atau mengubah pesanan yang sudah masuk.

File penting:
- `app/Http/Controllers/Rintis/Admin/WalkInController.php`, `app/Http/Requests/Rintis/WalkInRequest.php`: akses seller dan checkout pelanggan langsung melalui PlaceOrder yang sudah ada; actor/source dicatat di riwayat privat.
- `resources/js/Pages/Rintis/Admin/OpenOrders/WalkIn.vue`: pemilihan produk/opsi, pembayaran dan kembalian.
- `app/Models/Rintis/WeeklySchedule.php`, migration `2026_11_02_142709_create_rintis_weekly_schedules_table.php`: penyimpanan satu konfigurasi jadwal per toko. Migration telah diterapkan dan terverifikasi Ran pada PostgreSQL lokal, tanpa menghapus data lama.
- `WeeklyScheduleController.php`, `WeeklyScheduleRequest.php`, `WeeklySchedule.vue`: formulir jadwal, validasi kepemilikan menu dan jam, serta skip hari ini.
- `app/Services/Rintis/OpenWeeklySchedule.php`, `app/Console/Commands/Rintis/RunWeeklySchedules.php`, `routes/console.php`: pembukaan otomatis transaksional, anti-duplikasi dan tugas tiap menit `rintis:open-weekly`.
- `Order.php`: receipt mengirim ID menu sesi yang tidak rahasia agar rating per produk dapat menggabungkan varian dengan benar; HPP tetap privat.

Pemeriksaan: 270 tes seluruh aplikasi lulus (2764 assertions). Sesudah penyesuaian navigasi receipt, 9 tes SellerOrderingTest/ProductReviewTest diulang dan lulus (141 assertions). Pint pada seluruh PHP yang diubah lulus; npm run build lulus. QRIS memakai HTTP fake dalam tes, tidak membuat pembayaran provider nyata. Belum diuji visual/interaksi pada browser.

Batasan tahap ini:
- Pelanggan langsung tetap memerlukan sesi PO terbuka dan pickup aktif. Pesanan saat toko tutup/delivery untuk pelanggan langsung belum ditambahkan.
- Jadwal memakai satu rentang pada hari yang sama, belum melewati tengah malam atau pengecualian jam khusus tanggal tertentu. Menu otomatis tanpa kuota; pengaturan kuota tetap tersedia pada sesi manual.
- Pembukaan otomatis memerlukan scheduler Laravel berjalan; bila scheduler terlambat, sesi bisa dibuka saat scheduler pulih selama masih dalam jam hari itu. Tidak membuat sesi untuk hari yang sudah lewat.
- QRIS tetap mengikuti konfigurasi Sandbox Wemeet saat ini; belum mengaktifkan produksi atau settlement banyak seller.
- Pemulihan rating historis tidak menghapus data lama.

Langkah berikutnya: coba transaksi pelanggan langsung di UI, lalu atur jadwal dan periksa pembukaan pada menit berikutnya; pengecualian tanggal/jam lintas hari dapat ditambahkan sesuai kebutuhan.

### Tahap 69 — Menu berbeda untuk setiap hari (2 November 2026)

- Tujuan: jadwal mingguan tidak menganggap semua hari menjual menu yang sama.
- Weekly schedule kini memiliki pilihan Menu pada setiap hari aktif, diringkas menjadi jumlah produk terpilih agar formulir tetap rapi. Contoh Senin Americano, Selasa Tea, Rabu Closed. Hari aktif wajib memiliki minimal satu produk; hari Closed boleh tanpa menu.
- `WeeklySchedule.vue` menampilkan pemilihan per hari; `WeeklyScheduleRequest.php` memvalidasi menu milik toko sendiri, produk tidak terhapus, dan tidak ada produk ganda dalam hari yang sama. Produk yang sama boleh dijual di beberapa hari.
- `WeeklyScheduleController.php` menyimpan menu di JSON masing-masing hari. `OpenWeeklySchedule.php` hanya mengambil produk untuk hari berjalan ketika membuat sesi. Tidak perlu migration baru.
- Konfigurasi lama tetap terbaca memakai menu bersama sebagai nilai awal untuk masing-masing hari; setelah disimpan memakai menu per hari. Sesi yang sudah terbit dan harga historisnya tidak diubah oleh edit jadwal.
- Cara mencoba: Open orders → Weekly schedule → buka Menu pada hari yang diinginkan, centang produk, simpan. Nonaktifkan hari untuk Closed; untuk libur sesekali gunakan Skip automatic opening today yang sudah tersedia.
- Pemeriksaan: 6 tes SellerOrderingTest lulus (77 assertions), termasuk menu Senin berbeda dengan Selasa, Rabu tutup, validasi menu kosong/duplikat, kompatibilitas konfigurasi lama, serta pembatasan toko. Pint dan npm run build lulus. Belum diuji visual pada browser.
- Batasan: ini menu berulang mingguan; pengecualian menu khusus tanggal tertentu belum disediakan. Perubahan berlaku untuk sesi berikutnya, tidak mengganti menu sesi yang telah dibuka.

### Tahap 70 — Navbar tetap lengkap di Weekly schedule (2 November 2026)

- Penyebab: RintisLayout menyembunyikan Catalog dan Settings ketika props catalogUrl/settingsUrl tidak tersedia. Dua halaman seller baru belum mengirim URL tersebut.
- Perbaikan: WeeklyScheduleController dan WalkInController mengirim URL katalog serta pengaturan toko setelah pemeriksaan kepemilikan, mengikuti pola halaman seller lainnya. Navbar Weekly schedule dan Create order kini mempunyai tautan yang sama dengan halaman operasional lain.
- Cara mencoba: buka Weekly schedule dari Open orders atau muat ulang langsung; Catalog dan Settings tetap tersedia. Coba juga Create order pada sesi aktif.
- Pemeriksaan: 7 tes SellerOrderingTest lulus (93 assertions), termasuk URL navigasi kedua halaman; Pint lulus. Tidak ada perubahan frontend/aset, database atau hak akses. Pemeriksaan visual browser belum dilakukan.

### Tahap 71 — Penyederhanaan Orders, pickup siap, cash dan pemilih jam (4 November 2026)

Tujuan: menyelesaikan arahan terbaru mengenai posisi Create order, Delivery queue, pengelompokan status QRIS, halaman pengambilan, nominal cash serta konsistensi pemilih jam.

Hasil:
- Create order dipindah dari baris tabel daftar sesi ke header halaman Orders dalam sesi aktif. Tombol Delivery queue pada header Orders dihapus; akses Deliveries tetap melalui navbar dan tautan per pesanan yang sudah Out for delivery sesuai keputusan sebelumnya.
- Notifikasi membuka tab sesuai status pesanan saat ini, tidak selalu All orders. Received cash/paid masuk To process, Preparing/Ready pickup masuk Preparing & pickup, Ready/Out delivery masuk In Deliveries, digital unpaid masuk Awaiting payment; Completed dan Expired memiliki tab sendiri. QRIS Paid tidak otomatis menjadi Preparing; seller tetap memulai persiapan. Screenshot Received di All orders sebelumnya berasal dari tautan notifikasi view=all, bukan perubahan metode pickup menjadi delivery.
- Receipt Ready pickup menampilkan QR pengambilan dan lokasi/alamat di depan. Detail progres/transaksi tertutup secara default. Peta/marker dan tombol Google Maps membuka petunjuk arah Google Maps ke koordinat toko. Pelanggan tetap hanya mendapat lokasi privat setelah Ready sebagaimana aturan server sebelumnya.
- Nominal cash_received tetap sesuai input; tabel seller dan detail kini menampilkan nominal diterima beserta kembalian terpisah dari order total. Opsi Customer leaves the change tersedia di walk-in, scan pickup dan penyelesaian delivery. Bila dipilih, cash_change = 0; contoh total Rp22.423 dan input Rp30.000 tetap mencatat Rp30.000 diterima tanpa kembalian. Bila tidak dipilih, kembalian dihitung biasa. Total produk/historis tidak dinaikkan otomatis; selisih yang ditinggalkan dapat dibedakan dari nilai penjualan. Konfirmasi ulang tidak mengganti nominal yang sudah tercatat.
- Weekly schedule memakai DateTimePicker yang sudah ada dengan mode time-only, sehingga pemilihan jam melalui dialog Apply time konsisten dengan formulir sesi. Jadwal mingguan tetap jam per hari, bukan tanggal tertentu.

File utama: OpenOrderController.php, NotificationController.php, Orders.vue, Index.vue, Receipt.vue, PickupMap.vue; CompletePickup.php/CompletePickupRequest.php/PickupController.php/Pickup.vue, DeliveryController.php/Deliveries.vue, WalkInController.php/WalkInRequest.php/WalkIn.vue; DateTimePicker.vue dan WeeklySchedule.vue.

Cara mencoba: buka Orders suatu sesi aktif untuk Create order; buat QRIS pickup lalu bayar, periksa To process dan mulai persiapan; tandai Ready dan buka receipt pelanggan untuk QR/alamat dengan detail tertutup; klik map untuk Google Maps. Uji cash input melebihi total, dengan/ tanpa Customer leaves the change. Buka Weekly schedule dan klik jam untuk dialog Apply time.

Pemeriksaan: seluruh 276 tes lulus (2900 assertions), termasuk perpindahan tab QRIS pickup/notifikasi dan uang diterima/kembalian pada walk-in/pickup/delivery; Pint lulus. npm run build lulus. Tidak mengubah data transaksi lama, tidak memerlukan migration, tidak melakukan pembayaran nyata. Pemeriksaan visual/interaksi browser belum dilakukan. Pelacakan GPS langsung dan laporan pendapatan/tip terpisah belum ditambahkan.

### Tahap 72 — Menghapus timer QRIS pada seller (4 November 2026)

- Permintaan: tampilan seller cukup memperlihatkan waktu sesi agar tidak membingungkan dengan waktu pembayaran QRIS.
- `resources/js/Pages/Rintis/Admin/OpenOrders/Orders.vue`: panel QRIS payment time left serta countdown QRIS pada baris pesanan dihapus. Panel waktu buka/tutup sesi tetap tampil penuh tanpa kolom kosong. Status Paid/Unpaid dan pengelompokan Awaiting payment tetap tersedia.
- Aturan cutoff QRIS, verifikasi provider dan timer pelanggan tetap bekerja; perubahan hanya tampilan seller. Tidak mengubah database atau transaksi.
- Pemeriksaan: npm run build lulus; tidak menambah tes untuk penghapusan tampilan sederhana ini. Belum diperiksa visual dalam browser.
- Diskusi jadwal pengantaran: usulan sederhana adalah mulai pengantaran dan perkiraan selesai per sesi PO. Belum diimplementasikan pada tahap ini karena pengguna masih membahas bentuk pengaturannya.

### Tahap 73 — Alamat wajib, waktu pengantaran dan pembatalan cash (5 November 2026)

Tujuan: pembeli mengetahui perkiraan pengantaran sebelum checkout dan seller bisa membatalkan pesanan cash sesuai persetujuan pemilik.

- Delivery address tetap divalidasi wajib di server ketika memilih Delivery. Label Required dan contoh alamat diperjelas; tombol checkout juga menunggu alamat berisi dan quote pengiriman tersedia. Pin peta tidak menggantikan alamat tertulis.
- Form sesi PO delivery menyediakan Delivery starts dan Estimated delivery ends, memakai pemilih tanggal/jam yang sudah ada. Publikasi baru wajib mengisinya: mulai paling cepat saat PO tutup, selesai setelah mulai. Ini perkiraan rentang pengantaran, bukan pelacakan GPS atau jaminan waktu tiba.
- Rentang tampil pada menu PO, checkout delivery dan receipt. Saat checkout, waktu disalin ke pesanan sehingga perubahan sesi tidak mengganti waktu historis pesanan tersebut. Tidak ada migration baru; memakai fulfillment JSON yang sudah tersedia.
- Weekly schedule menyediakan jam pengantaran per hari aktif untuk toko delivery. Jadwal lama yang belum mempunyai nilai tetap terbaca: pembukaan otomatis memakai mulai 30 menit setelah close dan selesai dua jam setelah close. Sesi lama tanpa rentang tidak diberi informasi waktu buatan pada receipt. Seller sebaiknya memeriksa dan menyimpan rentang jadwal sebelum pemakaian berikutnya.
- Cancel order tersedia pada pesanan cash unpaid sebelum dispatch; wajib alasan. Server memeriksa pemilik toko dan status terkini dalam transaksi, menyimpan jejak pembatalan, mempertahankan rincian transaksi, dan mengembalikan kuota sekali saja. Pesanan yang berada dalam rute planned perlu dikeluarkan dengan Clear plan dahulu; perjalanan aktif tidak dapat dibatalkan. Paid/QRIS tidak dibatalkan melalui fitur ini karena refund belum tersedia.
- Pesanan batal tampil di tab Cancelled dan receipt; tidak memblokir checkout baru atau sesi berikutnya. Tidak ada penghapusan pesanan maupun pengembalian uang otomatis.

File penting: Checkout.vue (alamat/checkout), OpenOrderRequest.php dan SaveOpenOrder.php (validasi/simpan jadwal), DeliveryWindow.vue (rentang waktu bersama), OpenOrder.php/PlaceOrder.php (data publik dan snapshot pesanan), Form.vue/WeeklySchedule.vue serta OpenWeeklySchedule.php (pengaturan sesi/jadwal), CancelOrder.php (pembatalan atomik), OpenOrderController.php/Orders.vue (tombol, alasan dan filter), ActiveCheckout.php/Receipt.vue/OrderHistory.vue (pesanan terminal).

Cara mencoba: buat sesi delivery dan isi rentang setelah waktu close; buka menu pelanggan lalu checkout untuk melihat waktu. Kosongkan alamat untuk memeriksa penolakan checkout. Buat cash unpaid, buka Orders → Cancel order, isi alasan dan konfirmasi; periksa tab Cancelled serta receipt. Coba pesanan paid untuk memastikan tombol pembatalan tidak tersedia. Periksa jam delivery pada setiap hari Weekly schedule.

Pemeriksaan: seluruh 280 tes lulus (2939 assertions), termasuk alamat kosong, rentang publikasi, snapshot waktu historis, pembatasan kepemilikan/pembayaran/dispatch, pembatalan berulang dan pengembalian kuota. Pint dan npm run build lulus. Belum dilakukan pemeriksaan visual browser. Tidak ada migration atau pembayaran nyata.

Opsi Delivery now/Priority belum dibuat. Usulan berikutnya: seller mengaktifkan per sesi, menentukan tambahan ongkir serta perkiraan waktu, dan membatasi kapasitas; jangan menjanjikan pengantaran instan ketika seller sedang menyiapkan pesanan lain. Fokus tahap ini tetap satu rentang pengantaran standar per sesi.

### Tahap 74 — Pilihan Standard, Priority dan Hemat (5 November 2026)

Tujuan: pembeli memilih layanan dan mengetahui estimasi pengantaran serta biaya sebelum Place order, sesuai pembagian yang disetujui pemilik.

- Form sesi PO delivery menyediakan Standard delivery schedule dan dua pilihan opsional. Priority: seller mengisi tambahan biaya di atas ongkir lokasi dan estimasi menit sejak pemesanan. Hemat: seller mengisi ongkir khusus (menggantikan ongkir lokasi) serta estimasi durasi; mulai pengantaran tetap 15 menit setelah PO close. Standard tetap mengikuti rentang sesi dan ongkir lokasi. Biaya berlaku per pesanan, bukan per cup.
- Checkout menampilkan pilihan yang diaktifkan, waktu/estimasi serta cara perhitungan biayanya. Ringkasan dan total mengikuti pilihan. Quote lokasi tetap wajib pada semua layanan untuk pemeriksaan rute dan batas jarak, termasuk Hemat.
- OpenOrderRequest memvalidasi biaya/durasi yang wajib ketika opsi diaktifkan. OpenOrder::deliveryOptions menyediakan data pilihan yang aman untuk publik. PlaceOrder menghitung biaya dari konfigurasi sesi di server, menolak opsi nonaktif, dan menyimpan nama layanan/waktu pada fulfillment pesanan. Kiriman delivery_fee dari browser tidak dipercaya. Harga dan waktu historis tetap tersimpan walau data sesi berubah.
- Receipt menampilkan layanan dan rentang waktu; Orders dan Deliveries memberi label layanan untuk seller. Hemat ditolak jika di-dispatch sebelum PO close +15 menit, termasuk lewat start perjalanan yang memakai UpdateOrderStatus. Priority adalah estimasi secepatnya setelah disiapkan, bukan pelacakan GPS atau jaminan tiba tepat menit tertentu. Seller tetap mengatur persiapan/rute.
- Konfigurasi memakai fulfillment JSON; tidak memerlukan migration. Sesi lama tetap Standard; opsi tidak diaktifkan otomatis pada sesi terbit. Sesi yang sudah published tetap tidak diedit agar tidak mengubah janji transaksi. Weekly schedule masih menghasilkan Standard dengan rentang per harinya; pengaturan Priority/Hemat pada jadwal mingguan belum ditambahkan.

File utama: OpenOrderRequest.php/SaveOpenOrder.php (pengaturan sesi), OpenOrder.php (pilihan publik), CheckoutRequest.php/PlaceOrder.php (validasi dan harga server), Form.vue/Checkout.vue (pilihan dan estimasi), Orders.vue/Deliveries.vue/Receipt.vue (label layanan), UpdateOrderStatus.php (jadwal Hemat).

Cara mencoba: buat sesi manual baru dengan delivery, isi rentang Standard, aktifkan Priority dan/atau Hemat, isi biaya serta durasi dan publish. Di checkout pilih Delivery, isi alamat dan hitung ongkir; ganti pilihan untuk melihat total. Contoh ongkir lokasi Rp2.400 + tambahan Priority Rp5.000 menjadi Rp7.400; Hemat Rp1.500 menjadi ongkir Rp1.500. PO close 17.00 berarti Hemat mulai 17.15. Periksa label di Orders/Deliveries dan receipt.

Pemeriksaan: 284 tes lulus (3001 assertions), termasuk publikasi dengan konfigurasi invalid, tiga perhitungan layanan, penolakan pilihan nonaktif tanpa mengurangi kuota, snapshot historis serta batas dispatch Hemat. Pint dan npm run build lulus. Pemeriksaan visual browser belum dilakukan. Tidak ada pembayaran nyata, migration, atau perubahan transaksi lama.

### Tahap 75 — Default biaya toko dan tanda mulai perjalanan (5 November 2026)

Tujuan: seller cukup mengatur biaya layanan sekali di Settings, bisa menyesuaikan saat membuka PO, dan pelanggan melihat animasi perjalanan setelah perjalanan benar-benar dimulai.

- Store settings kini menyimpan default Priority (aktif, tambahan ongkir, estimasi menit) serta Hemat (aktif, ongkir khusus, durasi). Standard menggunakan pengaturan ongkir tetap/per kilometer yang sudah tersedia. Penambahan kolom nullable delivery_options JSON melalui migration telah dijalankan di PostgreSQL pengembangan.
- Form PO baru otomatis menerima default itu. Standard pricing dan biaya Priority/Hemat bisa disesuaikan langsung di form. Override hanya disalin ke sesi; tidak mengubah Store settings atau sesi/pesanan lama. Harga publikasi tetap terkunci. Sesi otomatis dari Weekly schedule juga mewarisi default toko ketika dibuat; rentang Standard tetap mengikuti jadwal per hari, Hemat close +15 menit.
- StoreRequest memvalidasi/simpan default; OpenOrderController mengirim nilai awal; SaveOpenOrder menggabungkan default dan override sebelum snapshot. Data lama tanpa default tidak mengaktifkan Priority/Hemat otomatis.
- Dispatch order dapat memindahkan pesanan ke operasional Deliveries, tetapi belum menandai perjalanan benar-benar berangkat. Start delivery trip mencatat event delivery_started untuk setiap pesanan dalam transaksi yang sama dengan aktivasi rute. Pengulangan Start tidak menambah event ganda.
- Receipt mendapat flag deliveryStarted. Sebelum Start, pesanan yang sudah Dispatch ditampilkan sebagai menunggu keberangkatan; setelah Start muncul On the way dan animasi di luar detail yang tertutup. OrderProgress memakai waktu mulai perjalanan untuk tahap On the way. Animasi adalah indikator status, bukan GPS langsung. Perjalanan lama yang sudah aktif sebelum penambahan event tidak diberi waktu keberangkatan historis buatan.

File penting: migration add_delivery_options_to_rintis_stores_table, Store.php/StoreRequest.php/StoreController.php/StoreForm.vue (default toko); OpenOrderRequest.php/OpenOrderController.php/SaveOpenOrder.php/Form.vue (override sesi); DeliveryController.php/Order.php (event berangkat); DeliveryMotion.vue/OrderProgress.vue/Receipt.vue (tampilan pelanggan).

Cara mencoba: Settings → Default delivery choices → simpan. Buat PO baru dan periksa biaya terisi; ubah salah satu biaya lalu simpan sesi, kembali Settings untuk memastikan default tetap. Buat delivery, siapkan dan Dispatch; pelanggan masih menunggu perjalanan. Buka Deliveries, rencanakan rute dan tekan Start delivery trip; receipt pelanggan memperbarui otomatis pada polling berikutnya dan memperlihatkan animasi meskipun detail tertutup.

Pemeriksaan: seluruh 286 tes lulus (3023 assertions), termasuk default Settings/form, warisan nilai dan override sesi yang tidak mengubah default/histori, serta event Start idempotent. Tambahan pemeriksaan Dispatch belum berarti perjalanan dimulai diuji pada DeliveryTest. Pint dan npm run build lulus. Migration PostgreSQL berhasil. Belum ada pengujian visual browser atau pelacakan posisi langsung.

### Tahap 76 — Halaman Income seller (5 November 2026)

Tujuan: seller dapat melihat pemasukan tanpa terminal, dengan nilai penjualan dan pembayaran yang dibedakan.

Hasil yang tersedia:
- Menu Income pada navigasi seller, dengan filter Today, This week, This month, rentang tanggal dan metode pembayaran. Batas tanggal memakai WIB. Rentang dibatasi sampai satu tahun. Filter mengacu pada tanggal pesanan dibuat, bukan tanggal uang dibayar atau dicairkan; penjelasan ini terlihat di halaman.
- Product sales dan Delivery income hanya menjumlah pesanan Completed yang Paid. Payments received memasukkan cash yang ditahan serta pembayaran digital terverifikasi, termasuk pesanan masih berjalan. Paid · In progress menunjukkan bagian pembayaran yang pesanan belum selesai.
- Payment breakdown memisahkan cash, QRIS dan transfer bank. Kembalian ditampilkan terpisah dan dikeluarkan dari uang diterima. Selisih cash yang ditinggalkan pelanggan ditampilkan terpisah dari penjualan produk, tetapi termasuk cash retained. Paid cash historis tanpa nominal cash_received menggunakan total pesanan yang tercatat sebagai fallback; rincian uang tunai lama yang tidak pernah dicatat tidak direkonstruksi.
- Unpaid, Expired dan Cancelled tidak menambah pembayaran diterima. Tabel tetap memperlihatkan transaksi beserta status dan nilai Received sehingga histori dapat ditelusuri. Klik nomor pesanan membuka detail dalam sesi seller sendiri. Tabel 20 transaksi per halaman, sedangkan ringkasan mencakup seluruh hasil filter, bukan hanya halaman aktif.
- Ringkasan dihitung dengan pembacaan bertahap kolom yang diperlukan; tabel tidak mengirim token receipt, telepon, alamat maupun resep/HPP. Akses memerlukan login, akses Rintis dan kepemilikan toko sebagaimana panel lain.
- Tidak menghitung laba, fee gateway, saldo cair maupun refund yang belum mempunyai pencatatan lengkap. Verifikasi QRIS bukan konfirmasi uang cair ke rekening; hal ini dijelaskan pada halaman. Tidak mengubah transaksi atau membuat migration baru.

File penting:
- IncomeRequest.php: otorisasi dan validasi filter.
- IncomeController.php: batas tanggal WIB, data toko sendiri, ringkasan dan pagination.
- IncomeReport.php: pemisahan penjualan, pembayaran, kembalian dan extra cash.
- Income.vue: ringkasan, filter, breakdown dan tabel responsif.
- routes/rintis.php, HandleInertiaRequests.php dan RintisLayout.vue: route serta menu Income.

Cara mencoba: masuk seller → Income → pilih periode dan metode. Cocokkan transaksi Completed dengan Product sales/Delivery income, lihat pembayaran pesanan berjalan di Paid · In progress. Coba cash dengan kembalian atau selisih yang ditinggalkan pelanggan. Klik nomor pesanan untuk melihat transaksi sumber; periode tanpa pesanan menampilkan nilai nol dan keadaan kosong.

Pemeriksaan: seluruh 290 tes lulus (3119 assertions), mencakup perhitungan dan status, pembatasan tenant/login, tanggal di batas tengah malam WIB, rentang invalid, filter metode, data kosong serta ringkasan di seluruh halaman. Pint dan npm run build lulus. Belum dilakukan pemeriksaan visual browser. Langkah berikutnya bila dibutuhkan adalah pencatatan fee/payout gateway yang nyata, kemudian laporan keuntungan berbasis snapshot HPP; keduanya belum tersedia pada halaman ini.

### Tahap 77 — Grafik metrik Income (5 November 2026)

- Tujuan: halaman Income lebih mudah dibaca melalui grafik tanpa mengubah aturan pemasukan.
- Ditambahkan Income trend berupa grafik area/garis harian, dengan pilihan Payments (pembayaran tercatat termasuk pesanan berjalan) dan Completed sales (produk + ongkir dari pesanan Paid Completed). Arahkan pointer/sentuh grafik atau gunakan panah keyboard saat grafik fokus untuk melihat nilai tanggal tertentu.
- Payment mix berupa diagram lingkaran Cash, QRIS dan Bank transfer, beserta nominal/persentasenya. Diagram kosong tidak menampilkan angka transaksi buatan. Hari tanpa pemasukan ditampilkan nol; periode panjang tetap mengikuti filter sampai batas satu tahun.
- IncomeReport menambahkan agregat harian berdasarkan tanggal pesanan WIB dalam pembacaan bertahap yang sama dengan ringkasan. Cancelled/Expired/Unpaid tidak dimasukkan. Data grafik mencakup seluruh hasil filter, tidak hanya halaman tabel aktif. Makna tanggal, pembayaran, ongkir, kembalian dan extra cash mengikuti tahap 76.
- File utama: IncomeCharts.vue (grafik responsif, interaksi dan akses keyboard), Income.vue (penempatan grafik), IncomeReport.php (agregat harian), IncomeTest.php (kesesuaian grafik dengan total/filter/batas WIB).
- Cara mencoba: Seller → Income, pilih periode; ubah Payments/Completed sales, sentuh/arahkan pointer pada grafik dan periksa Payment mix. Ganti metode pembayaran atau periode kosong untuk memeriksa pembaruan serta nilai nol.
- Pemeriksaan: 4 tes Income lulus (103 assertions), Pint dan npm run build lulus. Tidak menambah library, migration atau mengubah transaksi. Tampilan/interaksi belum diperiksa langsung dalam browser.

### Tahap 78 — Fokus aplikasi Rintis (6 November 2026)

- Tujuan: menjadikan repositori ini aplikasi Rintis dan menunda admin pusat OneClickStore.
- Halaman `/` sekarang menuju pintu masuk Rintis: seller dengan akses aktif masuk workspace, pengunjung menuju direktori toko. `/account` mengarah ke workspace Rintis atau halaman akun Rintis jika akses seller belum aktif. Hak platform owner tidak lagi mengarahkan pengguna ke admin Central.
- `config/central.php` menetapkan enabled=false; route admin Central tidak didaftarkan. Navigasi admin pusat dan switch app tidak tampil pada Rintis. Login, pendaftaran, logo teks dan judul browser memakai identitas Rintis.
- File penting: routes/web.php (pintu masuk dan penonaktifan route), AccountController.php (tujuan login), HandleInertiaRequests.php (navigasi), Rintis/Account.vue (akun tanpa akses seller), layout Auth/Rintis dan app.js (identitas).
- Panduan AGENTS.md dan dokumen konsep diberi keputusan terbaru yang mengesampingkan cakupan lama. Kode Central lama dipertahankan sebagai arsip/fondasi dependensi; ini belum ekstraksi fisik seluruh model akun/hak akses. Nama folder disk masih oneclickstore agar workspace dan proses server tidak terputus. Tidak ada penghapusan data/migration atau perubahan aturan pembayaran.
- Cara mencoba: buka `/`, login seller lama, periksa workspace dan menu akun; `/central/admin` menghasilkan 404. Akun tanpa aktivasi tetap tidak diberi akses toko otomatis. Panel seller tetap tersedia. Admin platform baru belum dibuat.
- Pemeriksaan: 290 tes lulus (3141 assertions), termasuk pintu masuk Rintis, akun owner tidak masuk Central, route Central nonaktif, dan akses seller terjaga. Tes modul Central lama dijalankan dengan modul diaktifkan khusus lingkungan tes. Pint dan npm run build lulus; belum diperiksa visual langsung di browser.

### Tahap 79 — Rintis berdiri sendiri, tanpa pembungkus dan aktivasi Central (6 November 2026)

Tujuan: memperbaiki tahap 78 yang baru menonaktifkan Central. Sekarang Rintis menjadi satu aplikasi utama dengan pendaftaran mandiri.

Hasil yang dapat dipakai:

- Pengguna mendaftar di `/register`, langsung login, lalu dapat membuka `/admin` dan menyiapkan satu toko. Tidak ada aktivasi produk/langganan Central untuk masuk. Pembeli tetap dapat memesan tanpa akun. Halaman publik menyediakan Sign in dan Create your store.
- Policy tetap membatasi toko berdasarkan owner_id; authenticated tidak berarti dapat membaca/mengubah toko orang lain. Endpoint admin tetap memerlukan login dan scoped bindings. Akun lama tetap dapat masuk.
- Folder pembungkus Rintis dilepas: `app/Models`, `app/Services`, `app/Policies`, `app/Notifications`, `app/Http/Controllers`, `app/Http/Requests`, `database/factories`, `resources/js/Pages`, `Components`, `Layouts` dan `tests/Feature` menjadi struktur utama. Namespace, import Vue, nama komponen Inertia, factory dan referensinya diperbarui.
- Controller seller ada di `app/Http/Controllers/Admin`; halaman seller di `resources/js/Pages/Admin`; layout utama `resources/js/Layouts/AppLayout.vue`. Admin di sini berarti pengelola toko, bukan panel admin platform.
- Kode Central (controller/request/model/action/halaman/layout/route/config), middleware aktivasi produk, perintah aktivasi/pemilik platform dan UI pindah aplikasi dihapus. User hanya memiliki relasi store, tanpa relasi akses produk. Jadwal otomatis tidak lagi bergantung pada aktivasi Central.
- Route utama ada di `routes/web.php` dan `routes/store.php`. URL tidak memakai `/rintis`; nama route tidak memakai `rintis.`. Misalnya `admin.stores.show`, `storefront`, `checkout.show`. Link GET lama `/rintis/...` dialihkan ke URL baru, dengan query tetap terjaga. Endpoint webhook Midtrans lama tetap diterima agar notifikasi pembayaran tidak langsung terputus. Tipe notifikasi lama juga tetap terbaca.
- Seeder akun contoh menjadi `DemoAccountSeeder`, konfigurasi `rintis.seed_account` dan env `RINTIS_SEED_ACCOUNT_PASSWORD`; nama/email contoh lama tetap dipakai, tetapi akun baru hasil seed bukan admin platform. Env password lama masih dibaca sebagai fallback untuk kompatibilitas lokal. Tidak ada password yang disalin ke laporan.
- README utama dan AGENTS.md disusun sesuai aplikasi mandiri. Dokumen Oneclick ditandai arsip konsep merek, bukan instruksi membangun Central di sini. Identitas APP_NAME lokal/contoh memakai Rintis.

Perlindungan data dan batasan:

- Tidak menjalankan fresh migration atau menghapus akun/toko/pesanan. Tabel transaksi dan lokasi foto lama tetap digunakan; prefix tabel `rintis_` bukan ketergantungan subproduk. Migration dan tabel Central lama dipertahankan sebagai riwayat skema/data, tanpa kode aplikasi yang menggunakannya. Pembersihan data lama perlu pekerjaan tersendiri, bukan penghapusan diam-diam.
- Folder disk masih `oneclickstore` karena itulah workspace aktif. Identitas dan struktur aplikasi sudah Rintis. Tidak membuat proyek Central baru, tidak deploy atau push GitHub.
- Batas sandbox, merchant gateway, routing delivery, scheduler dan pembayaran nyata tidak diubah. Refaktor ini tidak mengaktifkan layanan produksi atau payout banyak seller.

Cara mencoba: buka `/` sebagai tamu → direktori; klik Create your store → daftar → `/account` → `/admin` → setup toko. Setelah toko ada, login kembali mengarah ke toko sendiri. Toko publik berada di `/stores/{slug}`. Akses `/central/admin` menghasilkan 404. Link receipt lama tetap diarahkan ke halaman yang sama pada URL baru. Jika browser masih menyimpan tampilan lama, muat ulang halaman; jalankan kembali proses dev bila masih memakai kode lama.

Pemeriksaan: 256 tes backend lulus (2712 assertions), 23 tes frontend lulus, Pint dan build produksi lulus. Tes khusus admin/aktivasi portofolio Central dihapus bersama fitur yang sudah tidak berlaku; tes autentikasi, kepemilikan toko, katalog, transaksi, pembayaran, pengantaran dan Income tetap dijalankan. Ditambahkan pemeriksaan tautan lama dan notifikasi sebelum perubahan namespace; tes pendaftaran sekarang membuktikan akun biasa dapat langsung membuka setup toko. Cache konfigurasi/route/view dibersihkan. Belum dilakukan pemeriksaan visual browser langsung.

### Tahap 80 — README bahasa Inggris dan publikasi GitHub (6 November 2026)

- Tujuan: menyiapkan repositori Rintis untuk di-clone dan dicoba orang lain, lalu push ke repository GitHub yang ditentukan pemilik.
- README.md ditulis dalam bahasa Inggris: gambaran aplikasi, fitur dan batasnya, alur seller/pembeli, stack, prasyarat PHP/Composer/Node/PostgreSQL/extension, langkah clone, database, environment, key, migration, storage, server/Vite dan scheduler. Tersedia panduan akun contoh, integrasi peta/wilayah/routing/Sandbox, struktur file, route, tes dan troubleshooting.
- Kebutuhan PHP mengikuti lock yang memerlukan PHP minimal 8.4.1; versi 8.5 direkomendasikan. Tidak menjanjikan inventory penuh, pembayaran nyata, payout otomatis banyak seller, maupun pelacakan GPS.
- .env.example memakai nama database/user rintis dan menjelaskan seeder akun biasa, bukan admin platform. Tidak mengubah database lokal atau kredensialnya.
- Repository tujuan sudah memiliki riwayat README; riwayat tersebut dipertahankan, tanpa force push. Kode sumber dipublikasikan tanpa .env, token, upload pengguna, database lokal, dependency vendor/node_modules, atau hasil build.

- Pemeriksaan sebelum push: 256 tes backend lulus (2712 assertions), 23 tes frontend lulus, npm run build dan composer check-platform-reqs lulus. Pemindaian kandidat source tidak menemukan pola secret yang diperiksa; pengabaian .env, database SQLite, upload storage dan .codex diverifikasi. Instalasi dari mesin kosong belum diuji; panduan dicocokkan dengan lock file dan konfigurasi proyek.

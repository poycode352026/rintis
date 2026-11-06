# Panduan Kerja Rintis

## Identitas dan cakupan

- Repositori ini adalah aplikasi **Rintis** yang berdiri sendiri, bukan Central OneClickStore dan bukan wadah beberapa subproduk. Oneclick adalah merek induk/pengembang.
- Tidak ada panel admin Central, portofolio aplikasi, atau aktivasi akses produk di aplikasi ini. Central akan menjadi proyek tersendiri bila diminta nanti. Jangan membangunnya kembali tanpa permintaan.
- Pengguna dapat mendaftar, login, lalu menyiapkan satu toko. Pembeli tetap dapat memesan tanpa akun. Login tidak memberi hak mengakses toko orang lain; policy dan scoped bindings wajib menjaga kepemilikan data.
- Wemeet Coffee adalah toko pengguna pertama, bukan identitas wajib semua toko. Konfigurasi pembayaran saat ini masih mengikuti batas integrasi yang ada; tidak otomatis mendukung pembayaran langsung ke rekening banyak seller.
- Baca `docs/Rintis.md` untuk kebutuhan bisnis dan `docs/Progress.md` untuk riwayat implementasi. Keputusan terbaru pengguna mengesampingkan dokumen lama. Rencana bukan bukti fitur tersedia.

## Teknologi dan struktur

- Laravel 13, PHP, Vue 3, Inertia 3, Tailwind CSS 4, PostgreSQL, Vite; PHPUnit dengan SQLite untuk tes terisolasi.
- Periksa versi paket terpasang sebelum memakai API versi tertentu. Boost sudah tersedia; jangan memasang ulang dependensi tanpa kebutuhan.
- Tidak perlu folder pembungkus Rintis di setiap direktori: model ada di `app/Models`, controller di `app/Http/Controllers`, validasi di `app/Http/Requests`, proses bisnis di `app/Services`, policy di `app/Policies`.
- `Admin` adalah area **seller/pengelola toko**, bukan admin platform. Halamannya ada di `resources/js/Pages/Admin`; halaman pembeli di `Pages/Storefront`; layout utama di `resources/js/Layouts/AppLayout.vue`.
- `routes/web.php` menangani pintu masuk dan autentikasi; `routes/store.php` menangani toko, katalog, pesanan, pembayaran, pengantaran dan operasional seller. Nama route tidak memakai prefix rintis. URL utama `/`, `/explore`, `/admin`, `/stores/{slug}`.
- `config/rintis.php` berisi konfigurasi wilayah, peta, routing, zona waktu, dan akun contoh seeding. Nama konfigurasi mengikuti nama aplikasi, bukan penanda submodul.
- Tabel transaksi masih memakai prefix `rintis_` agar data lama tetap kompatibel. Migration Central lama dipertahankan sebagai riwayat skema, tetapi model, route dan fondasi aktivasi Central sudah tidak dipakai. Jangan menghapus tabel/data atau mengubah migration yang pernah dijalankan hanya untuk mengganti nama.
- Link pelanggan lama `/rintis/...` tetap dialihkan ke URL baru; endpoint webhook lama tetap diterima. Nama tipe notifikasi lama masih dibaca agar riwayat tidak hilang.

## Alur kerja dan keamanan

- Baca kode sekitar sebelum mengedit; pertahankan pola yang ada, jangan menambah fitur atau abstraksi tanpa kebutuhan.
- Gunakan skills relevan: laravel-best-practices untuk PHP/backend, tailwindcss-development untuk styling, testing-best-practices untuk desain tes. infer-conventions hanya ketika diminta. Jika `.ai/rules/index.md` tersedia, baca aturan terkait.
- Gunakan Boost bila tersedia. Jika tidak, periksa kode paket atau dokumentasi resmi sesuai versi.
- Gunakan Artisan dengan `--no-interaction` saat ada generator yang sesuai. Jangan mengedit vendor, node_modules atau hasil build untuk memperbaiki sumber.
- Environment dibaca dari konfigurasi; kode aplikasi memakai `config()`. Jangan mencetak kredensial, token privat pelanggan, `.env`, atau data sensitif dalam log/laporan.
- Validasi dan otorisasi wajib di server. Jangan mengandalkan penyembunyian tombol. Jangan mengirim alamat/koordinat privat ke props publik sebelum tahap yang diperbolehkan.
- Perubahan skema melalui migration. Jangan menjalankan `migrate:fresh`, menghapus data pengguna, deployment, mengaktifkan layanan berbayar atau mengirim pesan eksternal tanpa otorisasi.
- Transaksi, penguncian dan idempotensi harus dipertahankan untuk pembayaran, kuota, status, pickup dan delivery. Harga/pengaturan baru tidak boleh mengubah snapshot transaksi historis.
- Pisahkan pemasukan bruto, pembayaran diterima, refund, fee dan laba. Jangan menyatakan Income sebagai saldo payout atau keuntungan bersih.
- Jangan membuat ulang konfigurasi Claude; proyek memakai Codex.

## Komunikasi dan pemeriksaan

- Berkomunikasi dalam bahasa Indonesia yang mudah dipahami. Pengguna sedang belajar: jelaskan alur file dan logika saat relevan. UI dan nama kode memakai bahasa Inggris; rupiah dan zona waktu mengikuti konfigurasi aplikasi.
- Jangan menambah teks/fitur yang tidak diperlukan. Jaga responsivitas, keadaan kosong/memuat/gagal/berhasil dan akses keyboard.
- Jalankan tes bermakna untuk perilaku yang berubah, lalu Pint untuk file PHP dan `npm run build` untuk frontend. Perubahan besar pada struktur perlu pengujian regresi. Jangan mengklaim pemeriksaan browser bila belum dilakukan.
- Setiap tugas yang mengubah proyek harus menambahkan **satu entri** di `docs/Progress.md`: tanggal, tujuan, hasil, file utama, cara mencoba, pemeriksaan nyata, keterbatasan dan langkah berikutnya. Pertahankan riwayat; jangan membuat laporan terpisah setiap tugas. Pertanyaan tanpa perubahan tidak memerlukan laporan baru.

# SETSUNA — 刹那 · せつな

**Kamera tamu berbasis QR untuk acara yang cuma sekali.**

Satu QR di meja penerima tamu mengubah ponsel setiap tamu jadi kamera sekali
pakai. Tiap orang dapat jatah terbatas — misalnya 18 foto, 2 video 15 detik,
dan 1 boomerang — dan semua hasilnya berkumpul jadi satu album yang terbuka
bersamaan di portal acara milik klien.

Dipakai untuk pernikahan, wisuda kampus, prom sekolah, gathering kantor, dan
acara lain yang butuh dokumentasi dari banyak sudut sekaligus.

---

## Cara kerjanya

1. Klien memesan paket di halaman publik. Pesanan masuk sebagai
   `subscription` berstatus *pending* beserta `event` berstatus *draft*.
2. Admin memverifikasi pembayaran. Melunasi pesanan otomatis mengaktifkan
   acaranya.
3. Admin mencetak papan QR (`/admin/events/{slug}/qr`) dan memasangnya di venue.
4. Tamu memindai QR → **langsung masuk mode kamera**, tanpa aplikasi dan
   tanpa akun. Cukup menulis nama sekali.
5. Tiap jepretan diunggah satu per satu dan memotong kuota tamu itu.
6. Album bersama terbuka sesuai aturan: langsung, otomatis setelah acara,
   atau manual dari panel admin.

---

## Peta rute

### Publik

| Rute | Isi |
| --- | --- |
| `/` | Beranda: cara kerja, jenis acara, preset, harga |
| `/harga` | Daftar paket |
| `/faq` | Pertanyaan umum |
| `/pesan/{plan}` | Formulir pemesanan |
| `/pesanan/{id}` | Konfirmasi pesanan |

### Portal acara (satu "sub folder" per klien)

Slug acara hidup di root, misalnya `/pernikahan-emma`. Polanya dibatasi dan
slug sistem ditolak lewat `config/setsuna.php → reserved_slugs`, jadi tidak
bisa menabrak rute aplikasi.

| Rute | Isi |
| --- | --- |
| `/{slug}` | Halaman sambutan acara |
| `/{slug}/kamera` | **Mode kamera** — yang dibuka QR |
| `/{slug}/galeri` | Album bersama |
| `/{slug}/rollku` | Roll pribadi perangkat ini |
| `/{slug}/tamu/{guest}` | Album satu tamu |
| `/{slug}/media/{id}/unduh` | Unduh satu berkas |
| `POST /{slug}/api/tamu` | Daftar tamu |
| `POST /{slug}/api/tangkap` | Unggah tangkapan |
| `GET /{slug}/api/kuota` | Sisa kuota |
| `GET /{slug}/api/roll` | Roll pribadi (JSON) |

### Admin (`/admin`)

| Modul | Isi |
| --- | --- |
| Acara | CRUD, status buka/tutup, buka album, ganti token QR, papan QR siap cetak, unduh semua (.zip) |
| Media | Moderasi per acara: setujui, sembunyikan, hapus, aksi massal |
| Tamu | Pemakaian kuota, tambah jatah, blokir, hapus |
| Klien | CRUD + riwayat acara dan pesanan |
| Pesanan | CRUD, tandai lunas (mengaktifkan acara), batalkan |
| Paket | CRUD paket langganan yang tampil di halaman harga |

---

## Struktur data

| Tabel | Isi |
| --- | --- |
| `plans` | Paket langganan: harga, kuota per tamu, masa simpan, fitur |
| `clients` | Pemesan (mempelai, panitia, atau EO) |
| `events` | Acara + slug portal + setelan kamera dan album |
| `subscriptions` | Pesanan paket; lunas → acara aktif |
| `event_guests` | Satu baris per perangkat yang scan, plus pemakaian kuota |
| `event_media` | Foto, video, boomerang milik tiap tamu |
| `event_scans` | Log scan QR untuk statistik |

Kuota dipotong di dalam transaksi dengan baris tamu dikunci
(`CaptureService::store`), jadi dua unggahan berbarengan dari satu ponsel
tidak bisa melewati jatah.

---

## Preset film

Setiap acara **selalu** memakai preset bawaan **Natural + Enhance**: warna apa
adanya, hanya dirapikan sedikit. Selain itu klien bisa memilih **satu** preset
film: Classic 400, Noir, Amber Hour, Midnight, Polaroid, atau Direct Flash.

Foto dibakar bersama presetnya lewat canvas, jadi berkas yang tersimpan sama
dengan yang dilihat tamu. Video dan boomerang direkam apa adanya dan presetnya
diterapkan saat pemutaran — merekam lewat canvas memang bisa membakar filter,
tapi rapuh di Safari iOS, dan mayoritas tamu memakai ponsel.

Definisinya ada di `app/Support/FilmPresets.php`.

---

## Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate

# sesuaikan DB_* di .env (proyek ini memakai PostgreSQL)
php artisan migrate --seed
php artisan storage:link

php artisan setsuna:logo SETSUNA --apply   # bangun ulang logo & favicon
php artisan serve
```

Seeder menyiapkan:

- akun superadmin `superadmin@gmail.com` / `12qwaszx123!!@@##`
- tiga paket langganan
- dua acara contoh lengkap dengan tamu dan foto:
  `/pernikahan-emma` dan `/wisuda-cendana-2026`

### Syarat kamera tamu

`getUserMedia` hanya jalan di **HTTPS** (atau `localhost`). Untuk uji coba di
ponsel, arahkan lewat tunnel HTTPS atau pasang sertifikat di server.

---

## Merek

Logo dibangun ulang dari kode, jadi tidak ada berkas biner yang perlu
disimpan manual:

```bash
php artisan setsuna:logo SETSUNA --kana=せつな --apply
```

Lambangnya sebuah **ensō** — lingkaran sapuan kuas yang sengaja tidak
tertutup — yang sekaligus terbaca sebagai cincin lensa, dengan satu kilau di
celahnya. Perintah ini menulis SVG, set favicon, apple touch icon, OG image,
dan manifest ke `public/assets/media/branding/`, lalu mengarahkan setting
merek ke berkas itu.

### Gambar hero

Taruh berkas di `public/assets/media/hero/hero.jpg` dan beranda otomatis
memakainya sebagai latar hero. Kalau tidak ada, beranda menyusun kolase dari
foto acara yang sudah masuk. Keduanya ditutup selubung gelap supaya judulnya
tetap terbaca.

---

## Catatan teknis

- **Rate limit unggahan** dikunci per perangkat tamu, bukan per IP — satu
  venue biasanya berbagi satu WiFi, dan ratusan tamu tidak boleh saling
  menghabiskan jatah. Lihat limiter `capture` di `AppServiceProvider`.
- **`Permissions-Policy`** menyetel `camera=(self)`; tanpa itu browser
  memblokir `getUserMedia` bahkan untuk halaman kita sendiri.
- **Sesi tamu** memakai cookie `setsuna_guest_<hash-event>`, satu per acara,
  berlaku setahun. Tidak ada login untuk tamu.
- **Foto contoh** dibuat `App\Services\DemoPhotoFactory` dengan GD, bukan
  berkas biner di repo.
- Batas ukuran unggahan diatur di `config/setsuna.php`. Pastikan
  `upload_max_filesize` dan `post_max_size` di PHP cukup untuk video 15 detik.

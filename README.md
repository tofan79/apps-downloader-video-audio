# Apps-Downloader-Video-Audio

Sebuah aplikasi web downloader sederhana, modern, dan sangat kuat berbasis **Laravel, Svelte, dan Inertia.js** yang menggunakan **yt-dlp** di balik layarnya. Aplikasi ini mendukung pengunduhan Video, Audio, dan bahkan Playlist lengkap dalam berbagai macam format dan kualitas.

![Dashboard Preview](https://i.imgur.com/mEvTA2U.png)

## ✨ Fitur Utama
- **⚡ Single Page Application (SPA):** Dibangun dengan Svelte dan Inertia.js untuk navigasi lancar tanpa reload halaman.
- **🎬 Dukungan yt-dlp:** Mendukung ribuan situs (YouTube, Twitter, TikTok, Facebook, dll).
- **📝 Metadata Preview:** Menampilkan thumbnail, durasi, dan informasi media sebelum mengunduh.
- **📁 File Manager Internal:** Melihat daftar file terunduh langsung dari aplikasi dan mengunduhnya ke perangkat Anda.
- **📊 Real-time Progress:** Menampilkan bar progres, kecepatan download, dan estimasi waktu selesai secara real-time.
- **🍪 Fitur Cookies:** Pengguna dapat mengunggah file `cookies.txt` untuk mengunduh media dari situs/video yang memerlukan autentikasi (misal video members-only).
- **🎵 Ekstraksi Audio:** Mengunduh audio secara langsung dalam format mp3, flac, aac, dll.
- **📋 Unduhan Playlist:** Mendukung pengunduhan seluruh konten dari sebuah playlist dengan penomoran otomatis.

---

## 🛠️ Persyaratan Sistem

Pastikan sebelum menginstal, sistem Anda sudah memiliki komponen berikut:
1. **PHP >= 8.2**
2. **Composer**
3. **Node.js & NPM**
4. **yt-dlp** (Diinstal secara global, misal bisa diakses dari terminal)
5. *Opsional:* **Docker & Docker Compose** (Jika ingin menjalankan via Docker)

---

## 🚀 Instalasi & Setup (Native / Manual)

1. **Clone repositori ini:**
   ```bash
   git clone https://github.com/usernameAnda/apps-downloader-video-audio.git
   cd apps-downloader-video-audio
   ```

2. **Instal dependensi PHP & Node.js:**
   ```bash
   composer install
   npm install
   ```

3. **Salin file `.env` dan generate APP_KEY:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database SQLite:**
   Secara default, aplikasi berjalan di atas SQLite. Jalankan migrasi berikut untuk membuat struktur database:
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

5. **(Opsional) Atur path yt-dlp & Downloads di `.env`:**
   Pastikan variabel berikut sesuai dengan path di PC/Server Anda. Jika Anda tidak mengubahnya, secara default file unduhan akan disimpan di dalam folder `storage/downloads/` di dalam project.
   ```env
   DOWNLOAD_PATH=/path/to/your/storage/downloads
   YTDLP_PATH=/usr/local/bin/yt-dlp
   ```

6. **Jalankan Aplikasi:**
   Anda dapat menjalankan semuanya sekaligus menggunakan satu perintah berikut:
   ```bash
   npm run start
   ```
   *Note: Perintah di atas akan menjalankan Laravel Server (port 8000), Vite development server, dan Queue worker secara bersamaan.*

7. **Buka Aplikasi:**
   Kunjungi browser Anda di `http://localhost:8000`.

---

## 🐳 Instalasi (Docker)

Aplikasi ini juga menyediakan konfigurasi `docker-compose.yml` agar sangat mudah di-deploy di VPS Anda tanpa harus pusing memikirkan setelan enviroment PHP, queue, dll.

1. Sesuaikan variabel di `.env`:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=/var/www/database/database.sqlite
   ```

2. Bangun dan jalankan kontainer:
   ```bash
   docker-compose up -d --build
   ```

3. Akses aplikasi:
   Buka `http://localhost:8000`. Jika menggunakan VPS, port bisa Anda proxy-kan ke domain tertentu melalui Nginx. Segala proses antrean (Queue) untuk mengunduh file secara default sudah diurus otomatis oleh kontainer worker.

---

## 💡 Cara Penggunaan Aplikasi

1. **Paste Link:** Paste sebuah URL (YouTube, Twitter, dll) ke dalam kotak teks utama di dashboard.
2. **Tunggu Loading Metadata:** Aplikasi akan langsung berinteraksi dengan `yt-dlp` untuk memverifikasi apakah URL bisa di-download, berapa resolusi tertingginya, dsb.
3. **Pilih Mode:** Pilih **Video** atau **Audio**, kemudian tentukan resolusi atau bitrate (misal: 1080p, 720p, mp3 320kbps).
4. **Tekan Download:** Proses akan dipindahkan ke background dan Anda dapat melihat progresnya secara realtime.
5. **Akses File:** Setelah selesai, hasil unduhan bisa dilihat pada kotak "File Manager" di bawah halaman. Tekan "Ke PC" untuk menyimpannya, atau "Hapus" untuk menghilangkannya dari server.

---

## 📄 Lisensi

Aplikasi ini didistribusikan di bawah lisensi [MIT License](LICENSE). Anda bebas memodifikasi dan membagikan kembali aplikasi ini dengan tanggung jawab masing-masing.

> **Disclaimer:** Aplikasi ini dibuat sebagai alat utilitas jaringan semata. Pengguna bertanggung jawab penuh atas segala bentuk materi yang diunduh (termasuk kepatuhan terhadap Hak Cipta).

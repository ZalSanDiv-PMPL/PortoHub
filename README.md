<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

# PortoHub

**PortoHub** adalah aplikasi web manajemen dan pameran (*showcase*) portofolio siswa yang dikembangkan untuk mempermudah proses validasi karya siswa oleh guru dan menampilkannya sebagai galeri publik. Aplikasi ini dibangun dengan stack modern menggunakan Laravel 11, Livewire Volt, dan Tailwind CSS (mengusung desain modern berbasis *Glassmorphism*).

## ✨ Fitur Utama

- **Sistem Role Berlapis**: Admin (validasi pengguna), Guru (validasi proyek & siswa), dan Siswa (mengajukan proyek).
- **Integrasi GitHub API**: Otomatis menarik metadata repositori (jumlah *commit*, waktu *commit* terakhir, bahasa pemrograman) milik siswa.
- **Sistem Penilaian & Validasi**: Guru dapat memberikan skor dan catatan/masukan perbaikan untuk setiap proyek.
- **Galeri Portofolio Publik**: Karya siswa yang sudah divalidasi akan otomatis tampil di halaman galeri publik.
- **Sistem Notifikasi Real-time & Latar Belakang**: Dilengkapi *Queue Worker* untuk memproses notifikasi interaktif (pesan/komentar) secara mulus.

---

## 💻 Prasyarat Sistem

Pastikan environment Anda sudah memiliki:
- **PHP 8.3** atau lebih baru
- **Composer**
- **Node.js** (LTS) & **NPM**
- **Database**: MySQL/MariaDB (disarankan) atau SQLite

---

## 🚀 Panduan Setup (Instalasi Lengkap)

Ikuti langkah-langkah di bawah ini untuk menjalankan PortoHub di lingkungan pengembangan (*development*) Anda.

### 1. Clone & Install Dependencies
```bash
git clone https://github.com/ZalSanDiv-PMPL/PortoHub.git
cd PortoHub

# Install dependensi PHP
composer install

# Install dependensi Frontend
npm install
```

### 2. Setup Environment
Salin file konfigurasi bawaan dan *generate* kunci aplikasi:
```bash
copy .env.example .env      # (Gunakan 'cp' jika di Mac/Linux)
php artisan key:generate
```

Buka file `.env` dan atur koneksi *database* sesuai sistem Anda.
Contoh untuk MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portohub
DB_USERNAME=root
DB_PASSWORD=
```
*(Catatan: Buat database kosong bernama `portohub` di MySQL Anda terlebih dahulu)*

### 3. Setup File Storage & Database Seeders
Aplikasi menggunakan sistem upload (avatar & dokumen), sehingga `storage:link` **wajib** dijalankan.
```bash
# Buat symlink untuk storage folder
php artisan storage:link

# Jalankan migrasi tabel beserta pembuatan data dummy simulasi
php artisan migrate:fresh --seed
```

---

## 🛠️ Menjalankan Aplikasi (Local Development)

PortoHub mengandalkan proses latar belakang (Queue) dan tugas terjadwal (Cron/Scheduler) untuk fitur notifikasi dan sinkronisasi GitHub. Oleh karena itu, saat pengembangan lokal, Anda idealnya perlu menjalankan **4 proses terminal secara bersamaan**:

**Terminal 1 (Web Server):**
```bash
php artisan serve
```
**Terminal 2 (Frontend Asset Bundler):**
```bash
npm run dev
```
**Terminal 3 (Queue Worker untuk Notifikasi & Background Jobs):**
```bash
php artisan queue:work
# Atau bisa menggunakan queue:listen jika Anda sering mengubah source code.
```
**Terminal 4 (Scheduler / Cron Job Simulasi Lokal):**
```bash
php artisan schedule:work
# Perintah ini akan menjalankan sinkronisasi data GitHub secara otomatis sesuai jadwal.
```

Aplikasi kini dapat diakses di: **`http://127.0.0.1:8000`**

---

## 🔐 Akun Testing (Demo Seeders)

Ketika Anda menjalankan `php artisan migrate:fresh --seed`, sistem otomatis membuat beberapa akun dengan tingkatan *role* yang berbeda untuk keperluan *testing* fungsionalitas. **Semua password akun adalah: `password`**

| Role | Nama / Identitas | Email Login | Status Skenario |
| :--- | :--- | :--- | :--- |
| **Admin** | Admin PortoHub | `admin@portohub.local` | Admin pusat. |
| **Teacher** | Pak Hendra | `hendra.rpl@portohub.test` | Guru RPL, memiliki kelas X RPL B & XI RPL A. |
| **Teacher** | Bu Dina | `dina.tkj@portohub.test` | Guru TKJ, memvalidasi proyek jurusan TKJ. |
| **Student** | Wafi Saputra | `wafi@portohub.test` | Siswa tervalidasi. Memiliki proyek berstatus **Approved**. |
| **Student** | Nabila Putri | `nabila@portohub.test` | Siswa tervalidasi. Memiliki proyek berstatus **Under Review**. |
| **Student** | Tupai Kidal | `tupaikidal@portohub.test` | Siswa tervalidasi. Memiliki proyek berstatus **Rejected**. |
| **Student** | Roni Pratama | `roni@portohub.test` | Siswa **belum** divalidasi oleh Admin. |

Gunakan akun-akun di atas untuk mencoba alur persetujuan proyek dan interaksi (komentar) antara Guru dan Siswa.

---

## Git Workflow (Saran Branching)
Saat berkontribusi ke repositori ini, biasakan membuat *branch* baru dari `main`:
```bash
git checkout main
git pull origin main
git checkout -b feat/nama-fitur
```
Penamaan *branch* yang disarankan:
- `feat/nama-fitur`
- `fix/perbaikan-bug`
- `chore/maintenance`

---

*Dikembangkan dengan ❤️ untuk modernisasi ekosistem pendidikan.*

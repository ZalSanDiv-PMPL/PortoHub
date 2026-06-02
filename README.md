<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

# PortoHub

**PortoHub** adalah aplikasi web manajemen dan pameran (*showcase*) portofolio siswa yang dikembangkan untuk mempermudah proses validasi karya siswa oleh guru dan menampilkannya sebagai galeri publik. Aplikasi ini dibangun dengan stack modern menggunakan Laravel 13, Livewire Volt, dan Tailwind CSS (mengusung desain modern berbasis *Glassmorphism*).

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

## 🌐 Panduan Deployment (Production)

Saat Anda bersiap untuk mengunggah aplikasi ke server *production* (seperti VPS atau Shared Hosting), ikuti pedoman standar Laravel ini untuk memastikan keamanan dan performa yang optimal:

### 1. Konfigurasi `.env`
Pastikan Anda mengubah variabel ini di file `.env` server Anda:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com
```

### 2. Install Dependencies (Production Mode)
Hindari menginstal *package* untuk development (seperti pest/phpunit):
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Build Asset Frontend
Kompilasi CSS dan JS Anda menjadi versi *minified*:
```bash
npm install
npm run build
```

### 4. Cache Konfigurasi & Route
Meningkatkan kecepatan load dengan mem-*cache* pengaturan kerangka kerja:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Eksekusi Migrasi
Jalankan migrasi database di server (tanpa `seeder` tes agar database bersih):
```bash
php artisan migrate --force
```

### 6. Background Jobs & Scheduler (Penting)
Aplikasi sangat bergantung pada *Queue* dan sinkronisasi GitHub otomatis. Untuk pengguna **cPanel / Shared Hosting** (tanpa akses Supervisor), tambahkan **2 Cron Jobs** ini agar berjalan setiap menit (`* * * * *`):

**Cron Job 1: Menjalankan Scheduler (Otomatis memicu sinkronisasi GitHub)**
```bash
/usr/local/bin/php /home/username_cpanel/public_html/portohub/artisan schedule:run >> /dev/null 2>&1
```

**Cron Job 2: Memproses Antrean Latar Belakang (Notifikasi, Job Latar Belakang)**
```bash
/usr/local/bin/php /home/username_cpanel/public_html/portohub/artisan queue:work --stop-when-empty >> /dev/null 2>&1
```
*(Catatan: Sesuaikan `/usr/local/bin/php` dan *path* proyek dengan lokasi instalasi di server Anda. Parameter `--stop-when-empty` sangat esensial agar server tidak kelebihan muatan/overload).*

*(Opsional)* Jika Anda menggunakan VPS mandiri, Anda tetap direkomendasikan menggunakan utilitas manajemen proses seperti **Supervisor** untuk `queue:work`.

---

## 🔐 Akun Testing (Demo Seeders)

Ketika Anda menjalankan `php artisan migrate:fresh --seed`, sistem otomatis membuat beberapa akun dengan tingkatan *role* yang berbeda untuk keperluan *testing* fungsionalitas. Anda dapat login menggunakan **Email** ataupun **Username**.

Sebagian besar akun memiliki password *default*: **`password`** (kecuali ditandai khusus).

| Role | Nama / Identitas | Email / Username Login | Password | Status Skenario |
| :--- | :--- | :--- | :--- | :--- |
| **Admin** | Admin PortoHub | `admin@portohub.local` / `admin` | `password` | Admin pusat. |
| **Teacher** | Pak Hendra | `hendra.rpl@portohub.test` / `pak-hendra` | `password` | Guru RPL, memiliki kelas X RPL B & XI RPL A. |
| **Teacher** | Bu Dina | `dina.tkj@portohub.test` / `bu-dina` | `password` | Guru TKJ, memvalidasi proyek jurusan TKJ. |
| **Student** | Wafi Saputra | `wafi@portohub.test` / `wafi-saputra` | `password` | Siswa tervalidasi. Memiliki proyek berstatus **Approved**. |
| **Student** | Nabila Putri | `nabila@portohub.test` / `nabila-putri` | `password` | Siswa tervalidasi. Memiliki proyek berstatus **Under Review**. |
| **Student** | Tupai Kidal | `tupaikidal@portohub.test` / `tupaikidal` | **`Kambingguling_001`** | Siswa tervalidasi. Memiliki proyek berstatus **Rejected**. |
| **Student** | Roni Pratama | `roni@portohub.test` / `roni-pratama` | `password` | Siswa **belum** divalidasi oleh Admin. |

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

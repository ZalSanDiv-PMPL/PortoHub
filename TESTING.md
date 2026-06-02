# 🧪 Panduan Pengujian Manual (Comprehensive Testing Guide) PortoHub

Dokumen ini berisi panduan skenario pengujian sangat rinci (*end-to-end testing*) untuk seluruh peran (Siswa, Guru, Admin) pada aplikasi PortoHub. Panduan ini dirancang untuk memastikan semua fungsi, batasan (edge cases), dan keamanan akses bekerja sesuai ekspektasi.

---

## 🔑 0. Persiapan & Akun Demo (Seeder)
Sebelum memulai pengujian, jalankan perintah berikut untuk memastikan *database* dalam keadaan bersih dan *job queue* berjalan:
```bash
php artisan migrate:fresh --seed
php artisan queue:work --stop-when-empty
```
*Catatan: Semua sandi default adalah `password` kecuali disebutkan lain. Anda dapat login menggunakan **Email** ataupun **Username**.*

| Peran | Nama | Username / Email Login | Catatan Status Awal |
| :--- | :--- | :--- | :--- |
| **Admin** | Admin PortoHub | `admin` | Memiliki kontrol penuh atas platform. |
| **Guru** | Pak Hendra | `pak-hendra` | Valid, Akses ke X RPL B & XI RPL A. |
| **Guru** | Bu Dina | `bu-dina` | Valid, Akses ke jurusan TKJ. |
| **Siswa** | Wafi Saputra | `wafi-saputra` | Valid, Proyek: **Approved**. |
| **Siswa** | Nabila Putri | `nabila-putri` | Valid, Proyek: **Under Review**. |
| **Siswa** | Tupai Kidal | `tupaikidal` *(Pass: Kambingguling_001)*| Valid, Proyek: **Rejected** (Perlu Revisi). |
| **Siswa** | Roni Pratama | `roni-pratama` | **Belum divalidasi** Admin. |

---

## 🏃‍♂️ 1. Skenario Registrasi (Autentikasi & Onboarding)

### 1A. Registrasi Siswa Menggunakan GitHub (Otomatis)
1. Buka halaman `/register`.
2. Klik *toggle* ke peran **Siswa**.
3. Klik tombol **"Lanjut dengan GitHub"**.
4. **Ekspektasi:** Anda akan dialihkan ke GitHub untuk otorisasi. Setelah berhasil, Anda langsung masuk ke Dashboard Siswa.
5. **Ekspektasi Lanjutan:** Di Dashboard, akan muncul banner kuning bertuliskan **"Data Akademik Belum Lengkap"** karena sistem hanya menarik nama dan email dari GitHub, sementara NIS dan Tahun Angkatan belum terisi.

### 1B. Registrasi Siswa Secara Manual (Tanpa GitHub di Awal)
1. Buka halaman `/register`.
2. Pastikan *toggle* berada di peran **Siswa**.
3. Isi form secara manual (Nama, Email, Username unik, dan Password). Klik **Daftar**.
4. **Ekspektasi:** Anda berhasil masuk ke Dashboard Siswa.
5. **Ekspektasi Lanjutan:** Di layar *Onboarding* Dashboard, Anda akan melihat tiga instruksi (Langkah 1: Lengkapi Profil, Langkah 2: Hubungkan GitHub, Langkah 3: Unggah Proyek). 
6. Buka halaman Pengaturan Profil. Klik **Hubungkan GitHub** pada bagian yang tersedia.
7. **Ekspektasi:** Status integrasi berhasil (Token dan Username GitHub Anda terhubung ke database).

### 1C. Registrasi Guru (Manual)
1. Buka halaman `/register`.
2. Geser *toggle* ke peran **Guru**. *(Perhatikan peringatan bahwa integrasi GitHub tidak tersedia untuk guru)*.
3. Isi form (Nama, Email, Username, Password). Klik **Daftar**.
4. **Ekspektasi:** Anda dialihkan masuk ke Dashboard Guru.
5. **Ekspektasi Lanjutan:** Muncul banner peringatan berwarna kuning **"Data Akademik Belum Lengkap"**. Guru diberitahu bahwa NIP, Spesialisasi, dan Departemen wajib diisi sebelum Admin bisa memvalidasi akun.

---

## 🛡️ 2. Skenario Penguncian Validasi (Admin Flow)

Pengujian ini memastikan Admin memiliki kontrol ketat terhadap pengguna baru sebelum mereka dapat beraktivitas di sistem.

### 2A. Validasi Guru oleh Admin
1. **Login** menggunakan akun `admin`.
2. Navigasi ke **Dashboard > Manajemen Pengguna**. Buka tab "Guru Menunggu Validasi".
3. Temukan akun Guru yang baru saja dibuat di *Skenario 1C*.
4. **Ekspektasi (Edge Case - Data Kosong):** Tombol **"Setujui"** akan **NONAKTIF (Disabled)**. Ada tulisan merah *"Menunggu Guru"* dan *"Data Kosong"*. Admin **TIDAK BISA** memvalidasi.
5. Buka tab samaran (*Incognito*), login menggunakan akun Guru baru tersebut. Buka **Profil > Informasi Akademik**. Isi NIP, Departemen, dan Spesialisasi. Simpan.
6. Kembali ke layar Admin, *refresh* halaman.
7. **Ekspektasi:** Tombol **"Setujui"** sekarang aktif berwarna hitam. Klik tombol tersebut.
8. **Ekspektasi (Locking Mechanism):** Kembali ke layar Guru, di halaman Profil. Form NIP dan Spesialisasi sekarang terkunci permanen (hanya teks read-only) dan muncul peringatan kuning bahwa data tidak bisa lagi diedit karena telah divalidasi.

### 2B. Validasi Siswa dan Penempatan Kelas oleh Admin
1. Di layar Admin, buka daftar "Siswa Menunggu Validasi". Temukan akun `roni-pratama` (atau Siswa 1B).
2. **Ekspektasi:** Sama seperti guru, jika NIS kosong, tombol "Validasi & Tempatkan" tidak bisa ditekan.
3. Pastikan NIS siswa telah diisi, lalu klik **"Validasi & Tempatkan"**.
4. Pilih Kelas (misal: "XI RPL A") dan pilih Guru Pengampu (misal: "Pak Hendra"). Klik Simpan.
5. **Ekspektasi (Siswa):** Saat `roni-pratama` membuka Dashboard-nya, banner biru "Akun sedang dalam peninjauan" hilang. Tombol **"Ajukan Proyek"** yang tadinya mati, kini menjadi aktif. Form NIS di Profil-nya kini terkunci.

---

## 🚀 3. Skenario Pengajuan Proyek (Student Flow)

1. **Login** sebagai Siswa yang sudah divalidasi (contoh: `roni-pratama`).
2. Di Dashboard, klik **"Ajukan Proyek Baru"**.
3. **Ekspektasi:** Sistem secara otomatis memuat daftar *Repository* publik dari akun GitHub yang telah dihubungkan.
4. Pilih salah satu *Repository*.
5. Isi kelengkapan form:
   - Judul Proyek
   - Deskripsi Singkat
   - Tech Stack (Ketik dan tekan *Enter* / koma)
   - Unggah Thumbnail gambar (PNG/JPG)
   - Lampiran Berkas PDF Dokumen Proyek (Jika ada)
   - Link Demo Aplikasi.
6. Klik **Ajukan Proyek**.
7. **Ekspektasi:** Anda diarahkan ke layar "Kelola Proyek". Status proyek adalah **Under Review** (kuning).

---

## 👨‍🏫 4. Skenario Penilaian dan Keputusan (Teacher Flow)

### 4A. Menyetujui Proyek (Approval)
1. **Login** sebagai `pak-hendra`.
2. Di Dashboard Guru, ada indikator angka *"Menunggu Validasi"*. Gulir ke tabel antrean proyek.
3. Anda akan melihat proyek baru milik `Roni Pratama`. Klik tombol **"Review"**.
4. **Ekspektasi Lanjutan:** Layar menampilkan detail GitHub Metadata (Jumlah Commit, dsb).
5. Gulir ke bagian **Form Validasi**:
   - Berikan skor (0-100) untuk 4 aspek utama (Fungsionalitas, Kualitas Kode, Dokumentasi, Orisinalitas).
   - Ketik **Catatan Akhir** yang positif (misal: "Sangat baik, arsitekturnya bersih").
   - **Centang kotak *"Saya menyetujui proyek ini"***.
   - Klik **Simpan Penilaian**.
6. **Ekspektasi:** Proyek Roni Pratama pindah ke tab "Lulus" di Dashboard Guru.

### 4B. Menolak Proyek (Revisi)
1. Masih sebagai `pak-hendra`, buka proyek milik `Nabila Putri` (status *Under Review* bawaan *Seeder*).
2. Klik **Review**.
3. Di *Form* Validasi:
   - Ketik alasan penolakan di bagian *"Alasan Penolakan / Permintaan Revisi"*.
   - **JANGAN** mencentang kotak persetujuan.
   - Klik **Tolak & Minta Revisi**.
4. **Ekspektasi:** Proyek berpindah ke status **Rejected** (Merah). 

---

## 💬 5. Skenario Interaksi (Diskusi & Pengajuan Ulang)

### 5A. Notifikasi Diskusi
1. Lanjutkan pada Proyek Nabila yang baru ditolak. Sebagai Guru (`pak-hendra`), tambahkan pesan pada kolom diskusi di sebelah kanan: *"Nabila, fitur login masih jebol, tolong perbaiki middleware-nya."*
2. **Login** sebagai `nabila-putri`.
3. Di Dashboard, pada kartu proyeknya (yang berwarna merah), tombol **Lihat Revisi** memiliki titik *ping* merah (indikator pesan baru). Klik tombol tersebut.
4. Nabila membalas komentar: *"Baik Pak, saya sudah commit perbaikannya di GitHub."*

### 5B. Pengajuan Ulang Proyek
1. Nabila mengklik tombol biru besar **"Ajukan Ulang Proyek"** di bagian bawah *Modal* diskusi.
2. **Ekspektasi:** Status proyek Nabila di Dashboard kembali menjadi **Under Review** (kuning).
3. Saat Guru (`pak-hendra`) mengecek kembali Dashboard-nya, antrean Nabila akan naik kembali untuk direview.

---

## 🌍 6. Skenario Eksplorasi Publik (Guest Access & Privacy)

Skenario ini mensimulasikan pengunjung dari luar sekolah (HRD, perusahaan, atau masyarakat umum).

### 6A. Navigasi Galeri dan Filter
1. Buka `http://localhost:8000` tanpa login.
2. Gulir ke bawah hingga menemuka etalase portofolio.
3. Uji coba tombol filter (*Semua, Web, Mobile, AI*). 
4. **Ekspektasi:** Galeri merespons perubahan filter tanpa harus memuat ulang halaman (*Livewire DOM manipulation*).

### 6B. Pencarian Pintar (Global Search)
1. Klik kolom pencarian di navigasi atas atau gunakan *shortcut* `Ctrl+K`.
2. Ketik nama `"Wafi"` atau judul proyek `"EduTrack"`.
3. **Ekspektasi:** Layar pop-up (*modal*) akan memunculkan hasil siswa Wafi beserta proyeknya yang *Approved*. Anda bisa mengklik hasil tersebut untuk menuju ke halaman detail.

### 6C. CV Profil Publik & Proteksi Privasi
1. Kunjungi halaman URL Publik siswa teladan: `http://localhost:8000/@wafi-saputra`.
2. **Ekspektasi:** Terbuka halaman CV Profesional, yang menampilkan aktivitas GitHub, tumpukan teknologi, dan kartu proyeknya. Semua UI ditampilkan dengan rapi tanpa tombol edit.
3. **Pengujian Privasi (Edge Case):** Coba kunjungi `http://localhost:8000/@roni-pratama` (Asumsikan Anda belum melakukan Skenario 2 untuk roni, artinya roni belum divalidasi admin).
4. **Ekspektasi:** Anda akan mendapatkan respon **404 Not Found** atau "Akun tidak tersedia". Sistem **TIDAK AKAN** mempublikasikan informasi akademik siswa sampai Admin sekolah menyetujui akun tersebut.
5. Coba buka *URL* proyek yang memiliki status *Under Review* (misal dengan menebak URL ID proyek). 
6. **Ekspektasi:** Sistem akan memblokir (*Unauthorized*) pengunjung umum dari melihat proyek yang belum disetujui (*Approved*).

---

> **Tip Pengujian:** Selalu pantau log `laravel.log` atau layar terminal yang menjalankan `php artisan queue:work` selama melakukan skenario di atas. Ini akan menunjukkan kepada Anda seberapa responsif aplikasi mengirimkan simulasi email dan *database notifications* setiap kali aksi krusial (seperti validasi admin atau komentar guru) dieksekusi.

# PLANS.md

Rencana kerja ini menjadi panduan untuk menyelesaikan PortoHub sampai siap dipakai, diuji, dan dirilis dengan rapi.

PortoHub diposisikan sebagai platform dokumentasi dan validasi portfolio proyek akhir siswa RPL, dengan peran utama siswa, guru RPL, dan admin.

## 1. Tujuan Utama

Menyelesaikan aplikasi PortoHub sebagai web app Laravel 13 + Livewire + Volt yang:

- Stabil dan bebas error utama.
- Aman untuk autentikasi lokal, login sosial, dan kontrol akses berbasis peran.
- Punya UI auth yang konsisten, responsif, dan mudah dipakai.
- Lulus pengujian dasar dan siap dipublikasikan.
- Punya dokumentasi yang cukup untuk development dan maintenance.

Sasaran produk utamanya adalah:

- Siswa dapat mendokumentasikan proyek, melampirkan URL, file, dan progress.
- Guru dapat memvalidasi proyek, memberi komentar, dan memberi penilaian.
- Admin dapat mengelola data master, otorisasi, dan pemantauan sistem.

## 2. Kondisi Saat Ini

Yang sudah dikerjakan dan dianggap sebagai fondasi:

- Auth GitHub sudah diarahkan ke flow yang mendukung refresh token.
- Token disimpan terenkripsi melalui model `GithubToken`.
- Ada proteksi unlink akun agar user tidak terkunci tanpa password lokal.
- Fitur set password lokal pertama kali sudah mendukung akun sosial.
- Halaman `login` dan `register` sudah dirapikan, dilokalkan ke Bahasa Indonesia, dan lebih responsif.
- Komponen pesan status dan error sudah diseragamkan.
- Tombol utama auth sudah dibuat biru dengan teks putih.
- Toggle show/hide password sudah ditambahkan.
- Tombol GitHub social login sudah muncul di halaman `register`.
- Beberapa penyesuaian development environment sudah dilakukan, termasuk kompatibilitas Windows untuk `composer run dev`.

Fondasi domain dan database yang menjadi acuan rencana:

- Struktur data inti mengarah ke 11 tabel utama: `users`, `teachers`, `students`, `class_assignments`, `projects`, `validations`, `comments`, `project_urls`, `documentation`, `github_tokens`, dan `github_metadata`.
- Relasi guru-siswa dikendalikan oleh `class_assignments` agar akses validasi sesuai kelas dan semester.
- Proyek siswa mengikuti lifecycle `draft → submitted → under_review → approved → archived`, dengan status `rejected` saat ditolak.
- Integrasi GitHub tidak hanya menyimpan token, tetapi juga metadata repository seperti commit count, frekuensi commit, dan commit terakhir.

## 3. Ruang Lingkup Penyelesaian

Rencana ini dibagi menjadi beberapa jalur kerja agar penyelesaiannya terarah.

### 3.1 Auth dan Keamanan

- Pastikan seluruh alur login, register, reset password, confirm password, verify email, dan GitHub OAuth berjalan konsisten.
- Pastikan token GitHub bisa di-refresh secara aman.
- Pastikan unlink social account selalu dicegah jika akun belum punya password lokal.
- Tambahkan mekanisme peringatan yang jelas ketika akun hanya punya satu metode login.
- Tambahkan tes untuk skenario autentikasi penting.

### 3.2 UI/UX

- Pertahankan konsistensi visual seluruh halaman auth.
- Pastikan tampilan mobile dan desktop sama-sama rapi.
- Rapikan halaman dashboard dan halaman internal lain agar mengikuti bahasa visual yang sama.
- Hindari elemen visual yang terlalu generik atau tidak konsisten.
- Pastikan state error, status, empty state, dan action button punya gaya yang seragam.

### 3.3 Data dan Domain

- Review model, migration, factory, dan seeder agar sesuai kebutuhan domain siswa, guru, dan admin.
- Pastikan struktur tabel inti selaras dengan dokumen database: profil pengguna, assignment kelas, proyek, validasi, komentar, dokumentasi, token GitHub, dan metadata GitHub.
- Pastikan field penting seperti token, timestamp, status proyek, status validasi, dan penanda publik/private tercatat dengan benar.
- Tambahkan relasi, accessor, atau scope bila dibutuhkan untuk query umum seperti dashboard guru, arsip publik, dan ringkasan progress siswa.
- Pastikan indexing mendukung pencarian yang sering dipakai: email, NIS, NIP, status proyek, komentar per proyek, dan assignment per kelas.

### 3.4 Kualitas Kode dan Testing

- Tambahkan atau rapikan feature test untuk alur auth, profil, dan proteksi akun.
- Tambahkan unit test bila ada logika yang layak dipisah.
- Pastikan `php artisan test` berjalan hijau untuk area yang disentuh.
- Pastikan error Blade, PHP, dan Livewire yang relevan tidak tersisa.

### 3.5 Build dan Operasional

- Pastikan `composer run dev`, `php artisan serve`, dan `npm run dev` berjalan sesuai lingkungan lokal.
- Pastikan build frontend berhasil.
- Pastikan dokumentasi setup lokal tetap akurat.
- Siapkan langkah deploy jika proyek akan dipindahkan ke server produksi.
- Pastikan seeding awal tersedia untuk guru, siswa, assignment kelas, dan contoh proyek agar dashboard bisa diuji end-to-end.

## 4. Urutan Kerja Prioritas

### Fase 1 - Stabilkan fondasi

1. Audit seluruh alur auth.
2. Pastikan GitHub login dan token refresh aman.
3. Pastikan proteksi akun sosial sudah lengkap.
4. Rapikan validasi dan pesan error.
5. Tambahkan tes untuk semua skenario utama.
6. Pastikan struktur model dasar untuk `users`, `teachers`, dan `students` konsisten dengan migrasi.

### Fase 2 - Selesaikan UI utama

1. Finalisasi halaman auth.
2. Rapikan halaman profile dan dashboard.
3. Satukan komponen UI yang berulang.
4. Pastikan layout responsif di mobile.
5. Pastikan visual konsisten antar halaman.
6. Siapkan tampilan awal untuk dashboard siswa dan guru.

### Fase 3 - Lengkapi fitur produk

1. Bangun profil peran siswa, guru, dan admin di atas tabel `users`.
2. Implementasikan assignment kelas sebagai dasar otorisasi guru ke siswa.
3. Bangun modul proyek siswa: submission, status progress, URL proyek, dan dokumentasi.
4. Bangun dashboard guru untuk daftar siswa, filter kelas, review proyek, dan feedback.
5. Implementasikan komentar, validasi, rating, dan riwayat penilaian.
6. Bangun arsip proyek approved untuk portfolio publik.
7. Integrasikan sinkronisasi metadata GitHub untuk commit history, stars, forks, dan frekuensi commit.
8. Rapikan navigasi dan struktur informasi.
9. Tambahkan state loading, empty, error, dan permission denied untuk tiap fitur.

### Fase 4 - Hardening dan release

1. Audit security dan edge case.
2. Jalankan test suite penuh.
3. Jalankan build frontend production.
4. Dokumentasikan cara menjalankan dan melakukan deploy.
5. Buat checklist rilis final.
6. Validasi seed data dan query utama untuk dashboard, arsip, dan permission check.

## 5. Checklist Kerja Detail

### Auth

- [x] Login lokal.
- [x] Register lokal.
- [x] Reset password.
- [x] Confirm password.
- [x] Verify email.
- [x] GitHub OAuth login.
- [x] Simpan token GitHub terenkripsi.
- [x] Proteksi unlink akun tanpa password lokal.
- [x] Toggle show/hide password.
- [x] Lokalisaasi teks auth ke Bahasa Indonesia.
- [ ] Sediakan refresh token automation untuk GitHub.
- [ ] Tambahkan notifikasi saat metode login hanya satu.

### UX

- [x] Tombol auth berwarna biru dengan teks putih.
- [x] Status dan error message seragam.
- [x] Layout auth responsif.
- [x] Tombol GitHub muncul di login dan register.
- [ ] Rapikan halaman non-auth agar konsisten.
- [ ] Audit spacing, typography, dan hierarchy visual.
- [ ] Bangun tampilan dashboard siswa yang menonjolkan progress proyek.
- [ ] Bangun tampilan dashboard guru dengan fokus pada review dan validasi cepat.

### Testing

- [x] Update test yang terdampak perubahan password.
- [x] Jalankan test relevan untuk validasi perubahan.
- [ ] Tambahkan test untuk GitHub OAuth edge case.
- [ ] Tambahkan test untuk unlink protection.
- [ ] Tambahkan test untuk toggle password bila diperlukan.
- [ ] Tambahkan test permission guru terhadap `class_assignments`.
- [ ] Tambahkan test lifecycle proyek dari draft sampai archived.
- [ ] Tambahkan test komentar, validasi, dan arsip publik.

### Maintenance

- [x] Kompatibilitas `composer run dev` di Windows.
- [ ] Review dependensi yang benar-benar dipakai.
- [ ] Audit konfigurasi environment dan documentasi setup.
- [ ] Pastikan tidak ada log debug tersisa.
- [ ] Finalisasi factory dan seeder untuk teacher, student, class assignment, project, validation, comment, dan GitHub metadata.

## 6. Definition of Done

Project dianggap selesai jika:

- Semua alur auth utama berjalan tanpa error.
- Login sosial aman dan tidak bisa membuat akun terkunci.
- Role siswa, guru, dan admin berjalan sesuai otorisasi.
- Dashboard guru, dashboard siswa, arsip, dan alur proyek utama sudah tersedia.
- UI utama konsisten, responsif, dan mudah dipahami.
- Test suite utama lulus.
- Build frontend lulus.
- Dokumentasi setup dan workflow cukup jelas untuk dipakai developer lain.
- Tidak ada log debug, teks placeholder, atau elemen UI sisa percobaan.
- Data inti dan relasi database sesuai dengan desain yang terdokumentasi.

## 7. Risiko yang Perlu Dijaga

- Token OAuth bisa gagal diperbarui jika job refresh belum ada.
- Akun sosial bisa terkunci jika proteksi unlink tidak konsisten.
- Perubahan UI bisa menyebar ke komponen lain bila class dasar terlalu global.
- State Livewire bisa berubah saat re-render jika interaksi JS tidak dipisah dengan benar.
- Lingkungan Windows bisa berbeda dengan Linux/macOS untuk proses dev dan test.
- Query dashboard guru bisa lambat jika indexing dan eager loading tidak direncanakan sejak awal.
- Struktur data yang terlalu longgar bisa menyulitkan validasi dan arsip publik.

## 8. Langkah Berikutnya yang Disarankan

1. Tambahkan mekanisme refresh token otomatis untuk GitHub.
2. Tambahkan test untuk alur OAuth dan unlink.
3. Bangun entity dan relasi inti sesuai `DATABASES.md` secara bertahap.
4. Rapikan halaman non-auth yang masih memakai gaya default.
5. Audit komponen UI yang dipakai berulang lalu ekstrak bila perlu.
6. Siapkan checklist release dan deployment.

## 9. Catatan Operasional

- Prioritaskan perbaikan yang mencegah data rusak atau akun terkunci.
- Jangan memperluas scope sebelum alur auth dan test utama stabil.
- Setiap perubahan UI besar sebaiknya diikuti validasi visual dan validasi error.
- Setiap perubahan logika autentikasi sebaiknya diikuti test.
- Rancang dashboard guru dan siswa dengan prioritas pada query yang paling sering dipakai.
- Gunakan seeding sample data untuk memvalidasi alur validasi, komentar, dan arsip sejak awal.

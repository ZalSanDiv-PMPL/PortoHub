# DOCS.md - Dokumentasi Project PortoHub

## 📋 Ringkasan Eksekutif

PortoHub adalah platform dokumentasi dan validasi portfolio proyek akhir siswa RPL (Rekayasa Perangkat Lunak). Platform ini memfasilitasi siswa dalam mendokumentasikan portofolio mereka dan memberikan guru RPL kemampuan untuk memvalidasi, menilai, serta memberikan feedback terhadap proyek siswa.

---

## 🎯 Pendahuluan dan Konteks Proyek

### Latar Belakang
Aplikasi PortoHub dikembangkan untuk mengatasi kebutuhan dokumentasi dan validasi proyek akhir siswa RPL. Sistem ini berfungsi sebagai tempat pusat di mana:
- **Siswa** dapat mendokumentasikan dan menampilkan proyek akhir mereka
- **Guru RPL** dapat memvalidasi keaslian dan kualitas proyek dengan efisien
- **Industri** dapat mengakses portfolio siswa untuk kebutuhan rekrutmen

### Stakeholder Utama
- **Siswa RPL**: Pengembang dan pemilik proyek
- **Guru RPL**: Validator dan penilai
- **Industri**: Pengguna akhir yang melihat portfolio siswa

---

## 🔐 Verifikasi Keaslian Proyek dan Penggunaan AI

### Metodologi Verifikasi
Platform mengimplementasikan sistem verifikasi multi-layer untuk memastikan keaslian proyek siswa:

#### 1. **Verifikasi Kode (Code Verification)**
- **Metode Typo**: Guru menyisipkan typo dalam modul pembelajaran agar siswa harus mencari dan memperbaikinya sendiri
- **Instruksi Visual**: Guru memberikan instruksi dalam bentuk gambar/screenshot, memaksa siswa untuk mengetik ulang kode secara manual
- **GitHub Tracking**: Memantau riwayat commit untuk memverifikasi pengerjaan bertahap, bukan hasil unduhan instan

#### 2. **Demonstrasi White-Box Testing**
- **Periode Demonstrasi**: 3 minggu terakhir dari proses pengerjaan proyek (total ~7-8 minggu)
- **Format Ujian**: Siswa harus membongkar dan menjelaskan kode mereka
- **Aspek Penilaian**:
  - Penjelasan algoritma yang digunakan
  - Pemahaman tipe data dan struktur data
  - Variasi tugas (mengubah warna, menambah fitur, dll) untuk memastikan pemahaman mendalam

### Kebijakan Penggunaan AI
- ✅ **Penggunaan AI diperbolehkan** dengan syarat:
  - Siswa memahami fundamental pemrograman
  - Siswa dapat menjelaskan setiap bagian kode saat demonstrasi
  - Siswa menguasai logika di balik implementasi

- ✅ **GitHub Cloning diperbolehkan** dengan tetap menerapkan metodologi verifikasi di atas

### Sistem Pembelajaran ATM
Platform mendukung sistem **ATM (Amati, Tiru, Modifikasi)**:
1. **Amati**: Siswa melihat contoh kode/proyek
2. **Tiru**: Siswa mereplikasi dan memahami kode tersebut
3. **Modifikasi**: Siswa membuat variasi sesuai dengan pemahaman mereka

---

## 📊 Alur Verifikasi dan Penggunaan GitHub

### Proses Verifikasi Berkelanjutan
```
Minggu 1-4: Pengerjaan Awal + Konsultasi Rutin
↓
Minggu 5-7: Pengembangan Lanjutan + Validasi Checkpoint
↓
Minggu 8: Demonstrasi Akhir & Penilaian Final
```

### Peran GitHub sebagai Portfolio
- **Mandatory**: Setiap siswa RPL diwajibkan memiliki akun GitHub sejak kelas 1
- **Publik**: Repository harus bersifat publik untuk akses industri
- **Tracking**: Guru memvalidasi melalui:
  - Riwayat commit (history)
  - Tanggal dan frekuensi pembaruan
  - Konsistensi pengerjaan bertahap
  - Dokumentasi dalam repository

### Konsultasi Fleksibel
- Siswa bebas berkonsultasi dengan **guru RPL mana pun** (tidak hanya guru kelasnya)
- Konsultasi dilakukan secara berkelanjutan sepanjang 7-8 minggu pengerjaan
- Dokumentasi konsultasi dapat tersimpan dalam issues atau discussions di GitHub

---

## ✨ Indikator Validasi dan Fitur Aplikasi

### Indikator Validasi Utama
Fokus validasi adalah memastikan **program berjalan dengan baik (functional requirements terpenuhi)**

### Fitur Dashboard untuk Guru
Dashboard guru dirancang untuk mempercepat proses pengecekan dengan menampilkan:

#### 1. **Data Identitas Siswa**
- Nama lengkap siswa
- Kelas/Tingkat siswa
- NIS (Nomor Induk Siswa)

#### 2. **Status Progress Proyek**
- Tahapan pengembangan (User Requirements, Design, Development, Testing, Deployment)
- Model pengembangan yang digunakan (Waterfall, Agile, dll)
- Persentase completion
- Tanggal deadline dan status keterlambatan

#### 3. **Akses Langsung ke Repository**
- Tombol "View on GitHub" untuk akses cepat ke repository
- Link preview atau live demo (jika tersedia)
- Statistik GitHub (stars, forks, watchers)

#### 4. **Sistem Komentar dan Feedback**
- **Fitur Komentar**: Guru dapat memberikan masukan tertulis
- **Persistensi**: Komentar tetap tersimpan sebagai catatan perbaikan untuk siswa
- **Notifikasi**: Siswa mendapatkan notifikasi ketika ada feedback baru
- **Tracking**: Riwayat feedback dapat diakses untuk melihat perkembangan

### Keamanan dan Otorisasi
- **Tabel Relasi Guru-Siswa**: Database khusus menghubungkan:
  - ID Guru
  - Daftar Siswa dalam Kelas yang Diampu
- **Akses Terbatas**: Guru hanya dapat memvalidasi siswa yang relevan (sesuai kelas)
- **Role-Based Access**: Berbeda antara guru, siswa, dan admin

---

## 🗂️ Fitur Arsip dan Template Pengajuan

### Fitur Archive (Arsip)
Fitur archive sangat penting untuk:
- ✅ **Preservation**: Menyimpan karya siswa yang telah lulus
- ✅ **Public Reference**: Akses publik sebagai referensi untuk siswa tingkat lebih rendah
- ✅ **Career Support**: Tersedia untuk kebutuhan lamaran kerja ke industri
- ✅ **Legacy**: Dokumentasi historis progress program

### Template Pengajuan Proyek
Template mengikuti tahapan model pengembangan yang dipilih siswa:

#### **Jika Waterfall Model**
1. **User Requirements Specification (URS)**
   - Deskripsi kebutuhan pengguna
   - Functional requirements
   - Non-functional requirements

2. **System Design Document (SDD)**
   - Arsitektur sistem
   - Database schema
   - UI/UX design (wireframe/mockup)

3. **Implementation & Development**
   - Source code dengan dokumentasi
   - Comment dalam kode
   - Video tutorial penggunaan

4. **Testing & Quality Assurance**
   - Test cases
   - Test results
   - Bug tracking (jika ada)

5. **Deployment & Documentation**
   - User manual
   - Installation guide
   - System maintenance documentation

#### **Jika Agile Model**
1. **Product Backlog**
2. **Sprint Planning & Execution** (per sprint)
3. **Increment/Demo** (hasil setiap sprint)
4. **Retrospective & Lessons Learned**

### Dokumentasi Pendukung
Siswa wajib menyediakan:
- 📹 **Video Tutorial**: Demonstrasi penggunaan aplikasi
- 📝 **Code Comments**: Dokumentasi inline dalam kode
- 📄 **README.md**: Panduan setup dan instalasi
- 📊 **System Design**: Diagram dan dokumentasi arsitektur
- ✅ **Testing Documentation**: Hasil testing dan quality metrics

---

## 🏗️ Arsitektur dan Technology Stack

### Technology Stack
```
Backend:    Laravel (PHP)
Frontend:   Vue.js/Livewire + Tailwind CSS
Database:   MySQL
Build Tool: Vite
Testing:    Pest/PHPUnit
```

### Modul-Modul Utama
```
1. Authentication & Authorization
   ├── Student Login/Registration
   ├── Teacher Login/Registration
   └── Admin Dashboard

2. Student Portal
   ├── Project Submission
   ├── Documentation Upload
   ├── GitHub Integration
   └── Progress Tracking

3. Teacher Validation Dashboard
   ├── Student List & Filtering
   ├── Project Review Interface
   ├── Comment & Feedback System
   ├── Grading & Validation
   └── Report Generation

4. Archive & Portfolio
   ├── Approved Projects Archive
   ├── Public Portfolio Access
   └── Statistics & Analytics

5. GitHub Integration
   ├── OAuth Authentication
   ├── Repository Metadata Fetch
   ├── Commit History Tracking
   └── Webhook Integration
```

---

## 📱 Fitur-Fitur Utama

### Untuk Siswa
- ✅ Dashboard personal dengan status proyek
- ✅ Upload dan kelola dokumentasi proyek
- ✅ Link repository GitHub otomatis
- ✅ Lihat feedback dari guru
- ✅ Track progress pengerjaan
- ✅ Konsultasi dengan guru

### Untuk Guru
- ✅ Dashboard terpusat dengan semua siswa
- ✅ Quick filtering by class/status
- ✅ Review proyek dengan code preview
- ✅ Sistem komentar dan rating
- ✅ Download/export hasil validasi
- ✅ Generate laporan penilaian

### Untuk Admin
- ✅ Manajemen guru dan siswa
- ✅ Konfigurasi tahun akademik
- ✅ System analytics dan reports
- ✅ Backup dan maintenance

---

## 🚀 Implementasi

### Phase 1: Foundation (Sprint 1-2)
- Setup project structure
- Database schema implementation
- Authentication system
- Basic student & teacher profiles

### Phase 2: Core Features (Sprint 3-5)
- Student project submission
- Teacher dashboard
- Comment & feedback system
- GitHub integration

### Phase 3: Enhancement (Sprint 6-8)
- Archive system
- Analytics & reporting
- Performance optimization
- Security hardening

### Phase 4: Polish & Deployment (Sprint 9+)
- Testing & QA
- Documentation
- User training
- Production deployment

---

## 📋 Convention & Best Practices

### Code Standards
- Follow Laravel conventions
- PSR-12 coding standards
- Clear variable naming
- Comprehensive code comments

### Git Workflow
- Feature branches for development
- Meaningful commit messages
- Pull request reviews
- Semantic versioning

### Database
- Proper indexing untuk performa
- Foreign key constraints
- Migration versioning
- Data integrity checks

---

## 📞 Contact & Support

Untuk pertanyaan lebih lanjut atau riset lebih mendalam, hubungi stakeholder proyek atau guru RPL pembimbing.

---

**Last Updated**: May 7, 2026  
**Version**: 1.0 (Initial Documentation)

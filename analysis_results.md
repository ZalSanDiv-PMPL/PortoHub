# 🔍 Analisis Kode Lengkap — PortoHub

> **PortoHub** adalah platform dokumentasi dan validasi portfolio proyek akhir siswa RPL (Rekayasa Perangkat Lunak), dibangun dengan **Laravel 13**, **Livewire 3 + Volt**, dan **TailwindCSS 3**.

---

## 📋 Daftar Isi

1. [Ringkasan Arsitektur](#1-ringkasan-arsitektur)
2. [Analisis Migrations (21 files)](#2-analisis-migrations)
3. [Analisis Models (11 files)](#3-analisis-models)
4. [Analisis Controllers](#4-analisis-controllers)
5. [Analisis Middleware](#5-analisis-middleware)
6. [Analisis Livewire Components](#6-analisis-livewire-components)
7. [Analisis Routes](#7-analisis-routes)
8. [Analisis Views & Layouts](#8-analisis-views--layouts)
9. [Analisis Blade Components](#9-analisis-blade-components)
10. [Analisis Seeders & Factories](#10-analisis-seeders--factories)
11. [Analisis Tests](#11-analisis-tests)
12. [Analisis Konfigurasi & Providers](#12-analisis-konfigurasi--providers)
13. [Entity Relationship Diagram](#13-entity-relationship-diagram)
14. [Temuan Masalah & Bug](#14-temuan-masalah--bug)
15. [Rekomendasi Perbaikan](#15-rekomendasi-perbaikan)

---

## 1. Ringkasan Arsitektur

| Aspek | Detail |
|---|---|
| **Framework** | Laravel 13 + PHP 8.3 |
| **Frontend** | Livewire 3 + Volt, TailwindCSS 3, Vite 8 |
| **Database** | SQLite (development) |
| **Auth** | Laravel Breeze + GitHub App OAuth |
| **Testing** | Pest 4 + PHPUnit |
| **Roles** | `student`, `teacher`, `admin` |

### Stack Utama
- **Backend**: Laravel 13, Livewire 3.6, Volt 1.7
- **Auth**: Laravel Breeze + GitHub App OAuth (manual, tanpa Socialite driver)
- **Database**: SQLite (`database/database.sqlite`)
- **Build**: Vite 8, TailwindCSS 3, PostCSS

### Struktur Direktori Utama

```
PortoHub/
├── app/
│   ├── Http/Controllers/     → 2 controller + 2 auth controller
│   ├── Http/Middleware/       → 1 custom middleware (EnsureUserHasRole)
│   ├── Livewire/             → Actions, Forms, Public components
│   ├── Models/               → 11 Eloquent models
│   ├── Providers/            → AppServiceProvider, VoltServiceProvider
│   └── View/Components/      → AppLayout, GuestLayout
├── database/
│   ├── migrations/           → 21 migration files
│   ├── factories/            → 7 factory files
│   └── seeders/              → 2 seeder files
├── resources/views/
│   ├── components/           → 16 Blade components + layouts/
│   ├── landing/partials/     → 9 partials (hero, header, footer, dll.)
│   ├── layouts/              → 3 layouts (app, guest, auth-split)
│   └── livewire/             → dashboard/, profile/, public/, pages/
├── routes/                   → web.php, auth.php, console.php
└── tests/                    → 7 Feature tests + 1 Unit test
```

---

## 2. Analisis Migrations

Total: **21 migration files**, urutan kronologis:

### 2.1 Core Tables (Laravel Default)

| # | File | Tabel | Deskripsi |
|---|------|-------|-----------|
| 1 | [create_users_table](file:///c:/laragon/www/PortoHub/database/migrations/0001_01_01_000000_create_users_table.php) | `users`, `password_reset_tokens`, `sessions` | User dasar + session driver database |
| 2 | [create_cache_table](file:///c:/laragon/www/PortoHub/database/migrations/0001_01_01_000001_create_cache_table.php) | `cache`, `cache_locks` | Cache driver database |
| 3 | [create_jobs_table](file:///c:/laragon/www/PortoHub/database/migrations/0001_01_01_000002_create_jobs_table.php) | `jobs`, `job_batches`, `failed_jobs` | Queue driver database |

### 2.2 Domain Tables

| # | File | Tabel | Kolom Penting | Relasi |
|---|------|-------|---------------|--------|
| 4 | [create_teachers_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000001_create_teachers_table.php) | `teachers` | `nip`, `specialization`, `department`, `is_validated` | `users` (1:1) |
| 5 | [create_students_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000002_create_students_table.php) | `students` | `nis`, `year`, `is_validated` | `users` (1:1) |
| 6 | [create_class_assignments_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000003_create_class_assignments_table.php) | `class_assignments` | `class`, `semester`, `is_active` | `teachers` + `students` (pivot) |
| 7 | [create_projects_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000004_create_projects_table.php) | `projects` | `title`, `description`, `development_model`, `status`, `github_url` | `students` (M:1) |
| 8 | [create_project_urls_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000005_create_project_urls_table.php) | `project_urls` | `url_type` (enum), `url`, `is_public` | `projects` (M:1) |
| 9 | [create_validations_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000006_create_validations_table.php) | `validations` | 4 skor penilaian, `is_approved`, `validation_date` | `projects` (1:1), `teachers` (M:1) |
| 10 | [create_comments_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000007_create_comments_table.php) | `comments` | `content`, `comment_type` (enum), `status`, `is_pinned` | `projects` (M:1), `teachers` (M:1) |
| 11 | [create_documentation_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000008_create_documentation_table.php) | `documentation` | `doc_type`, `file_name`, `file_path`, `file_size`, `mime_type` | `projects` (M:1) |
| 12 | [create_github_tokens_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000009_create_github_tokens_table.php) | `github_tokens` | `access_token`, `refresh_token`, `github_id`, `github_username` | `users` (1:1) |
| 13 | [create_github_metadata_table](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_000010_create_github_metadata_table.php) | `github_metadata` | `repo_name`, `commit_count`, `language`, `stars`, `forks` | `projects` (1:1) |

### 2.3 Alter/Migration Lanjutan

| # | File | Perubahan |
|---|------|-----------|
| 14 | [add_github_app_fields](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_07_231436_add_github_app_fields_to_github_tokens_table.php) | Tambah `installation_id`, `token_type`, `expires_in`, `refreshed_at` ke `github_tokens` |
| 15 | [add_password_set_at](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_08_000000_add_password_set_at_to_users_table.php) | Tambah `password_set_at` ke `users` + backfill data |
| 16 | [add_role_fields](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_10_103220_add_role_fields_to_users_table.php) | Tambah `role` (enum), `is_active`, `last_login_at` ke `users` |
| 17 | [add_thumbnail_path](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_26_061409_add_thumbnail_path_to_projects_table.php) | Tambah `thumbnail_path` ke `projects` |
| 18 | [add_visibility](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_29_000001_add_visibility_to_projects_table.php) | Tambah `visibility` (enum: `public`, `restricted`, `private`) ke `projects` |
| 19 | [add_tech_stack](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_29_000002_add_tech_stack_to_projects_table.php) | Tambah `tech_stack` (JSON) ke `projects` |
| 20 | [add_avatar_path](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_30_093051_add_avatar_path_to_users_table.php) | Tambah `avatar_path` ke `users` |
| 21 | [make_nis_nullable](file:///c:/laragon/www/PortoHub/database/migrations/2026_05_30_111754_make_nis_nullable_in_students_table.php) | `nis` dan `year` di `students` menjadi nullable |

### Catatan Migration

> [!TIP]
> **Indexing**: Index composite sudah diterapkan dengan baik — `idx_student_status`, `idx_project_created`, `idx_project_teacher`, `unique_assignment`.

> [!WARNING]
> **Inkonsistensi `semester`**: Di migration `class_assignments`, kolom `semester` bertipe `integer`, tetapi di `ClassAssignmentFactory`, value-nya berupa string `'Ganjil 2025/2026'`. Ini **akan gagal insert** pada database strict (MySQL strict mode). Di SQLite mungkin lolos karena type affinity.

---

## 3. Analisis Models

### 3.1 [User](file:///c:/laragon/www/PortoHub/app/Models/User.php)

```
User
├── Relations: teacher (hasOne), student (hasOne), githubToken (hasOne)
├── Attributes: #[Fillable], #[Hidden] (PHP 8 Attributes)
├── Casts: email_verified_at, password_set_at, password (hashed)
├── Eager Load: $with = ['githubToken'] ⚠️
├── Appends: avatar_url
├── Methods: hasLocalPassword(), isTeacher(), isStudent(), isAdmin()
└── Accessor: getAvatarUrlAttribute() → fallback chain: upload → GitHub → UI Avatars
```

> [!WARNING]
> **Performance Issue — `$with = ['githubToken']`**: Setiap kali User di-query, `githubToken` selalu di-eager-load. Ini memaksa JOIN/query tambahan bahkan saat tidak diperlukan (misalnya di listing, admin panel). Sebaiknya gunakan eager loading eksplisit (`->with('githubToken')`) hanya saat dibutuhkan.

### 3.2 [Student](file:///c:/laragon/www/PortoHub/app/Models/Student.php)

```
Student
├── Relations: user (belongsTo), classAssignments (hasMany), teachers (belongsToMany via class_assignments), projects (hasMany)
├── Fillable: user_id, nis, year, phone, address, is_validated
├── Casts: is_validated (boolean)
└── Accessor: getActiveClassAttribute() → ambil kelas aktif atau terakhir
```

> [!NOTE]
> `getActiveClassAttribute()` menggunakan `$this->classAssignments` (collection method, bukan query). Ini artinya semua class assignments di-load dulu ke memory sebelum di-filter. Untuk data besar, ini bisa menjadi masalah performa.

### 3.3 [Teacher](file:///c:/laragon/www/PortoHub/app/Models/Teacher.php)

```
Teacher
├── Relations: user (belongsTo), classAssignments (hasMany), students (belongsToMany via class_assignments)
├── Fillable: user_id, nip, specialization, department, phone, address, is_validated
└── Casts: is_validated (boolean)
```

### 3.4 [Project](file:///c:/laragon/www/PortoHub/app/Models/Project.php)

```
Project
├── Relations: student (belongsTo), validation (hasOne), comments (hasMany),
│              documentation (hasMany), urls (hasMany), githubMetadata (hasOne)
├── Fillable: 12 kolom termasuk tech_stack, visibility, thumbnail_path
├── Casts: submission_date (datetime), approval_date (datetime), tech_stack (array)
└── Scopes: scopePubliclyVisible() → where visibility = 'public'
```

> [!TIP]
> **Well-designed**: Model Project memiliki relasi yang lengkap dan scope `publiclyVisible()` yang reusable.

### 3.5 [Comment](file:///c:/laragon/www/PortoHub/app/Models/Comment.php)

```
Comment
├── Relations: project (belongsTo), teacher (belongsTo)
├── Fillable: project_id, teacher_id, content, comment_type, status, is_pinned
└── Casts: is_pinned (boolean)
```

### 3.6 [Validation](file:///c:/laragon/www/PortoHub/app/Models/Validation.php)

```
Validation
├── Relations: project (belongsTo), teacher (belongsTo)
├── Table: 'validations' (explicitly set)
├── Fillable: project_id, teacher_id, 4 skor, is_approved, validation_date, notes
└── Casts: is_approved (boolean), validation_date (datetime)
```

### 3.7 Remaining Models

| Model | File | Relasi | Catatan |
|-------|------|--------|---------|
| [ClassAssignment](file:///c:/laragon/www/PortoHub/app/Models/ClassAssignment.php) | Pivot table | teacher ↔ student | Explicit table name |
| [Documentation](file:///c:/laragon/www/PortoHub/app/Models/Documentation.php) | M:1 Project | Stores uploaded files | No `is_public` cast (boolean) |
| [GithubMetadata](file:///c:/laragon/www/PortoHub/app/Models/GithubMetadata.php) | 1:1 Project | Repository stats | 13 fillable fields |
| [GithubToken](file:///c:/laragon/www/PortoHub/app/Models/GithubToken.php) | 1:1 User | OAuth tokens | `encrypted` cast for tokens ✅ |
| [ProjectUrl](file:///c:/laragon/www/PortoHub/app/Models/ProjectUrl.php) | M:1 Project | External links | No `is_public` cast |

---

## 4. Analisis Controllers

### 4.1 [LandingPageController](file:///c:/laragon/www/PortoHub/app/Http/Controllers/LandingPageController.php)

- **Fungsi**: Query 4 proyek terbaru yang approved + public untuk landing page
- **Query Optimization**: Menggunakan `with('student.user')` ✅ dan scope `publiclyVisible()` ✅
- **Penilaian**: Simple dan efektif. Tidak ada masalah.

### 4.2 [GitHubAppAuthController](file:///c:/laragon/www/PortoHub/app/Http/Controllers/Auth/GitHubAppAuthController.php) — **222 baris**

Ini adalah controller **terpanjang dan terpenting** di proyek. Menangani:

1. **`redirectToProvider()`** — Redirect ke GitHub OAuth
2. **`redirectToProviderLink()`** — Link GitHub ke akun yang sudah login
3. **`handleProviderCallback()`** — Handle callback dari GitHub dengan 3 flow:
   - **Flow 1 (Link)**: User sudah login, hubungkan GitHub
   - **Flow 2.1 (Login via GitHub ID)**: Cari existing user by `github_id`
   - **Flow 2.2 (Auto-link via Email)**: Fallback match by email
   - **Flow 2.3 (Register)**: Buat user + student baru
4. **`unlinkProvider()`** — Putuskan hubungan GitHub

#### Analisis Keamanan

| Aspek | Status | Detail |
|-------|--------|--------|
| CSRF State Verification | ✅ | Random state disimpan di session dan diverifikasi |
| Identity Collision Protection | ✅ | Cek apakah GitHub account sudah terpaut ke user lain |
| Password-gated Unlink | ✅ | Harus punya local password sebelum disconnect |
| Error Handling | ✅ | Try-catch untuk HTTP calls + logging |
| Dummy Email Fallback | ⚠️ | `'no-reply@' . $githubUsername . '.local'` — bisa crash jika ada duplicate |

> [!CAUTION]
> **Risiko Keamanan — Auto-link by Email (Flow 2.2)**: Jika seseorang mengatur email GitHub mereka ke email user lain, mereka bisa auto-link dan login ke akun orang itu. Ini adalah **account takeover vector**. Sebaiknya email matching hanya dilakukan jika email GitHub sudah **terverifikasi** (cek `verified` field dari GitHub API).

> [!WARNING]
> **Dummy Email Collision**: Saat register user baru tanpa email GitHub, menggunakan `'no-reply@' . $githubUsername . '.local'`. Karena kolom `email` unique, jika user mengganti GitHub username dan user lain mengambil username tersebut, bisa terjadi collision.

### 4.3 [VerifyEmailController](file:///c:/laragon/www/PortoHub/app/Http/Controllers/Auth/VerifyEmailController.php)

- Standard Laravel email verification. Tidak ada modifikasi. ✅

---

## 5. Analisis Middleware

### [EnsureUserHasRole](file:///c:/laragon/www/PortoHub/app/Http/Middleware/EnsureUserHasRole.php)

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (!$request->user() || !in_array($request->user()->role, $roles)) {
        abort(403, 'Anda tidak memiliki izin...');
    }
    return $next($request);
}
```

- **Registrasi**: Harus didaftarkan sebagai alias `role` di `bootstrap/app.php` (Laravel 13)
- **Penggunaan**: `middleware('role:admin,teacher,student')` di route dashboard
- **Penilaian**: Cukup sederhana dan fungsional. Tidak menggunakan Gate/Policy (yang lebih scalable untuk otorisasi kompleks).

---

## 6. Analisis Livewire Components

### 6.1 [LoginForm](file:///c:/laragon/www/PortoHub/app/Livewire/Forms/LoginForm.php) (Livewire Form Object)

- Standard authentication form dengan rate limiting (5 attempts)
- Menggunakan `RateLimiter` dan `Lockout` event
- Throttle key berbasis email + IP ✅

### 6.2 [Logout](file:///c:/laragon/www/PortoHub/app/Livewire/Actions/Logout.php)

- Invokable class yang logout, invalidate session, dan regenerate token ✅

### 6.3 [StudentProfile](file:///c:/laragon/www/PortoHub/app/Livewire/Public/StudentProfile.php) — **Public Profile Page**

- **Mount**: Load student + projects (approved, public) with validation + githubMetadata
- **Stats Calculation**: Total projects, total commits, average score, top 5 tech skills
- **Layout**: `components.layouts.public`

> [!WARNING]
> **Layout Mismatch**: Component ini menggunakan `->layout('components.layouts.public')`, tapi ini adalah full-page Livewire component yang di-mount langsung di route sebagai `Route::get('/student/{id}', StudentProfile::class)`. Ini benar secara fungsional, tapi `id` parameter bisa di-bypass jika tidak ada validasi tipe (integer). Seseorang bisa inject string.

### 6.4 Volt Components (Inline in Blade Views)

Dashboard components berada di Blade views (Volt-style):
- [dashboard/student.blade.php](file:///c:/laragon/www/PortoHub/resources/views/livewire/dashboard/student.blade.php) — **81KB** 😱
- [dashboard/teacher.blade.php](file:///c:/laragon/www/PortoHub/resources/views/livewire/dashboard/teacher.blade.php) — **39KB**
- [dashboard/admin.blade.php](file:///c:/laragon/www/PortoHub/resources/views/livewire/dashboard/admin.blade.php) — **31KB**

> [!CAUTION]
> **Code Smell — Monolithic Blade Files**: File `student.blade.php` berukuran **81KB** (ratusan baris). Ini mengindikasikan semua logika dan UI dimasukkan ke satu file. Sebaiknya dipecah menjadi komponen-komponen kecil yang reusable.

---

## 7. Analisis Routes

### [web.php](file:///c:/laragon/www/PortoHub/routes/web.php)

| Route | Method | Handler | Middleware | Catatan |
|-------|--------|---------|------------|---------|
| `/` | GET | `LandingPageController@index` | — | Landing page |
| `/gallery` | GET | `Route::view('gallery')` | — | Gallery view |
| `/projects/{project}` | GET | Closure | — | Detail proyek (cek approved + public) |
| `/student/{id}` | GET | `StudentProfile::class` | — | Profil publik siswa |
| `/dashboard` | GET | `Route::view('dashboard')` | `auth, verified, role:admin,teacher,student` | Dashboard role-based |
| `/profile` | GET | `Route::view('profile')` | `auth` | Profile settings |
| `auth/github-app` | GET | `GitHubAppAuthController@redirectToProvider` | — | GitHub OAuth redirect |
| `auth/github-app/callback` | GET | `GitHubAppAuthController@handleProviderCallback` | — | GitHub callback |
| `auth/github-app/link` | GET | `GitHubAppAuthController@redirectToProviderLink` | `auth` | Link GitHub |
| `auth/github-app/unlink` | POST | `GitHubAppAuthController@unlinkProvider` | `auth` | Unlink GitHub |

### [auth.php](file:///c:/laragon/www/PortoHub/routes/auth.php)

- Menggunakan **Volt routes**: `Volt::route('login', 'pages.auth.login')` dll.
- Guest routes: `register`, `login`, `forgot-password`, `reset-password`
- Auth routes: `verify-email`, `confirm-password`, `logout`

> [!WARNING]
> **Route `/projects/{project}` menggunakan Closure**: Ini menggunakan inline closure dengan logic. Sebaiknya dipindahkan ke controller method agar lebih testable dan maintainable. Juga, `$project->load(...)` inside closure memuat banyak relasi — jika salah satu relasi besar, bisa lambat.

---

## 8. Analisis Views & Layouts

### 8.1 Layouts

| File | Digunakan Untuk | Font | Fitur |
|------|-----------------|------|-------|
| [app.blade.php](file:///c:/laragon/www/PortoHub/resources/views/layouts/app.blade.php) | Dashboard, Profile (auth) | Figtree + Manrope | Flash messages (success/error), header |
| [guest.blade.php](file:///c:/laragon/www/PortoHub/resources/views/layouts/guest.blade.php) | Login, Register | Figtree | Radial gradient background, centered card |
| [auth-split.blade.php](file:///c:/laragon/www/PortoHub/resources/views/layouts/auth-split.blade.php) | — | Figtree | Minimal, full-width |
| [public.blade.php](file:///c:/laragon/www/PortoHub/resources/views/components/layouts/public.blade.php) | Landing, Gallery, Project Detail | Figtree + Manrope | SEO meta, skip-to-content, footer |

> [!NOTE]
> `auth-split.blade.php` tampak **tidak digunakan** di mana pun. Bisa dihapus untuk mengurangi dead code.

### 8.2 Halaman Utama

| View | Ukuran | Deskripsi |
|------|--------|-----------|
| [welcome.blade.php](file:///c:/laragon/www/PortoHub/resources/views/welcome.blade.php) | 317B | Wrapper: hero + portfolio gallery + testimonials |
| [dashboard.blade.php](file:///c:/laragon/www/PortoHub/resources/views/dashboard.blade.php) | 306B | Role-based conditional Livewire component |
| [profile.blade.php](file:///c:/laragon/www/PortoHub/resources/views/profile.blade.php) | 5.7KB | Profile forms + GitHub integration UI |
| [gallery.blade.php](file:///c:/laragon/www/PortoHub/resources/views/gallery.blade.php) | 198B | Wrapper untuk portfolio gallery |
| [project-detail.blade.php](file:///c:/laragon/www/PortoHub/resources/views/project-detail.blade.php) | 17KB | Detail proyek lengkap: hero, scores, docs, links |

### 8.3 Landing Page Partials (9 files)

| Partial | Ukuran | Fungsi |
|---------|--------|--------|
| `hero.blade.php` | 4.7KB | Hero section dengan CTA |
| `header.blade.php` | 7.3KB | Navigasi utama (responsive) |
| `footer.blade.php` | 6.1KB | Footer multi-kolom |
| `testimonials.blade.php` | 6.7KB | Testimonial section |
| `features.blade.php` | 2.7KB | Feature highlights |
| `gallery.blade.php` | 3.9KB | Gallery section di landing |
| `platform.blade.php` | 3.1KB | Platform description |
| `process.blade.php` | 2.7KB | Workflow/process section |
| `cta.blade.php` | 1.7KB | Call-to-action section |

### 8.4 Livewire Views

| View | Ukuran | Tipe |
|------|--------|------|
| `dashboard/student.blade.php` | **81KB** | Volt (inline PHP + Blade) |
| `dashboard/teacher.blade.php` | **39KB** | Volt |
| `dashboard/admin.blade.php` | **31KB** | Volt |
| `public/portfolio-gallery.blade.php` | **13KB** | Volt |
| `public/student-profile.blade.php` | **17KB** | Blade (companion to PHP class) |
| `profile/update-profile-information-form.blade.php` | 9.2KB | Volt |
| `profile/update-academic-info-form.blade.php` | 8.2KB | Volt |
| `profile/update-password-form.blade.php` | 3.4KB | Volt |
| `profile/delete-user-form.blade.php` | 2.6KB | Volt |

---

## 9. Analisis Blade Components

### Reusable Components (16 files)

| Component | Deskripsi |
|-----------|-----------|
| [avatar.blade.php](file:///c:/laragon/www/PortoHub/resources/views/components/avatar.blade.php) | Avatar image dengan fallback |
| [glass-card.blade.php](file:///c:/laragon/www/PortoHub/resources/views/components/glass-card.blade.php) | Glassmorphism card |
| [modal.blade.php](file:///c:/laragon/www/PortoHub/resources/views/components/modal.blade.php) | Modal dialog (Alpine.js) |
| [dropdown.blade.php](file:///c:/laragon/www/PortoHub/resources/views/components/dropdown.blade.php) | Dropdown menu (Alpine.js) |
| `primary-button`, `secondary-button`, `danger-button` | Button variants |
| `text-input`, `input-label`, `input-error` | Form elements |
| `nav-link`, `responsive-nav-link`, `dropdown-link` | Navigation links |
| `action-message`, `auth-session-status` | Feedback/status messages |
| `application-logo` | SVG logo component |

---

## 10. Analisis Seeders & Factories

### 10.1 Seeders

#### [DatabaseSeeder](file:///c:/laragon/www/PortoHub/database/seeders/DatabaseSeeder.php)

Membuat:
1. **Admin** (`admin@portohub.local`)
2. **Test User "Tupai Kidal"** (`tupaikidal` — ini bukan email valid!)
3. Memanggil `DemoPortfolioSeeder`
4. Menghubungkan Tupai Kidal ke kelas Pak Hendra

> [!WARNING]
> **Email tidak valid**: `tupaikidal` bukan format email yang valid. Kolom `email` di database memang hanya `string` tanpa validasi format, tapi ini bisa menyebabkan masalah jika ada validasi email di form atau saat mengirim notification.

#### [DemoPortfolioSeeder](file:///c:/laragon/www/PortoHub/database/seeders/DemoPortfolioSeeder.php)

Membuat ekosistem demo lengkap:
- 1 Teacher (Pak Hendra)
- 3 Students (Wafi, Roni, Nabila)
- 3 Projects dengan status berbeda (approved, approved, under_review)
- Per project: URLs, Documentation, GithubMetadata, Validation, Comment
- **Penilaian**: Very well-structured seeder. Menggunakan `updateOrCreate` untuk idempotency ✅

### 10.2 Factories (7 files)

| Factory | Catatan |
|---------|---------|
| [UserFactory](file:///c:/laragon/www/PortoHub/database/factories/UserFactory.php) | ✅ Standard, cached password hash |
| [StudentFactory](file:///c:/laragon/www/PortoHub/database/factories/StudentFactory.php) | ✅ Includes `unvalidated()` state |
| [TeacherFactory](file:///c:/laragon/www/PortoHub/database/factories/TeacherFactory.php) | ✅ |
| [ProjectFactory](file:///c:/laragon/www/PortoHub/database/factories/ProjectFactory.php) | ✅ States: `approved()`, `rejected()`, `private()`, `restricted()` |
| [CommentFactory](file:///c:/laragon/www/PortoHub/database/factories/CommentFactory.php) | ✅ States: `pinned()`, `viewed()` |
| [ValidationFactory](file:///c:/laragon/www/PortoHub/database/factories/ValidationFactory.php) | ✅ State: `rejected()` |
| [ClassAssignmentFactory](file:///c:/laragon/www/PortoHub/database/factories/ClassAssignmentFactory.php) | ⚠️ `semester` = string, tapi kolom DB = integer |

> [!CAUTION]
> **Bug — ClassAssignmentFactory**: `'semester' => $this->faker->randomElement(['Ganjil 2025/2026', 'Genap 2025/2026'])` menghasilkan string, tapi kolom `semester` di migration bertipe `integer`. Ini **akan error** di MySQL strict mode. Fix: gunakan `$this->faker->randomElement([1, 2])`.

---

## 11. Analisis Tests

### Test Suite Overview

| File | Tipe | Test Count | Framework |
|------|------|------------|-----------|
| [ProfileTest](file:///c:/laragon/www/PortoHub/tests/Feature/ProfileTest.php) | Feature | 5 | Pest |
| [CommentSystemTest](file:///c:/laragon/www/PortoHub/tests/Feature/CommentSystemTest.php) | Feature | 4 | PHPUnit |
| [ProjectLifecycleTest](file:///c:/laragon/www/PortoHub/tests/Feature/ProjectLifecycleTest.php) | Feature | 4 | PHPUnit |
| [ProjectVisibilityTest](file:///c:/laragon/www/PortoHub/tests/Feature/ProjectVisibilityTest.php) | Feature | 5 | PHPUnit |
| [TeacherValidationTest](file:///c:/laragon/www/PortoHub/tests/Feature/TeacherValidationTest.php) | Feature | 3 | PHPUnit |
| [DocumentationUploadTest](file:///c:/laragon/www/PortoHub/tests/Feature/DocumentationUploadTest.php) | Feature | 3 | PHPUnit |
| Auth tests | Feature | ? | Pest (di subdirectory) |

**Total**: ~24+ test cases

### Temuan Test

> [!NOTE]
> **Mixing Frameworks**: Proyek mencampur PHPUnit-style test classes (CommentSystemTest, dll.) dengan Pest-style tests (ProfileTest). Meskipun keduanya kompatibel berkat Pest adapter, sebaiknya pilih satu gaya untuk konsistensi.

> [!IMPORTANT]
> **Test Coverage Gap**: Tidak ada test untuk:
> - `GitHubAppAuthController` (komponen paling kompleks!)
> - `LandingPageController`
> - Middleware `EnsureUserHasRole`
> - Livewire dashboard components
> - StudentProfile component

---

## 12. Analisis Konfigurasi & Providers

### [AppServiceProvider](file:///c:/laragon/www/PortoHub/app/Providers/AppServiceProvider.php)

```php
Model::shouldBeStrict(! $this->app->isProduction());
```

✅ **Best Practice**: Mengaktifkan strict mode (prevent lazy loading, prevent silently discarding attributes, prevent accessing missing attributes) di non-production.

### [VoltServiceProvider](file:///c:/laragon/www/PortoHub/app/Providers/VoltServiceProvider.php)

- Mount Volt pada `views/livewire` dan `views/pages` ✅

### [config/services.php](file:///c:/laragon/www/PortoHub/config/services.php)

- GitHub App config via env vars: `GITHUB_APP_ID`, `GITHUB_APP_CLIENT_ID`, `GITHUB_APP_CLIENT_SECRET`, `GITHUB_APP_CALLBACK_URL` ✅

### Dependencies

**Produksi**:
- `laravel/framework` ^13.0
- `laravel/socialite` ^5.27 — ⚠️ **Diinstall tapi TIDAK DIGUNAKAN** (auth GitHub di-implementasi manual)
- `livewire/livewire` ^3.6.4
- `livewire/volt` ^1.7.0

**Development**:
- `laravel/breeze` ^2.4
- `pestphp/pest` ^4.6
- `fakerphp/faker`, `mockery/mockery`

> [!WARNING]
> **Unused Dependency**: `laravel/socialite` diinstall di `composer.json` tapi tidak digunakan di mana pun kode. GitHub OAuth diimplementasi manual di `GitHubAppAuthController`. Sebaiknya dihapus untuk mengurangi dependency footprint.

---

## 13. Entity Relationship Diagram

```mermaid
erDiagram
    users ||--o| teachers : "hasOne"
    users ||--o| students : "hasOne"
    users ||--o| github_tokens : "hasOne"

    teachers ||--o{ class_assignments : "hasMany"
    students ||--o{ class_assignments : "hasMany"
    teachers }o--o{ students : "belongsToMany (via class_assignments)"

    students ||--o{ projects : "hasMany"

    projects ||--o| validations : "hasOne"
    projects ||--o{ comments : "hasMany"
    projects ||--o{ documentation : "hasMany"
    projects ||--o{ project_urls : "hasMany"
    projects ||--o| github_metadata : "hasOne"

    teachers ||--o{ validations : "hasMany"
    teachers ||--o{ comments : "hasMany"

    users {
        bigint id PK
        string name
        string email UK
        string avatar_path
        enum role "student|teacher|admin"
        string password
        timestamp password_set_at
        boolean is_active
        timestamp last_login_at
    }

    teachers {
        bigint id PK
        bigint user_id FK,UK
        string nip UK
        string specialization
        string department
        boolean is_validated
    }

    students {
        bigint id PK
        bigint user_id FK,UK
        string nis UK,nullable
        integer year nullable
        boolean is_validated
    }

    projects {
        bigint id PK
        bigint student_id FK
        string title
        longtext description
        string thumbnail_path
        enum development_model
        string github_url
        enum status "draft|submitted|under_review|approved|rejected|archived"
        enum visibility "public|restricted|private"
        json tech_stack
    }

    validations {
        bigint id PK
        bigint project_id FK,UK
        bigint teacher_id FK
        decimal functionality_score
        decimal code_quality_score
        decimal documentation_score
        decimal originality_score
        boolean is_approved
    }
```

---

## 14. Temuan Masalah & Bug

### 🔴 Critical

| # | Masalah | Lokasi | Dampak |
|---|---------|--------|--------|
| 1 | **ClassAssignment semester type mismatch** | `ClassAssignmentFactory` vs migration | Insert akan gagal di MySQL strict mode |
| 2 | **Auto-link by email tanpa verifikasi** | `GitHubAppAuthController` L173-181 | Potensi account takeover |
| 3 | **Tidak ada test untuk GitHub Auth flow** | `tests/Feature/` | Regresi tidak terdeteksi pada flow auth paling kritis |

### 🟡 Warning

| # | Masalah | Lokasi | Dampak |
|---|---------|--------|--------|
| 4 | **`$with = ['githubToken']` di User** | `User.php` L20 | N+1 query prevention yang terlalu agresif — memuat token di setiap user query |
| 5 | **Monolithic Volt files (81KB, 39KB)** | `livewire/dashboard/` | Sulit maintain, test, dan debug |
| 6 | **Route closure untuk project detail** | `web.php` L10-16 | Tidak bisa di-cache (`php artisan route:cache`) |
| 7 | **Socialite installed but unused** | `composer.json` L14 | Dependency bloat |
| 8 | **`auth-split` layout unused** | `layouts/auth-split.blade.php` | Dead code |
| 9 | **Dummy email saat register via GitHub** | `GitHubAppAuthController` L187 | Potential unique constraint violation |
| 10 | **Email seeder `tupaikidal` bukan format valid** | `DatabaseSeeder.php` L32 | Bisa gagal di validasi email |

### 🟢 Info / Minor

| # | Masalah | Lokasi |
|---|---------|--------|
| 11 | Inconsistent code style (2-space vs 4-space indent) | Beberapa model vs controller |
| 12 | Missing return type hints di beberapa relasi | Semua model |
| 13 | `Documentation` model tidak cast `is_public` ke boolean | `Documentation.php` |
| 14 | `ProjectUrl` model tidak cast `is_public` ke boolean | `ProjectUrl.php` |
| 15 | `last_login_at` ada di migration tapi tidak di-cast di User model | `User.php` |

---

## 15. Rekomendasi Perbaikan

### Prioritas Tinggi

1. **Fix `ClassAssignmentFactory` semester type** — Ubah ke integer:
   ```diff
   - 'semester' => $this->faker->randomElement(['Ganjil 2025/2026', 'Genap 2025/2026']),
   + 'semester' => $this->faker->randomElement([1, 2]),
   ```

2. **Amankan email auto-link di GitHub Auth** — Tambahkan cek email verified:
   ```php
   // Hanya auto-link jika email GitHub terverifikasi
   $emailsResponse = Http::withToken($accessToken)->get('https://api.github.com/user/emails')->json();
   $verifiedEmail = collect($emailsResponse)->firstWhere('verified', true)?->email;
   ```

3. **Tambahkan test untuk `GitHubAppAuthController`** — Minimal mock HTTP calls ke GitHub API

4. **Pecah monolithic dashboard Volt files** — Extract ke komponen kecil:
   - `StudentProjectCard`
   - `StudentProjectForm`
   - `TeacherReviewPanel`
   - dll.

### Prioritas Sedang

5. **Hapus `$with = ['githubToken']`** dari User model, eager load hanya saat diperlukan
6. **Pindahkan route closure ke controller method** untuk project detail
7. **Hapus `laravel/socialite`** dari dependencies
8. **Hapus `auth-split.blade.php`** (dead code)
9. **Tambahkan cast `is_public`** ke `Documentation` dan `ProjectUrl` models

### Prioritas Rendah

10. **Standardisasi code style** — Gunakan Laravel Pint secara konsisten
11. **Tambahkan return type hints** pada semua relasi Eloquent
12. **Cast `last_login_at`** ke datetime di User model
13. **Perbaiki email seeder** (`tupaikidal` → `tupaikidal@portohub.test`)

---

> [!IMPORTANT]
> Secara keseluruhan, **PortoHub** adalah proyek yang **cukup well-structured** dengan arsitektur yang jelas. Database schema dirancang dengan baik (normalisasi, indexing, constraints). GitHub OAuth flow cukup robust. Namun, ada beberapa area kritis (keamanan auto-link, type mismatch factory, dan monolithic Volt files) yang perlu segera diperbaiki.

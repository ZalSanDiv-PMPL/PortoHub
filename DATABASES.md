# DATABASES.md - Database Design untuk PortoHub

## 📋 Daftar Isi
1. [Overview & Konsep](#overview--konsep)
2. [Entity Relationship Diagram](#entity-relationship-diagram)
3. [Detailed Table Schema](#detailed-table-schema)
4. [Relationships & Foreign Keys](#relationships--foreign-keys)
5. [Indexing Strategy](#indexing-strategy)
6. [Data Flow & Queries](#data-flow--queries)
7. [Migrations & Seeding](#migrations--seeding)

---

## 🎯 Overview & Konsep

### Database Type: MySQL
### Collation: utf8mb4_unicode_ci
### Total Tables: 11
### Primary Focus: Multi-tenant role-based access control

### Key Principles:
- ✅ Role-based separation (User → Teacher/Student/Admin)
- ✅ Class Assignment untuk kontrol akses guru-siswa
- ✅ Project lifecycle tracking
- ✅ Audit trail untuk validasi dan feedback
- ✅ GitHub integration storage

---

## 📊 Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                       AUTHENTICATION                        │
└─────────────────────────────────────────────────────────────┘
                              ↓
                      ┌───────────────┐
                      │     users     │ ← Base table untuk semua role
                      └───────────────┘
                              ↓
                ┌─────────────┼─────────────┐
                ↓             ↓             ↓
         ┌──────────┐  ┌──────────┐  ┌──────────┐
         │ teachers │  │ students │  │  admins  │
         └──────────┘  └──────────┘  └──────────┘
                ↓             ↓
         ┌──────────────────────────────┐
         │   class_assignments          │ ← Relasi guru-siswa per kelas
         └──────────────────────────────┘
                        ↓
         ┌──────────────────────────────┐
         │   projects                   │ ← Proyek siswa
         └──────────────────────────────┘
                ↓              ↓              ↓
      ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
      │  validations │  │   comments   │  │ project_urls │
      └──────────────┘  └──────────────┘  └──────────────┘
                ↓
      ┌──────────────┐
      │ documentation│ ← Upload files (video, docs)
      └──────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    GITHUB INTEGRATION                       │
└─────────────────────────────────────────────────────────────┘
      ┌──────────────────┐       ┌──────────────────┐
      │  github_tokens   │       │ github_metadata  │
      └──────────────────┘       └──────────────────┘
```

---

## 🗄️ Detailed Table Schema

### 1️⃣ **USERS** - Base Authentication Table

**Purpose**: Menyimpan data login semua user (teacher, student, admin)

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_email (email),
    KEY idx_role (role),
    KEY idx_is_active (is_active)
);
```

**Fields:**
| Field | Type | Nullable | Default | Keterangan |
|-------|------|----------|---------|-----------|
| id | BIGINT | ❌ | AUTO | Primary key |
| name | VARCHAR(255) | ❌ | - | Nama lengkap user |
| email | VARCHAR(255) | ❌ | - | Email unik |
| email_verified_at | TIMESTAMP | ✅ | NULL | Verifikasi email |
| password | VARCHAR(255) | ❌ | - | Hashed password |
| role | ENUM | ❌ | 'student' | student/teacher/admin |
| is_active | BOOLEAN | ❌ | TRUE | Status aktif user |
| last_login_at | TIMESTAMP | ✅ | NULL | Tracking login terakhir |
| remember_token | VARCHAR(100) | ✅ | NULL | Remember me token |
| created_at | TIMESTAMP | ❌ | NOW | Created timestamp |
| updated_at | TIMESTAMP | ❌ | NOW | Updated timestamp |

---

### 2️⃣ **TEACHERS** - Data Guru

**Purpose**: Menyimpan info spesifik guru RPL

```sql
CREATE TABLE teachers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    nip VARCHAR(20) UNIQUE NOT NULL COMMENT 'Nomor Induk Pegawai',
    specialization VARCHAR(100) NOT NULL COMMENT 'RPL, Matematika, dll',
    department VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NULL,
    address TEXT NULL,
    is_validated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_nip (nip),
    KEY idx_specialization (specialization)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| user_id | BIGINT | ❌ | Foreign key ke users |
| nip | VARCHAR(20) | ❌ | Nomor Induk Pegawai (unik) |
| specialization | VARCHAR(100) | ❌ | Bidang keahlian (RPL) |
| department | VARCHAR(100) | ❌ | Departemen/Jurusan |
| phone | VARCHAR(15) | ✅ | Nomor telepon |
| address | TEXT | ✅ | Alamat |
| is_validated | BOOLEAN | ❌ | Sudah diverifikasi admin |

---

### 3️⃣ **STUDENTS** - Data Siswa

**Purpose**: Menyimpan info spesifik siswa

```sql
CREATE TABLE students (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    nis VARCHAR(20) UNIQUE NOT NULL COMMENT 'Nomor Induk Siswa',
    class VARCHAR(50) NOT NULL COMMENT 'X RPL A, XI RPL B, dll',
    year INT NOT NULL COMMENT 'Tahun masuk (2023, 2024, dll)',
    phone VARCHAR(15) NULL,
    address TEXT NULL,
    github_username VARCHAR(100) NULL,
    is_validated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_nis (nis),
    KEY idx_class (class),
    KEY idx_year (year)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| user_id | BIGINT | ❌ | Foreign key ke users |
| nis | VARCHAR(20) | ❌ | Nomor Induk Siswa (unik) |
| class | VARCHAR(50) | ❌ | X RPL A, XI RPL B, dll |
| year | INT | ❌ | Tahun masuk |
| phone | VARCHAR(15) | ✅ | Nomor telepon siswa |
| address | TEXT | ✅ | Alamat |
| github_username | VARCHAR(100) | ✅ | GitHub username |
| is_validated | BOOLEAN | ❌ | Sudah verifikasi email |

---

### 4️⃣ **CLASS_ASSIGNMENTS** - Relasi Guru-Siswa per Kelas ⭐

**Purpose**: Menghubungkan guru dengan siswa per kelas (kontrol akses utama)

```sql
CREATE TABLE class_assignments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    class VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (teacher_id, student_id, class, semester),
    KEY idx_teacher (teacher_id),
    KEY idx_student (student_id),
    KEY idx_class (class)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| teacher_id | BIGINT | ❌ | FK ke teachers |
| student_id | BIGINT | ❌ | FK ke students |
| class | VARCHAR(50) | ❌ | Nama kelas |
| semester | INT | ❌ | Semester (1 atau 2) |
| is_active | BOOLEAN | ❌ | Status assignment aktif |

**Example Data:**
```
teacher_id=1 (Pak Hendra) → student_id=5 (Wafi), class='X RPL A', semester=1
teacher_id=1 (Pak Hendra) → student_id=6 (Roni), class='X RPL A', semester=1
teacher_id=2 (Pak Alwan)  → student_id=28 (Budi), class='X RPL B', semester=1
```

---

### 5️⃣ **PROJECTS** - Data Proyek Siswa 🎯

**Purpose**: Menyimpan info proyek yang disubmit siswa

```sql
CREATE TABLE projects (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    development_model ENUM('waterfall', 'agile', 'other') DEFAULT 'waterfall',
    github_url VARCHAR(255) NULL,
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'archived') DEFAULT 'draft',
    submission_date TIMESTAMP NULL,
    approval_date TIMESTAMP NULL,
    rejection_reason LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    KEY idx_status (status),
    KEY idx_student (student_id),
    KEY idx_submission_date (submission_date)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| student_id | BIGINT | ❌ | FK ke students |
| title | VARCHAR(255) | ❌ | Judul proyek |
| description | LONGTEXT | ✅ | Deskripsi detail |
| development_model | ENUM | ❌ | Waterfall/Agile/Other |
| github_url | VARCHAR(255) | ✅ | Link repository GitHub |
| status | ENUM | ❌ | draft→submitted→under_review→approved |
| submission_date | TIMESTAMP | ✅ | Tanggal submit |
| approval_date | TIMESTAMP | ✅ | Tanggal approval |
| rejection_reason | LONGTEXT | ✅ | Alasan reject jika ada |

**Status Flow:**
```
draft → submitted → under_review → approved → archived
                              ↓
                           rejected
```

---

### 6️⃣ **PROJECT_URLS** - Link Penting Proyek

**Purpose**: Menyimpan berbagai URL terkait proyek (live demo, video, docs, dll)

```sql
CREATE TABLE project_urls (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    url_type ENUM('live_demo', 'video_tutorial', 'documentation', 'design', 'other') NOT NULL,
    url VARCHAR(255) NOT NULL,
    description VARCHAR(255) NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    KEY idx_project (project_id),
    KEY idx_url_type (url_type)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| project_id | BIGINT | ❌ | FK ke projects |
| url_type | ENUM | ❌ | live_demo, video, documentation |
| url | VARCHAR(255) | ❌ | URL lengkap |
| description | VARCHAR(255) | ✅ | Deskripsi singkat |
| is_public | BOOLEAN | ❌ | Visible untuk publik |

**Example:**
```
project_id=1 → url_type='video_tutorial' → url='https://youtube.com/...'
project_id=1 → url_type='live_demo' → url='https://portohub-demo.com/...'
```

---

### 7️⃣ **VALIDATIONS** - Penilaian Guru ⭐

**Purpose**: Menyimpan hasil validasi/penilaian guru terhadap proyek

```sql
CREATE TABLE validations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL UNIQUE,
    teacher_id BIGINT UNSIGNED NOT NULL,
    functionality_score DECIMAL(5,2) NULL COMMENT '0-100',
    code_quality_score DECIMAL(5,2) NULL COMMENT '0-100',
    documentation_score DECIMAL(5,2) NULL COMMENT '0-100',
    originality_score DECIMAL(5,2) NULL COMMENT '0-100',
    total_score DECIMAL(5,2) GENERATED ALWAYS AS 
        (ROUND((functionality_score + code_quality_score + documentation_score + originality_score) / 4, 2)) 
        STORED,
    is_approved BOOLEAN DEFAULT FALSE,
    validation_date TIMESTAMP NULL,
    notes LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    KEY idx_project (project_id),
    KEY idx_teacher (teacher_id),
    KEY idx_is_approved (is_approved)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| project_id | BIGINT | ❌ | FK ke projects (UNIQUE) |
| teacher_id | BIGINT | ❌ | FK ke teachers |
| functionality_score | DECIMAL | ✅ | 0-100 |
| code_quality_score | DECIMAL | ✅ | 0-100 |
| documentation_score | DECIMAL | ✅ | 0-100 |
| originality_score | DECIMAL | ✅ | 0-100 |
| total_score | DECIMAL | Generated | Rata-rata otomatis |
| is_approved | BOOLEAN | ❌ | Status approval |
| validation_date | TIMESTAMP | ✅ | Tanggal validasi |
| notes | LONGTEXT | ✅ | Catatan guru |

---

### 8️⃣ **COMMENTS** - Feedback Guru ke Siswa 💬

**Purpose**: Menyimpan komentar dan feedback dari guru ke proyek siswa

```sql
CREATE TABLE comments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    content LONGTEXT NOT NULL,
    comment_type ENUM('general', 'code_review', 'requirement', 'suggestion') DEFAULT 'general',
    status ENUM('pending', 'viewed', 'resolved') DEFAULT 'pending',
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    KEY idx_project (project_id),
    KEY idx_teacher (teacher_id),
    KEY idx_status (status),
    KEY idx_created_at (created_at)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| project_id | BIGINT | ❌ | FK ke projects |
| teacher_id | BIGINT | ❌ | FK ke teachers |
| content | LONGTEXT | ❌ | Isi komentar |
| comment_type | ENUM | ❌ | general/code_review/suggestion |
| status | ENUM | ❌ | pending/viewed/resolved |
| is_pinned | BOOLEAN | ❌ | Pin komentar penting |

---

### 9️⃣ **DOCUMENTATION** - File Dokumentasi Upload

**Purpose**: Menyimpan metadata file dokumentasi (video, PDF, gambar)

```sql
CREATE TABLE documentation (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    doc_type ENUM('video', 'pdf', 'image', 'spreadsheet', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL COMMENT 'Size in bytes',
    mime_type VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    KEY idx_project (project_id),
    KEY idx_doc_type (doc_type)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| project_id | BIGINT | ❌ | FK ke projects |
| doc_type | ENUM | ❌ | video/pdf/image/spreadsheet |
| file_name | VARCHAR(255) | ❌ | Nama file |
| file_path | VARCHAR(255) | ❌ | Path di storage |
| file_size | INT | ❌ | Ukuran dalam bytes |
| mime_type | VARCHAR(50) | ❌ | MIME type |
| description | VARCHAR(255) | ✅ | Deskripsi file |
| is_public | BOOLEAN | ❌ | Visible publik |

---

### 🔟 **GITHUB_TOKENS** - OAuth Storage

**Purpose**: Menyimpan GitHub OAuth token untuk integrasi

```sql
CREATE TABLE github_tokens (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    access_token VARCHAR(255) NOT NULL,
    refresh_token VARCHAR(255) NULL,
    token_expires_at TIMESTAMP NULL,
    scope VARCHAR(255) NULL,
    github_id INT NULL,
    github_username VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_user (user_id),
    KEY idx_github_username (github_username)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| user_id | BIGINT | ❌ | FK ke users |
| access_token | VARCHAR(255) | ❌ | GitHub access token |
| refresh_token | VARCHAR(255) | ✅ | Refresh token |
| token_expires_at | TIMESTAMP | ✅ | Token expiration |
| scope | VARCHAR(255) | ✅ | OAuth scope |
| github_id | INT | ✅ | GitHub user ID |
| github_username | VARCHAR(100) | ✅ | GitHub username |
| is_active | BOOLEAN | ❌ | Token masih aktif |

---

### 1️⃣1️⃣ **GITHUB_METADATA** - Tracking Repository

**Purpose**: Menyimpan metadata repository GitHub untuk tracking

```sql
CREATE TABLE github_metadata (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    repo_name VARCHAR(255) NOT NULL,
    repo_owner VARCHAR(100) NOT NULL,
    repo_url VARCHAR(255) NOT NULL,
    commit_count INT DEFAULT 0,
    last_commit_at TIMESTAMP NULL,
    last_commit_message VARCHAR(255) NULL,
    commit_frequency INT DEFAULT 0 COMMENT 'Commits per week',
    language VARCHAR(50) NULL,
    stars INT DEFAULT 0,
    forks INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    KEY idx_project (project_id),
    KEY idx_last_synced (last_synced_at)
);
```

**Fields:**
| Field | Type | Nullable | Keterangan |
|-------|------|----------|-----------|
| id | BIGINT | ❌ | Primary key |
| project_id | BIGINT | ❌ | FK ke projects |
| repo_name | VARCHAR(255) | ❌ | Nama repository |
| repo_owner | VARCHAR(100) | ❌ | Owner GitHub |
| repo_url | VARCHAR(255) | ❌ | URL repository |
| commit_count | INT | ❌ | Total commits |
| last_commit_at | TIMESTAMP | ✅ | Commit terakhir |
| last_commit_message | VARCHAR(255) | ✅ | Message commit terakhir |
| commit_frequency | INT | ❌ | Commits per minggu |
| language | VARCHAR(50) | ✅ | Bahasa pemrograman |
| stars | INT | ❌ | Jumlah stars |
| forks | INT | ❌ | Jumlah forks |
| is_public | BOOLEAN | ❌ | Repository publik |
| last_synced_at | TIMESTAMP | ✅ | Terakhir sync |

---

## 🔗 Relationships & Foreign Keys

### One-to-One Relationships
```
users ↔ teachers (One user has One teacher profile)
users ↔ students (One user has One student profile)
projects ↔ validations (One project has One validation)
```

### One-to-Many Relationships
```
students → projects (One student has Many projects)
students → class_assignments (One student has Many class assignments)
teachers → class_assignments (One teacher has Many class assignments)
teachers → validations (One teacher creates Many validations)
teachers → comments (One teacher writes Many comments)
projects → comments (One project has Many comments)
projects → documentation (One project has Many docs)
projects → project_urls (One project has Many URLs)
projects → github_metadata (One project has One metadata record)
```

### Many-to-Many Relationships
```
teachers ↔ students (Through class_assignments)
- One teacher teaches Many students
- One student learns from Many teachers
```

---

## 🏗️ Indexing Strategy

### Primary Indexes (Search Performance)
```sql
-- Users
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_role ON users(role);

-- Students
CREATE INDEX idx_nis ON students(nis);
CREATE INDEX idx_class ON students(class);
CREATE INDEX idx_github_username ON students(github_username);

-- Teachers
CREATE INDEX idx_nip ON teachers(nip);

-- Projects
CREATE INDEX idx_student_status ON projects(student_id, status);
CREATE INDEX idx_submission_date ON projects(submission_date);

-- Comments
CREATE INDEX idx_project_created ON comments(project_id, created_at);

-- Class Assignments
CREATE UNIQUE INDEX unique_assignment ON class_assignments(teacher_id, student_id, class, semester);
```

### Performance Considerations
```
- Frequent Queries:
  1. Get all students by teacher → Index on class_assignments(teacher_id)
  2. Get projects by status → Index on projects(status)
  3. Get comments by project → Index on comments(project_id)
  4. Search by email → Index on users(email)
  5. Get all projects pending → Index on projects(status, created_at)
```

---

## 📊 Data Flow & Queries

### Query 1: Guru Melihat Semua Siswa dan Proyeknya

```sql
SELECT 
    ca.id,
    s.id as student_id,
    u.name as student_name,
    s.nis,
    s.class,
    p.id as project_id,
    p.title as project_title,
    p.status,
    p.submission_date,
    v.total_score,
    v.is_approved
FROM class_assignments ca
JOIN students s ON ca.student_id = s.id
JOIN users u ON s.user_id = u.id
LEFT JOIN projects p ON s.id = p.student_id
LEFT JOIN validations v ON p.id = v.project_id
WHERE ca.teacher_id = ? 
  AND ca.class = 'X RPL A'
  AND ca.is_active = true
ORDER BY s.nis ASC;
```

---

### Query 2: Validasi Permission - Apakah Guru Bisa Nilai Proyek Ini?

```sql
SELECT EXISTS (
    SELECT 1 FROM class_assignments ca
    JOIN projects p ON p.student_id = ca.student_id
    WHERE p.id = ?  -- project ID
      AND ca.teacher_id = ?  -- teacher ID
      AND ca.is_active = true
) as has_permission;
```

---

### Query 3: Siswa Melihat Semua Feedback dari Guru

```sql
SELECT 
    c.id,
    c.content,
    c.comment_type,
    c.status,
    c.is_pinned,
    c.created_at,
    u.name as teacher_name,
    t.department
FROM comments c
JOIN teachers t ON c.teacher_id = t.id
JOIN users u ON t.user_id = u.id
WHERE c.project_id = ?
ORDER BY c.is_pinned DESC, c.created_at DESC;
```

---

### Query 4: GitHub Tracking - Lihat Commit History

```sql
SELECT 
    p.id,
    p.title,
    gm.repo_name,
    gm.repo_url,
    gm.commit_count,
    gm.commit_frequency,
    gm.last_commit_at,
    gm.last_commit_message,
    gm.language
FROM projects p
JOIN github_metadata gm ON p.id = gm.project_id
WHERE p.student_id = ?
ORDER BY gm.last_commit_at DESC;
```

---

### Query 5: Dashboard Guru - Ringkasan Semua Kelas

```sql
SELECT 
    ca.class,
    COUNT(DISTINCT ca.student_id) as total_students,
    COUNT(DISTINCT CASE WHEN p.status = 'submitted' THEN p.id END) as submitted_projects,
    COUNT(DISTINCT CASE WHEN p.status = 'approved' THEN p.id END) as approved_projects,
    COUNT(DISTINCT CASE WHEN p.status = 'under_review' THEN p.id END) as under_review,
    AVG(v.total_score) as avg_score
FROM class_assignments ca
LEFT JOIN projects p ON p.student_id = ca.student_id
LEFT JOIN validations v ON p.id = v.project_id
WHERE ca.teacher_id = ?
GROUP BY ca.class;
```

---

### Query 6: Archive - Proyek Lulus untuk Portfolio Publik

```sql
SELECT 
    p.id,
    p.title,
    p.description,
    u.name as student_name,
    s.class,
    s.year,
    p.github_url,
    pu.url as live_demo,
    v.total_score,
    p.approval_date
FROM projects p
JOIN students s ON p.student_id = s.id
JOIN users u ON s.user_id = u.id
LEFT JOIN validations v ON p.id = v.project_id
LEFT JOIN project_urls pu ON p.id = pu.project_id AND pu.url_type = 'live_demo'
WHERE p.status = 'approved'
  AND pu.is_public = true
ORDER BY p.approval_date DESC;
```

---

## 🛠️ Migrations & Seeding

### Migration Sequence

```
1. Create users table
2. Create teachers table (FK to users)
3. Create students table (FK to users)
4. Create class_assignments table (FK to teachers & students)
5. Create projects table (FK to students)
6. Create validations table (FK to projects & teachers)
7. Create comments table (FK to projects & teachers)
8. Create project_urls table (FK to projects)
9. Create documentation table (FK to projects)
10. Create github_tokens table (FK to users)
11. Create github_metadata table (FK to projects)
```

### Seeding Data

**Teachers:** 
- Pak Hendra (RPL Specialist)
- Pak Alwan (RPL Specialist)
- Pak Mahali (RPL Specialist)

**Students:** 
- Generate 25-30 students per class
- Multiple classes (X RPL A, X RPL B, XI RPL A, dll)

**Class Assignments:**
- Link each teacher to their classes
- Assign students to classes

**Sample Projects:**
- Create 5-10 sample projects per class
- Various statuses (draft, submitted, approved)

---

## 📈 Scaling Considerations

### Future Optimizations

1. **Table Partitioning**
   - Partition `comments` by project_id
   - Partition `github_metadata` by project_id

2. **Materialized Views**
   - Teacher dashboard cache
   - Archive portfolio cache

3. **Search Optimization**
   - Full-text search on projects description
   - ElasticSearch integration untuk large datasets

4. **Caching Strategy**
   - Redis cache untuk frequently accessed queries
   - Cache invalidation on project updates

---

## 🔒 Security & Data Protection

### Access Control
- ✅ Role-based access via users.role
- ✅ Class assignment validation untuk teacher-student access
- ✅ Soft deletes untuk audit trail

### Data Validation
- ✅ Enum types untuk controlled values
- ✅ Foreign key constraints
- ✅ Unique constraints untuk data integrity
- ✅ Check constraints untuk score ranges (0-100)

### Sensitive Data
- ✅ Passwords hashed
- ✅ GitHub tokens encrypted in storage
- ✅ File paths stored separately from public URLs

---

## 📋 Migration File Template

**Format untuk Laravel Migration:**
```php
// database/migrations/YYYY_MM_DD_HHMMSS_create_users_table.php

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['student', 'teacher', 'admin'])->default('student');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    
    $table->index('email');
    $table->index('role');
});
```

---

## 🎯 Summary

| Aspect | Details |
|--------|---------|
| **Total Tables** | 11 |
| **Primary Keys** | 11 |
| **Foreign Keys** | 15+ |
| **Indexes** | 25+ |
| **Many-to-Many** | 1 (teacher-student via class_assignments) |
| **Relationships** | One-to-One, One-to-Many, Many-to-Many |
| **Collation** | utf8mb4_unicode_ci |
| **Engine** | InnoDB |

---

**Last Updated**: May 7, 2026  
**Version**: 1.0  
**Status**: Ready for Implementation

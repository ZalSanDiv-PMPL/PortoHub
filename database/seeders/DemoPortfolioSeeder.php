<?php

namespace Database\Seeders;

use App\Models\ClassAssignment;
use App\Models\Comment;
use App\Models\Documentation;
use App\Models\GithubMetadata;
use App\Models\GithubToken;
use App\Models\Project;
use App\Models\ProjectUrl;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Validation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoPortfolioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // 1. BUAT GURU (TEACHERS)
        // ==========================================
        $teacherData = [
            [
                'email' => 'hendra.rpl@portohub.test',
                'name' => 'Pak Hendra',
                'nip' => '198504120001',
                'specialization' => 'RPL',
                'department' => 'Rekayasa Perangkat Lunak',
            ],
            [
                'email' => 'dina.tkj@portohub.test',
                'name' => 'Bu Dina',
                'nip' => '199008230002',
                'specialization' => 'TKJ',
                'department' => 'Teknik Komputer dan Jaringan',
            ],
        ];

        $teachers = [];
        foreach ($teacherData as $td) {
            $user = User::updateOrCreate(
                ['email' => $td['email']],
                [
                    'name' => $td['name'],
                    'headline' => 'Computer Science Teacher | Tech Enthusiast',
                    'username' => Str::slug($td['name']),
                    'role' => 'teacher',
                    'password' => 'password',
                    'password_set_at' => now(),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $teachers[$td['email']] = Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nip' => $td['nip'],
                    'specialization' => $td['specialization'],
                    'department' => $td['department'],
                    'phone' => '0812' . rand(10000000, 99999999),
                    'address' => 'Malang, Jawa Timur',
                    'is_validated' => true,
                ]
            );
        }

        // ==========================================
        // 2. BUAT SISWA (STUDENTS) & PROYEK
        // ==========================================
        $students = collect([
            // --- APPROVED PROJECTS (DITAMPILKAN DI LANDING PAGE) ---
            [
                'email' => 'wafi@portohub.test',
                'name' => 'Wafi Saputra',
                'nis' => '2026001',
                'class' => 'XI RPL A',
                'teacher_email' => 'hendra.rpl@portohub.test',
                'is_validated' => true,
                'github_username' => 'wafi-saputra',
                'project' => [
                    'title' => 'EduTrack: Platform Manajemen Pembelajaran (LMS)',
                    'development_model' => 'agile',
                    'status' => 'approved',
                    'github_url' => 'https://github.com/portohub/edutrack-lms',
                    'description' => 'Sistem manajemen pembelajaran (LMS) modern berbasis web. Memiliki fitur pelacakan nilai, manajemen tugas, dan integrasi absensi secara real-time.',
                    'repo_name' => 'edutrack-lms',
                    'repo_owner' => 'wafi-saputra',
                    'language' => 'PHP',
                    'thumbnail_path' => 'thumbnails/edutrack_lms.png',
                    'commit_count' => 124,
                    'commit_frequency' => 18,
                    'submission_offset' => 18,
                    'approval_offset' => 6,
                    'validation_note' => 'Struktur kode stabil, dokumentasi lengkap, dan flow utama berjalan tanpa error. Layak untuk referensi publik.',
                ]
            ],
            [
                'email' => 'siti.aminah@portohub.test',
                'name' => 'Siti Aminah',
                'nis' => '2026002',
                'class' => 'XI RPL A',
                'teacher_email' => 'hendra.rpl@portohub.test',
                'is_validated' => true,
                'github_username' => 'siti-bakery',
                'project' => [
                    'title' => 'Sweet Bakery E-Commerce & Inventory',
                    'development_model' => 'waterfall',
                    'status' => 'approved',
                    'github_url' => 'https://github.com/portohub/sweet-bakery-pos',
                    'description' => 'Aplikasi kasir (POS) sekaligus etalase toko roti online. Memudahkan pelanggan melihat katalog kue dan pemilik toko untuk memanajemen stok bahan.',
                    'repo_name' => 'sweet-bakery-pos',
                    'repo_owner' => 'siti-bakery',
                    'language' => 'JavaScript',
                    'thumbnail_path' => 'thumbnails/ecommerce_bakery.png',
                    'commit_count' => 95,
                    'commit_frequency' => 10,
                    'submission_offset' => 20,
                    'approval_offset' => 5,
                    'validation_note' => 'Tampilan antarmuka sangat estetik dan fungsionalitas keranjang belanja berjalan mulus.',
                ]
            ],
            [
                'email' => 'budi.santoso@portohub.test',
                'name' => 'Budi Santoso',
                'nis' => '2026003',
                'class' => 'XI TKJ B',
                'teacher_email' => 'dina.tkj@portohub.test',
                'is_validated' => true,
                'github_username' => 'budi-cloud',
                'project' => [
                    'title' => 'Smart Cloud Storage Server',
                    'development_model' => 'other',
                    'status' => 'approved',
                    'github_url' => 'https://github.com/portohub/smart-cloud-storage',
                    'description' => 'Implementasi private cloud server menggunakan Nextcloud yang di-hosting pada Raspberry Pi untuk kebutuhan file sharing sekolah.',
                    'repo_name' => 'smart-cloud-storage',
                    'repo_owner' => 'budi-cloud',
                    'language' => 'Shell',
                    'thumbnail_path' => 'thumbnails/cloud_storage.png',
                    'commit_count' => 45,
                    'commit_frequency' => 5,
                    'submission_offset' => 15,
                    'approval_offset' => 3,
                    'validation_note' => 'Konfigurasi server sangat baik, setup firewall dan SSL certificate sudah diimplementasikan dengan aman.',
                ]
            ],
            [
                'email' => 'citra@portohub.test',
                'name' => 'Citra Kirana',
                'nis' => '2026004',
                'class' => 'XI RPL B',
                'teacher_email' => 'hendra.rpl@portohub.test',
                'is_validated' => true,
                'github_username' => 'citra-ai',
                'project' => [
                    'title' => 'AI Stock Market Prediction System',
                    'development_model' => 'agile',
                    'status' => 'approved',
                    'github_url' => 'https://github.com/portohub/ai-stock-predict',
                    'description' => 'Dashboard pintar yang menggunakan Machine Learning untuk memprediksi tren saham harian dengan visualisasi data candlestick yang interaktif.',
                    'repo_name' => 'ai-stock-predict',
                    'repo_owner' => 'citra-ai',
                    'language' => 'Python',
                    'thumbnail_path' => 'thumbnails/ai_stock.png',
                    'commit_count' => 112,
                    'commit_frequency' => 15,
                    'submission_offset' => 10,
                    'approval_offset' => 2,
                    'validation_note' => 'Algoritma prediksi berjalan cukup akurat dan UI dark mode-nya sangat luar biasa.',
                ]
            ],

            // --- UNDER REVIEW ---
            [
                'email' => 'nabila@portohub.test',
                'name' => 'Nabila Putri',
                'nis' => '2026005',
                'class' => 'XI RPL B',
                'teacher_email' => 'hendra.rpl@portohub.test',
                'is_validated' => true,
                'github_username' => 'nabila-putri',
                'project' => [
                    'title' => 'Smart POS & Inventori Apotek',
                    'development_model' => 'waterfall',
                    'status' => 'under_review',
                    'github_url' => 'https://github.com/portohub/smart-pos-apotek',
                    'description' => 'Aplikasi pencatatan stok obat dan Point of Sales (POS) khusus untuk apotek, dengan pengingat tanggal kedaluwarsa obat secara otomatis.',
                    'repo_name' => 'smart-pos-apotek',
                    'repo_owner' => 'nabila-putri',
                    'language' => 'PHP',
                    'thumbnail_path' => 'thumbnails/smart_pos.png',
                    'commit_count' => 68,
                    'commit_frequency' => 8,
                    'submission_offset' => 2,
                    'approval_offset' => null,
                    'validation_note' => 'Dokumentasi sudah cukup baik, tapi periksa kembali keamanan pada fitur login dan otorisasi level kasir.',
                ]
            ],

            // --- REJECTED ---
            [
                'email' => 'tupaikidal@portohub.test',
                'name' => 'Tupai Kidal',
                'nis' => '2026099',
                'class' => 'X TKJ A',
                'teacher_email' => 'dina.tkj@portohub.test',
                'is_validated' => true,
                'github_username' => 'tupaikidal-dev',
                'project' => [
                    'title' => 'Sistem Monitoring Jaringan SNMP',
                    'development_model' => 'other',
                    'status' => 'rejected',
                    'github_url' => 'https://github.com/tupaikidal/snmp-network-monitor',
                    'description' => 'Dashboard monitoring jaringan berbasis SNMP untuk memantau penggunaan bandwidth dan uptime server sekolah secara real-time.',
                    'repo_name' => 'snmp-network-monitor',
                    'repo_owner' => 'tupaikidal',
                    'language' => 'C++',
                    'thumbnail_path' => 'thumbnails/snmp_monitor.png',
                    'commit_count' => 34,
                    'commit_frequency' => 4,
                    'submission_offset' => 5,
                    'approval_offset' => null,
                    'validation_note' => 'Konfigurasi SSH server dan alert system masih error. Topologi jaringan di dokumen juga belum lengkap.',
                    'rejection_reason' => 'Keamanan SSH masih terbuka untuk publik, harap amankan dengan public key authentication.',
                ]
            ],

            // --- UNVALIDATED ---
            [
                'email' => 'roni@portohub.test',
                'name' => 'Roni Pratama',
                'nis' => '2026006',
                'class' => 'XI RPL A',
                'teacher_email' => 'hendra.rpl@portohub.test',
                'is_validated' => false, // BELUM DIVALIDASI ADMIN
                'github_username' => 'roni-pratama',
                'project' => null // Tidak punya proyek
            ],
        ]);

        foreach ($students as $studentData) {
            $user = User::updateOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'headline' => 'Junior Web Developer | Vocational High School Student',
                    'username' => Str::slug($studentData['name']),
                    'linkedin_username' => Str::slug($studentData['name']),
                    'role' => 'student',
                    'password' => 'password',
                    'password_set_at' => now(),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nis' => $studentData['nis'],
                    'year' => 2024,
                    'phone' => '0812' . substr($studentData['nis'], -6),
                    'address' => 'Malang, Jawa Timur',
                    'is_validated' => $studentData['is_validated'],
                ]
            );

            GithubToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'github_id' => rand(1000000, 9999999),
                    'github_username' => $studentData['github_username'],
                    'access_token' => 'dummy_token_'.Str::random(10),
                    'is_active' => true,
                ]
            );

            $assignedTeacher = $teachers[$studentData['teacher_email']];

            ClassAssignment::updateOrCreate(
                [
                    'teacher_id' => $assignedTeacher->id,
                    'student_id' => $student->id,
                    'class' => $studentData['class'],
                    'semester' => 2,
                ],
                [
                    'is_active' => true,
                ]
            );

            if ($studentData['project']) {
                $projData = $studentData['project'];
                
                $project = Project::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'title' => $projData['title'],
                    ],
                    [
                        'description' => $projData['description'],
                        'development_model' => $projData['development_model'],
                        'github_url' => $projData['github_url'],
                        'status' => $projData['status'],
                        'submission_date' => now()->subDays($projData['submission_offset']),
                        'approval_date' => $projData['approval_offset'] ? now()->subDays($projData['approval_offset']) : null,
                        'rejection_reason' => $projData['rejection_reason'] ?? null,
                        'thumbnail_path' => $projData['thumbnail_path'] ?? null,
                    ]
                );

                ProjectUrl::updateOrCreate(
                    ['project_id' => $project->id, 'url_type' => 'live_demo'],
                    [
                        'url' => 'https://demo.portohub.test/'.Str::slug($projData['title']),
                        'description' => 'Live demo proyek',
                        'is_public' => true,
                    ]
                );

                ProjectUrl::updateOrCreate(
                    ['project_id' => $project->id, 'url_type' => 'documentation'],
                    [
                        'url' => 'https://docs.portohub.test/'.Str::slug($projData['title']),
                        'description' => 'Dokumentasi proyek',
                        'is_public' => true,
                    ]
                );

                Documentation::updateOrCreate(
                    ['project_id' => $project->id, 'file_name' => $projData['title'].' Guide.pdf'],
                    [
                        'doc_type' => 'pdf',
                        'file_path' => 'docs/'.Str::slug($projData['title']).'-guide.pdf',
                        'file_size' => 2048000,
                        'mime_type' => 'application/pdf',
                        'description' => 'Panduan implementasi dan penggunaan proyek.',
                        'is_public' => true,
                    ]
                );

                GithubMetadata::updateOrCreate(
                    ['project_id' => $project->id],
                    [
                        'repo_name' => $projData['repo_name'],
                        'repo_owner' => $projData['repo_owner'],
                        'repo_url' => $projData['github_url'],
                        'commit_count' => $projData['commit_count'],
                        'last_commit_at' => now()->subDays(1),
                        'last_commit_message' => 'Final polish and validation improvements',
                        'commit_frequency' => $projData['commit_frequency'],
                        'language' => $projData['language'],
                        'stars' => rand(0, 50),
                        'forks' => rand(0, 10),
                        'is_public' => true,
                        'last_synced_at' => now(),
                    ]
                );

                // Buat Skor & Komentar berdasarkan Status
                if ($projData['status'] !== 'submitted') {
                    Validation::updateOrCreate(
                        ['project_id' => $project->id],
                        [
                            'teacher_id' => $assignedTeacher->id,
                            'functionality_score' => $projData['status'] === 'approved' ? rand(88, 98) : ($projData['status'] === 'rejected' ? rand(40, 65) : rand(75, 85)),
                            'code_quality_score' => $projData['status'] === 'approved' ? rand(85, 95) : ($projData['status'] === 'rejected' ? rand(50, 60) : rand(70, 80)),
                            'documentation_score' => $projData['status'] === 'approved' ? rand(90, 100) : ($projData['status'] === 'rejected' ? rand(45, 55) : rand(70, 78)),
                            'originality_score' => $projData['status'] === 'approved' ? rand(85, 95) : ($projData['status'] === 'rejected' ? rand(60, 70) : rand(75, 85)),
                            'is_approved' => $projData['status'] === 'approved',
                            'validation_date' => $projData['status'] === 'approved' ? now()->subDays(2) : null,
                            'notes' => $projData['validation_note'],
                        ]
                    );

                    Comment::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'teacher_id' => $assignedTeacher->id,
                            'comment_type' => 'general',
                        ],
                        [
                            'content' => $projData['validation_note'],
                            'status' => $projData['status'] === 'approved' ? 'resolved' : 'pending',
                            'is_pinned' => $projData['status'] === 'approved' || $projData['status'] === 'rejected',
                        ]
                    );
                }
            }
        }
    }
}

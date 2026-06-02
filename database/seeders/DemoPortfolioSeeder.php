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
            [
                'email' => 'wafi@portohub.test',
                'name' => 'Wafi Saputra',
                'nis' => '2026001',
                'class' => 'XI RPL A',
                'teacher_email' => 'hendra.rpl@portohub.test',
                'is_validated' => true,
                'github_username' => 'wafi-saputra',
                'project' => [
                    'title' => 'FinTech Dashboard',
                    'development_model' => 'agile',
                    'status' => 'approved',
                    'github_url' => 'https://github.com/portohub/fintech-dashboard',
                    'description' => 'Dashboard keuangan dengan visualisasi arus kas, kontrol role-based access, dan dokumentasi deployment yang rapi.',
                    'repo_name' => 'fintech-dashboard',
                    'repo_owner' => 'portohub',
                    'language' => 'PHP',
                    'commit_count' => 86,
                    'commit_frequency' => 12,
                    'submission_offset' => 18,
                    'approval_offset' => 6,
                    'validation_note' => 'Struktur kode stabil, dokumentasi lengkap, dan flow utama berjalan tanpa error. Layak untuk referensi publik.',
                ]
            ],
            [
                'email' => 'nabila@portohub.test',
                'name' => 'Nabila Putri',
                'nis' => '2026002',
                'class' => 'XI RPL B',
                'teacher_email' => 'hendra.rpl@portohub.test',
                'is_validated' => true,
                'github_username' => 'nabila-putri',
                'project' => [
                    'title' => 'Learning Archive System',
                    'development_model' => 'waterfall',
                    'status' => 'under_review',
                    'github_url' => 'https://github.com/portohub/learning-archive-system',
                    'description' => 'Sistem arsip karya siswa untuk menampung proyek yang sudah tervalidasi dan siap dipakai sebagai referensi publik.',
                    'repo_name' => 'learning-archive-system',
                    'repo_owner' => 'portohub',
                    'language' => 'PHP',
                    'commit_count' => 41,
                    'commit_frequency' => 6,
                    'submission_offset' => 2,
                    'approval_offset' => null,
                    'validation_note' => 'Dokumentasi sudah cukup baik, tapi periksa kembali keamanan pada fitur login.',
                ]
            ],
            [
                'email' => 'tupaikidal@portohub.test',
                'name' => 'Tupai Kidal',
                'nis' => '2026099',
                'class' => 'X TKJ A',
                'teacher_email' => 'dina.tkj@portohub.test',
                'is_validated' => true,
                'github_username' => 'tupaikidal-dev',
                'project' => [
                    'title' => 'IoT Smart Farm Monitoring',
                    'development_model' => 'other',
                    'status' => 'rejected',
                    'github_url' => 'https://github.com/tupaikidal/iot-smart-farm',
                    'description' => 'Sistem pemantauan lahan pertanian cerdas menggunakan IoT dan Arduino.',
                    'repo_name' => 'iot-smart-farm',
                    'repo_owner' => 'tupaikidal',
                    'language' => 'C++',
                    'commit_count' => 12,
                    'commit_frequency' => 2,
                    'submission_offset' => 5,
                    'approval_offset' => null,
                    'validation_note' => 'Sistem masih memiliki beberapa bug saat sensor terputus. Silakan perbaiki logic error handling-nya.',
                    'rejection_reason' => 'Proyek dikembalikan untuk diperbaiki (error handling sensor gagal).',
                ]
            ],
            [
                'email' => 'roni@portohub.test',
                'name' => 'Roni Pratama',
                'nis' => '2026003',
                'class' => 'XI RPL A',
                'teacher_email' => 'hendra.rpl@portohub.test',
                'is_validated' => false, // BELUM DIVALIDASI ADMIN
                'github_username' => 'roni-pratama',
                'project' => null // Tidak punya proyek karena belum tervalidasi
            ],
        ]);

        foreach ($students as $studentData) {
            $user = User::updateOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
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
                            'functionality_score' => $projData['status'] === 'approved' ? 91.25 : ($projData['status'] === 'rejected' ? 50.00 : 82.50),
                            'code_quality_score' => $projData['status'] === 'approved' ? 89.50 : ($projData['status'] === 'rejected' ? 60.00 : 78.00),
                            'documentation_score' => $projData['status'] === 'approved' ? 92.00 : ($projData['status'] === 'rejected' ? 55.00 : 74.50),
                            'originality_score' => $projData['status'] === 'approved' ? 88.25 : ($projData['status'] === 'rejected' ? 70.00 : 80.00),
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

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
        $teacherUser = User::updateOrCreate(
            ['email' => 'hendra.rpl@portohub.test'],
            [
                'name' => 'Pak Hendra',
                'role' => 'teacher',
                'password' => 'password',
                'password_set_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $teacher = Teacher::updateOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'nip' => '198504120001',
                'specialization' => 'RPL',
                'department' => 'Rekayasa Perangkat Lunak',
                'phone' => '081234567801',
                'address' => 'SMKN 5 Malang',
                'is_validated' => true,
            ]
        );

        $students = collect([
            [
                'email' => 'wafi@portohub.test',
                'name' => 'Wafi Saputra',
                'nis' => '2026001',
                'class' => 'XI RPL A',
                'github_username' => 'wafi-saputra',
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
                'validation_note' => 'Struktur kode stabil, dokumentasi lengkap, dan flow utama berjalan tanpa error.',
            ],
            [
                'email' => 'roni@portohub.test',
                'name' => 'Roni Pratama',
                'nis' => '2026002',
                'class' => 'XI RPL A',
                'github_username' => 'roni-pratama',
                'title' => 'Habit Tracker with AI Insight',
                'development_model' => 'waterfall',
                'status' => 'approved',
                'github_url' => 'https://github.com/portohub/habit-tracker-ai',
                'description' => 'Aplikasi habit tracker dengan reminder pintar, ringkasan progres, dan insight kebiasaan yang mudah dibaca.',
                'repo_name' => 'habit-tracker-ai',
                'repo_owner' => 'portohub',
                'language' => 'JavaScript',
                'commit_count' => 64,
                'commit_frequency' => 9,
                'submission_offset' => 12,
                'approval_offset' => 3,
                'validation_note' => 'Komponen antarmuka rapi dan proses validasi menunjukkan pemahaman yang baik terhadap alur fitur.',
            ],
            [
                'email' => 'nabila@portohub.test',
                'name' => 'Nabila Putri',
                'nis' => '2026003',
                'class' => 'XI RPL B',
                'github_username' => 'nabila-putri',
                'title' => 'Learning Archive System',
                'development_model' => 'other',
                'status' => 'under_review',
                'github_url' => 'https://github.com/portohub/learning-archive-system',
                'description' => 'Sistem arsip karya siswa untuk menampung proyek yang sudah tervalidasi dan siap dipakai sebagai referensi publik.',
                'repo_name' => 'learning-archive-system',
                'repo_owner' => 'portohub',
                'language' => 'PHP',
                'commit_count' => 41,
                'commit_frequency' => 6,
                'submission_offset' => 5,
                'approval_offset' => null,
                'validation_note' => 'Perlu penyempurnaan pada dokumentasi dan finalisasi feedback terakhir dari guru.',
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
                ]
            );

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nis' => $studentData['nis'],
                    'year' => 2024,
                    'phone' => '0812'.substr($studentData['nis'], -6),
                    'address' => 'Malang, Jawa Timur',
                    'is_validated' => true,
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

            ClassAssignment::updateOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'class' => $studentData['class'],
                    'semester' => 2,
                ],
                [
                    'is_active' => true,
                ]
            );

            $project = Project::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'title' => $studentData['title'],
                ],
                [
                    'description' => $studentData['description'],
                    'development_model' => $studentData['development_model'],
                    'github_url' => $studentData['github_url'],
                    'status' => $studentData['status'],
                    'submission_date' => now()->subDays($studentData['submission_offset']),
                    'approval_date' => $studentData['approval_offset'] ? now()->subDays($studentData['approval_offset']) : null,
                    'rejection_reason' => null,
                ]
            );

            ProjectUrl::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'url_type' => 'live_demo',
                ],
                [
                    'url' => 'https://demo.portohub.test/'.$studentData['title'],
                    'description' => 'Live demo proyek',
                    'is_public' => true,
                ]
            );

            ProjectUrl::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'url_type' => 'documentation',
                ],
                [
                    'url' => 'https://docs.portohub.test/'.$studentData['title'],
                    'description' => 'Dokumentasi proyek',
                    'is_public' => true,
                ]
            );

            Documentation::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'file_name' => $studentData['title'].' Guide.pdf',
                ],
                [
                    'doc_type' => 'pdf',
                    'file_path' => 'docs/'.str($studentData['title'])->slug('-').'-guide.pdf',
                    'file_size' => 2048000,
                    'mime_type' => 'application/pdf',
                    'description' => 'Panduan implementasi dan penggunaan proyek.',
                    'is_public' => true,
                ]
            );

            GithubMetadata::updateOrCreate(
                [
                    'project_id' => $project->id,
                ],
                [
                    'repo_name' => $studentData['repo_name'],
                    'repo_owner' => $studentData['repo_owner'],
                    'repo_url' => $studentData['github_url'],
                    'commit_count' => $studentData['commit_count'],
                    'last_commit_at' => now()->subDays(1),
                    'last_commit_message' => 'Final polish and validation improvements',
                    'commit_frequency' => $studentData['commit_frequency'],
                    'language' => $studentData['language'],
                    'stars' => 0,
                    'forks' => 0,
                    'is_public' => true,
                    'last_synced_at' => now(),
                ]
            );

            Validation::updateOrCreate(
                [
                    'project_id' => $project->id,
                ],
                [
                    'teacher_id' => $teacher->id,
                    'functionality_score' => $studentData['status'] === 'under_review' ? 82.50 : 91.25,
                    'code_quality_score' => $studentData['status'] === 'under_review' ? 78.00 : 89.50,
                    'documentation_score' => $studentData['status'] === 'under_review' ? 74.50 : 92.00,
                    'originality_score' => $studentData['status'] === 'under_review' ? 80.00 : 88.25,
                    'is_approved' => $studentData['status'] === 'approved',
                    'validation_date' => $studentData['status'] === 'approved' ? now()->subDays(2) : null,
                    'notes' => $studentData['validation_note'],
                ]
            );

            Comment::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'teacher_id' => $teacher->id,
                    'comment_type' => 'general',
                ],
                [
                    'content' => $studentData['status'] === 'approved'
                        ? 'Proyek sudah layak ditampilkan sebagai referensi portfolio publik.'
                        : 'Lengkapi dokumentasi dan lakukan validasi ulang sebelum diarsipkan.',
                    'status' => $studentData['status'] === 'approved' ? 'resolved' : 'pending',
                    'is_pinned' => $studentData['status'] === 'approved',
                ]
            );
        }
    }
}

<?php

namespace App\Livewire\Public;

use App\Models\User;
use Livewire\Component;

class StudentCv extends Component
{
    public $student;
    public $projects;
    public $stats = [];
    public $topSkills = [];

    public function mount($username)
    {
        // 1. Fetch user by username
        $user = User::where('username', $username)->firstOrFail();

        // 2. Make sure it's a student
        if (!$user->isStudent()) {
            abort(404);
        }

        // 3. Authorization: Only the profile owner can view their own CV
        if (!auth()->check() || auth()->id() !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk melihat CV milik pengguna lain.');
        }

        // 4. Get the student profile and relations
        $this->student = $user->student()->with(['user.githubToken'])->firstOrFail();

        // 4. Fetch approved projects
        $this->projects = $this->student->projects()
            ->where('status', 'approved')
            ->where('visibility', 'public')
            ->with(['validation', 'githubMetadata'])
            ->latest()
            ->get();

        $this->calculateStats();
    }

    private function calculateStats()
    {
        $this->stats['total_projects'] = $this->projects->count();

        $totalCommits = 0;
        $totalScore = 0;
        $scoreCount = 0;
        $techCount = [];

        foreach ($this->projects as $project) {
            // Commits
            if ($project->githubMetadata) {
                $totalCommits += $project->githubMetadata->commit_count ?? 0;
            }

            // Scores
            if ($project->validation) {
                $avg = ($project->validation->functionality_score +
                    $project->validation->code_quality_score +
                    $project->validation->documentation_score +
                    $project->validation->originality_score) / 4;
                $totalScore += $avg;
                $scoreCount++;
            }

            // Skills aggregation
            if (is_array($project->tech_stack)) {
                foreach ($project->tech_stack as $tech) {
                    $techCount[$tech] = ($techCount[$tech] ?? 0) + 1;
                }
            }
        }

        $this->stats['total_commits'] = $totalCommits;
        $this->stats['avg_score'] = $scoreCount > 0 ? round($totalScore / $scoreCount, 1) : 0;

        // Sort tech stack by count descending
        arsort($techCount);
        $this->topSkills = $techCount; // Keep all skills for CV
    }

    public function render()
    {
        return view('livewire.public.student-cv')
            ->layout('components.layouts.print', [
                'title' => 'CV - ' . $this->student->user->name
            ]);
    }
}

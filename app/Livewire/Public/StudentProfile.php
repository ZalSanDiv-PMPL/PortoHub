<?php

namespace App\Livewire\Public;

use App\Models\Project;
use App\Models\Student;
use Livewire\Component;

class StudentProfile extends Component
{
    public $studentId;

    public $student;

    public $projects = [];

    public $topSkills = [];

    public $stats = [
        'total_projects' => 0,
        'total_commits' => 0,
        'avg_score' => 0,
    ];

    public function mount($id)
    {
        $this->studentId = $id;

        $this->student = Student::with(['user.githubToken', 'classAssignments'])->findOrFail($id);

        $this->projects = Project::where('student_id', $id)
            ->publiclyVisible()
            ->where('status', 'approved')
            ->with(['validation', 'githubMetadata'])
            ->orderBy('created_at', 'desc')
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

            // Tech Stack
            if (! empty($project->tech_stack) && is_array($project->tech_stack)) {
                foreach ($project->tech_stack as $tech) {
                    if (! isset($techCount[$tech])) {
                        $techCount[$tech] = 0;
                    }
                    $techCount[$tech]++;
                }
            }
        }

        $this->stats['total_commits'] = $totalCommits;
        $this->stats['avg_score'] = $scoreCount > 0 ? round($totalScore / $scoreCount, 1) : 0;

        // Sort tech stack by count descending
        arsort($techCount);
        $this->topSkills = array_slice($techCount, 0, 5, true); // Get top 5
    }

    public function render()
    {
        return view('livewire.public.student-profile')->layout('components.layouts.public');
    }
}

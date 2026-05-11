<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index()
    {
        $projects = Project::query()
            ->with(['student.user', 'githubMetadata', 'validations', 'urls'])
            ->whereIn('status', ['approved', 'under_review', 'submitted'])
            ->orderByDesc('approval_date')
            ->orderByDesc('submission_date')
            ->get()
            ->sort(function (Project $left, Project $right) {
                $priority = [
                    'approved' => 0,
                    'under_review' => 1,
                    'submitted' => 2,
                ];

                $leftPriority = $priority[$left->status] ?? 3;
                $rightPriority = $priority[$right->status] ?? 3;

                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                $leftDate = $left->approval_date ?? $left->submission_date ?? $left->created_at;
                $rightDate = $right->approval_date ?? $right->submission_date ?? $right->created_at;

                return $rightDate <=> $leftDate;
            })
            ->values()
            ->map(fn (Project $project) => $this->toProjectCard($project));

        return view('welcome', [
            'projects' => $projects,
            'featuredProject' => $projects->first(),
        ]);
    }

    private function toProjectCard(Project $project): array
    {
        $liveDemo = $project->urls->firstWhere('url_type', 'live_demo');

        return [
            'id' => $project->id,
            'title' => $project->title,
            'type' => ucfirst($project->development_model),
            'summary' => Str::limit($project->description ?? 'Dokumentasi proyek PortoHub.', 120),
            'image' => 'https://placehold.co/800x600?text=' . urlencode($project->title),
            'student_name' => $project->student?->user?->name ?? 'Siswa PortoHub',
            'class' => $project->student?->class ?? '-',
            'status' => $project->status,
            'github_url' => $project->github_url,
            'live_demo_url' => $liveDemo?->url,
            'repository_name' => $project->githubMetadata?->repo_name,
            'language' => $project->githubMetadata?->language,
            'commit_count' => $project->githubMetadata?->commit_count,
        ];
    }
}

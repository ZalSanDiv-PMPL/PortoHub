<?php

namespace App\Livewire\Layout;

use App\Models\Project;
use App\Models\User;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';
    public array $studentResults = [];
    public array $projectResults = [];

    public function updatedQuery()
    {
        $this->studentResults = [];
        $this->projectResults = [];

        $search = trim($this->query);

        if (strlen($search) >= 2) {
            // Search for students
            $this->studentResults = User::where('role', 'student')
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('username', 'like', '%' . $search . '%')
                      ->orWhereHas('student', function ($sq) use ($search) {
                          $sq->where('nis', 'like', '%' . $search . '%');
                      });
                })
                ->take(3)
                ->get()
                ->toArray();

            // Search for projects (only approved and public)
            $this->projectResults = Project::where('status', 'approved')
                ->where('visibility', 'public')
                ->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhereJsonContains('tech_stack', $search);
                })
                ->with('student.user')
                ->take(4)
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'title' => $project->title,
                        'slug' => \Illuminate\Support\Str::slug($project->title) . '-' . $project->id,
                        'author_name' => $project->student->user->name ?? 'Unknown',
                    ];
                })
                ->toArray();
        }
    }

    public function render()
    {
        return view('livewire.layout.global-search');
    }
}

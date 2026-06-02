<?php

namespace App\Http\Controllers;

use App\Models\Project;

class ProjectController extends Controller
{
    public function show(Project $project)
    {
        if ($project->status !== 'approved' || $project->visibility !== 'public') {
            abort(404);
        }

        $project->load([
            'student.user.githubToken',
            'student.classAssignments',
            'validation',
            'githubMetadata',
            'documentation',
            'urls',
            'comments.teacher.user',
        ]);

        return view('project-detail', compact('project'));
    }
}

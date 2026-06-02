<?php

namespace App\Http\Controllers;

use App\Models\Project;

class LandingPageController extends Controller
{
    public function index()
    {
        $featuredProjects = Project::query()
            ->with('student.user.githubToken')
            ->where('status', 'approved')
            ->publiclyVisible()
            ->orderByDesc('approval_date')
            ->take(4)
            ->get();

        return view('welcome', compact('featuredProjects'));
    }
}

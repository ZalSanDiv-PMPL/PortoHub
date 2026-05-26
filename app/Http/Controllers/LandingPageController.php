<?php

namespace App\Http\Controllers;

class LandingPageController extends Controller
{
    public function index()
    {
        $featuredProjects = \App\Models\Project::query()
            ->with('student.user')
            ->where('status', 'approved')
            ->orderByDesc('approval_date')
            ->take(4)
            ->get();

        return view('welcome', compact('featuredProjects'));
    }
}

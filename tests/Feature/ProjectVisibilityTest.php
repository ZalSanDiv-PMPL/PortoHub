<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_project_appears_in_gallery(): void
    {
        $project = Project::factory()->approved()->create(['visibility' => 'public']);

        $response = $this->get(route('gallery'));

        $response->assertStatus(200);
    }

    public function test_public_project_detail_page_accessible(): void
    {
        $project = Project::factory()->approved()->create(['visibility' => 'public']);

        $response = $this->get(route('project.show', $project));

        $response->assertStatus(200);
        $response->assertSee($project->title);
    }

    public function test_private_project_detail_returns_404(): void
    {
        $project = Project::factory()->approved()->create(['visibility' => 'private']);

        $response = $this->get(route('project.show', $project));

        $response->assertStatus(404);
    }

    public function test_restricted_project_detail_returns_404(): void
    {
        $project = Project::factory()->approved()->create(['visibility' => 'restricted']);

        $response = $this->get(route('project.show', $project));

        $response->assertStatus(404);
    }

    public function test_unapproved_project_detail_returns_404(): void
    {
        $project = Project::factory()->create(['visibility' => 'public', 'status' => 'submitted']);

        $response = $this->get(route('project.show', $project));

        $response->assertStatus(404);
    }
}

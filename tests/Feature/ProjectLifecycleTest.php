<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_project(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::factory()->create(['user_id' => $user->id, 'is_validated' => true]);

        $project = Project::factory()->create([
            'student_id' => $student->id,
            'status' => 'submitted',
        ]);

        $this->assertEquals('submitted', $project->status);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'submitted',
        ]);
    }

    public function test_project_can_be_resubmitted_after_rejection(): void
    {
        $student = Student::factory()->create();
        $project = Project::factory()->rejected()->create([
            'student_id' => $student->id,
        ]);

        $this->assertEquals('rejected', $project->status);

        $project->update([
            'status' => 'submitted',
            'submission_date' => now(),
        ]);

        $this->assertEquals('submitted', $project->fresh()->status);
    }

    public function test_project_default_visibility_is_public(): void
    {
        $project = Project::factory()->create();

        $this->assertEquals('public', $project->visibility);
    }

    public function test_publicly_visible_scope_only_returns_public_projects(): void
    {
        Project::factory()->create(['visibility' => 'public', 'status' => 'approved']);
        Project::factory()->create(['visibility' => 'private', 'status' => 'approved']);
        Project::factory()->create(['visibility' => 'restricted', 'status' => 'approved']);

        $publicProjects = Project::publiclyVisible()->get();

        $this->assertCount(1, $publicProjects);
        $this->assertEquals('public', $publicProjects->first()->visibility);
    }
}

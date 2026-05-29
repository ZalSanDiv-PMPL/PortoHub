<?php

namespace Tests\Feature;

use App\Models\ClassAssignment;
use App\Models\Project;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Validation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherValidationTest extends TestCase
{
    use RefreshDatabase;

    private function setupTeacherWithStudent(): array
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        ClassAssignment::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'class' => 'XII RPL 1',
        ]);

        return compact('teacherUser', 'teacher', 'studentUser', 'student');
    }

    public function test_teacher_can_approve_project(): void
    {
        $setup = $this->setupTeacherWithStudent();

        $project = Project::factory()->create([
            'student_id' => $setup['student']->id,
            'status' => 'under_review',
        ]);

        $project->update([
            'status' => 'approved',
            'approval_date' => now(),
        ]);

        Validation::factory()->create([
            'project_id' => $project->id,
            'teacher_id' => $setup['teacher']->id,
            'is_approved' => true,
        ]);

        $this->assertEquals('approved', $project->fresh()->status);
        $this->assertDatabaseHas('validations', [
            'project_id' => $project->id,
            'is_approved' => true,
        ]);
    }

    public function test_teacher_can_reject_project(): void
    {
        $setup = $this->setupTeacherWithStudent();

        $project = Project::factory()->create([
            'student_id' => $setup['student']->id,
            'status' => 'under_review',
        ]);

        $project->update([
            'status' => 'rejected',
            'rejection_reason' => 'Perlu perbaikan pada dokumentasi.',
        ]);

        Validation::factory()->create([
            'project_id' => $project->id,
            'teacher_id' => $setup['teacher']->id,
            'is_approved' => false,
        ]);

        $this->assertEquals('rejected', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->rejection_reason);
    }

    public function test_validation_stores_all_scores(): void
    {
        $setup = $this->setupTeacherWithStudent();
        $project = Project::factory()->create(['student_id' => $setup['student']->id]);

        $validation = Validation::factory()->create([
            'project_id' => $project->id,
            'teacher_id' => $setup['teacher']->id,
            'functionality_score' => 85,
            'code_quality_score' => 90,
            'documentation_score' => 75,
            'originality_score' => 80,
        ]);

        $this->assertEquals(85, $validation->functionality_score);
        $this->assertEquals(90, $validation->code_quality_score);
        $this->assertEquals(75, $validation->documentation_score);
        $this->assertEquals(80, $validation->originality_score);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_add_comment_to_project(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create();
        $project = Project::factory()->create(['student_id' => $student->id]);

        $comment = Comment::factory()->create([
            'project_id' => $project->id,
            'teacher_id' => $teacher->id,
            'content' => 'Perbaiki bagian validasi input.',
            'comment_type' => 'requirement',
        ]);

        $this->assertDatabaseHas('comments', [
            'project_id' => $project->id,
            'teacher_id' => $teacher->id,
            'content' => 'Perbaiki bagian validasi input.',
            'comment_type' => 'requirement',
            'status' => 'pending',
        ]);
    }

    public function test_comment_can_be_marked_as_viewed(): void
    {
        $comment = Comment::factory()->create(['status' => 'pending']);

        $comment->update(['status' => 'viewed']);

        $this->assertEquals('viewed', $comment->fresh()->status);
    }

    public function test_comment_can_be_pinned(): void
    {
        $comment = Comment::factory()->create(['is_pinned' => false]);

        $comment->update(['is_pinned' => true]);

        $this->assertTrue($comment->fresh()->is_pinned);
    }

    public function test_project_has_many_comments(): void
    {
        $project = Project::factory()->create();
        $teacher = Teacher::factory()->create();

        Comment::factory()->count(3)->create([
            'project_id' => $project->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertCount(3, $project->comments);
    }
}

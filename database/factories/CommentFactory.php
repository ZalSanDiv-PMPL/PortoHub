<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'teacher_id' => Teacher::factory(),
            'content' => $this->faker->paragraph(),
            'comment_type' => $this->faker->randomElement(['general', 'code_review', 'requirement', 'suggestion']),
            'status' => 'pending',
            'is_pinned' => false,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }

    public function viewed(): static
    {
        return $this->state(fn () => ['status' => 'viewed']);
    }
}

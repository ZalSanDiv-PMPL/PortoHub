<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification
{
    use Queueable;

    public $project;
    public $teacher;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, Teacher $teacher)
    {
        $this->project = $project;
        $this->teacher = $teacher;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_comment',
            'project_id' => $this->project->id,
            'project_title' => $this->project->title,
            'teacher_id' => $this->teacher->id,
            'teacher_name' => $this->teacher->user->name,
            'message' => "Guru {$this->teacher->user->name} memberikan komentar pada proyek \"{$this->project->title}\" Anda.",
            'url' => route('project.show', $this->project->id),
        ];
    }
}

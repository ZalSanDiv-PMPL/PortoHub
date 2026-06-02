<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProjectSubmitted extends Notification
{
    use Queueable;

    public $project;
    public $student;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, Student $student)
    {
        $this->project = $project;
        $this->student = $student;
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
            'type' => 'project_submitted',
            'project_id' => $this->project->id,
            'project_title' => $this->project->title,
            'student_id' => $this->student->id,
            'student_name' => $this->student->user->name,
            'message' => "Siswa {$this->student->user->name} telah mengunggah proyek \"{$this->project->title}\" untuk direviu.",
            'url' => route('project.show', $this->project->id),
        ];
    }
}

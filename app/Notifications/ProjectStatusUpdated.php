<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProjectStatusUpdated extends Notification
{
    use Queueable;

    public $project;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
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
        $statusLabels = [
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak/Revisi',
            'under_review' => 'Sedang Direviu',
            'submitted' => 'Menunggu Reviu',
            'archived' => 'Diarsipkan',
            'draft' => 'Draft',
        ];

        $statusText = $statusLabels[$this->project->status] ?? $this->project->status;

        $notes = null;
        if ($this->project->status === 'rejected') {
            $notes = $this->project->rejection_reason;
        } elseif ($this->project->status === 'approved' && $this->project->validation) {
            $notes = $this->project->validation->notes;
        }

        return [
            'type' => 'project_status_updated',
            'project_id' => $this->project->id,
            'project_title' => $this->project->title,
            'status' => $this->project->status,
            'message' => "Proyek \"{$this->project->title}\" Anda telah diperbarui statusnya menjadi {$statusText}.",
            'notes' => $notes,
            'url' => route('project.show', $this->project->id),
        ];
    }
}

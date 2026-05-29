<?php

namespace Tests\Feature;

use App\Models\Documentation;
use App\Models\Project;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentationUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_documentation_record_can_be_created(): void
    {
        $project = Project::factory()->create();

        $doc = Documentation::create([
            'project_id' => $project->id,
            'doc_type' => 'pdf',
            'file_name' => 'manual.pdf',
            'file_path' => 'documentation/1/manual.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            'description' => 'Manual pengguna',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('documentation', [
            'project_id' => $project->id,
            'doc_type' => 'pdf',
            'file_name' => 'manual.pdf',
        ]);
    }

    public function test_project_has_many_documentation(): void
    {
        $project = Project::factory()->create();

        Documentation::create([
            'project_id' => $project->id,
            'doc_type' => 'pdf',
            'file_name' => 'doc1.pdf',
            'file_path' => 'documentation/1/doc1.pdf',
            'file_size' => 512000,
            'mime_type' => 'application/pdf',
            'is_public' => true,
        ]);

        Documentation::create([
            'project_id' => $project->id,
            'doc_type' => 'video',
            'file_name' => 'demo.mp4',
            'file_path' => 'documentation/1/demo.mp4',
            'file_size' => 5120000,
            'mime_type' => 'video/mp4',
            'is_public' => true,
        ]);

        $this->assertCount(2, $project->documentation);
    }

    public function test_public_docs_are_filtered_correctly(): void
    {
        $project = Project::factory()->create();

        Documentation::create([
            'project_id' => $project->id,
            'doc_type' => 'pdf',
            'file_name' => 'public.pdf',
            'file_path' => 'documentation/1/public.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'is_public' => true,
        ]);

        Documentation::create([
            'project_id' => $project->id,
            'doc_type' => 'pdf',
            'file_name' => 'private.pdf',
            'file_path' => 'documentation/1/private.pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'is_public' => false,
        ]);

        $publicDocs = $project->documentation->where('is_public', true);
        $this->assertCount(1, $publicDocs);
        $this->assertEquals('public.pdf', $publicDocs->first()->file_name);
    }
}

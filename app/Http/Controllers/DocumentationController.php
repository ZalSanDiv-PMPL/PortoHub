<?php

namespace App\Http\Controllers;

use App\Models\Documentation;
use Illuminate\Support\Facades\Storage;

class DocumentationController extends Controller
{
  public function download(Documentation $documentation)
  {
    $project = $documentation->project()->with('student')->first();

    if (! $project) {
      abort(404);
    }

    $user = auth()->user();
    $isOwner = $user && $project->student && $project->student->user_id === $user->id;
    $isPublic = $documentation->is_public
      && $project->status === 'approved'
      && $project->visibility === 'public';

    if (! $isPublic && ! $isOwner) {
      abort(404);
    }

    $disk = Storage::disk('local')->exists($documentation->file_path) ? 'local' : 'public';
    $name = $documentation->file_name ?: basename($documentation->file_path);

    return Storage::disk($disk)->download($documentation->file_path, $name);
  }
}

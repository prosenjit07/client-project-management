<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ProcessProjectAttachments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public int $projectId;
    /** @var array<int, UploadedFile> */
    public array $attachments;

    /**
     * @param array<int, UploadedFile> $attachments
     */
    public function __construct(int $projectId, array $attachments)
    {
        $this->projectId = $projectId;
        $this->attachments = $attachments;
    }

    public function handle(): void
    {
        try {
            $project = Project::find($this->projectId);
            if (!$project) {
                Log::error("Project not found for ID: " . $this->projectId);
                return;
            }

            foreach ($this->attachments as $file) {
                if (!$file instanceof UploadedFile) {
                    Log::warning('Invalid file provided for project attachments', [
                        'project_id' => $this->projectId,
                        'file' => is_object($file) ? get_class($file) : gettype($file)
                    ]);
                    continue;
                }

                $directory = "projects/{$project->id}";
                $storedPath = $file->store($directory, 'public');

                if (!$storedPath) {
                    throw new \RuntimeException("Failed to store file: " . $file->getClientOriginalName());
                }

                ProjectFile::create([
                    'project_id' => $project->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $storedPath,
                    'file_type' => $file->getClientMimeType(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing project attachments: ' . $e->getMessage(), [
                'project_id' => $this->projectId,
                'exception' => $e
            ]);
            throw $e; // Re-throw to mark the job as failed
        }
    }
}

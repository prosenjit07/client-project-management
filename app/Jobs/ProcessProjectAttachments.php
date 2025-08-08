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
    /** @var array<int, array> */
    public array $attachments = [];

    /**
     * @param int $projectId
     * @param array<int, \Illuminate\Http\UploadedFile> $attachments
     */
    public function __construct(int $projectId, array $attachments)
    {
        $this->projectId = $projectId;
        
        Log::info('Processing attachments for project', [
            'project_id' => $projectId,
            'file_count' => count($attachments)
        ]);
        
        // Store file content and metadata
        foreach ($attachments as $index => $file) {
            if ($file->isValid()) {
                $filePath = $file->getRealPath();
                $fileContent = file_get_contents($filePath);
                
                Log::debug('Processing file', [
                    'project_id' => $projectId,
                    'file_index' => $index,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_mime' => $file->getClientMimeType(),
                    'content_length' => strlen($fileContent)
                ]);
                
                $this->attachments[] = [
                    'originalName' => $file->getClientOriginalName(),
                    'mimeType' => $file->getClientMimeType(),
                    'content' => $fileContent,
                    'extension' => $file->getClientOriginalExtension(),
                    'size' => $file->getSize(),
                ];
            } else {
                Log::warning('Invalid file skipped', [
                    'project_id' => $projectId,
                    'file_index' => $index,
                    'file_name' => $file->getClientOriginalName(),
                    'error' => $file->getError(),
                    'error_message' => $file->getErrorMessage()
                ]);
            }
        }
    }

    public function handle(): void
    {
        try {
            $project = Project::find($this->projectId);
            if (!$project) {
                Log::error("Project not found for ID: " . $this->projectId);
                return;
            }

            $directory = 'projects/' . $project->id;
            
            Log::info('Starting file storage', [
                'project_id' => $project->id,
                'directory' => $directory,
                'file_count' => count($this->attachments)
            ]);
            
            // Ensure the directory exists
            $directoryCreated = Storage::disk('public')->makeDirectory($directory, 0755, true);
            
            if (!$directoryCreated) {
                Log::error('Failed to create directory', [
                    'project_id' => $project->id,
                    'directory' => $directory
                ]);
                throw new \RuntimeException("Failed to create directory: " . $directory);
            }
            
            foreach ($this->attachments as $index => $fileInfo) {
                try {
                    Log::debug('Storing file', [
                        'project_id' => $project->id,
                        'file_index' => $index,
                        'original_name' => $fileInfo['originalName'],
                        'size' => $fileInfo['size']
                    ]);
                    
                    // Generate a unique filename
                    $filename = uniqid() . '.' . $fileInfo['extension'];
                    $filePath = $directory . '/' . $filename;
                    
                    // Store the file content directly
                    $stored = Storage::disk('public')->put($filePath, $fileInfo['content']);
                    
                    if (!$stored) {
                        $error = "Failed to store file: " . $fileInfo['originalName'];
                        Log::error($error, [
                            'project_id' => $project->id,
                            'file_path' => $filePath,
                            'file_size' => $fileInfo['size']
                        ]);
                        throw new \RuntimeException($error);
                    }
                    
                    // Verify the file was stored
                    if (!Storage::disk('public')->exists($filePath)) {
                        $error = "File storage verification failed: " . $filePath;
                        Log::error($error, [
                            'project_id' => $project->id,
                            'file_path' => $filePath
                        ]);
                        throw new \RuntimeException($error);
                    }
                    
                    // Create the file record
                    $fileRecord = ProjectFile::create([
                        'project_id' => $project->id,
                        'file_name' => $fileInfo['originalName'],
                        'file_path' => $filePath,
                        'file_type' => $fileInfo['mimeType'],
                    ]);
                    
                    Log::info('File stored successfully', [
                        'project_id' => $project->id,
                        'file_id' => $fileRecord->id,
                        'file_path' => $filePath,
                        'stored_size' => Storage::disk('public')->size($filePath)
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Error processing file: ' . $e->getMessage(), [
                        'project_id' => $this->projectId,
                        'file' => $fileInfo['originalName'] ?? 'unknown',
                        'exception' => $e
                    ]);
                    continue;
                }
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

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

class ProcessProjectAttachments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public int $projectId;
    
    /** @var array */
    protected $attachments = [];

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
        
        // Store file metadata and move files to a temporary location
        foreach ($attachments as $index => $fileData) {
            $file = $fileData['file'] ?? null;
            
            if ($file && $file->isValid()) {
                // Store file in a temporary location
                $tempPath = $file->store('temp/' . $projectId, 'local');
                
                Log::debug('Stored file in temp location', [
                    'project_id' => $projectId,
                    'file_index' => $index,
                    'file_name' => $fileData['original_name'] ?? $file->getClientOriginalName(),
                    'temp_path' => $tempPath,
                    'file_size' => $fileData['size'] ?? $file->getSize(),
                    'file_mime' => $fileData['mime_type'] ?? $file->getClientMimeType()
                ]);
                
                $this->attachments[] = [
                    'temp_path' => $tempPath,
                    'original_name' => $fileData['original_name'] ?? $file->getClientOriginalName(),
                    'mime_type' => $fileData['mime_type'] ?? $file->getClientMimeType(),
                    'extension' => $fileData['extension'] ?? $file->getClientOriginalExtension(),
                    'size' => $fileData['size'] ?? $file->getSize(),
                ];
            } else {
                Log::warning('Invalid file skipped', [
                    'project_id' => $projectId,
                    'file_index' => $index,
                    'file_data' => $fileData,
                    'error' => $file ? $file->getError() : 'No file object',
                    'error_message' => $file ? $file->getErrorMessage() : 'No file object in attachment data'
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
                    $tempPath = $fileInfo['temp_path'] ?? null;
                    
                    if (!$tempPath || !Storage::disk('local')->exists($tempPath)) {
                        Log::error('Temporary file not found', [
                            'project_id' => $project->id,
                            'temp_path' => $tempPath,
                            'file_info' => $fileInfo
                        ]);
                        continue;
                    }
                    
                    // Generate a unique filename
                    $filename = uniqid() . '_' . $fileInfo['original_name'];
                    $destinationPath = $directory . '/' . $filename;
                    
                    // Get the file content from temp storage
                    $fileContent = Storage::disk('local')->get($tempPath);
                    
                    // Store the file in the public disk
                    $stored = Storage::disk('public')->put($destinationPath, $fileContent);
                    
                    if (!$stored) {
                        throw new \RuntimeException("Failed to store file in public disk");
                    }
                    
                    // Verify the file was stored
                    if (!Storage::disk('public')->exists($destinationPath)) {
                        throw new \RuntimeException("File storage verification failed: " . $destinationPath);
                    }
                    
                    // Delete the temp file
                    Storage::disk('local')->delete($tempPath);
                    
                    // Create a record in the database
                    $project->files()->create([
                        'file_name' => $filename,
                        'file_path' => $destinationPath,
                        'file_type' => $fileInfo['mime_type'],
                    ]);
                    
                    Log::info('File processed and stored successfully', [
                        'project_id' => $project->id,
                        'original_name' => $fileInfo['original_name'],
                        'stored_path' => $destinationPath,
                        'file_size' => $fileInfo['size']
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Error processing file: ' . $e->getMessage(), [
                        'project_id' => $project->id,
                        'file_info' => $fileInfo['original_name'] ?? 'unknown',
                        'exception' => $e->getMessage()
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

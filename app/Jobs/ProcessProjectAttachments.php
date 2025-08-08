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
        foreach ($attachments as $index => $file) {
            if ($file->isValid()) {
                // Store file in a temporary location
                $tempPath = $file->store('temp/' . $projectId, 'local');
                
                Log::debug('Stored file in temp location', [
                    'project_id' => $projectId,
                    'file_index' => $index,
                    'file_name' => $file->getClientOriginalName(),
                    'temp_path' => $tempPath,
                    'file_size' => $file->getSize(),
                    'file_mime' => $file->getClientMimeType()
                ]);
                
                $this->attachments[] = [
                    'temp_path' => $tempPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
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
                
                // Move the file from temp to final location
                Storage::disk('public')->put(
                    $destinationPath,
                    Storage::disk('local')->get($tempPath)
                );
                
                // Delete the temp file
                Storage::disk('local')->delete($tempPath);
                
                // Create a record in the database
                $project->files()->create([
                    'filename' => $filename,
                    'original_name' => $fileInfo['original_name'],
                    'mime_type' => $fileInfo['mime_type'],
                    'size' => $fileInfo['size'],
                    'path' => $destinationPath
                ]);
                
                Log::info('File processed successfully', [
                    'project_id' => $project->id,
                    'original_name' => $fileInfo['original_name'],
                    'stored_path' => $destinationPath
                ]);
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

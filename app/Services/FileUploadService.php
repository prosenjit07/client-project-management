<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploadService
{
    /**
     * Upload a file to the specified disk and return the file path
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @param array $allowedExtensions Array of allowed file extensions (without dot)
     * @param int $maxFileSizeKB Maximum file size in KB
     * @return array
     * @throws \Exception
     */
    /**
     * Default allowed mime types
     *
     * @var array
     */
    protected $defaultAllowedMimeTypes = [
        // Images
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        
        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
    ];

    /**
     * Upload a file to the specified disk and return the file path
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @param array $allowedExtensions Array of allowed file extensions (without dot)
     * @param int $maxFileSizeKB Maximum file size in KB
     * @return array
     * @throws \Exception
     */
    public function upload(
        UploadedFile $file,
        string $directory = 'uploads',
        string $disk = 'public',
        array $allowedExtensions = [],
        int $maxFileSizeKB = 5120
    ): array {
        try {
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $fileSizeKB = $fileSize / 1024;

            // Log the upload attempt
            Log::info('File upload attempt', [
                'original_name' => $originalName,
                'extension' => $extension,
                'mime_type' => $mimeType,
                'size_kb' => $fileSizeKB,
                'disk' => $disk,
                'directory' => $directory
            ]);

            // Validate file size
            if ($fileSizeKB > $maxFileSizeKB) {
                throw new \Exception("File size ({$fileSizeKB}KB) exceeds the maximum limit of {$maxFileSizeKB}KB");
            }

            // If no specific extensions provided, use all allowed types
            $allowedExtensions = !empty($allowedExtensions) ? $allowedExtensions : array_keys($this->defaultAllowedMimeTypes);
            
            // Get allowed mime types for the provided extensions
            $allowedMimeTypes = [];
            foreach ($allowedExtensions as $ext) {
                if (isset($this->defaultAllowedMimeTypes[$ext])) {
                    $allowedMimeTypes[] = $this->defaultAllowedMimeTypes[$ext];
                }
            }

            // Validate file type by extension and mime type
            $isValidMime = empty($allowedMimeTypes) || in_array($mimeType, $allowedMimeTypes);
            $isValidExtension = empty($allowedExtensions) || in_array($extension, $allowedExtensions);
            
            if (!$isValidMime || !$isValidExtension) {
                throw new \Exception(sprintf(
                    'File type not allowed. Allowed types: %s',
                    implode(', ', array_keys(array_intersect($this->defaultAllowedMimeTypes, $allowedMimeTypes)))
                ));
            }

            // Ensure directory exists
            if (!Storage::disk($disk)->exists($directory)) {
                Storage::disk($disk)->makeDirectory($directory, 0755, true);
            }

            // Generate a unique filename
            $filename = $this->generateUniqueFilename($file);
            
            // Store the file
            $path = $file->storeAs(
                rtrim($directory, '/'), // Remove trailing slashes
                $filename,
                $disk
            );

            if (!$path) {
                throw new \Exception("Failed to store the uploaded file");
            }

            // Verify the file was actually stored
            if (!Storage::disk($disk)->exists($path)) {
                throw new \Exception("Failed to verify the uploaded file");
            }

            return [
                'original_name' => $file->getClientOriginalName(),
                'file_name' => $filename,
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'disk' => $disk,
                'url' => $this->getFileUrl($path, $disk)
            ];
        } catch (FileException $e) {
            throw new \Exception("File upload failed: " . $e->getMessage());
        }
    }

    /**
     * Upload multiple files
     *
     * @param array $files
     * @param string $directory
     * @param string $disk
     * @param array $allowedMimeTypes
     * @param int $maxFileSizeKB
     * @return array
     */
    /**
     * Upload multiple files
     *
     * @param array $files Array of UploadedFile instances
     * @param string $directory
     * @param string $disk
     * @param array $allowedExtensions Array of allowed file extensions (without dot)
     * @param int $maxFileSizeKB Maximum file size in KB per file
     * @return array
     * @throws \Exception If any file fails to upload
     */
    public function uploadMultiple(
        array $files,
        string $directory = 'uploads',
        string $disk = 'public',
        array $allowedExtensions = [],
        int $maxFileSizeKB = 5120
    ): array {
        $uploadedFiles = [];
        
        $uploadedFiles = [];
        $errors = [];

        foreach ($files as $key => $file) {
            try {
                if ($file instanceof UploadedFile) {
                    $uploadedFiles[$key] = $this->upload(
                        $file, 
                        $directory, 
                        $disk, 
                        $allowedExtensions, 
                        $maxFileSizeKB
                    );
                } else {
                    $errors[$key] = 'Invalid file instance';
                }
            } catch (\Exception $e) {
                $errors[$key] = $e->getMessage();
                Log::error('File upload failed', [
                    'error' => $e->getMessage(),
                    'file' => $file->getClientOriginalName()
                ]);
            }
        }

        // If there were any errors and no files were uploaded, throw an exception
        if (!empty($errors) && empty($uploadedFiles)) {
            throw new \Exception('Failed to upload files: ' . implode(', ', $errors));
        }
        
        return [
            'successful' => $uploadedFiles,
            'failed' => $errors
        ];
    }

 
    // Delete a file from storage
   
    public function deleteFile($path, string $disk = 'public'): array
    {
        $deleted = [];
        $errors = [];
        
        $paths = is_array($path) ? $path : [$path];
        
        foreach ($paths as $filePath) {
            try {
                if (Storage::disk($disk)->exists($filePath)) {
                    $result = Storage::disk($disk)->delete($filePath);
                    if ($result) {
                        $deleted[] = $filePath;
                        Log::info('File deleted successfully', ['path' => $filePath, 'disk' => $disk]);
                    } else {
                        $errors[$filePath] = 'Failed to delete file';
                        Log::error('Failed to delete file', ['path' => $filePath, 'disk' => $disk]);
                    }
                } else {
                    $errors[$filePath] = 'File does not exist';
                    Log::warning('Attempted to delete non-existent file', ['path' => $filePath, 'disk' => $disk]);
                }
            } catch (\Exception $e) {
                $errors[$filePath] = $e->getMessage();
                Log::error('Error deleting file', [
                    'path' => $filePath,
                    'disk' => $disk,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'success' => $deleted,
            'errors' => $errors
        ];
    }
    
    /**
     * Get the URL for a stored file
     *
     * @param string $path
     * @param string $disk
     * @return string|null
     */
    public function getFileUrl(string $path, string $disk = 'public'): ?string
    {
        try {
            $storage = Storage::disk($disk);
            
            if (!$storage->exists($path)) {
                return null;
            }
            
            // For local and public disks, construct the URL manually
            if ($disk === 'local' || $disk === 'public') {
                // Remove any leading slashes to prevent double slashes in URL
                $cleanPath = ltrim($path, '/');
                return url('storage/' . $cleanPath);
            }
            
            // For other disks (like S3), use the Storage facade to generate the URL
            try {
                return Storage::disk($disk)->$url;
            } catch (\Exception $urlException) {
                // If URL generation fails, log the error and return null
                Log::warning('Failed to generate URL using Storage facade', [
                    'path' => $path,
                    'disk' => $disk,
                    'error' => $urlException->getMessage()
                ]);
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error('Error getting file URL', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Generate a unique filename
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9\-\._]/', '_', $filename);
        
        // Add a random string to ensure uniqueness
        return $filename . '_' . Str::random(10) . '.' . strtolower($extension);
    }
}

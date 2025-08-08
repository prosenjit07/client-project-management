<?php

namespace App\Services;

use App\Http\Requests\ProjectReportRequest;
use App\Jobs\ProcessProjectAttachments;
use App\Models\Client;
use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ProjectService
{
    protected $projectRepository;

    public function __construct(ProjectRepositoryInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }
    /**
     * Create a new project with client and queue file uploads
     *
     * @param array $clientData
     * @param array $projectData
     * @param array<\Illuminate\Http\UploadedFile> $attachments
     * @return Project
     * @throws \Exception
     */
    public function createProjectWithClientAndQueueFiles(array $clientData, array $projectData, array $attachments = []): Project
    {
        return DB::transaction(function () use ($clientData, $projectData, $attachments) {
            try {
                // Validate required fields
                if (empty($clientData['name']) || empty($clientData['email'])) {
                    throw new \InvalidArgumentException('Client name and email are required');
                }
                
                if (empty($projectData['project_name'])) {
                    throw new \InvalidArgumentException('Project name is required');
                }

                // Create client
                $client = Client::create([
                    'name' => $clientData['name'],
                    'email' => $clientData['email'],
                    'phone' => $clientData['phone'] ?? null,
                    'industry' => $clientData['industry'] ?? null,
                ]);

                // Create project
                $project = Project::create([
                    'client_id' => $client->id,
                    'project_name' => $projectData['project_name'],
                    'project_type' => $projectData['project_type'] ?? ($clientData['project_type'] ?? 'web_app'),
                    'start_date' => $projectData['start_date'] ?? null,
                    'end_date' => $projectData['end_date'] ?? null,
                    'estimated_budget' => $projectData['estimated_budget'] ?? null,
                    'description' => $projectData['description'] ?? null,
                ]);

                // Process any attachments in the background
                if (!empty($attachments)) {
                    $validAttachments = [];
                    
                    foreach ($attachments as $fileData) {
                        $file = $fileData['file'] ?? null;
                        
                        if ($file && $file->isValid()) {
                            $validAttachments[] = [
                                'file' => $file,
                                'original_name' => $fileData['original_name'] ?? $file->getClientOriginalName(),
                                'mime_type' => $fileData['mime_type'] ?? $file->getClientMimeType(),
                                'size' => $fileData['size'] ?? $file->getSize(),
                                'extension' => $fileData['extension'] ?? $file->getClientOriginalExtension()
                            ];
                            
                            Log::debug('Valid file found for processing', [
                                'project_id' => $project->id,
                                'file_name' => $fileData['original_name'] ?? $file->getClientOriginalName(),
                                'file_size' => $fileData['size'] ?? $file->getSize(),
                                'mime_type' => $fileData['mime_type'] ?? $file->getMimeType()
                            ]);
                        } else {
                            Log::warning('Invalid file skipped', [
                                'project_id' => $project->id,
                                'file_data' => $fileData,
                                'is_file' => $file ? 'Yes' : 'No',
                                'is_valid' => $file && $file->isValid() ? 'Yes' : 'No',
                                'error' => $file ? $file->getErrorMessage() : 'No file object',
                                'error_code' => $file ? $file->getError() : 'N/A'
                            ]);
                        }
                    }
                    
                    if (!empty($validAttachments)) {
                        Log::info('Dispatching ProcessProjectAttachments job', [
                            'project_id' => $project->id,
                            'valid_files_count' => count($validAttachments)
                        ]);
                        
                        // Run synchronously to ensure files are persisted immediately
                        // This avoids requiring a queue worker for core functionality
                        ProcessProjectAttachments::dispatchSync($project->id, $validAttachments);
                        
                        Log::info('ProcessProjectAttachments job dispatched', [
                            'project_id' => $project->id
                        ]);
                    } else {
                        Log::warning('No valid attachments to process', [
                            'project_id' => $project->id,
                            'total_attachments' => count($attachments)
                        ]);
                    }
                } else {
                    Log::info('No attachments to process', [
                        'project_id' => $project->id
                    ]);
                }

                return $project->load('client');
                
            } catch (\Exception $e) {
                Log::error('Error in createProjectWithClientAndQueueFiles: ' . $e->getMessage(), [
                    'client_data' => $clientData,
                    'project_data' => $projectData,
                    'has_attachments' => !empty($attachments),
                    'exception' => $e
                ]);
                
                // Re-throw the exception to trigger transaction rollback
                throw $e;
            }
        });
    }

    /**
     * Get filtered projects query with eager loading and sorting
     */
    public function getFilteredProjectsQuery(ProjectReportRequest $request): Builder
    {
        $filters = $request->validated();
        return $this->projectRepository->getQuery()
            ->with(['client'])
            ->withCount('files')
            ->when(isset($filters['client_name']), function ($query) use ($filters) {
                $query->whereHas('client', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['client_name'] . '%');
                });
            })
            ->when(isset($filters['project_type']), function ($query) use ($filters) {
                $query->where('project_type', $filters['project_type']);
            })
            ->when(isset($filters['start_date']), function ($query) use ($filters) {
                $query->whereDate('start_date', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']), function ($query) use ($filters) {
                $query->whereDate('end_date', '<=', $filters['end_date']);
            })
            ->when(isset($filters['sort_by']), function ($query) use ($filters) {
                $direction = $filters['sort_dir'] ?? 'asc';
                if (in_array($filters['sort_by'], ['project_name', 'estimated_budget', 'start_date', 'end_date'])) {
                    $query->orderBy($filters['sort_by'], $direction);
                }
            }, function ($query) {
                $query->latest('id');
            });
    }

    /**
     * Get paginated projects for the report
     */
    public function getPaginatedProjects(ProjectReportRequest $request, int $perPage = 10)
    {
        $filters = $request->validated();
        return $this->projectRepository->getFilteredProjects(
            $filters,
            ['client'],
            $perPage
        );
    }

    /**
     * Get all filtered projects for export
     */
    public function getAllFilteredProjects(ProjectReportRequest $request): Collection
    {
        $filters = $request->validated();
        return $this->projectRepository->getProjectsForExport(
            $filters,
            ['client']
        );
    }

    /**
     * Get available project types for filtering
     */
    public function getProjectTypes(): array
    {
        return $this->projectRepository->getProjectTypes();
    }
}






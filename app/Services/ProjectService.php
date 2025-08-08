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
    public function createProjectWithClientAndQueueFiles(array $clientData, array $projectData, array $attachments = []): Project
    {
        return DB::transaction(function () use ($clientData, $projectData, $attachments) {
            try {
                // Create client
                $client = Client::create([
                    'name' => $clientData['name'],
                    'email' => $clientData['email'],
                    'phone' => $clientData['phone'],
                    'industry' => $clientData['industry'] ?? null,
                ]);

                // Create project
                $project = Project::create([
                    'client_id' => $client->id,
                    'project_name' => $projectData['project_name'],
                    'project_type' => $projectData['project_type'] ?? ($clientData['project_type'] ?? null),
                    'start_date' => $projectData['start_date'] ?? null,
                    'end_date' => $projectData['end_date'] ?? null,
                    'estimated_budget' => $projectData['estimated_budget'] ?? null,
                    'description' => $projectData['description'] ?? null,
                ]);

                // Process any attachments in the background
                if (!empty($attachments)) {
                    ProcessProjectAttachments::dispatch($project->id, $attachments);
                }

                return $project;
            } catch (\Exception $e) {
                Log::error('Error creating project: ' . $e->getMessage(), [
                    'client_data' => $clientData,
                    'project_data' => $projectData,
                    'exception' => $e
                ]);
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






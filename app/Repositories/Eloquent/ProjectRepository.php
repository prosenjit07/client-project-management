<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    public function getFilteredProjects(array $filters, array $relations = [], int $perPage = 10)
    {
        $query = $this->model->with($relations)
            ->withCount('files')
            ->when(isset($filters['client_name']), function (Builder $query) use ($filters) {
                $query->whereHas('client', function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['client_name'] . '%');
                });
            })
            ->when(isset($filters['project_type']), function (Builder $query) use ($filters) {
                $query->where('project_type', $filters['project_type']);
            })
            ->when(isset($filters['start_date']), function (Builder $query) use ($filters) {
                $query->whereDate('start_date', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']), function (Builder $query) use ($filters) {
                $query->whereDate('end_date', '<=', $filters['end_date']);
            });

        // Apply sorting
        if (isset($filters['sort_by'])) {
            $direction = $filters['sort_dir'] ?? 'asc';
            if (in_array($filters['sort_by'], ['project_name', 'estimated_budget', 'start_date', 'end_date'])) {
                $query->orderBy($filters['sort_by'], $direction);
            }
        } else {
            $query->latest('id');
        }

        return $query->paginate($perPage);
    }

    public function getProjectsForExport(array $filters, array $relations = []): Collection
    {
        return $this->getFilteredQuery($filters, $relations)->get();
    }

    public function getProjectTypes(): array
    {
        return $this->model->distinct()
            ->whereNotNull('project_type')
            ->pluck('project_type')
            ->toArray();
    }

    private function getFilteredQuery(array $filters, array $relations = []): Builder
    {
        return $this->model->with($relations)
            ->withCount('files')
            ->when(isset($filters['client_name']), function (Builder $query) use ($filters) {
                $query->whereHas('client', function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['client_name'] . '%');
                });
            })
            ->when(isset($filters['project_type']), function (Builder $query) use ($filters) {
                $query->where('project_type', $filters['project_type']);
            })
            ->when(isset($filters['start_date']), function (Builder $query) use ($filters) {
                $query->whereDate('start_date', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']), function (Builder $query) use ($filters) {
                $query->whereDate('end_date', '<=', $filters['end_date']);
            });
    }
}

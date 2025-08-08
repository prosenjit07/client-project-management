<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

interface ProjectRepositoryInterface extends BaseRepositoryInterface
{
    public function getFilteredProjects(array $filters, array $relations = [], int $perPage = 10);
    
    public function getProjectsForExport(array $filters, array $relations = []): Collection;
    
    public function getProjectTypes(): array;
}

<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*'], array $relations = []): Collection;
    
    public function findById(int $modelId, array $columns = ['*'], array $relations = []): ?Model;
    
    public function create(array $payload): ?Model;
    
    public function update(int $modelId, array $payload): bool;
    
    public function deleteById(int $modelId): bool;
    
    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    
    public function getQuery(): Builder;
}
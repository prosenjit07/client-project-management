<?php

namespace App\Providers;

use App\Models\Project;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Services\FileUploadService;
use App\Services\ProjectService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Project Repository
        $this->app->bind(
            ProjectRepositoryInterface::class,
            function ($app) {
                return new ProjectRepository(new Project());
            }
        );

        // Bind Project Service
        $this->app->bind(ProjectService::class, function ($app) {
            return new ProjectService(
                $app->make(ProjectRepositoryInterface::class)
            );
        });

        // Bind File Upload Service
        $this->app->bind(FileUploadService::class, function ($app) {
            return new FileUploadService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

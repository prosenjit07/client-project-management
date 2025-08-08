<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectReportRequest;
use App\Services\ProjectService;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectReportController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Display a listing of projects with filters
     */
    public function index(ProjectReportRequest $request): Response
    {
        $projects = $this->projectService->getPaginatedProjects($request);
        $projectTypes = $this->projectService->getProjectTypes();

        return Inertia::render('Client/Index', [
            'filters' => $request->only(['client_name', 'project_type', 'start_date', 'end_date', 'sort_by', 'sort_dir']),
            'projects' => $projects,
            'projectTypes' => $projectTypes,
        ]);
    }

    /**
     * Export projects to Excel
     */
    public function exportExcel(ProjectReportRequest $request)
    {
        $data = $this->projectService->getAllFilteredProjects($request);
        return Excel::download(new \App\Exports\ProjectsExport($data), 'projects.xlsx');
    }

    /**
     * Export projects to PDF
     */
    public function exportPdf(ProjectReportRequest $request)
    {
        $data = $this->projectService->getAllFilteredProjects($request);
        $pdf = Pdf::loadView('pdf.projects', ['projects' => $data]);
        return $pdf->download('projects.pdf');
    }
}






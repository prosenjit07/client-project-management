<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProjectsExport implements FromCollection, WithHeadings, Responsable
{
    use Exportable;

    private Collection $projects;
    private string $fileName = 'projects.xlsx';
    private array $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct(Collection $projects)
    {
        $this->projects = $projects;
    }

    public function collection(): Collection
    {
        return $this->projects->map(function ($project) {
            return [
                'Project Name' => $project->project_name,
                'Client Name' => optional($project->client)->name,
                'Budget' => $project->estimated_budget,
                'Start Date' => optional($project->start_date)->format('Y-m-d'),
                'End Date' => optional($project->end_date)->format('Y-m-d'),
                'Total Files' => $project->files_count ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return ['Project Name', 'Client Name', 'Budget', 'Start Date', 'End Date', 'Total Files'];
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return Excel::download($this, $this->fileName, null, $this->headers);
    }
}






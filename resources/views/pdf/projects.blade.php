<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Projects Report</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; font-size: 12px; }
        th { background: #f5f5f5; text-align: left; }
    </style>
    </head>
<body>
<h3>Projects Report</h3>
<table>
    <thead>
    <tr>
        <th>Project Name</th>
        <th>Client Name</th>
        <th>Budget</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Total Files</th>
    </tr>
    </thead>
    <tbody>
    @foreach($projects as $project)
        <tr>
            <td>{{ $project->project_name }}</td>
            <td>{{ optional($project->client)->name }}</td>
            <td>{{ number_format((float) $project->estimated_budget, 2) }}</td>
            <td>{{ optional($project->start_date)->format('Y-m-d') }}</td>
            <td>{{ optional($project->end_date)->format('Y-m-d') }}</td>
            <td>{{ $project->files_count ?? 0 }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>






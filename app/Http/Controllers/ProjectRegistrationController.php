<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProjectRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Client/Create');
    }

    public function finalize(Request $request, ProjectService $projectService): RedirectResponse
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                // Step 1 validation
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
                'phone' => ['required', 'string', 'max:50'],
                'industry' => ['nullable', 'string', 'max:100'],
                'project_type' => ['required', 'in:web_app,mobile_app,erp,e_commerce'],
                
                // Step 2 validation
                'project_name' => ['required', 'string', 'max:255', 'unique:projects,project_name'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'estimated_budget' => ['nullable', 'numeric', 'min:0'],
                'description' => ['nullable', 'string', 'min:10'],
                
                // Files validation
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:5120'],
            ]);

            // Extract client and project data
            $clientData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'industry' => $validated['industry'] ?? null,
            ];

            $projectData = [
                'project_name' => $validated['project_name'],
                'project_type' => $validated['project_type'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'estimated_budget' => $validated['estimated_budget'] ?? null,
                'description' => $validated['description'] ?? null,
            ];

            // Process the project creation and file uploads
            $files = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $files[] = [
                            'file' => $file,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
                            'extension' => $file->getClientOriginalExtension()
                        ];
                    } else {
                        Log::warning('Invalid file uploaded', [
                            'name' => $file->getClientOriginalName(),
                            'error' => $file->getError(),
                            'error_message' => $file->getErrorMessage()
                        ]);
                    }
                }
            }
            
            Log::info('Processing project registration', [
                'client_email' => $clientData['email'],
                'project_name' => $projectData['project_name'],
                'file_count' => count($files)
            ]);
            
            $project = $projectService->createProjectWithClientAndQueueFiles(
                clientData: $clientData,
                projectData: $projectData,
                attachments: $files
            );

            return redirect()->route('project.register.create')
                ->with([
                    'success' => 'Project registered successfully! Project ID: '.$project->id,
                    'step' => 1
                ]);
                
        } catch (\Exception $e) {
            Log::error('Error in project registration: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except(['attachments'])
            ]);
            
            return back()->withInput()->withErrors([
                'error' => 'An error occurred while processing your request. Please try again.'
            ]);
        }
    }
}
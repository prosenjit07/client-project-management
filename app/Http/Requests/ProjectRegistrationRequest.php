<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
            'phone' => ['required', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:100'],
            'project_type' => ['required', 'in:web_app,mobile_app,erp,e_commerce'],

            // Step 2
            'project_name' => ['required', 'string', 'max:255', 'unique:projects,project_name'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'min:10'],

            // Files
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:5120'],
        ];
    }
}



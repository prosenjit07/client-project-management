<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
            'phone' => ['required', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:100'],
            'project_type' => ['required', 'in:web_app,mobile_app,erp,e_commerce'],
        ];
    }
}
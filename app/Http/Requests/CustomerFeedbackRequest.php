<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'request_type' => 'sometimes|string|max:255',
            'subject' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max file size // Allow images & documents (max: 2MB) 2048
        ];
    }
}

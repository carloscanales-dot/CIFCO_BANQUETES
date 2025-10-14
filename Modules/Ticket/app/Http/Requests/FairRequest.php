<?php

namespace Modules\Ticket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FairRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'fair_name' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'status' => 'required'
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'fair_name.required' => 'El campo :attribute es obligatorio.',
            'start_date.required' => 'El campo :attribute es obligatorio.',
            'end_date.required' => 'El campo :attribute es obligatorio.',
            'status.required' => 'El campo :attribute es obligatorio.'
        ];
    }
}

<?php

namespace Modules\Ticket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProductRequest extends FormRequest
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
            'prefix' => 'required',
            'product_name' => 'required',
            'unit_price' => 'required',
            'status' => 'required'
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'prefix.required' => 'El campo :attribute es obligatorio.',
            'product_name.required' => 'El campo :attribute es obligatorio.',
            'unit_price.required' => 'El campo :attribute es obligatorio.',
            'status.required' => 'El campo :attribute es obligatorio.'
        ];
    }
}

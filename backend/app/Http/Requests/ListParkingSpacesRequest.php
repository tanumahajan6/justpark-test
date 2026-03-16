<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListParkingSpacesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // No auth required for this task
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date'],
            'end_time'   => ['required', 'date', 'after:start_time'],
            'location'   => ['nullable', 'string', 'max:255'],
            'max_price'  => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.required' => 'A start time is required.',
            'end_time.required'   => 'An end time is required.',
            'end_time.after'      => 'End time must be after start time.',
        ];
    }
}
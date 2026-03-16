<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parking_space_id' => ['required', 'integer', 'exists:parking_spaces,id'],
            'user_id'          => ['required', 'integer'],
            'start_time'       => ['required', 'date'],
            'end_time'         => ['required', 'date', 'after:start_time'],
        ];
    }
}
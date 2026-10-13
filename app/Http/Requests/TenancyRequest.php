<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'move_in_date' => ['required', 'date_format:Y-m-d'],
            'move_out_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:move_in_date'],
        ];
    }
}

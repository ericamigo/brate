<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;

class OccupancyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string|ConditionalRules>> */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'period_start' => ['nullable', 'date_format:Y-m-d'],
            'period_end' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('period_start'), ['after_or_equal:period_start'])],
        ];
    }
}

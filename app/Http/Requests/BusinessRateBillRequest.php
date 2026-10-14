<?php

namespace App\Http\Requests;

use App\Enums\BillingStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class BusinessRateBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string|Enum>> */
    public function rules(): array
    {
        return [
            'case_number' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', Rule::enum(BillingStage::class)],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'biller_id' => ['required', 'integer', 'exists:billers,id'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'files' => [$this->isMethod('post') ? 'nullable' : 'prohibited', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,bmp,pdf', 'max:20480'],
        ];
    }
}

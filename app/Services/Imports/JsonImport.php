<?php

namespace App\Services\Imports;

use App\Models\Building;
use App\Models\Company;
use App\Models\Tenancy;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

class JsonImport
{
    /** @return array{created: int, skipped: int} */
    public function import(string $json): array
    {
        try {
            $rows = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['json' => 'Enter valid JSON.']);
        }

        if (! is_array($rows) || $rows === []) {
            throw ValidationException::withMessages(['json' => 'Enter a non-empty JSON array of rows.']);
        }

        $validated = [];
        foreach ($rows as $index => $row) {
            $number = $index + 1;
            if (! $row instanceof \stdClass) {
                throw ValidationException::withMessages(['json' => "Row {$number}: Enter an object with company, building, unit, tenant and dates."]);
            }
            $data = (array) $row;
            foreach (['company', 'building', 'unit', 'tenant'] as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $data[$field] = trim($data[$field]);
                }
            }
            $validator = Validator::make($data, [
                'company' => ['required', 'string', 'max:255'],
                'building' => ['required', 'string', 'max:255'],
                'unit' => ['required', 'string', 'max:255'],
                'tenant' => ['required', 'string', 'max:255'],
                'move_in_date' => ['required', 'date_format:Y-m-d'],
                'move_out_date' => ['present', 'nullable', 'date_format:Y-m-d', 'after_or_equal:move_in_date'],
            ]);
            if ($validator->fails()) {
                throw ValidationException::withMessages(['json' => array_map(
                    fn ($message) => "Row {$number}: {$message}", $validator->errors()->all()
                )]);
            }
            $validated[] = $validator->validated();
        }

        return DB::transaction(function () use ($validated) {
            $created = 0;
            $skipped = 0;
            foreach ($validated as $row) {
                $company = Company::firstOrCreate(['name' => $row['company']]);
                $building = Building::firstOrCreate(['company_id' => $company->id, 'name' => $row['building']]);
                $unit = Unit::firstOrCreate(['building_id' => $building->id, 'name' => $row['unit']]);
                $tenant = Tenant::firstOrCreate(['name' => $row['tenant']]);
                $end = $row['move_out_date'];
                $tenancy = Tenancy::query()->where('unit_id', $unit->id)->where('tenant_id', $tenant->id)
                    ->whereDate('move_in_date', $row['move_in_date'])
                    ->when($end === null, fn ($query) => $query->whereNull('move_out_date'), fn ($query) => $query->whereDate('move_out_date', $end))
                    ->first() ?? Tenancy::create([
                        'unit_id' => $unit->id, 'tenant_id' => $tenant->id,
                        'move_in_date' => $row['move_in_date'], 'move_out_date' => $end,
                    ]);
                $tenancy->wasRecentlyCreated ? $created++ : $skipped++;
            }

            return compact('created', 'skipped');
        });
    }
}

<?php

namespace App\Services\Imports;

use App\Models\Building;
use App\Models\Tenancy;
use App\Models\Tenant;
use App\Models\Unit;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

class ReLeasedImport
{
    public const HEADERS = [
        'Property', 'Tenancy', 'Area', 'Area Status', 'Area Size', 'Term Length',
        'Term Start Date', 'Term Expiry Date', 'Tenancy Status', 'Next Rent Review Date',
        'Next Rent Review Type', 'Rent (£/annum)', 'Rent (£/area unit)', 'Rent (£/frequency)', 'Rent Frequency',
    ];

    /** @return array{created: int, skipped: int, missingStart: int} */
    public function import(UploadedFile $file, int $companyId): array
    {
        try {
            $reader = new Xlsx;
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly($reader->listWorksheetNames($file->getPathname())[0]);
            $workbook = $reader->load($file->getPathname());
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'The file could not be read as an Excel workbook.']);
        }

        try {
            $sheet = $workbook->getSheet(0);
            $headers = $sheet->rangeToArray('A1:O1', null, false, false)[0];
            if ($headers !== self::HEADERS || $sheet->getHighestDataColumn() !== 'O') {
                throw ValidationException::withMessages(['file' => 'The first row must contain the exact Re-Leased headers in the expected order.']);
            }

            return DB::transaction(function () use ($sheet, $companyId, $workbook) {
                $created = 0;
                $skipped = 0;
                $missingStart = 0;
                for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                    $values = $sheet->rangeToArray("A{$row}:O{$row}", null, false, false)[0];
                    $tenantName = $sheet->getCell('B'.$row)->getCalculatedValue();
                    if ($tenantName === null || (is_string($tenantName) && preg_match('/^[\s\p{Z}\x{200B}\x{FEFF}]*$/u', $tenantName))) {
                        continue;
                    }
                    $values[1] = $tenantName;
                    if (count(array_filter($values, fn ($value) => $value !== null && $value !== '')) === 0) {
                        continue;
                    }
                    foreach ([0 => 'Property', 1 => 'Tenancy', 2 => 'Area'] as $index => $label) {
                        if (! is_string($values[$index]) || trim($values[$index]) === '' || mb_strlen($values[$index]) > 255) {
                            throw ValidationException::withMessages(['file' => "Row {$row}: {$label} must be a name of at most 255 characters."]);
                        }
                    }
                    $start = $this->date($sheet->getCell('G'.$row)->getCalculatedValue(), $row, 'Term Start Date', true, $workbook->getExcelCalendar());
                    $end = $this->date($sheet->getCell('H'.$row)->getCalculatedValue(), $row, 'Term Expiry Date', true, $workbook->getExcelCalendar());
                    if ($start !== null && $end !== null && $end < $start) {
                        throw ValidationException::withMessages(['file' => "Row {$row}: Term Expiry Date must be on or after Term Start Date."]);
                    }
                    $building = Building::firstOrCreate(['company_id' => $companyId, 'name' => $values[0]]);
                    $unit = Unit::firstOrCreate(['building_id' => $building->id, 'name' => $values[2]]);
                    $tenant = Tenant::firstOrCreate(['name' => $values[1]]);
                    if ($start === null) {
                        $missingStart++;

                        continue;
                    }
                    $attributes = [
                        'unit_id' => $unit->id, 'tenant_id' => $tenant->id,
                        'move_in_date' => $start, 'move_out_date' => $end,
                    ];
                    $tenancy = Tenancy::query()->where('unit_id', $unit->id)->where('tenant_id', $tenant->id)
                        ->whereDate('move_in_date', $start)
                        ->when($end === null, fn ($query) => $query->whereNull('move_out_date'), fn ($query) => $query->whereDate('move_out_date', $end))
                        ->first() ?? Tenancy::create($attributes);
                    $tenancy->wasRecentlyCreated ? $created++ : $skipped++;
                }
                if ($created + $skipped + $missingStart === 0) {
                    throw ValidationException::withMessages(['file' => 'The workbook contains no tenancy rows.']);
                }

                return compact('created', 'skipped', 'missingStart');
            });
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    private function date(mixed $value, int $row, string $label, bool $nullable, int $calendar): ?string
    {
        if (is_string($value)) {
            $value = preg_replace('/^[\s\p{Z}\x{200B}\x{FEFF}]+|[\s\p{Z}\x{200B}\x{FEFF}]+$/u', '', $value);
        }
        if ($nullable && ($value === null || $value === '')) {
            return null;
        }
        if ((is_int($value) || is_float($value)) && $value >= 1 && $value <= 2958465) {
            $previous = Date::getExcelCalendar();
            try {
                Date::setExcelCalendar($calendar);

                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            } finally {
                Date::setExcelCalendar($previous);
            }
        }
        if (is_string($value)) {
            foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-M-Y', 'd M Y'] as $format) {
                $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            }
        }
        throw ValidationException::withMessages(['file' => "Row {$row}: {$label} must be a valid Excel date, DD/MM/YYYY, or YYYY-MM-DD."]);
    }
}

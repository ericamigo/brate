<?php

namespace App\Services;

use App\Models\BusinessRateBill;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class BillDocuments
{
    /** @param callable(): BusinessRateBill $owner
     * @param  list<UploadedFile>  $files
     */
    public function save(callable $owner, array $files): BusinessRateBill
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($owner, $files, &$paths) {
                $bill = $owner();
                foreach ($files as $index => $file) {
                    $filename = $file->getClientOriginalName();
                    if (mb_strlen($filename) > 255) {
                        throw ValidationException::withMessages(["files.{$index}" => 'The filename must not exceed 255 characters.']);
                    }
                    $path = $file->store('documents', 'local');
                    if ($path === false) {
                        throw ValidationException::withMessages(['files' => 'The files could not be stored. Please try again.']);
                    }
                    $paths[] = $path;
                    $bill->documents()->create(['filename' => $filename, 'path' => $path]);
                }

                return $bill;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }
}

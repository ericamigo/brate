<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['filename', 'path'])]
class Document extends Model
{
    public function storagePath(): string
    {
        return $this->path;
    }
}

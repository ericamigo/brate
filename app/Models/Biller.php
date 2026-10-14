<?php

namespace App\Models;

use Database\Factories\BillerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Biller extends Model
{
    /** @use HasFactory<BillerFactory> */
    use HasFactory;

    /** @return HasMany<BusinessRateBill, $this> */
    public function bills(): HasMany
    {
        return $this->hasMany(BusinessRateBill::class);
    }
}

<?php

namespace App\Enums;

enum BillingStage: string
{
    case Enforcement = 'enforcement';
    case Summon = 'summon';
    case Unverified = 'unverified';
}

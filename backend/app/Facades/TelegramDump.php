<?php

namespace App\Facades;

use App\Services\TelegramDumpService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool dumpDev(mixed $text)
 * @method static bool dumpProd(mixed $text)
 * @method static never dd(mixed $text)
 */
class TelegramDump extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TelegramDumpService::class;
    }
}

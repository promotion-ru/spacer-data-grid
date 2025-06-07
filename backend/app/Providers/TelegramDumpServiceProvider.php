<?php

namespace App\Providers;

use App\Services\TelegramDumpService;
use Illuminate\Support\ServiceProvider;

class TelegramDumpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelegramDumpService::class);

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/telegram-dump.php', 'telegram-dump'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/telegram-dump.php' => config_path('telegram-dump.php'),
        ], 'telegram-dump-config');
    }
}

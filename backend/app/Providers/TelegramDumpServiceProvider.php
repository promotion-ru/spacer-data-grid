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

        $this->registerHelpers();
    }

    private function registerHelpers(): void
    {
        if (!function_exists('t_dump')) {
            function t_dump(mixed $text): bool
            {
                return app(TelegramDumpService::class)->dump($text);
            }
        }

        if (!function_exists('t_dump_dev')) {
            function t_dump_dev(mixed $text): bool
            {
                return app(TelegramDumpService::class)->dumpDev($text);
            }
        }

        if (!function_exists('t_dump_prod')) {
            function t_dump_prod(mixed $text): bool
            {
                return app(TelegramDumpService::class)->dumpProd($text);
            }
        }

        if (!function_exists('t_dd')) {
            function t_dd(mixed $text): never
            {
                app(TelegramDumpService::class)->dd($text);
            }
        }
    }
}

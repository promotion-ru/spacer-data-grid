<?php

namespace App\Console\Commands;

use App\Facades\TelegramDump;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:minute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестовая команда которая запускается каждую минуту';

    public function handle()
    {
        // Можно добавить лог для проверки что команда работает
        TelegramDump::dump('TestCommand executed at ' . now()->toDateTimeString());

        // Ничего не делаем
        $this->info('Команда выполнена успешно');
    }
}

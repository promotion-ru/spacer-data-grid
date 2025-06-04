<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

readonly class TelegramDumpService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('telegram-dump');
    }

    public function dumpDev(mixed $text): bool
    {
        return $this->dump($text, 'dev');
    }

    public function dump(mixed $text, string $environment = 'default'): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        $chatId = $this->getChatId($environment);
        $token = $this->getToken($environment);

        if (!$chatId || !$token) {
            Log::warning("TelegramDump: Missing credentials for environment: {$environment}");
            return false;
        }

        return $this->sendMessage($text, $chatId, $token);
    }

    private function getChatId(string $environment): ?string
    {
        return match ($environment) {
            'dev' => $this->config['dev_chat_id'] ?? null,
            'prod' => $this->config['prod_chat_id'] ?? null,
            'default' => $this->config['default_chat_id'] ?? null,
            default => null,
        };
    }

    private function getToken(string $environment): ?string
    {
        return match ($environment) {
            'dev' => $this->config['dev_token'] ?? null,
            'prod' => $this->config['prod_token'] ?? null,
            'default' => $this->config['default_token'] ?? null,
            default => null,
        };
    }

    private function sendMessage(mixed $text, string $chatId, string $token): bool
    {
        try {
            $formattedText = $this->formatText($text);

            $maxLength = $this->config['max_message_length'] ?? 4096;
            if (strlen($formattedText) > $maxLength) {
                $formattedText = substr($formattedText, 0, $maxLength - 3) . '...';
            }

            $response = Http::timeout($this->config['timeout'] ?? 10)
                ->withHeaders([
                    'User-Agent' => 'Laravel/' . app()->version() . ' TelegramDump/1.0'
                ])
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'                  => $chatId,
                    'text'                     => $formattedText,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->failed()) {
                Log::error('TelegramDump: Failed to send message', [
                    'status'      => $response->status(),
                    'body'        => $response->body(),
                    'environment' => app()->environment()
                ]);
                return false;
            }

            Log::debug('TelegramDump: Message sent successfully', [
                'environment'    => app()->environment(),
                'message_length' => strlen($formattedText)
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('TelegramDump: Exception occurred', [
                'message'     => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
                'environment' => app()->environment()
            ]);
            return false;
        }
    }

    private function formatText(mixed $text): string
    {
        return match (true) {
            is_array($text) || is_object($text) => '<pre>' . var_export($text, true) . '</pre>',
            is_bool($text) => $text ? 'true' : 'false',
            is_null($text) => 'null',
            default => (string)$text,
        };
    }

    public function dumpProd(mixed $text): bool
    {
        return $this->dump($text, 'prod');
    }

    public function dd(mixed $text): never
    {
        $this->dump($text);
        exit(1);
    }
}

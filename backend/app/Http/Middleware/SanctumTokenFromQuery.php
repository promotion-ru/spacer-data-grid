<?php

// app/Http/Middleware/SanctumTokenFromQuery.php

namespace App\Http\Middleware;

use App\Facades\TelegramDump;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanctumTokenFromQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        // Если токен уже есть в заголовке Authorization, пропускаем
        if ($request->hasHeader('Authorization')) {
            return $next($request);
        }

        // Получаем токен из GET параметра
        $token = $request->query('token');
        if ($token) {
            // Очищаем токен от возможных префиксов
            $cleanToken = str_replace(['Bearer ', 'bearer '], '', $token);
            // Добавляем токен в заголовок Authorization
            $request->headers->set('Authorization', 'Bearer ' . $cleanToken);
        }

        return $next($request);
    }
}

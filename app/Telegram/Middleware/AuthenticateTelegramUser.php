<?php

namespace App\Telegram\Middleware;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class AuthenticateTelegramUser
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $chatId = (string) $bot->user()?->id;

        $user = User::where('telegram_chat_id', $chatId)->first();

        if ($user === null) {
            $bot->sendMessage(
                "No encontré tu cuenta vinculada.\n\n" .
                "Registrate en la web y vinculá tu cuenta desde el perfil con tu Chat ID: <code>{$chatId}</code>",
                parse_mode: 'HTML'
            );
            return;
        }

        $bot->set('user', $user);

        $next($bot);
    }
}

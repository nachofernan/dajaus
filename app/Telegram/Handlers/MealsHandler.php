<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class MealsHandler
{
    public function suggest(Nutgram $bot): void
    {
        /** @var User $user */
        $user = $bot->get('user');

        $meal = $this->findAvailableMeal($user, 5)
            ?? $this->findAvailableMeal($user, 3)
            ?? $this->findAvailableMeal($user, 1)
            ?? $user->meals()->inRandomOrder()->first();

        if ($meal === null) {
            $bot->sendMessage('No tenés comidas registradas. Agregá algunas desde la web.');
            return;
        }

        $meal->update(['last_suggested_at' => now()]);

        $bot->sendMessage("🍽 *¿Qué tal si comés...?*\n\n*{$meal->name}*", parse_mode: 'Markdown');
    }

    private function findAvailableMeal(User $user, int $days): mixed
    {
        return $user->meals()
            ->where(function ($q) use ($days) {
                $q->whereNull('last_suggested_at')
                    ->orWhere('last_suggested_at', '<', now()->subDays($days));
            })
            ->inRandomOrder()
            ->first();
    }
}

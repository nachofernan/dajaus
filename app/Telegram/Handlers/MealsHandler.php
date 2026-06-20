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

    public function ingredients(Nutgram $bot): void
    {
        /** @var User $user */
        $user = $bot->get('user');

        $text = $bot->message()->text ?? '';
        $mealName = trim(preg_replace('/^\/ingredientes\S*\s*/i', '', $text));

        $meals = $user->meals()->with('ingredients')->orderBy('name')->get();

        if ($meals->isEmpty()) {
            $bot->sendMessage('No tenés comidas registradas. Agregá algunas desde la web.');
            return;
        }

        if ($mealName === '') {
            $lines = $meals->map(fn($m) => "• {$m->name}")->join("\n");
            $bot->sendMessage("¿De cuál comida?\n\n{$lines}\n\nUsá: /ingredientes [comida]");
            return;
        }

        $matches = $meals->filter(
            fn($m) => str_contains(mb_strtolower($m->name), mb_strtolower($mealName))
        );

        if ($matches->isEmpty()) {
            $lines = $meals->map(fn($m) => "• {$m->name}")->join("\n");
            $bot->sendMessage("No encontré esa comida. Tus comidas:\n\n{$lines}");
            return;
        }

        if ($matches->count() > 1) {
            $lines = $matches->map(fn($m) => "• {$m->name}")->join("\n");
            $bot->sendMessage("Encontré varias comidas, sé más específico:\n\n{$lines}");
            return;
        }

        $meal = $matches->first();

        if ($meal->ingredients->isEmpty()) {
            $bot->sendMessage("🍽 *{$meal->name}* no tiene ingredientes registrados todavía. Agregalos desde la web.", parse_mode: 'Markdown');
            return;
        }

        $lines = $meal->ingredients
            ->map(fn($i) => $i->quantity ? "• {$i->name} — {$i->quantity}" : "• {$i->name}")
            ->join("\n");

        $bot->sendMessage("🧂 *Ingredientes para {$meal->name}:*\n\n{$lines}", parse_mode: 'Markdown');
    }
}

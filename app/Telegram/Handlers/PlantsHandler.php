<?php

namespace App\Telegram\Handlers;

use App\Models\Plant;
use App\Models\PlantLog;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

class PlantsHandler
{
    public function list(Nutgram $bot): void
    {
        /** @var User $user */
        $user = $bot->get('user');
        $plants = $user->plants()->orderBy('name')->get();

        if ($plants->isEmpty()) {
            $bot->sendMessage('No tenés plantas registradas. Agregá una desde la web.');
            return;
        }

        $lines = $plants->map(fn($p) => "🌿 {$p->name}")->join("\n");
        $bot->sendMessage("*Tus plantas:*\n\n{$lines}", parse_mode: 'Markdown');
    }

    public function water(Nutgram $bot): void
    {
        /** @var User $user */
        $user = $bot->get('user');

        $text = $bot->message()->text ?? '';
        // Strip the /riego command and get the rest
        $args = trim(preg_replace('/^\/riego\S*\s*/i', '', $text));

        $plants = $user->plants()->orderBy('name')->get();

        if ($plants->isEmpty()) {
            $bot->sendMessage('No tenés plantas registradas. Agregá una desde la web.');
            return;
        }

        // If user has only one plant and no args, use that plant
        if ($plants->count() === 1 && $args === '') {
            $this->logWatering($bot, $plants->first(), '');
            return;
        }

        if ($args === '') {
            $lines = $plants->map(fn($p) => "• {$p->name}")->join("\n");
            $bot->sendMessage("¿Cuál regaste?\n\n{$lines}\n\nUsá: /riego [nombre]");
            return;
        }

        // Split: first word is plant name, rest is comment
        $parts = explode(' ', $args, 2);
        $plantName = $parts[0];
        $notes = $parts[1] ?? '';

        $plant = $plants->filter(
            fn($p) => str_contains(mb_strtolower($p->name), mb_strtolower($plantName))
        )->first();

        if ($plant === null) {
            $lines = $plants->map(fn($p) => "• {$p->name}")->join("\n");
            $bot->sendMessage("No encontré esa planta. Tus plantas:\n\n{$lines}");
            return;
        }

        $this->logWatering($bot, $plant, $notes);
    }

    private function logWatering(Nutgram $bot, Plant $plant, string $notes): void
    {
        PlantLog::create([
            'plant_id' => $plant->id,
            'notes' => $notes ?: null,
            'created_at' => now(),
        ]);

        $msg = "✅ Riego registrado para *{$plant->name}*";
        if ($notes) {
            $msg .= "\n📝 {$notes}";
        }

        $bot->sendMessage($msg, parse_mode: 'Markdown');
    }
}

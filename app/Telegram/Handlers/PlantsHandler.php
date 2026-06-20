<?php

namespace App\Telegram\Handlers;

use App\Models\Plant;
use App\Models\PlantLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $text = $bot->message()->text ?? '';
        // Strip the /riego command and get the rest
        $args = trim(preg_replace('/^\/riego\S*\s*/i', '', $text));

        $this->handleWatering($bot, $args, null);
    }

    /**
     * Handles a photo upload. The caption decides the intent:
     * "/riego [planta]" attaches the photo to a new watering log,
     * "/fotoplanta [planta]" sets it as the plant's profile photo.
     */
    public function photo(Nutgram $bot): void
    {
        $photos = $bot->message()->photo ?? [];

        if (empty($photos)) {
            return;
        }

        $caption = trim($bot->message()->caption ?? '');

        if ($caption !== '' && preg_match('/^\/?riego\b\s*(.*)$/iu', $caption, $m)) {
            $this->handleWatering($bot, trim($m[1]), $photos);
            return;
        }

        if (preg_match('/^\/?fotoplanta\b\s*(.*)$/iu', $caption, $m)) {
            $this->setProfilePhoto($bot, trim($m[1]), $photos);
            return;
        }

        $bot->sendMessage(
            "📷 Recibí la foto, pero no sé qué hacer con ella.\n\n" .
            "Mandala de nuevo con un texto (caption):\n" .
            "• `/riego [planta]` para registrar un riego con foto\n" .
            "• `/fotoplanta [planta]` para usarla como foto de perfil",
            parse_mode: 'Markdown'
        );
    }

    private function handleWatering(Nutgram $bot, string $args, ?array $photos): void
    {
        /** @var User $user */
        $user = $bot->get('user');
        $plants = $user->plants()->orderBy('name')->get();

        if ($plants->isEmpty()) {
            $bot->sendMessage('No tenés plantas registradas. Agregá una desde la web.');
            return;
        }

        // If user has only one plant and no args, use that plant
        if ($plants->count() === 1 && $args === '') {
            $this->logWatering($bot, $plants->first(), '', $photos);
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

        $this->logWatering($bot, $plant, $notes, $photos);
    }

    private function logWatering(Nutgram $bot, Plant $plant, string $notes, ?array $photos): void
    {
        $photoPath = $photos ? $this->downloadPhoto($bot, $photos, 'plant-logs') : null;

        PlantLog::create([
            'plant_id' => $plant->id,
            'notes' => $notes ?: null,
            'photo_path' => $photoPath,
            'created_at' => now(),
        ]);

        $msg = "✅ Riego registrado para *{$plant->name}*";
        if ($notes) {
            $msg .= "\n📝 {$notes}";
        }
        if ($photoPath) {
            $msg .= "\n📷 Foto guardada";
        }

        $bot->sendMessage($msg, parse_mode: 'Markdown');
    }

    private function setProfilePhoto(Nutgram $bot, string $plantName, array $photos): void
    {
        /** @var User $user */
        $user = $bot->get('user');
        $plants = $user->plants()->orderBy('name')->get();

        if ($plants->isEmpty()) {
            $bot->sendMessage('No tenés plantas registradas. Agregá una desde la web.');
            return;
        }

        $plant = null;
        if ($plantName === '' && $plants->count() === 1) {
            $plant = $plants->first();
        } elseif ($plantName !== '') {
            $plant = $plants->filter(
                fn($p) => str_contains(mb_strtolower($p->name), mb_strtolower($plantName))
            )->first();
        }

        if ($plant === null) {
            $lines = $plants->map(fn($p) => "• {$p->name}")->join("\n");
            $bot->sendMessage("¿De cuál planta es la foto?\n\n{$lines}\n\nMandala de nuevo con el texto: /fotoplanta [nombre]");
            return;
        }

        $plant->update(['photo_path' => $this->downloadPhoto($bot, $photos, 'plants')]);

        $bot->sendMessage("📷 Foto de perfil actualizada para *{$plant->name}*.", parse_mode: 'Markdown');
    }

    private function downloadPhoto(Nutgram $bot, array $photos, string $folder): string
    {
        $best = collect($photos)->sortByDesc('width')->first();
        $file = $bot->getFile($best->file_id);

        $contents = Http::get($file->url())->body();
        $extension = pathinfo($file->file_path ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        $relativePath = "{$folder}/" . Str::uuid() . ".{$extension}";

        Storage::disk('public')->put($relativePath, $contents);

        return $relativePath;
    }
}

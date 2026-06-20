<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\AIMatcherService;
use SergiX44\Nutgram\Nutgram;

class ShoppingHandler
{
    public function __construct(private AIMatcherService $ai) {}

    public function list(Nutgram $bot): void
    {
        /** @var User $user */
        $user = $bot->get('user');

        $items = $user->shoppingItems()
            ->whereNull('purchased_at')
            ->orderBy('name')
            ->get();

        if ($items->isEmpty()) {
            $bot->sendMessage('✅ La lista está vacía. ¡Todo comprado!');
            return;
        }

        $lines = $items->map(fn($i) => "• {$i->name}")->join("\n");
        $bot->sendMessage("🛒 *Lista de compras:*\n\n{$lines}", parse_mode: 'Markdown');
    }

    public function add(Nutgram $bot): void
    {
        /** @var User $user */
        $user = $bot->get('user');

        $text = $bot->message()->text ?? '';
        $itemName = trim(preg_replace('/^\/agregar\S*\s*/i', '', $text));

        if ($itemName === '') {
            $bot->sendMessage('Usá: /agregar [item]\nEjemplo: /agregar leche');
            return;
        }

        $allItems = $user->shoppingItems()->get();
        $result = $this->ai->match($itemName, $allItems);

        if ($result['action'] === 'match') {
            $existing = $allItems->firstWhere('id', $result['id']);

            if ($existing && $existing->purchased_at !== null) {
                $existing->update(['purchased_at' => null]);
                $bot->sendMessage("♻️ *{$existing->name}* reactivado en tu lista.", parse_mode: 'Markdown');
            } else {
                $bot->sendMessage("ℹ️ *{$existing->name}* ya está en tu lista.", parse_mode: 'Markdown');
            }
        } else {
            $user->shoppingItems()->create(['name' => $result['name']]);
            $bot->sendMessage("✅ *{$result['name']}* agregado a tu lista.", parse_mode: 'Markdown');
        }
    }

    public function markBought(Nutgram $bot): void
    {
        /** @var User $user */
        $user = $bot->get('user');

        $text = $bot->message()->text ?? '';
        $itemName = trim(preg_replace('/^\/compre\S*\s*/i', '', $text));

        if ($itemName === '') {
            $bot->sendMessage('Usá: /compre [item]\nEjemplo: /compre leche');
            return;
        }

        $pendingItems = $user->shoppingItems()->whereNull('purchased_at')->get();

        if ($pendingItems->isEmpty()) {
            $bot->sendMessage('No hay items pendientes en tu lista.');
            return;
        }

        $result = $this->ai->match($itemName, $pendingItems);

        if ($result['action'] === 'match') {
            $item = $pendingItems->firstWhere('id', $result['id']);
            $item->update(['purchased_at' => now()]);
            $bot->sendMessage("✅ *{$item->name}* marcado como comprado.", parse_mode: 'Markdown');
        } else {
            $bot->sendMessage("No encontré *{$itemName}* en tu lista pendiente.", parse_mode: 'Markdown');
        }
    }
}

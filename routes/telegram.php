<?php

use App\Telegram\Handlers\MealsHandler;
use App\Telegram\Handlers\PlantsHandler;
use App\Telegram\Handlers\ShoppingHandler;
use App\Telegram\Middleware\AuthenticateTelegramUser;
use SergiX44\Nutgram\Nutgram;

/** @var Nutgram $bot */

$bot->middleware(AuthenticateTelegramUser::class);

// Plantas
$bot->onCommand('plantas', [PlantsHandler::class, 'list'])->description('Lista tus plantas');
$bot->onCommand('riego', [PlantsHandler::class, 'water'])->description('Registra un riego');

// Comidas
$bot->onCommand('comer', [MealsHandler::class, 'suggest'])->description('Sugerencia de qué comer');

// Lista del súper
$bot->onCommand('lista', [ShoppingHandler::class, 'list'])->description('Ver lista de compras pendiente');
$bot->onCommand('agregar', [ShoppingHandler::class, 'add'])->description('Agregar item a la lista');
$bot->onCommand('compre', [ShoppingHandler::class, 'markBought'])->description('Marcar item como comprado');

// Ayuda
$bot->onCommand('ayuda', function (Nutgram $bot) {
    $bot->sendMessage(
        "*Casa Bot — Comandos disponibles*\n\n" .
        "🛒 *Lista del súper*\n" .
        "`/lista` — Ver items pendientes\n" .
        "`/agregar [item]` — Agregar item\n" .
        "`/compre [item]` — Marcar como comprado\n\n" .
        "🍽 *Comidas*\n" .
        "`/comer` — Sugerencia random\n\n" .
        "🌿 *Plantas*\n" .
        "`/plantas` — Ver tus plantas\n" .
        "`/riego [planta]` — Registrar riego\n" .
        "`/riego [planta] [nota]` — Registrar riego con nota",
        parse_mode: 'Markdown'
    );
})->description('Lista de comandos disponibles');

$bot->onCommand('start', function (Nutgram $bot) {
    $bot->sendMessage(
        "¡Hola! Soy tu bot personal.\n\nUsá /ayuda para ver los comandos disponibles."
    );
});

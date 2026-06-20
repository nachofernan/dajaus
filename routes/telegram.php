<?php

use App\Telegram\Handlers\MealsHandler;
use App\Telegram\Handlers\PlantsHandler;
use App\Telegram\Handlers\ShoppingHandler;
use App\Telegram\Middleware\AuthenticateTelegramUser;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

/** @var Nutgram $bot */

$bot->middleware(AuthenticateTelegramUser::class);

// Plantas
$bot->onCommand('plantas', [PlantsHandler::class, 'list'])->description('Lista tus plantas');
$bot->onCommand('riego', [PlantsHandler::class, 'water'])->description('Registra un riego');
// Nutgram matches command patterns exactly: "riego" alone never matches "/riego LeBron".
// This second pattern catches the command when it's followed by arguments.
$bot->onCommand('riego {args}', [PlantsHandler::class, 'water']);
// Una foto con caption "/riego [planta]" o "/fotoplanta [planta]" decide qué hacer con la imagen.
$bot->onPhoto([PlantsHandler::class, 'photo']);

// Comidas
$bot->onCommand('comer', [MealsHandler::class, 'suggest'])->description('Sugerencia de qué comer');
$bot->onCommand('ingredientes', [MealsHandler::class, 'ingredients'])->description('Ver ingredientes de una comida');
$bot->onCommand('ingredientes {args}', [MealsHandler::class, 'ingredients']);

// Lista del súper
$bot->onCommand('lista', [ShoppingHandler::class, 'list'])->description('Ver lista de compras pendiente');
$bot->onCommand('agregar', [ShoppingHandler::class, 'add'])->description('Agregar item a la lista');
$bot->onCommand('agregar {args}', [ShoppingHandler::class, 'add']);
$bot->onCommand('compre', [ShoppingHandler::class, 'markBought'])->description('Marcar item como comprado');
$bot->onCommand('compre {args}', [ShoppingHandler::class, 'markBought']);
$bot->onCommand('favoritos', [ShoppingHandler::class, 'favorites'])->description('Ver items favoritos');

// Ayuda
$bot->onCommand('ayuda', function (Nutgram $bot) {
    $bot->sendMessage(
        "*Casa Bot — Comandos disponibles*\n\n" .
        "🛒 *Lista del súper*\n" .
        "`/lista` — Ver items pendientes\n" .
        "`/agregar [item]` — Agregar item\n" .
        "`/compre [item]` — Marcar como comprado\n" .
        "`/favoritos` — Ver items favoritos\n\n" .
        "🍽 *Comidas*\n" .
        "`/comer` — Sugerencia random\n" .
        "`/ingredientes [comida]` — Ver qué necesitás para una comida\n\n" .
        "🌿 *Plantas*\n" .
        "`/plantas` — Ver tus plantas\n" .
        "`/riego [planta]` — Registrar riego\n" .
        "`/riego [planta] [nota]` — Registrar riego con nota\n" .
        "📷 Mandá una foto con caption `/riego [planta]` para guardarla en el riego, " .
        "o `/fotoplanta [planta]` para usarla como foto de perfil",
        parse_mode: 'Markdown'
    );
})->description('Lista de comandos disponibles');

$bot->onCommand('start', function (Nutgram $bot) {
    $bot->sendMessage(
        "¡Hola! Soy tu bot personal.\n\nUsá /ayuda para ver los comandos disponibles."
    );
});

// Si no entendió nada de lo que mandaste, avisa en vez de quedarse mudo
$bot->fallback(function (Nutgram $bot) {
    $bot->sendMessage("No entendí ese comando 🤔\nUsá /ayuda para ver la lista completa.");
});

// Cualquier excepción no controlada (IA caída, error de Telegram, etc.) se loguea
// y el usuario recibe un aviso en vez de que el bot quede en silencio
$bot->onException(function (Nutgram $bot, Throwable $e) {
    Log::error('Error en el bot de Telegram: ' . $e->getMessage(), ['exception' => $e]);
    $bot->sendMessage('⚠️ Uy, algo salió mal de mi lado. Probá de nuevo en un rato.');
});

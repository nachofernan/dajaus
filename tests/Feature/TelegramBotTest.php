<?php

use App\Models\PlantLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Testing\FakeNutgram;

function sendTelegramText(User $user, string $text): FakeNutgram
{
    /** @var FakeNutgram $bot */
    $bot = app(Nutgram::class);

    $bot->hearMessage([
        'text' => $text,
        'from' => ['id' => (int) $user->telegram_chat_id, 'is_bot' => false, 'first_name' => $user->name],
    ])->reply();

    return $bot;
}

it('registra el riego cuando el comando trae el nombre de la planta', function () {
    $user = User::factory()->create(['telegram_chat_id' => '555']);
    $lebron = $user->plants()->create(['name' => 'LeBron']);
    $user->plants()->create(['name' => 'Otra']);

    $bot = sendTelegramText($user, '/riego LeBron');

    expect(PlantLog::where('plant_id', $lebron->id)->count())->toBe(1);
    $bot->assertCalled('sendMessage', 1);
});

it('registra el riego sin argumentos cuando hay una sola planta', function () {
    $user = User::factory()->create(['telegram_chat_id' => '556']);
    $plant = $user->plants()->create(['name' => 'LeBron']);

    $bot = sendTelegramText($user, '/riego');

    expect(PlantLog::where('plant_id', $plant->id)->count())->toBe(1);
    $bot->assertCalled('sendMessage', 1);
});

it('agrega un item a la lista cuando el comando trae el nombre del item', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => '{"action":"create","name":"leche"}']]],
        ]),
    ]);

    $user = User::factory()->create(['telegram_chat_id' => '557']);

    $bot = sendTelegramText($user, '/agregar leche');

    expect($user->shoppingItems()->where('name', 'leche')->exists())->toBeTrue();
    $bot->assertCalled('sendMessage', 1);
});

it('marca un item como comprado cuando el comando trae el nombre del item', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => '{"action":"match","id":1,"name":"leche"}']]],
        ]),
    ]);

    $user = User::factory()->create(['telegram_chat_id' => '558']);
    $item = $user->shoppingItems()->create(['name' => 'leche']);

    sendTelegramText($user, '/compre leche');

    expect($item->refresh()->purchased_at)->not->toBeNull();
});

it('responde con un mensaje de ayuda cuando no reconoce el comando', function () {
    $user = User::factory()->create(['telegram_chat_id' => '559']);

    $bot = sendTelegramText($user, '/comandoquenoexiste');

    $bot->assertCalled('sendMessage', 1);
    $bot->assertReplyText("No entendí ese comando 🤔\nUsá /ayuda para ver la lista completa.");
});

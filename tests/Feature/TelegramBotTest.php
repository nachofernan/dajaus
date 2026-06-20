<?php

use App\Models\PlantLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

function sendTelegramPhoto(User $user, ?string $caption): FakeNutgram
{
    /** @var FakeNutgram $bot */
    $bot = app(Nutgram::class);

    $bot->hearMessage([
        'caption' => $caption,
        'photo' => [
            ['file_id' => 'tg-photo-1', 'file_unique_id' => 'uniq-1', 'width' => 800, 'height' => 600],
        ],
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

it('lista los items favoritos', function () {
    $user = User::factory()->create(['telegram_chat_id' => '560']);
    $user->shoppingItems()->create(['name' => 'leche', 'is_favorite' => true]);
    $user->shoppingItems()->create(['name' => 'pan']);

    $bot = sendTelegramText($user, '/favoritos');

    $bot->assertCalled('sendMessage', 1);
});

it('devuelve los ingredientes de una comida', function () {
    $user = User::factory()->create(['telegram_chat_id' => '561']);
    $meal = $user->meals()->create(['name' => 'Milanesas con puré']);
    $meal->ingredients()->create(['name' => 'papa', 'quantity' => '1 kg']);
    $meal->ingredients()->create(['name' => 'carne']);

    $bot = sendTelegramText($user, '/ingredientes milanesas');

    $bot->assertCalled('sendMessage', 1);
});

it('pide precisar la comida cuando no se manda nombre en /ingredientes', function () {
    $user = User::factory()->create(['telegram_chat_id' => '562']);
    $user->meals()->create(['name' => 'Milanesas con puré']);

    $bot = sendTelegramText($user, '/ingredientes');

    $bot->assertCalled('sendMessage', 1);
});

it('registra un riego con foto cuando el caption trae /riego', function () {
    Storage::fake('public');
    Http::fake(['*' => Http::response('contenido-de-imagen')]);

    $user = User::factory()->create(['telegram_chat_id' => '563']);
    $plant = $user->plants()->create(['name' => 'LeBron']);

    $bot = sendTelegramPhoto($user, '/riego LeBron');

    $log = PlantLog::where('plant_id', $plant->id)->first();
    expect($log)->not->toBeNull();
    expect($log->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($log->photo_path);
    $bot->assertCalled('sendMessage', 1);

    $html = \Livewire\Livewire::actingAs($user)
        ->test(\App\Livewire\Plants\PlantManager::class)
        ->call('toggleLogs', $plant->id)
        ->html();

    expect($html)->toContain($log->photo_url);

    $dashboardHtml = \Livewire\Livewire::actingAs($user)
        ->test(\App\Livewire\Dashboard::class)
        ->html();

    expect($dashboardHtml)->not->toBeEmpty();
});

it('actualiza la foto de perfil de una planta cuando el caption trae /fotoplanta', function () {
    Storage::fake('public');
    Http::fake(['*' => Http::response('contenido-de-imagen')]);

    $user = User::factory()->create(['telegram_chat_id' => '564']);
    $plant = $user->plants()->create(['name' => 'LeBron']);

    $bot = sendTelegramPhoto($user, '/fotoplanta LeBron');

    $plant->refresh();
    expect($plant->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($plant->photo_path);
    $bot->assertCalled('sendMessage', 1);
});

it('avisa cuando manda una foto sin caption reconocible', function () {
    $user = User::factory()->create(['telegram_chat_id' => '565']);
    $user->plants()->create(['name' => 'LeBron']);

    $bot = sendTelegramPhoto($user, null);

    $bot->assertCalled('sendMessage', 1);
    expect(PlantLog::count())->toBe(0);
});

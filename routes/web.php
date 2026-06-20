<?php

use App\Livewire\Dashboard;
use App\Livewire\Meals\MealManager;
use App\Livewire\Plants\PlantManager;
use App\Livewire\Shopping\ShoppingList;
use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::post('/telegram/webhook', function (Nutgram $bot) {
    $bot->run();
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/plantas', PlantManager::class)->name('plants');
    Route::get('/comidas', MealManager::class)->name('meals');
    Route::get('/lista', ShoppingList::class)->name('shopping');
});

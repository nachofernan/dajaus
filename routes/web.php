<?php

use App\Livewire\Meals\MealManager;
use App\Livewire\Plants\PlantManager;
use App\Livewire\Shopping\ShoppingList;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/plantas', PlantManager::class)->name('plants');
    Route::get('/comidas', MealManager::class)->name('meals');
    Route::get('/lista', ShoppingList::class)->name('shopping');
});

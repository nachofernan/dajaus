<?php

use App\Livewire\Meals\MealManager;
use App\Models\User;
use Livewire\Livewire;

it('agrega un ingrediente a una comida', function () {
    $user = User::factory()->create();
    $meal = $user->meals()->create(['name' => 'Milanesas con puré']);

    Livewire::actingAs($user)
        ->test(MealManager::class)
        ->call('manageIngredients', $meal->id)
        ->set('ingredientName', 'papa')
        ->set('ingredientQuantity', '1 kg')
        ->call('addIngredient');

    expect($meal->ingredients()->where('name', 'papa')->where('quantity', '1 kg')->exists())->toBeTrue();
});

it('elimina un ingrediente', function () {
    $user = User::factory()->create();
    $meal = $user->meals()->create(['name' => 'Tarta']);
    $ingredient = $meal->ingredients()->create(['name' => 'harina', 'quantity' => '500g']);

    Livewire::actingAs($user)
        ->test(MealManager::class)
        ->call('deleteIngredient', $ingredient->id);

    expect($meal->ingredients()->where('id', $ingredient->id)->exists())->toBeFalse();
});

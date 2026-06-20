<?php

use App\Livewire\Shopping\ShoppingList;
use App\Models\User;
use Livewire\Livewire;

it('marca y desmarca un item como favorito', function () {
    $user = User::factory()->create();
    $item = $user->shoppingItems()->create(['name' => 'leche']);

    Livewire::actingAs($user)
        ->test(ShoppingList::class)
        ->call('toggleFavorite', $item->id);

    expect($item->refresh()->is_favorite)->toBeTrue();

    Livewire::actingAs($user)
        ->test(ShoppingList::class)
        ->call('toggleFavorite', $item->id);

    expect($item->refresh()->is_favorite)->toBeFalse();
});

it('guarda una nota para un item', function () {
    $user = User::factory()->create();
    $item = $user->shoppingItems()->create(['name' => 'pan']);

    Livewire::actingAs($user)
        ->test(ShoppingList::class)
        ->call('editNotes', $item->id)
        ->set('notesInput', 'integral, panadería de la esquina')
        ->call('saveNotes');

    expect($item->refresh()->notes)->toBe('integral, panadería de la esquina');
});

it('limpiar comprados no elimina los favoritos', function () {
    $user = User::factory()->create();
    $favorite = $user->shoppingItems()->create(['name' => 'leche', 'purchased_at' => now(), 'is_favorite' => true]);
    $regular = $user->shoppingItems()->create(['name' => 'pan', 'purchased_at' => now()]);

    Livewire::actingAs($user)
        ->test(ShoppingList::class)
        ->call('clearBought');

    expect($user->shoppingItems()->find($favorite->id))->not->toBeNull();
    expect($user->shoppingItems()->find($regular->id))->toBeNull();
});

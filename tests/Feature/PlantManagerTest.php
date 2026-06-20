<?php

use App\Livewire\Plants\PlantManager;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('crea una planta con foto de perfil', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PlantManager::class)
        ->set('name', 'LeBron')
        ->set('photo', UploadedFile::fake()->create('lebron.jpg', 10, 'image/jpeg'))
        ->call('save');

    $plant = $user->plants()->where('name', 'LeBron')->first();

    expect($plant)->not->toBeNull();
    expect($plant->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($plant->photo_path);
});

it('reemplaza la foto anterior al editar y borra el archivo viejo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $plant = $user->plants()->create([
        'name' => 'LeBron',
        'photo_path' => 'plants/old.jpg',
    ]);
    Storage::disk('public')->put('plants/old.jpg', 'contenido');

    Livewire::actingAs($user)
        ->test(PlantManager::class)
        ->call('edit', $plant->id)
        ->set('photo', UploadedFile::fake()->create('new.jpg', 10, 'image/jpeg'))
        ->call('save');

    $plant->refresh();

    expect($plant->photo_path)->not->toBe('plants/old.jpg');
    Storage::disk('public')->assertMissing('plants/old.jpg');
    Storage::disk('public')->assertExists($plant->photo_path);
});

it('borra la foto al eliminar la planta', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $plant = $user->plants()->create([
        'name' => 'LeBron',
        'photo_path' => 'plants/lebron.jpg',
    ]);
    Storage::disk('public')->put('plants/lebron.jpg', 'contenido');

    Livewire::actingAs($user)
        ->test(PlantManager::class)
        ->call('delete', $plant->id);

    Storage::disk('public')->assertMissing('plants/lebron.jpg');
});

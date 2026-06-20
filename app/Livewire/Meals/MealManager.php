<?php

namespace App\Livewire\Meals;

use Livewire\Component;

class MealManager extends Component
{
    public string $name = '';
    public ?int $editingId = null;
    public ?int $managingIngredientsId = null;
    public string $ingredientName = '';
    public string $ingredientQuantity = '';

    protected $rules = [
        'name' => 'required|string|max:150',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            auth()->user()->meals()->findOrFail($this->editingId)->update(['name' => $this->name]);
        } else {
            auth()->user()->meals()->create(['name' => $this->name]);
        }

        $this->reset('name', 'editingId');
    }

    public function edit(int $id): void
    {
        $meal = auth()->user()->meals()->findOrFail($id);
        $this->editingId = $meal->id;
        $this->name = $meal->name;
        $this->managingIngredientsId = null;
    }

    public function delete(int $id): void
    {
        auth()->user()->meals()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->reset('editingId', 'name');
        }
        if ($this->managingIngredientsId === $id) {
            $this->managingIngredientsId = null;
        }
    }

    public function resetSuggested(int $id): void
    {
        auth()->user()->meals()->findOrFail($id)->update(['last_suggested_at' => null]);
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'name');
    }

    public function manageIngredients(int $mealId): void
    {
        $this->managingIngredientsId = $this->managingIngredientsId === $mealId ? null : $mealId;
        $this->reset('editingId', 'name', 'ingredientName', 'ingredientQuantity');
    }

    public function addIngredient(): void
    {
        $this->validate([
            'ingredientName' => 'required|string|max:150',
            'ingredientQuantity' => 'nullable|string|max:50',
        ]);

        $meal = auth()->user()->meals()->findOrFail($this->managingIngredientsId);
        $meal->ingredients()->create([
            'name' => $this->ingredientName,
            'quantity' => $this->ingredientQuantity !== '' ? $this->ingredientQuantity : null,
        ]);

        $this->reset('ingredientName', 'ingredientQuantity');
    }

    public function deleteIngredient(int $ingredientId): void
    {
        $mealIds = auth()->user()->meals()->pluck('id');
        \App\Models\MealIngredient::whereIn('meal_id', $mealIds)->findOrFail($ingredientId)->delete();
    }

    public function render()
    {
        $meals = auth()->user()->meals()
            ->with('ingredients')
            ->orderBy('name')
            ->get();

        return view('livewire.meals.meal-manager', compact('meals'))
            ->layout('layouts.app');
    }
}

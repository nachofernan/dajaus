<?php

namespace App\Livewire\Meals;

use Livewire\Component;

class MealManager extends Component
{
    public string $name = '';
    public ?int $editingId = null;

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
    }

    public function delete(int $id): void
    {
        auth()->user()->meals()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->reset('editingId', 'name');
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

    public function render()
    {
        $meals = auth()->user()->meals()
            ->orderBy('name')
            ->get();

        return view('livewire.meals.meal-manager', compact('meals'))
            ->layout('layouts.app');
    }
}

<?php

namespace App\Livewire\Shopping;

use Livewire\Component;

class ShoppingList extends Component
{
    public string $name = '';

    protected $rules = [
        'name' => 'required|string|max:150',
    ];

    public function add(): void
    {
        $this->validate();

        $existing = auth()->user()->shoppingItems()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($this->name)])
            ->first();

        if ($existing) {
            if ($existing->purchased_at !== null) {
                $existing->update(['purchased_at' => null]);
            }
            // already pending: do nothing, form clears either way
        } else {
            auth()->user()->shoppingItems()->create(['name' => $this->name]);
        }

        $this->reset('name');
    }

    public function toggle(int $id): void
    {
        $item = auth()->user()->shoppingItems()->findOrFail($id);
        $item->update([
            'purchased_at' => $item->purchased_at ? null : now(),
        ]);
    }

    public function delete(int $id): void
    {
        auth()->user()->shoppingItems()->findOrFail($id)->delete();
    }

    public function clearBought(): void
    {
        auth()->user()->shoppingItems()->whereNotNull('purchased_at')->delete();
    }

    public function render()
    {
        $pending = auth()->user()->shoppingItems()
            ->whereNull('purchased_at')
            ->orderBy('name')
            ->get();

        $bought = auth()->user()->shoppingItems()
            ->whereNotNull('purchased_at')
            ->orderBy('purchased_at', 'desc')
            ->get();

        return view('livewire.shopping.shopping-list', compact('pending', 'bought'))
            ->layout('layouts.app');
    }
}

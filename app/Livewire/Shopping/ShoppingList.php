<?php

namespace App\Livewire\Shopping;

use Livewire\Component;

class ShoppingList extends Component
{
    public string $name = '';
    public ?int $editingNotesId = null;
    public string $notesInput = '';

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

    public function toggleFavorite(int $id): void
    {
        $item = auth()->user()->shoppingItems()->findOrFail($id);
        $item->update(['is_favorite' => !$item->is_favorite]);
    }

    public function editNotes(int $id): void
    {
        $item = auth()->user()->shoppingItems()->findOrFail($id);
        $this->editingNotesId = $item->id;
        $this->notesInput = $item->notes ?? '';
    }

    public function saveNotes(): void
    {
        if ($this->editingNotesId === null) {
            return;
        }

        auth()->user()->shoppingItems()->findOrFail($this->editingNotesId)
            ->update(['notes' => $this->notesInput !== '' ? $this->notesInput : null]);

        $this->reset('editingNotesId', 'notesInput');
    }

    public function cancelNotes(): void
    {
        $this->reset('editingNotesId', 'notesInput');
    }

    public function delete(int $id): void
    {
        auth()->user()->shoppingItems()->findOrFail($id)->delete();
    }

    public function clearBought(): void
    {
        auth()->user()->shoppingItems()
            ->whereNotNull('purchased_at')
            ->where('is_favorite', false)
            ->delete();
    }

    public function render()
    {
        $pending = auth()->user()->shoppingItems()
            ->whereNull('purchased_at')
            ->orderBy('is_favorite', 'desc')
            ->orderBy('name')
            ->get();

        $bought = auth()->user()->shoppingItems()
            ->whereNotNull('purchased_at')
            ->orderBy('is_favorite', 'desc')
            ->orderBy('purchased_at', 'desc')
            ->get();

        return view('livewire.shopping.shopping-list', compact('pending', 'bought'))
            ->layout('layouts.app');
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        $pendingItems = $user->shoppingItems()
            ->whereNull('purchased_at')
            ->orderBy('created_at')
            ->get();

        $meals = $user->meals()->get();

        $lastSuggestedMeal = $meals
            ->whereNotNull('last_suggested_at')
            ->sortByDesc('last_suggested_at')
            ->first();

        $plants = $user->plants()
            ->with(['logs' => fn ($q) => $q->latest('created_at')->limit(1)])
            ->orderBy('name')
            ->get()
            ->map(function ($plant) {
                $lastWatered = $plant->logs->first()?->created_at;

                return (object) [
                    'id' => $plant->id,
                    'name' => $plant->name,
                    'last_watered' => $lastWatered,
                    'days_since' => $lastWatered ? (int) $lastWatered->diffInDays(now()) : null,
                ];
            })
            ->sortByDesc(fn ($plant) => $plant->days_since ?? PHP_INT_MAX)
            ->values();

        return view('livewire.dashboard', [
            'pendingItems' => $pendingItems,
            'mealsCount' => $meals->count(),
            'lastSuggestedMeal' => $lastSuggestedMeal,
            'plants' => $plants,
        ])->layout('layouts.app');
    }
}

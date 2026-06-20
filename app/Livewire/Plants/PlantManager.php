<?php

namespace App\Livewire\Plants;

use App\Models\Plant;
use Livewire\Component;

class PlantManager extends Component
{
    public string $name = '';
    public ?int $editingId = null;
    public ?int $viewingLogsId = null;

    protected $rules = [
        'name' => 'required|string|max:100',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            auth()->user()->plants()->findOrFail($this->editingId)->update(['name' => $this->name]);
        } else {
            auth()->user()->plants()->create(['name' => $this->name]);
        }

        $this->reset('name', 'editingId');
    }

    public function edit(Plant $plant): void
    {
        $this->editingId = $plant->id;
        $this->name = $plant->name;
        $this->viewingLogsId = null;
    }

    public function delete(Plant $plant): void
    {
        auth()->user()->plants()->findOrFail($plant->id)->delete();
        if ($this->viewingLogsId === $plant->id) {
            $this->viewingLogsId = null;
        }
    }

    public function toggleLogs(int $plantId): void
    {
        $this->viewingLogsId = $this->viewingLogsId === $plantId ? null : $plantId;
        $this->reset('editingId', 'name');
    }

    public function deleteLog(int $logId): void
    {
        $plantIds = auth()->user()->plants()->pluck('id');
        \App\Models\PlantLog::whereIn('plant_id', $plantIds)->findOrFail($logId)->delete();
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'name');
    }

    public function render()
    {
        $plants = auth()->user()->plants()
            ->with(['logs' => fn($q) => $q->latest('created_at')->limit(50)])
            ->orderBy('name')
            ->get();

        $viewingPlant = $this->viewingLogsId
            ? $plants->firstWhere('id', $this->viewingLogsId)
            : null;

        return view('livewire.plants.plant-manager', compact('plants', 'viewingPlant'))
            ->layout('layouts.app');
    }
}

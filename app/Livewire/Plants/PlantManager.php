<?php

namespace App\Livewire\Plants;

use App\Models\Plant;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PlantManager extends Component
{
    use WithFileUploads;

    public string $name = '';
    public $photo = null;
    public ?int $editingId = null;
    public ?int $viewingLogsId = null;

    protected $rules = [
        'name' => 'required|string|max:100',
        'photo' => 'nullable|image|max:5120',
    ];

    public function save(): void
    {
        $this->validate();

        $data = ['name' => $this->name];

        if ($this->photo) {
            $data['photo_path'] = $this->photo->store('plants', 'public');
        }

        if ($this->editingId) {
            $plant = auth()->user()->plants()->findOrFail($this->editingId);
            if ($this->photo && $plant->photo_path) {
                Storage::disk('public')->delete($plant->photo_path);
            }
            $plant->update($data);
        } else {
            auth()->user()->plants()->create($data);
        }

        $this->reset('name', 'photo', 'editingId');
    }

    public function edit(Plant $plant): void
    {
        $this->editingId = $plant->id;
        $this->name = $plant->name;
        $this->viewingLogsId = null;
        $this->reset('photo');
    }

    public function delete(Plant $plant): void
    {
        $plant = auth()->user()->plants()->findOrFail($plant->id);
        if ($plant->photo_path) {
            Storage::disk('public')->delete($plant->photo_path);
        }
        $plant->delete();
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
        $this->reset('editingId', 'name', 'photo');
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

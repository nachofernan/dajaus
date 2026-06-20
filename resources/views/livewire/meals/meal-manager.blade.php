<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        🍽 Comidas
    </h2>
</x-slot>

<div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Formulario --}}
            <div class="bg-white shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ $editingId ? 'Editar comida' : 'Agregar comida' }}
                </h3>
                <form wire:submit="save" class="flex gap-3">
                    <input
                        wire:model="name"
                        type="text"
                        placeholder="Ej: Milanesas con puré"
                        class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm"
                    />
                    <button type="submit"
                        class="px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-md hover:bg-orange-600 transition">
                        {{ $editingId ? 'Guardar' : 'Agregar' }}
                    </button>
                    @if ($editingId)
                        <button type="button" wire:click="cancelEdit"
                            class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                    @endif
                </form>
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Lista --}}
            <div class="bg-white shadow-xl sm:rounded-lg divide-y divide-gray-100">
                @forelse ($meals as $meal)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <span class="text-gray-800 font-medium">{{ $meal->name }}</span>
                            @if ($meal->last_suggested_at)
                                <span class="ml-3 text-xs text-gray-400">
                                    Sugerida {{ $meal->last_suggested_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($meal->last_suggested_at)
                                <button wire:click="resetSuggested({{ $meal->id }})"
                                    title="Resetear historial de sugerencia"
                                    class="text-sm px-3 py-1 text-gray-400 hover:bg-gray-100 rounded-md transition">
                                    ↺
                                </button>
                            @endif
                            <button wire:click="edit({{ $meal->id }})"
                                class="text-sm px-3 py-1 text-blue-600 hover:bg-blue-50 rounded-md transition">
                                Editar
                            </button>
                            <button wire:click="delete({{ $meal->id }})"
                                wire:confirm="¿Eliminar {{ $meal->name }}?"
                                class="text-sm px-3 py-1 text-red-600 hover:bg-red-50 rounded-md transition">
                                Eliminar
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-gray-500">
                        No tenés comidas registradas todavía.
                    </div>
                @endforelse
            </div>

    </div>
</div>

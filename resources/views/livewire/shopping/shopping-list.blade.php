<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        🛒 Lista del Súper
    </h2>
</x-slot>

<div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Formulario --}}
            <div class="bg-white shadow-xl sm:rounded-lg p-6">
                <form wire:submit="add" class="flex gap-3">
                    <input
                        wire:model="name"
                        type="text"
                        placeholder="Agregar item..."
                        class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                        Agregar
                    </button>
                </form>
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Pendientes --}}
            <div class="bg-white shadow-xl sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-medium text-gray-700">
                        Pendientes
                        @if ($pending->count())
                            <span class="ml-2 text-sm font-normal text-gray-400">({{ $pending->count() }})</span>
                        @endif
                    </h3>
                </div>
                @forelse ($pending as $item)
                    <div class="px-6 py-3 border-b border-gray-50 last:border-0">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-3 cursor-pointer flex-1">
                                <input
                                    type="checkbox"
                                    wire:click="toggle({{ $item->id }})"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 cursor-pointer"
                                />
                                <span class="text-gray-800">{{ $item->name }}</span>
                            </label>
                            <div class="flex items-center gap-1 ml-4">
                                <button wire:click="toggleFavorite({{ $item->id }})"
                                    title="Favorito"
                                    class="transition {{ $item->is_favorite ? 'text-amber-400' : 'text-gray-300 hover:text-amber-400' }}">
                                    {{ $item->is_favorite ? '⭐' : '☆' }}
                                </button>
                                <button wire:click="editNotes({{ $item->id }})"
                                    title="Nota"
                                    class="text-gray-300 hover:text-blue-500 transition">
                                    📝
                                </button>
                                <button wire:click="delete({{ $item->id }})"
                                    class="text-gray-300 hover:text-red-500 transition">
                                    ✕
                                </button>
                            </div>
                        </div>

                        @if ($item->notes && $editingNotesId !== $item->id)
                            <p class="text-xs text-gray-400 mt-1 ml-7">{{ $item->notes }}</p>
                        @endif

                        @if ($editingNotesId === $item->id)
                            <form wire:submit="saveNotes" class="flex gap-2 mt-2 ml-7">
                                <input
                                    wire:model="notesInput"
                                    type="text"
                                    placeholder="Marca, tienda, precio..."
                                    class="flex-1 text-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                                <button type="submit" class="text-xs text-blue-600 hover:underline">Guardar</button>
                                <button type="button" wire:click="cancelNotes" class="text-xs text-gray-400 hover:underline">Cancelar</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-400 text-sm">
                        ✅ Todo comprado.
                    </div>
                @endforelse
            </div>

            {{-- Comprados --}}
            @if ($bought->count())
                <div class="bg-white shadow-xl sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-medium text-gray-400 text-sm">
                            Comprados ({{ $bought->count() }})
                        </h3>
                        <button wire:click="clearBought"
                            wire:confirm="¿Eliminar todos los items comprados?"
                            class="text-xs text-red-400 hover:text-red-600 transition">
                            Limpiar
                        </button>
                    </div>
                    @foreach ($bought as $item)
                        <div class="flex items-center justify-between px-6 py-3 border-b border-gray-50 last:border-0">
                            <label class="flex items-center gap-3 cursor-pointer flex-1">
                                <input
                                    type="checkbox"
                                    checked
                                    wire:click="toggle({{ $item->id }})"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 cursor-pointer"
                                />
                                <span class="text-gray-400 line-through">{{ $item->name }}</span>
                            </label>
                            <div class="flex items-center gap-1 ml-4">
                                <button wire:click="toggleFavorite({{ $item->id }})"
                                    title="Favorito — sobrevive a 'Limpiar'"
                                    class="transition {{ $item->is_favorite ? 'text-amber-400' : 'text-gray-300 hover:text-amber-400' }}">
                                    {{ $item->is_favorite ? '⭐' : '☆' }}
                                </button>
                                <button wire:click="delete({{ $item->id }})"
                                    class="text-gray-300 hover:text-red-500 transition">
                                    ✕
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

    </div>
</div>

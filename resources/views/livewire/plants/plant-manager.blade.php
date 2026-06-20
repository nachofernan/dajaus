<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        🌿 Plantas
    </h2>
</x-slot>

<div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Formulario agregar / editar --}}
            <div class="bg-white shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ $editingId ? 'Editar planta' : 'Agregar planta' }}
                </h3>
                <form wire:submit="save" class="flex gap-3">
                    <input
                        wire:model="name"
                        type="text"
                        placeholder="Nombre de la planta"
                        class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                    />
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
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

            {{-- Lista de plantas --}}
            <div class="bg-white shadow-xl sm:rounded-lg divide-y divide-gray-100">
                @forelse ($plants as $plant)
                    <div>
                        <div class="flex items-center justify-between px-6 py-4">
                            <span class="text-gray-800 font-medium">{{ $plant->name }}</span>
                            <div class="flex items-center gap-2">
                                <button wire:click="toggleLogs({{ $plant->id }})"
                                    class="text-sm px-3 py-1 rounded-md {{ $viewingLogsId === $plant->id ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:bg-gray-100' }} transition">
                                    📋 Riegos ({{ $plant->logs->count() }})
                                </button>
                                <button wire:click="edit({{ $plant->id }})"
                                    class="text-sm px-3 py-1 text-blue-600 hover:bg-blue-50 rounded-md transition">
                                    Editar
                                </button>
                                <button wire:click="delete({{ $plant->id }})"
                                    wire:confirm="¿Eliminar {{ $plant->name }} y todos sus logs?"
                                    class="text-sm px-3 py-1 text-red-600 hover:bg-red-50 rounded-md transition">
                                    Eliminar
                                </button>
                            </div>
                        </div>

                        {{-- Timeline de riegos --}}
                        @if ($viewingLogsId === $plant->id)
                            <div class="bg-gray-50 px-6 pb-4">
                                @if ($plant->logs->isEmpty())
                                    <p class="text-sm text-gray-500 py-3">Sin riegos registrados.</p>
                                @else
                                    <ul class="space-y-2 pt-2">
                                        @foreach ($plant->logs as $log)
                                            <li class="flex items-start justify-between text-sm">
                                                <div>
                                                    <span class="text-gray-500">
                                                        {{ $log->created_at->format('d/m/Y H:i') }}
                                                    </span>
                                                    @if ($log->notes)
                                                        <span class="ml-2 text-gray-700">— {{ $log->notes }}</span>
                                                    @endif
                                                </div>
                                                <button wire:click="deleteLog({{ $log->id }})"
                                                    class="text-red-400 hover:text-red-600 ml-4 flex-shrink-0">
                                                    ✕
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-gray-500">
                        No tenés plantas registradas todavía.
                    </div>
                @endforelse
            </div>

    </div>
</div>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        👋 Hola, {{ auth()->user()->name }}
    </h2>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Plantas --}}
            <a href="{{ route('plants') }}" class="block bg-white shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">🌿 Plantas</h3>
                    <span class="text-sm text-gray-400">{{ $plants->count() }}</span>
                </div>

                @if ($plants->isEmpty())
                    <p class="text-sm text-gray-400">No tenés plantas registradas.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($plants->take(3) as $plant)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">{{ $plant->name }}</span>
                                @if ($plant->days_since === null)
                                    <span class="text-amber-600 font-medium">Sin riegos</span>
                                @elseif ($plant->days_since >= 7)
                                    <span class="text-amber-600 font-medium">hace {{ $plant->days_since }}d</span>
                                @else
                                    <span class="text-gray-400">hace {{ $plant->days_since }}d</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($plants->count() > 3)
                        <p class="text-xs text-gray-400 mt-3">+{{ $plants->count() - 3 }} más</p>
                    @endif
                @endif
            </a>

            {{-- Comidas --}}
            <a href="{{ route('meals') }}" class="block bg-white shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">🍽 Comidas</h3>
                    <span class="text-sm text-gray-400">{{ $mealsCount }}</span>
                </div>

                @if ($lastSuggestedMeal)
                    <p class="text-sm text-gray-400">Última sugerencia:</p>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $lastSuggestedMeal->name }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $lastSuggestedMeal->last_suggested_at->diffForHumans() }}</p>
                @else
                    <p class="text-sm text-gray-400">Todavía no pediste ninguna sugerencia.</p>
                @endif
            </a>

            {{-- Lista del súper --}}
            <a href="{{ route('shopping') }}" class="block bg-white shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">🛒 Lista</h3>
                    <span class="text-sm text-gray-400">{{ $pendingItems->count() }}</span>
                </div>

                @if ($pendingItems->isEmpty())
                    <p class="text-sm text-gray-400">✅ Todo comprado.</p>
                @else
                    <ul class="space-y-1">
                        @foreach ($pendingItems->take(5) as $item)
                            <li class="text-sm text-gray-700">• {{ $item->name }}</li>
                        @endforeach
                    </ul>
                    @if ($pendingItems->count() > 5)
                        <p class="text-xs text-gray-400 mt-2">+{{ $pendingItems->count() - 5 }} más</p>
                    @endif
                @endif
            </a>

        </div>
    </div>
</div>

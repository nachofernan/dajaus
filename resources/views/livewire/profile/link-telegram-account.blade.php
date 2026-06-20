<x-action-section>
    <x-slot name="title">Cuenta de Telegram</x-slot>

    <x-slot name="description">
        Vinculá tu cuenta de Telegram para poder usar el bot.
        Encontrás tu Chat ID enviándole <strong>/start</strong> al bot o usando
        <strong>@userinfobot</strong> en Telegram.
    </x-slot>

    <x-slot name="content">
        @if (auth()->user()->telegram_chat_id)
            <div class="flex items-center gap-3 mb-5 p-3 bg-green-50 rounded-lg border border-green-200">
                <span class="text-green-600 text-lg">✅</span>
                <div>
                    <p class="text-sm font-medium text-green-800">Cuenta vinculada</p>
                    <p class="text-xs text-green-600 font-mono">Chat ID: {{ auth()->user()->telegram_chat_id }}</p>
                </div>
            </div>
        @endif

        <form wire:submit="save">
            <div>
                <x-label for="telegram_chat_id" value="Chat ID de Telegram" />
                <x-input
                    id="telegram_chat_id"
                    type="text"
                    class="mt-1 block w-full"
                    wire:model="telegram_chat_id"
                    placeholder="Ej: 123456789"
                    inputmode="numeric"
                />
                @error('telegram_chat_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500">
                    Solo números. Podés obtenerlo mandando <code class="bg-gray-100 px-1 rounded">/start</code> al bot
                    o consultando <code class="bg-gray-100 px-1 rounded">@userinfobot</code>.
                </p>
            </div>

            <div class="flex items-center gap-4 mt-5">
                <x-button>Guardar</x-button>

                @if (auth()->user()->telegram_chat_id)
                    <button type="button" wire:click="unlink"
                        wire:confirm="¿Desvincular tu cuenta de Telegram?"
                        class="text-sm text-red-500 hover:text-red-700 transition">
                        Desvincular
                    </button>
                @endif

                <x-action-message class="ms-3" on="saved">
                    Guardado.
                </x-action-message>
            </div>
        </form>
    </x-slot>
</x-action-section>

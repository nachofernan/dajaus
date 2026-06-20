<?php

namespace App\Livewire\Profile;

use Illuminate\Validation\Rule;
use Livewire\Component;

class LinkTelegramAccount extends Component
{
    public string $telegram_chat_id = '';

    public function mount(): void
    {
        $this->telegram_chat_id = auth()->user()->telegram_chat_id ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'telegram_chat_id' => [
                'nullable',
                'string',
                'regex:/^\d+$/',
                Rule::unique('users', 'telegram_chat_id')->ignore(auth()->id()),
            ],
        ], [
            'telegram_chat_id.regex' => 'El Chat ID solo debe contener números.',
            'telegram_chat_id.unique' => 'Este Chat ID ya está vinculado a otra cuenta.',
        ]);

        auth()->user()->update([
            'telegram_chat_id' => $this->telegram_chat_id ?: null,
        ]);

        $this->dispatch('saved');
    }

    public function unlink(): void
    {
        auth()->user()->update(['telegram_chat_id' => null]);
        $this->telegram_chat_id = '';
        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.profile.link-telegram-account');
    }
}

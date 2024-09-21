<?php

use Livewire\Volt\Component;

new class extends Component {

    public $startTime;

    public string $name = '';

    public function with()
    {
        return [
            'listening_parties' => \App\Models\ListeningParty::all()
        ];
    }

    public function createListeningParty()
    {

    }
}; ?>

<div class="flex items-center justify-center min-h-screen bg-slate-50">
<div class="max-w-lg w-full px-4">
    <form wire:submit="createListeningParty" class="space-y-6">
        <x-input wire:model="name" placeholder="Listening Party Name"/>
        <x-datetime-picker
            wire:model.live="startTime"
            placeholder="Start time"
        />
        <x-button primary>Create Listening Party</x-button>
    </form>
</div>
</div>

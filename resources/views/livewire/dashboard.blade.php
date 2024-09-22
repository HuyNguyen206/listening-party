<?php

use App\Jobs\ProcessPodcastUrl;
use App\Models\Episode;
use App\Models\ListeningParty;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {

    #[Validate('required')]
    public $startTime;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|url')]
    public string $mediaUrl = '';

    public function with()
    {
        return [
            'listening_parties' => ListeningParty::query()->where('is_active', true)->with('episode.podcast')->orderBy('start_time')->get()
        ];
    }

    public function createListeningParty()
    {
        $this->validate();
        $episode = Episode::query()->firstOrCreate(['media_url' => $mediaUrl = $this->mediaUrl]);

        $listeningParty = ListeningParty::create([
            'episode_id' => $episode->id,
            'name' => $this->name,
            'start_time' => $this->startTime,
        ]);
        dispatch(new ProcessPodcastUrl($mediaUrl, $listeningParty, $episode));
        return redirect(route('parties.show', $listeningParty));
    }
}; ?>

<div class="min-h-screen bg-emerald-50 flex-col pt-8">
    <div class="flex items-center justify-center p-4">
        <div class="max-w-lg w-full">
            <x-card shadow="lg" rounded="md">
                <h2 class="text-xl font-bold font-serif text-center my-4">Let listen together</h2>
                <form wire:submit="createListeningParty" class="space-y-6">
                    <x-input wire:model="mediaUrl" placeholder="Podcast episode Url"
                             description="Direct episode link or youtube link, RSS feeds will grab the latest episode"/>
                    <x-input wire:model="name" placeholder="Listening Party Name"/>
                    <x-datetime-picker
                        wire:model.live="startTime"
                        placeholder="Start time"
                        :min="now()"
                    />
                    <x-button type="submit" primary>Create Listening Party</x-button>
                </form>
            </x-card>
        </div>
    </div>
</div>

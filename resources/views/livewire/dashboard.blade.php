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
            'listeningParties' => ListeningParty::query()->where('is_active', true)->with('episode.podcast')->orderBy('start_time')->get()
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
                    <x-button class="w-full" type="submit" primary>Create Listening Party</x-button>
                </form>
            </x-card>
        </div>
    </div>
    <div class="mt-5">
        <div class="max-w-lg mx-auto">
            <h3 class="text-lg font-serif my-2">
                Ongoing parties
            </h3>
            <div class="bg-white rounded-lg shadow-lg">
                @forelse($listeningParties as $listeningParty)
                    <div wire:key="{{$listeningParty->id}}">
                        <a href="{{route('parties.show', $listeningParty)}}">
                            <div class="flex space-x-4 items-center justify-between p-4 border-b border-gray-200 hover:bg-emerald-300 transition">
                                <div class="flex-shrink-0">
                                    <x-avatar src="{{$listeningParty->episode->podcast->artwork_url}}" size="xl" rounded="full"/>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[0.9rem] font-semibold truncate text-slate-900">{{$listeningParty->name}}</p>
                                    <p class="text-sm font-semibold truncate text-slate-400">{{$listeningParty->episode->title}}</p>
                                    <p class="tracking-tighter uppercase text-[0.7rem] text-slate-500">{{$listeningParty->episode->podcast->title}}</p>
                                    <p class="text-xs mt-2">{{$listeningParty->start_time}}</p>
                                </div>

                            </div>
                        </a>

                    </div>
                @empty
                    <div>No awwdio listening parties started yet... 😿</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

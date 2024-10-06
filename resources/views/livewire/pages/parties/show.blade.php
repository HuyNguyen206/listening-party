<?php

use App\Events\EmojiReactionEvent;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {

    #[\Livewire\Attributes\Validate('required|string|max:255')]
    public $message = '';

    public $userId;

    public $emojis = [];

    public \App\Models\ListeningParty $listeningParty;

    public function mount(\App\Models\ListeningParty $listeningParty)
    {
        $this->listeningParty = $listeningParty->load('episode.podcast');

        if (!auth()->check()) {
            if (!Session::has('user_id')) {
                $this->userId = uniqid('user_', true);
                Session::put('user_id', $this->userId);
            } else {
                $this->userId = Session::get('user_id');
            }
        } else {
            $this->userId = auth()->id();
        }
    }

    public function with()
    {
        return [
            'messages' => $this->listeningParty->messages()->with('user')->oldest()->get()
        ];
    }

    public function sendEmoji(string $emoji)
    {
        $newEmoji = [
            'id' => uniqid(),
            'emoji' => $emoji,
            'x' => rand(100, 300),
            'y' => rand(100, 300),
        ];

        event(new EmojiReactionEvent($this->listeningParty->id, $newEmoji, $this->userId));
    }

    #[On('echo:listening-party.{listeningParty.id},EmojiReactionEvent')]
    public function receiveEmoji($payload)
    {
        if ($payload['userId'] !== $this->userId) {
            $this->emojis[] = $payload['emoji'];
        }
    }

    public function sendMessages()
    {
        if (auth()->guest()) {
            session()->push('auth_redirect', route('parties.show', $this->listeningParty));

            return $this->redirect(route('login'));
        }

        $this->validate();
        $this->listeningParty->messages()->create([
            'user_id' => auth()->id(),
            'message' => $this->message,
        ]);
        event(new \App\Events\NewMessageEvent(listeningPartyId: $this->listeningParty->id, message: $this->message));

        $this->message = '';
    }

    public function getListeners()
    {
        return [
            'echo:listening-party.{listeningParty.id},NewMessageEvent' => 'refresh'
        ];
    }

}; ?>
<div class="grid grid-cols-2 gap-x-2 bg-emerald-50 py-5">
    <div class="min-h-screen flex flex-col items-center justify-start" x-data="{
            audio: null,
            isLoading: true,
            isLive: false,
            isPlaying: false,
            isReady: false,
            isFinished: false,
            copyNotification: false,
            endTimestamp: {{ $listeningParty->end_time?->timestamp ?? 'null' }},
            currentTime: 0,
            originCurrentPlayTime: 0,
            countdownText:null,
            startTimestamp: {{ $listeningParty->start_time->timestamp }},
            emojis: @entangle('emojis'),
            addEmoji(emoji, event) {
                const newEmoji = {
                    id: Date.now(),
                    emoji: emoji,
                    x: event.clientX,
                    y: event.clientY
                }
                this.emojis.push(newEmoji)
                $wire.sendEmoji(emoji)
            },

{{--            checkAndPlayAudio() {--}}
{{--                const elapsedTime = Math.max(0, Math.floor(Date.now() / 1000) - this.startTimestamp)--}}
{{--                 console.log(elapsedTime)--}}

{{--                if (elapsedTime >= 0) {--}}
{{--                    this.audio.currentTime = elapsedTime;--}}
{{--                    this.audio.play().catch(error => console.error('Playback failed:', error))--}}
{{--                } else {--}}
{{--                    setTimeout(() => this.checkAndPlayAudio(), 1000)--}}
{{--                }--}}
{{--            },--}}

            isFinish() {
                const now = Math.floor(Date.now() / 1000)
                return now - this.endTimestamp > 0;
            },
            checkAndUpdate() {
                this.isFinished = this.isFinish()
                if (this.isFinished) {
                    return;
                }

                const now = Math.floor(Date.now() / 1000)
                const timeUntilStart = this.startTimestamp - now
                console.log(timeUntilStart)
                if (timeUntilStart <= 0) {
                    if(!this.isPlaying) {
                        this.isLive = true
                         this.playAudio()
                    }
                } else {

                        const days = Math.floor(timeUntilStart / (60 * 60 * 24));
                        const hours = Math.floor((timeUntilStart % (60 * 60 * 24)) / (60 * 60));

{{--                                                const minutes = Math.floor(((timeUntilStart % (60 * 60 * 24)) % (60 * 60)) / 60)--}}
                        const minutes = Math.floor((timeUntilStart % (60 * 60)) / 60);
                        const seconds = timeUntilStart % 60

                        console.log(timeUntilStart, hours, minutes, seconds)
                        this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                }

            },

            calculateCurrentPlayTime() {
                     const now = Math.floor(Date.now() / 1000)
                    const elapsedTime = Math.max(0, now - this.startTimestamp)

                    return elapsedTime;
             },

            playAudio() {
                 this.audio.play().then(() => {
                    this.audio.currentTime = this.calculateCurrentPlayTime();
                 })
                 .catch(error => {
                     console.error('Playback failed:', error)
                     this.isPlaying = false
                     this.isReady = false
                     this.audio.currentTime = this.originCurrentPlayTime
                 })

            },

            joinAndBeReady() {
                this.isReady = true;
                if(this.isLive) {
                    this.playAudio()
                }

            },

            initAudioPlayer() {
                this.originCurrentPlayTime = this.calculateCurrentPlayTime()
                this.audio = this.$refs.audioPlayer;
                $nextTick(() => {  this.checkAndUpdate() })
                this.isFinished = this.isFinish()
                if (this.isFinished) {
                    return;
                }
{{--                this.checkAndUpdate()--}}
                if (this.audio.readyState > 0) {
                    this.isLoading = false;
                    setInterval(() => this.checkAndUpdate(), 1000)
                } else {
                    this.audio.addEventListener('loadedmetadata', () => {
                        this.isLoading = false;
                        setInterval(() => this.checkAndUpdate(), 1000)
                    });
                };

                this.audio.addEventListener('timeupdate', () => {
                    this.currentTime = this.audio.currentTime
                });

                   this.audio.addEventListener('play', () => {
                    this.isPlaying = true
                    this.isReady = true

                });

                   this.audio.addEventListener('pause', () => {
                    this.isPlaying = false
                });

                 this.audio.addEventListener('ended', () => {
                    this.isFinished = true
                    this.isPlaying = false
                    if (this.audio) {
                        this.audio.pause()
                    }
                });
            },
             copyToClipboard(textToCopy) {
                    // Navigator clipboard api needs a secure context (https)
                    if (navigator.clipboard && window.isSecureContext) {
                             navigator.clipboard.writeText(textToCopy);
                            this.copyNotification = true;
                            setTimeout(() => { this.copyNotification = false; }, 3000);
                    } else {
                        // Use the 'out of viewport hidden text area' trick
                        const textArea = document.createElement('textarea');
                        textArea.value = textToCopy;

                        // Move textarea out of the viewport so it's not visible
                        textArea.style.position = 'absolute';
                        textArea.style.left = '-999999px';

                        document.body.prepend(textArea);
                        textArea.select();

                        try {
                            document.execCommand('copy');
                            } catch (error) {
                            console.error(error);
                            } finally {
                            textArea.remove();
                        }
                     }
               },
            formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            }
        }">
        <div class="w-full max-w-3xl p-8 bg-white rounded-lg shadow-lg flex items-center justify-center">

            @if($listeningParty->end_time === null)
                <div>
                    <div wire:poll.5s class="text-green-600 font-bold font-serif">
                        Creating your <span> {{ $listeningParty->name }}</span>
                        listening party...
                    </div>
                </div>
            @else
                <audio x-ref="audioPlayer" preload="auto">
                    <source src="{{ $listeningParty->episode->media_url }}" type="audio/ogg">
                </audio>
                <div x-init="initAudioPlayer" class="w-full">
                    <div>
                        <div
                            class="flex space-x-2 md:flex-row flex-col items-center justify-between p-4 border-gray-200 hover:bg-emerald-50 transition">
                            <div class="flex-shrink-0">
                                <x-avatar src="{{$listeningParty->episode->podcast->artwork_url}}" size="xl"
                                          rounded="full"/>
                            </div>
                            <div class="flex-1">
                                <p class="text-[0.9rem] font-semibold truncate text-slate-900">{{$listeningParty->name}}</p>
                                <p class="text-sm max-w-xs font-semibold truncate text-slate-400">{{$listeningParty->episode->title}}</p>
                                <p class="tracking-tighter uppercase text-[0.7rem] text-slate-500">{{$listeningParty->episode->podcast->title}}</p>
                            </div>
                            <div x-show="!isLive && !isFinished" class="text-green-700 text-sm" x-cloak>
                                Starts in: <span x-text="countdownText"></span>
                            </div>
                            <div x-show="isLive" class="text-green-700 text-sm" x-cloak>
                                <div> Current time: <span x-text="formatTime(currentTime)"></span></div>
                                <div> Start time: {{$listeningParty->start_time}}</div>
                                <div x-show="isLoading">Loading...</div>
                            </div>
                        </div>
                        <button @click="copyToClipboard(window.location.href);"
                                class="flex items-center justify-center w-auto h-8 px-3 py-1 text-xs bg-white border rounded-md cursor-pointer border-neutral-200/60 hover:bg-neutral-100 active:bg-white focus:bg-white focus:outline-none text-neutral-500 hover:text-neutral-600 group">
                            <span x-show="!copyNotification">Copy to Clipboard</span>
                            <svg x-show="!copyNotification" class="w-4 h-4 ml-1.5 stroke-current"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                            </svg>
                            <span x-show="copyNotification" class="tracking-tight text-green-500" x-cloak>Copied to Clipboard</span>
                            <svg x-show="copyNotification" class="w-4 h-4 ml-1.5 text-green-500 stroke-current"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75"/>
                            </svg>
                        </button>
                        <x-button x-show="isLive && !isPlaying && !isFinished" x-cloak class="w-full"
                                  @click="joinAndBeReady()">Join and be ready
                        </x-button>
                        <h2 x-show="!isLive && !isFinished" x-cloak
                            class="text-md text-green-600 font-bold text-center font-serif mt-2">The show will
                            automatically start when the count down finish</h2>
                        <h2 x-show="isFinished" x-cloak class="mt-4 text-md text-green-600 font-bold text-center font-serif">
                            The show has finnish</h2>
                    </div>
                </div>
            @endif
        </div>
        <div class="flex p-8 max-w-3xl items-center justify-center mt-4 bg-white rounded-lg shadow-lg space-x-8 w-full">
            @foreach(['👍','💗','🤭','😬','👿'] as $emoji)
                <button @click="addEmoji('{{$emoji}}', $event)"
                        class="p-2 text-2xl transition-colors rounded-full hover:bg-emerald-100">
                    {{$emoji}}
                </button>
            @endforeach
        </div>
        <div class="fixed inset-0 pointer-events-none" aria-hidden="true">
            <template x-for="emoji in emojis" :key="emoji.id">
                <div class="absolute text-4xl animate-fall"
                     :style="`left: ${emoji.x}px; top: ${emoji.y}px;`" x-text="emoji.emoji"></div>
            </template>
        </div>
    </div>

    <div class="min-h-screen flexitems-start justify-center">
        <div class="w-full min-h-96 max-w-3xl p-8 bg-white rounded-lg shadow-lg flex flex-col">
            @forelse($messages as $message)
                <div class="text-gray-600 text-sm flex space-x-2 items-center justify-start my-4">
                    <img class="object-cover h-10 w-10 rounded-full" src="{{$message->user->avatar()}}"
                         alt="user avatar">
                    <span class="font-bold">{{ $message->user->name }}:</span>
                    <p class="ml-3">
                        {{ $message->message }}

                    </p>
                </div>
            @empty
                <p class="my-4">
                    No message
                </p>
            @endforelse
            <div class="flex space-x-2">
                @auth
                    <input class="border-gray-200 rounded-lg shadow w-full" type="text" placeholder="Message"
                           wire:model="message" wire:keydown.prevent.enter="sendMessages">
                @endauth
                <x-button sm class="w-40" wire:click.prevent="sendMessages">
                    @auth
                        Send message
                    @else
                        Login
                    @endauth
                </x-button>

            </div>

        </div>
    </div>
</div>



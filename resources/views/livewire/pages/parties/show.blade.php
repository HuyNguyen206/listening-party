<?php

use Livewire\Volt\Component;

new class extends Component {
    public \App\Models\ListeningParty $listeningParty;

    public function mount(\App\Models\ListeningParty $listeningParty)
    {
        $this->listeningParty = $listeningParty->load('episode.podcast');
    }
}; ?>
    <div class="min-h-screen bg-emerald-50 flex items-center justify-center" x-data="{
            audio: null,
            isLoading: true,
            isLive: false,
            isPlaying: false,
            isReady: false,
            currentTime: 0,
            originCurrentPlayTime: 0,
            countdownText:null,
            startTimestamp: {{ $listeningParty->start_time->timestamp }},

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
            checkAndUpdate() {
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
                        <div class="flex space-x-2 md:flex-row flex-col items-center justify-between p-4 border-gray-200 hover:bg-emerald-50 transition">
                            <div class="flex-shrink-0">
                                <x-avatar src="{{$listeningParty->episode->podcast->artwork_url}}" size="xl" rounded="full"/>
                            </div>
                            <div class="flex-1">
                                <p class="text-[0.9rem] font-semibold truncate text-slate-900">{{$listeningParty->name}}</p>
                                <p class="text-sm max-w-xs font-semibold truncate text-slate-400">{{$listeningParty->episode->title}}</p>
                                <p class="tracking-tighter uppercase text-[0.7rem] text-slate-500">{{$listeningParty->episode->podcast->title}}</p>
                            </div>
                            <div x-show="!isLive" class="text-green-700 text-sm" >
                                Starts in: <span x-text="countdownText"></span>
                            </div>
                            <div x-show="isLive" class="text-green-700 text-sm" >
                                <div> Current time: <span x-text="formatTime(currentTime)"></span></div>
                                <div> Start time: {{$listeningParty->start_time}}</div>
                                <div x-show="isLoading">Loading...</div>
                            </div>
                        </div>
                        <x-button x-show="isLive && !isPlaying" class="w-full" @click="joinAndBeReady()">Join and be ready</x-button>
                        <h2 x-show="!isLive" class="text-md text-green-600 font-bold text-center font-serif" >The show will automatically start when the count down finish</h2>
                    </div>
                </div>
            @endif
        </div>

    </div>

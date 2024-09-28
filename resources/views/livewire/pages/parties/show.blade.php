<?php

use Livewire\Volt\Component;

new class extends Component {
    public \App\Models\ListeningParty $listeningParty;

    public function mount(\App\Models\ListeningParty $listeningParty)
    {
        $this->listeningParty = $listeningParty->load('episode.podcast');
    }
}; ?>

<div x-data="{
            audio: null,
            isLoading: true,
            isLive: false,
            isPlaying: false,
            isReady: false,
            currentTime: 0,
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
                           if(this.isReady) {
                                this.audio.play().catch(error => console.error('Playback failed:', error))
                           }
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

            playAudio() {
                const now = Math.floor(Date.now() / 1000)
                const elapsedTime = Math.max(0, now - this.startTimestamp)
                this.audio.currentTime = elapsedTime;
                 this.audio.play().catch(error => {
                      console.error('Playback failed:', error)
                     this.isPlaying = false
                 })

            },

            initAudioPlayer() {
                this.audio = this.$refs.audioPlayer;

                if (this.audio.readyState > 0) {
                    this.isLoading = false;
                    this.checkAndUpdate();
                } else {
                    this.audio.addEventListener('loadedmetadata', () => {
                        this.isLoading = false;
                        this.checkAndUpdate();
                    });
                };

                this.audio.addEventListener('timeupdate', () => {
                    this.currentTime = this.audio.currentTime
                });

                   this.audio.addEventListener('play', () => {
                    this.isPlaying = true
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
        }" x-init="initAudioPlayer">
    @if($listeningParty->end_time === null)
        <div wire:poll.5s>
            Creating your <span> {{ $listeningParty->name }}</span>
            listening party
        </div>
    @else
        <div >
            <audio x-ref="audioPlayer" preload="auto">
                <source src="{{ $listeningParty->episode->media_url }}" type="audio/ogg">
            </audio>
            <div> {{ $listeningParty->episode->podcast->title }}</div>
            <div> {{ $listeningParty->episode->title }}</div>
            <div> Current time: <span x-text="formatTime(currentTime)"></span></div>
            <div> Start time: {{$listeningParty->start_time}}</div>
            <div x-show="isLoading">Loading...</div>
            <div x-text="countdownText"></div>

        </div>
    @endif
</div>

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
            currentTime: 0,
            startTimestamp: {{ $listeningParty->start_time->timestamp }},

            init() {
{{--                this.startCountdown();--}}
{{--                if (this.$refs.audioPlayer && !this.isFinished) {--}}
{{--                    this.initializeAudioPlayer();--}}
{{--                }--}}
            },

            initAudioPlayer() {
                this.audio = this.$refs.audioPlayer
                this.audio.addEventListener('loadedmetadata', () => {
                           this.isLoading = false;
                           this.checkAndPlayAudio()
               })
                this.audio.addEventListener('timeupdate', () => {
                           this.currentTime = this.audio.currentTime
               })
            },

            checkAndPlayAudio() {
                const elapsedTime = Math.max(0, Math.floor(Date.now() / 1000) - this.startTimeStamp)

                if (elapsedTime >= 0) {
                    this.audio.currentTime = elapsedTime;
                    this.audio.play().catch(error => console.error('Playback failed:', error))
                } else {
                    setTimeout(() => this.checkAndPlayAudio, 1000)
                }
            },

            formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            }
        }" x-init="init()">
    @if($listeningParty->end_time === null)
        <div>
            Creating your <span> {{ $listeningParty->name }}</span>
            listening party
        </div>
    @else
        <div >
            <audio x-ref="audioPlayer" controls src="{{ $listeningParty->episode->media_url }}" preload="auto"></audio>
            <div> {{ $listeningParty->episode->podcast->title }}</div>
            <div> {{ $listeningParty->episode->title }}</div>
            <div> Current time: <span x-text="formatTime(currentTime)"></span></div>
            <div> Start time: {{$listeningParty->start_time}}</div>
            <div x-show="isLoading">Loading...</div>

        </div>
    @endif
</div>

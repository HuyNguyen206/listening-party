<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\ListeningParty;
use App\Models\Podcast;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPodcastUrl implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string         $rssUrl,
        private ListeningParty $listeningParty,
        private Episode        $episode
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $xml = simplexml_load_file($this->rssUrl);

        $podcastData = [
            'description' => $xml->channel->description,
            'hosts' => $xml->channel->generator,
            'artwork_url' => $xml->channel->image->url,
            'rss_url' => $this->rssUrl,
        ];
        $podcast = Podcast::updateOrCreate(['title' => $xml->channel->title], $podcastData);

        if (!isset($xml->channel->item[0])) {
            return;
        }

        $latestEpisode = $xml->channel->item[0];

        $namespaces = $xml->getNamespaces(true);
        $itunesNamespace = $namespaces['itunes'] ?? null;
        $episodeLength = null;

        if ($itunesNamespace) {
            $episodeLength = (string)$latestEpisode->children($itunesNamespace)->duration;
        }

        if ($episodeLength === null) {
            $fileSize = (int)$latestEpisode->enclosure['length'];
            $bitrate = 128_000;
            $durationInSeconds = ceil($fileSize * 8 / $bitrate);
            $episodeLength = (string) $durationInSeconds;
        }

        try {
            if (strpos($episodeLength, ':') !== false) {
                $parts = explode(':', $episodeLength);
                switch (true) {
                    case count($parts) === 2:
                        $interval = CarbonInterval::createFromFormat('i:s', $episodeLength);
                        break;
                    case count($parts) === 3:
                        $interval = CarbonInterval::createFromFormat('H:i:s', $episodeLength);
                        break;
                    default:
                        throw new \Exception('Unsupported duration format');
                }
            } else {
                $interval = CarbonInterval::seconds((int)$episodeLength);
            }
        } catch (\Throwable $exception) {
            Log::error('Error parsing episode duration '. $exception->getMessage());
            $interval = CarbonInterval::hour();
        }

        $episodeData = [
            'title' => $latestEpisode->title,
            'podcast_id' => $podcast->id,
            'media_url' => (string)$latestEpisode->enclosure['url'],
        ];
        $this->episode->update($episodeData);

        $listeningPartyEndtime = $this->listeningParty->start_time->add($interval);
        $this->listeningParty->update([
            'end_time' => $listeningPartyEndtime,
        ]);
    }
}

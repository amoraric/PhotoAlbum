<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class SearchTags implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Photo $photo)
    {
        $this->photo = $photo;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Searching tags for photo with id ' . $this->photo->id);

        $path = 'storage/app/public/uploaded-images/';
        $explodeFileName = explode('.', $this->photo->original_name);
        $extension = '.' . $explodeFileName[count($explodeFileName) - 1];

        $process = Process::start('python3 python/search_tags.py ' . $path . $this->photo->hash . $extension);
        $processStringResults = $process->wait();

        if ($processStringResults->successful()) {
            $tags = $this->format($processStringResults->output());

            $validTags = $this->determinateValidTags($tags);

            Log::info($validTags);
        }

        if ($processStringResults->failed()) {
            Log::warning($processStringResults->errorOutput());
        }
    }

    private function format(string $text): array
    {
        $resultsText = explode('/step', $text, 2)[1];
        $stringTags = [];
        $tagsList = [];

        preg_match_all('/\(([^)]+)\)/', $resultsText, $stringTags);

        foreach ($stringTags[0] as $strTag) {
            $strTag = substr($strTag, 1, -1);
            $infosTag = explode(', ', $strTag);

            $value = floatval($infosTag[2]);
            $tag = substr(stripslashes($infosTag[1]), 1, -1);

            $item = [
                'value' => $value,
                'tag' => $tag
            ];

            $tagsList[] = $item;
        }

        return $tagsList;
    }

    private function determinateValidTags(array $tags): array
    {
        $validTags = [];

        foreach ($tags as $tag) {
            if ($tag['value'] > .6) {
                $validTags[] = $tag['tag'];
            }
        }

        return $validTags;
    }
}

<?php

namespace App\Jobs;

use App\Models\Album;
use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadedFileManager implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private array $filesDatas, private Album $album)
    {
        $this->filesDatas = $filesDatas;
        $this->album = $album;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $album = $this->album;
        $counter = count($this->filesDatas);

        Log::info(
            ($counter > 0 ? 'Processing ' : '')
            . $counter
            . ($counter > 1 ? ' photos' : ' photo')
            . ($counter === 0 ? ' processing' : '')
        );

        foreach ($this->filesDatas as $item => $datasFile) {
            $fileContents = Storage::get($datasFile['path']);

            $photo = new Photo();
            $hashFile = hash('sha256', $fileContents);
            $existingPhoto = $photo->photoExists($hashFile);

            Log::info('[' . ($item + 1) . '] Photo processing "' . $datasFile['path'] . '"');

            if (isset($existingPhoto)) {
                if (!$existingPhoto->photoExistsInAlbum($album->id)) {
                    $existingPhoto->albums()->attach($album->id);
                    $existingPhoto->albums()
                        ->updateExistingPivot($album->id, ['created_at' => Carbon::now()])
                    ;
                }

                Storage::delete($datasFile['path']);
            } else {
                $photo->original_name = substr($datasFile['original_name'], 0, 255);
                $photo->hash = $hashFile;

                $photo->saveFile($datasFile['path']);
                $photo->save();

                $photo = $photo->refresh();

                $photo->albums()->attach($album->id);
                $photo->albums()
                    ->updateExistingPivot($album->id, ['created_at' => Carbon::now()])
                ;

                SearchTags::dispatch($photo);
            }
        }

        Log::info('Job finished');
    }
}

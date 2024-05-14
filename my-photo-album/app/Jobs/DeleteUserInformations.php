<?php

namespace App\Jobs;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteUserInformations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $deletedAlbums = Album::getAllDeletedAlbums();
        $counter = $deletedAlbums->count();

        Log::info(
            $counter
            . ($counter > 1 ? ' albums' : ' album') 
            . ' will be deleted'
        );

        $deletedAlbums->map(function ($album, $item) {
            $photos = $album->photos()->get();

            $album->photos()->detach();

            $photos->map(function($photo) {
                $count = $photo->inAnotherAlbum();

                if ($count === 0) {
                    $photo->deleteFile();
                    Photo::destroy($photo->id);
                }
            });

            $user = $album->user()->get()->first();

            Album::destroy($album->id);

            Log::info('[' . ($item + 1) . '] Album with id ' . $album->id . ' has been deleted');

            $user = $user->refresh();

            if ($user->deleted_at && $user->albums()->get()->count() === 0) {
                $user->delete();

                Log::info('User "' . $user->email . '" has been deleted');
            }
        });

        Log::info('Job finished');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photos()
    {
        return $this->belongsToMany(Photo::class);
    }

    static public function countMyAlbums(int $userId)
    {
        return Album::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get()
            ->count()
        ;
    }

    static public function getMyAlbums(int $userId)
    {
        return Album::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get()
            ->sortBy([['created_at', 'desc']])
        ;
    }

    static function getAllDeletedAlbums()
    {
        return Album::whereNotNull('deleted_at')
            ->get()
        ;
    }
}

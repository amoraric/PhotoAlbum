<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class AlbumShared extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'photo_id', 'shared_user_id'];

    public static function addAlbumShared($owner_id, $album_id, $shared_user_id)
    {
        // Insert the photo data into the database using a raw SQL query
        $result = DB::insert('INSERT INTO album_shared (owner_id, album_id, shared_user_id,created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())', [
            $owner_id, $album_id, $shared_user_id
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'shared_user_id');
    }
}

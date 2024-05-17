<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Album extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'album_user');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    

    public static function insertAlbum($albumName, $userId)
    {
    // Use the DB facade to insert the album directly into the database
        DB::table('albums')->insert([
            'name' => $albumName,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    
}

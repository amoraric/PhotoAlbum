<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['filename', 'album_id', 'photo_name', 'name', 'path'];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'photo_user');
    }
}

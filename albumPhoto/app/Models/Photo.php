<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['filename', 'album_id', 'photo_name', 'path'];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'photo_user');
    }


    public static function addPhoto(Request $request)
    {
        // Validate the request
        $request->validate([
            'photo' => 'required|image',
            'album_id' => 'required|exists:albums,id',
            'photo_name' => 'required|string|max:255',
        ]);

        // Store the photo in the local storage
        $path = $request->file('photo')->store('photos', 'public');
        $filename = basename($path);

        // Insert the photo data into the database using a raw SQL query
        $result = DB::insert('INSERT INTO photos (filename, album_id, photo_name, path, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())', [
            $filename,
            $request->album_id,
            $request->photo_name,
            // $request->photo_name,
            $path,
        ]);

        if ($result) {
            // Return the created photo details
            return [
                'filename' => $filename,
                'album_id' => $request->album_id,
                'photo_name' => $request->photo_name,
                // 'name' => $request->photo_name,
                'path' => $path,
            ];
        } else {
            // Handle the error if the insertion was not successful
            throw new \Exception('Failed to insert photo data into the database.');
        }
    }


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PhotoShared extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'photo_id', 'shared_user_id', 'symmetric_key', 'symmetric_iv'];

    public static function addPhotoShared($owner_id, $photo_id, $shared_user_id, $symmetric_key, $symmetric_iv)
    {
        return DB::table('photo_shared')->insert([
            'owner_id' => $owner_id,
            'photo_id' => $photo_id,
            'shared_user_id' => $shared_user_id,
            'created_at' => now(),
            'updated_at' => now(),
            'symmetric_key' => $symmetric_key,
            'symmetric_iv' => $symmetric_iv
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'shared_user_id');
    }
}

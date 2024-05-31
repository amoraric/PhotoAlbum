<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google2fa_secret',
        'public_key',
        'private_key_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sharedPhotos()
    {
        return $this->belongsToMany(Photo::class, 'photo_shared');
    }

      // Définir la relation entre User et Album
      public function sharedAlbums()
      {
          return $this->belongsToMany(Album::class, 'album_user');
      }
      public function photos()
      {
          return $this->hasMany(Photo::class);
      }
      public static function generateKeyPair()
    {
        $opensslConf = getenv('OPENSSL_CONF');
        $config = array(
        "digest_alg" => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        "config" => $opensslConf

    );

    $res = openssl_pkey_new($config);
    if ($res === false) {
        throw new \Exception('Failed to generate key pair: ' . openssl_error_string());
    }

    openssl_pkey_export($res, $privateKey);
    $publicKey = openssl_pkey_get_details($res)['key'];

    return ['private_key' => $privateKey, 'public_key' => $publicKey];
}


}

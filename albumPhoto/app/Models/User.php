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
        'public_key_enc',
        'public_key_sign'
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
          // Get the OpenSSL configuration path from the environment variable
          $opensslConf = getenv('OPENSSL_CONF');
          if ($opensslConf === false) {
              throw new \Exception('OPENSSL_CONF environment variable is not set');
          }

          // Configuration array for OpenSSL
          $config = array(
              "digest_alg" => "sha256",
              "private_key_bits" => 2048,
              "private_key_type" => OPENSSL_KEYTYPE_RSA,
              "config" => $opensslConf
          );

          // Generate a new private (and public) key pair
          $res = openssl_pkey_new($config);
          if ($res === false) {
              throw new \Exception('Failed to generate key pair: ' . openssl_error_string());
          }

          // Export the private key to a variable
          $privateKey = '';
          if (!openssl_pkey_export($res, $privateKey, null, $config)) {
              throw new \Exception('Failed to export private key: ' . openssl_error_string());
          }

          // Extract the public key from the key pair
          $keyDetails = openssl_pkey_get_details($res);
          if ($keyDetails === false) {
              throw new \Exception('Failed to get key details: ' . openssl_error_string());
          }

          $publicKey = $keyDetails['key'];

          // Return the key pair
          return ['private_key' => $privateKey, 'public_key' => $publicKey];
      }



}

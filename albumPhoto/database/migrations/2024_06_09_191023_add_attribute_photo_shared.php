<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('photo_shared', function (Blueprint $table) {
            $table->boolean('sharedFromAlbum')->default(false);
        });
        Schema::table('album_shared', function (Blueprint $table) {
            $table->unique(['album_id', 'shared_user_id'], 'album_shared_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photo_shared', function (Blueprint $table) {
            $table->$table->dropColumn('sharedFromAlbum');
        });
        Schema::table('album_shared', function (Blueprint $table) {
            $table->dropUnique('album_shared_unique');
        });
    }
};

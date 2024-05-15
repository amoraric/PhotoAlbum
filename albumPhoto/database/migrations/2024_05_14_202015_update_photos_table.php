<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePhotosTable extends Migration
{
    public function up()
    {
        Schema::table('photos', function (Blueprint $table) {
            if (!Schema::hasColumn('photos', 'album_id')) {
                $table->unsignedBigInteger('album_id');
            }

            if (!Schema::hasColumn('photos', 'photo_name')) {
                $table->string('photo_name');
            }

            if (!Schema::hasColumn('photos', 'path')) {
                $table->string('path')->nullable();
            }

            // Re-add the foreign key constraint
            // $table->foreign('album_id')->references('id')->on('albums')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('photos', function (Blueprint $table) {
            if (Schema::hasColumn('photos', 'album_id')) {
                // $table->dropForeign(['album_id']);
                $table->dropColumn('album_id');
            }

            if (Schema::hasColumn('photos', 'photo_name')) {
                $table->dropColumn('photo_name');
            }

            if (Schema::hasColumn('photos', 'path')) {
                $table->dropColumn('path');
            }
        });
    }
}

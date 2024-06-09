<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotoSharedTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('photo_shared')) {
            Schema::create('photo_shared', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreignId('photo_id')->references('id')->on('photos')->cascadeOnDelete();
                $table->foreignId('shared_user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('photo_shared');
    }
}

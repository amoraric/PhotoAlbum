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
            $table->text('sharedEncrypted_key')->nullable();
            $table->text('sharedEncrypted_iv')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photo_shared', function (Blueprint $table) {
            $table->dropColumn('sharedEncrypted_key');
            $table->dropColumn('sharedEncrypted_iv');
        });
    }
};

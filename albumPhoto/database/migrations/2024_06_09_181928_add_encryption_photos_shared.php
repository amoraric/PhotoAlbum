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
            $table->text('symmetric_key')->nullable();
            $table->text('symmetric_iv')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('symmetric_key');
            $table->dropColumn('symmetric_iv');
        });
    }
};

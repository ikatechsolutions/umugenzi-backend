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
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedBigInteger('gift_id')->nullable();
            $table->foreign('gift_id')->references('id')->on('gifts')->onDelete('cascade');
            $table->string('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['gift_id']);
            $table->dropColumn('gift_id');
            $table->dropColumn('phone');
        });
    }
};

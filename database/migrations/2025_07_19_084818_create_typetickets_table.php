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
        Schema::create('typetickets', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->integer('prix');
            $table->unsignedBigInteger('evenement_id');
            $table->foreign('evenement_id')->references('id')->on('evenements');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typetickets');
    }
};

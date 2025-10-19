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
        Schema::create('paymentevents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evenement_id');
            $table->foreign('evenement_id')->references('id')->on('evenements');
            $table->string('montant');
            $table->string('method_payment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paymentevents');
    }
};

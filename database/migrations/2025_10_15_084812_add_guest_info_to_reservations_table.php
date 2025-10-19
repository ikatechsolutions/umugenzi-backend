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
        Schema::table('reservations', function (Blueprint $table) {
            // Rendre user_id nullable si ce n'est pas déjà fait
            $table->foreignId('user_id')->nullable()->change(); 
            
            // Ajouter les champs pour l'invité
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['name', 'email', 'phone']);
        });
    }
};

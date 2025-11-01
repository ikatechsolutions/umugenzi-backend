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
        Schema::table('ticketinstances', function (Blueprint $table) {
            // 1. Supprimer l'ancienne clé étrangère (nécessaire avant de modifier la colonne)
            $table->dropForeign(['reservation_id']);

            // 2. Modifier la colonne pour la rendre nullable
            $table->unsignedBigInteger('reservation_id')->nullable()->change();

            // 3. Recréer la clé étrangère (qui acceptera maintenant NULL)
            $table->foreign('reservation_id')->references('id')->on('reservations');

            $table->unsignedBigInteger('ticketdistribution_id')->nullable();
            $table->foreign('ticketdistribution_id')->references('id')->on('ticketdistributions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticketinstances', function (Blueprint $table) {
            // Pour l'annulation (rollback) :
            
            // 1. Supprimer la clé étrangère
            $table->dropForeign(['reservation_id']);

            // 2. Modifier la colonne pour la rendre NOT NULL (obligatoire)
            $table->unsignedBigInteger('reservation_id')->nullable(false)->change();

            // 3. Recréer la clé étrangère (NOT NULL)
            $table->foreign('reservation_id')->references('id')->on('reservations');

            // Pour l'annulation (rollback) :
            
            // 1. Supprimer la clé étrangère
            $table->dropForeign(['ticketdistribution_id']);

            // 2. Modifier la colonne pour la rendre NOT NULL (obligatoire)
            $table->unsignedBigInteger('ticketdistribution_id')->nullable(false)->change();

            // 3. Recréer la clé étrangère (NOT NULL)
            $table->foreign('ticketdistribution_id')->references('id')->on('ticketdistributions');
        });
    }
};

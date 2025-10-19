<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;

class Ticketinstance extends Model
{
    protected $fillable = [
        'reservation_id',
        'code_unique',
        'qr_code',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    // Méthode pour générer et créer les instances de ticket
    public static function createTicketInstances(Reservation $reservation, int $quantity)
    {
        $reservation->load('ticket.typeticket.evenement');

        $stock = $reservation->ticket;
        $typeticket = $stock->typeticket;
        $evenement = $typeticket->evenement;

        $tickets = [];
        $now = now();
        $dateStr = now()->format('Ymd');
        $evenementTitrePrefix = strtoupper(substr($evenement->titre, 0, 3)); // 3 premières lettres du titre de l'événement

        $renderer = new ImageRenderer(
            new RendererStyle(300), // Taille de 300px
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        for ($i = 0; $i < $quantity; $i++) {
            // Logique de génération du code_unique
            // Format: EVE_VIP_20251019_UUID
            $uniqueId = \Illuminate\Support\Str::uuid();
            $codeUnique = "{$evenementTitrePrefix}_{$typeticket->nom}_{$dateStr}_{$uniqueId}";

            // Génération du QR code (exemple: stockage de la chaîne du QR code, pas de l'image)
            // Si vous stockez l'image, vous devrez utiliser un stockage de fichiers (S3/local)
            $qrCodeData = $writer->writeString($codeUnique);

            $tickets[] = [
                'reservation_id' => $reservation->id,
                'code_unique' => $codeUnique,
                'qr_code' => $qrCodeData,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insertion en masse pour de meilleures performances
        self::insert($tickets);
    }
}

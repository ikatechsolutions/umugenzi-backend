<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class Ticketinstance extends Model
{
    protected $fillable = [
        'reservation_id',
        'code_unique',
        'qr_code',
        'ticketdistribution_id',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function ticketdistribution()
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

        $writer = new PngWriter();

        for ($i = 0; $i < $quantity; $i++) {
            // Logique de génération du code_unique
            // Format: EVE_VIP_20251019_UUID
            $uniqueId = \Illuminate\Support\Str::uuid();
            $codeUnique = "{$evenementTitrePrefix}_{$typeticket->nom}_{$dateStr}_{$uniqueId}";

            $qrCode = new QrCode($codeUnique); 

            $result = $writer->write($qrCode);
            $pngData = $result->getString();

            $base64Image = 'data:image/png;base64,' . base64_encode($pngData);

            $tickets[] = [
                'reservation_id' => $reservation->id,
                'code_unique' => $codeUnique,
                'qr_code' => $base64Image,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insertion en masse pour de meilleures performances
        self::insert($tickets);
    }

    public static function createDistributionTicketInstances(Ticketdistribution $ticketdistribution, int $quantity)
    {
        $ticketdistribution->load('ticket.typeticket.evenement');

        $stock = $ticketdistribution->ticket;
        $typeticket = $stock->typeticket;
        $evenement = $typeticket->evenement;

        $tickets = [];
        $now = now();
        $dateStr = now()->format('Ymd');
        $evenementTitrePrefix = strtoupper(substr($evenement->titre, 0, 3)); // 3 premières lettres du titre de l'événement

        $writer = new PngWriter();

        for ($i = 0; $i < $quantity; $i++) {
            // Logique de génération du code_unique
            // Format: EVE_VIP_20251019_UUID
            $uniqueId = \Illuminate\Support\Str::uuid();
            $codeUnique = "{$evenementTitrePrefix}_{$typeticket->nom}_{$dateStr}_{$uniqueId}";

            $qrCode = new QrCode($codeUnique); 

            $result = $writer->write($qrCode);
            $pngData = $result->getString();

            $base64Image = 'data:image/png;base64,' . base64_encode($pngData);

            $tickets[] = [
                'ticketdistribution_id' => $ticketdistribution->id,
                'code_unique' => $codeUnique,
                'qr_code' => $base64Image,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insertion en masse pour de meilleures performances
        self::insert($tickets);
    }
}

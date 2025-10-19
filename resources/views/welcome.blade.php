<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $ticketInstance->code_unique }}</title>
    <style>
        :root {
            /* Couleurs du design */
            --main-color: #0c183a; /* Bleu marine foncé, presque noir */
            --text-color: #ffffff; /* Blanc pour le texte */
            --accent-color: #f7a040; /* Orange/Jaune pour l'accentuation, si besoin */
            --perforation-color: #3f51b5; /* Couleur de la ligne de perforation */
        }

        body {
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f0f0; /* Fond gris clair pour voir le ticket */
            margin: 0;
        }

        .ticket-container {
            display: flex;
            width: 630px; /* Env. 210mm à 96dpi (7 pouces) */
            height: 222px; /* Env. 74mm à 96dpi (2.75 pouces) */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            color: var(--text-color);
        }

        /* ------------------ Section Principale (2/3) ------------------ */
        .ticket-main {
            flex: 2; /* Prend 2/3 de la largeur */
            background-color: var(--main-color);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            
            /* Pour intégrer l'image d'arrière-plan des étoiles */
            /* background-image: url('votre_image_etoiles.jpg'); 
            background-size: cover;
            background-position: center; */
        }

        .title {
            font-size: 2.2em;
            font-weight: 800;
            margin-bottom: 5px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .organizer {
            font-size: 0.8em;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .info-block {
            margin-bottom: 10px;
        }

        .datetime {
            font-size: 1.2em;
            font-weight: bold;
            margin: 0 0 5px 0;
        }

        .location {
            font-size: 0.9em;
            margin: 0;
            opacity: 0.9;
        }

        .qr-code-area {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .qr-code-placeholder {
            width: 80px;
            height: 80px;
            background-color: var(--text-color); /* Le QR code doit être clair */
            border: 5px solid var(--text-color); 
            /* Simulation du motif du QR code */
            background-image: repeating-linear-gradient(45deg, #000 0, #000 2px, transparent 2px, transparent 4px);
            margin-bottom: 5px;
        }

        .scan-text {
            font-size: 0.7em;
            opacity: 0.7;
            margin: 0;
        }

        /* ------------------ Section Souche (1/3) ------------------ */
        .ticket-stub {
            flex: 1; /* Prend 1/3 de la largeur */
            background-color: var(--main-color);
            position: relative;
            border-left: 2px dashed var(--text-color); /* Ligne de perforation simulée */
        }

        .stub-content {
            padding: 15px;
            text-align: center;
        }

        .stub-header {
            font-size: 0.7em;
            opacity: 0.8;
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .stub-label {
            font-size: 0.7em;
            margin: 5px 0 0 0;
            opacity: 0.6;
        }

        .ticket-number {
            font-size: 2.5em;
            font-weight: 900;
            margin: 0 0 15px 0;
            color: var(--text-color); 
        }

        .qr-code-placeholder.small {
            width: 60px;
            height: 60px;
            margin: 0 auto;
        }

        /* ------------------ Effet de Perforation (Facultatif) ------------------ */
        /* Ajout d'une petite décoration sur le bord de la souche pour l'effet "déchiré" */
        .ticket-stub::before,
        .ticket-stub::after {
            content: '';
            position: absolute;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background-color: #f0f0f0; /* Couleur du fond de la page */
            left: -8px; 
            z-index: 10;
        }

        .ticket-stub::before {
            top: -8px;
        }

        .ticket-stub::after {
            bottom: -8px;
        }
    </style>
</head>
<body>
    <div class="ticket-container">

        @php
            // Accès aux données de l'événement via les relations
            $reservation = $ticketInstance->reservation;
            $stock = $reservation->ticket;
            $typeTicket = $stock->typeticket;
            $evenement = $typeTicket->evenement;
        @endphp

        <div class="ticket-main">
            <h1 class="title">{{ strtoupper($evenement->titre) }}</h1>
            <p class="organizer">{{ $evenement->user->name }}</p>
            
            <div class="info-block">
                <p class="datetime">{{ \Carbon\Carbon::parse($evenement->date_event)->format('d/m/Y H:i') }} - {{ $evenement->heure }}</p>
                <p class="location">Lieu : {{ $evenement->place }}</p>
            </div>
            
            <div class="qr-code-area">
                <div class="qr-code">
                    {!! $ticketInstance->qr_code !!}
                </div>
                <p class="scan-text">SCANNEZ À L'ENTRÉE</p>
            </div>
            
        </div>

        <div class="ticket-stub">
            <div class="perforation-line"></div>
            <div class="stub-content">
                <p class="stub-header">{{ strtoupper($evenement->titre) }}<br>{{ \Carbon\Carbon::parse($evenement->date_event)->format('d/m/Y H:i') }}</p>
                
                <p class="stub-label">BILLET N°:</p>
                <p class="ticket-number">**007A**</p>
                
                <div class="qr-code">
                    {!! $ticketInstance->qr_code !!}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
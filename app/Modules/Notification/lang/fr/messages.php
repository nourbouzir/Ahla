<?php

return [
    'title' => 'Notifications', 'read_all' => 'Tout marquer comme lu', 'new' => 'Nouveau', 'none' => 'Aucune notification.',
    'open' => 'Ouvrir', 'saved' => 'Enregistré.', 'email_pref' => 'M’envoyer aussi les notifications par e-mail',
    'kinds' => [
        'booking_requested' => ['title' => 'Nouvelle demande de réservation', 'body' => ':name souhaite réserver « :experience » le :date. Merci de répondre sous 12 heures.'],
        'booking_confirmed' => ['title' => 'Réservation confirmée', 'body' => 'Votre réservation « :experience » le :date avec :name est confirmée.'],
        'booking_declined' => ['title' => 'Réservation refusée', 'body' => ':name a refusé « :experience » le :date. Rien n’a été débité.'],
        'booking_expired' => ['title' => 'Demande expirée', 'body' => 'La demande pour « :experience » le :date est restée sans réponse et a expiré.'],
        'booking_cancelled' => ['title' => 'Réservation annulée', 'body' => ':name a annulé « :experience » le :date.'],
        'payment_captured' => ['title' => 'Paiement reçu', 'body' => 'Nous avons débité :amount TND pour « :experience ».'],
        'payment_released' => ['title' => 'Réservation libérée', 'body' => 'Les :amount TND retenus pour « :experience » ont été libérés.'],
        'payment_failed' => ['title' => 'Paiement échoué', 'body' => 'Le paiement pour « :experience » n’a pas abouti.'],
        'refund_issued' => ['title' => 'Remboursement effectué', 'body' => 'Vous êtes remboursé de :amount TND pour « :experience ».'],
        'message_received' => ['title' => 'Nouveau message', 'body' => ':name vous a écrit au sujet de « :experience ».'],
    ],
];

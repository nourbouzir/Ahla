<?php

return [
    'nav' => [
        'title' => 'Espace guide', 'overview' => 'Vue d’ensemble', 'bookings' => 'Demandes', 'calendar' => 'Calendrier', 'clients' => 'Clients',
        'inbox' => 'Messages', 'experiences' => 'Expériences', 'availability' => 'Disponibilités', 'earnings' => 'Revenus', 'verification' => 'Vérification',
    ],
    'overview' => [
        'title' => 'Bon retour, :name', 'public_page' => 'Voir ma page publique', 'new_experience' => 'Nouvelle expérience',
        'needs_answer' => 'En attente de votre réponse', 'answer_in' => 'Répondre dans', 'expired' => 'expiré', 'all_answered' => 'Tout est à jour. Les nouvelles demandes apparaissent ici.',
        'agenda' => '7 prochains jours', 'open_calendar' => 'Ouvrir le calendrier', 'today' => 'Aujourd’hui', 'nothing_scheduled' => 'Rien de prévu cette semaine.',
        'last_months' => 'Revenus, 6 derniers mois', 'details' => 'Détails', 'messages' => 'Derniers messages', 'all' => 'Tout voir', 'no_messages' => 'Aucune conversation pour le moment.',
    ],
    'banner' => [
        'no_profile' => 'Ajoutez votre nom, une courte présentation et votre ville pour que les touristes vous trouvent.', 'complete_profile' => 'Compléter le profil',
        'pending' => 'Votre demande est en attente.', 'pending_text' => 'Vous apparaîtrez dans la recherche une fois vérifié par un administrateur.',
        'rejected' => 'Votre demande a été refusée.', 'rejected_text' => 'Lisez le motif et faites une nouvelle demande.',
        'suspended' => 'Votre compte est suspendu.', 'suspended_text' => 'Vous êtes masqué de la recherche pendant la suspension.',
        'go' => 'Ouvrir la vérification',
    ],
    'kpi' => [
        'pending' => 'Demandes à traiter', 'pending_hint' => 'Répondre sous 12 heures', 'pending_none' => 'Aucune en attente',
        'upcoming' => 'Réservations à venir', 'next' => 'Prochaine : :date', 'upcoming_none' => 'Rien de réservé pour l’instant',
        'earned' => 'Gagné ce mois-ci', 'clients' => 'Clients', 'completed' => ':n expériences terminées',
    ],
    'checklist' => [
        'title' => 'Préparez-vous aux réservations', 'profile' => 'Compléter votre profil d’hôte', 'verified' => 'Être vérifié par notre équipe',
        'experience' => 'Publier une expérience', 'hours' => 'Définir vos horaires hebdomadaires', 'photo' => 'Ajouter une photo de profil', 'done' => 'fait',
    ],
    'clients' => [
        'title' => 'Clients', 'subtitle' => ':n personnes avec qui vous avez travaillé ou allez travailler', 'search' => 'Rechercher', 'search_placeholder' => 'Nom',
        'sort' => 'Trier par', 'sort_recent' => 'Plus récent', 'sort_name' => 'Nom', 'sort_spent' => 'Montant dépensé', 'sort_bookings' => 'Réservations',
        'apply' => 'Appliquer', 'stage' => 'Filtrer par étape', 'all' => 'Tous',
        'empty' => 'Aucun client pour le moment', 'empty_text' => 'Quand un touriste vous réserve, il apparaît ici avec son historique et vos notes privées.',
        'no_match' => 'Aucun client ne correspond', 'no_match_text' => 'Essayez un autre nom ou retirez le filtre d’étape.',
        'col_client' => 'Client', 'col_stage' => 'Étape', 'col_bookings' => 'Réservations', 'col_spent' => 'Dépensé', 'col_last' => 'Dernière / prochaine', 'open' => 'Ouvrir',
        'next_on' => 'Prochaine : :date',
    ],
    'stage' => ['awaiting' => 'Attend votre réponse', 'upcoming' => 'À venir', 'returning' => 'Fidèle', 'past' => 'Ancien client', 'closed' => 'Aucune visite terminée'],
    'client' => [
        'back' => 'Tous les clients', 'summary' => 'Résumé du client', 'message' => 'Écrire', 'completed' => 'Terminées', 'history' => 'Historique des réservations',
        'note' => 'Note privée', 'note_hint' => 'Vous seul la voyez. Idéal pour les allergies, les centres d’intérêt ou ce qu’il faut préparer.',
        'note_placeholder' => 'Par exemple : végétarien, passionné de photo, parle un peu arabe.', 'note_saved' => 'Note enregistrée.', 'contact' => 'Contact',
    ],
    'inbox' => [
        'title' => 'Messages', 'unread_count' => ':n non lus', 'caught_up' => 'Tout est lu', 'show_all' => 'Tout afficher', 'unread_only' => 'Non lus seulement',
        'explain' => 'Chaque réservation a sa propre discussion privée entre vous et le client. Les messages sont traduits entre l’anglais, le français et l’arabe.',
        'none_unread' => 'Aucun message non lu', 'empty' => 'Aucune conversation', 'empty_text' => 'Une discussion s’ouvre dès qu’un touriste demande une réservation.',
        'you' => 'Vous : ', 'no_messages' => 'Pas encore de message. Dites bonjour !',
    ],
    'calendar' => [
        'title' => 'Calendrier', 'prev' => 'Mois précédent', 'next' => 'Mois suivant', 'today' => 'Aujourd’hui', 'day_off' => 'Jour de repos', 'off' => 'Repos',
        'hint' => 'Les jours de repos et vos horaires hebdomadaires décident quand les touristes peuvent vous réserver.', 'edit_hours' => 'Modifier les disponibilités',
    ],
    'earnings' => [
        'title' => 'Revenus', 'all_time' => 'Depuis le début', 'export' => 'Exporter en CSV', 'period' => 'Période', 'gross' => 'Payé par les clients', 'refunded' => 'Remboursé', 'net' => 'Vous gagnez',
        'note' => 'Seuls les paiements réellement encaissés sont comptés, remboursements déduits. Ahla ne prélève aucune commission dans cette version.',
        'empty' => 'Aucun paiement pour le moment', 'empty_text' => 'L’argent apparaît ici dès qu’un client a payé une réservation confirmée.', 'col_date' => 'Date', 'col_booking' => 'Réservation',
    ],
];

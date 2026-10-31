<?php

return [
    'nav' => ['title' => 'Administration', 'overview' => 'Vue d’ensemble', 'users' => 'Utilisateurs', 'guides' => 'Guides', 'verifications' => 'Candidatures', 'bookings' => 'Réservations', 'revenue' => 'Revenus', 'audit' => 'Journal d’audit'],
    'overview' => [
        'title' => 'Vue d’ensemble de la plateforme', 'review_applications' => 'Examiner les candidatures', 'daily' => 'Réservations demandées par jour', 'last_days' => ':n derniers jours',
        'volume_months' => 'Volume, 6 derniers mois', 'details' => 'Détails', 'by_status' => 'Réservations par statut', 'attention' => 'À surveiller',
        'all_clear' => 'Rien à signaler pour le moment.', 'guides_by_status' => 'Guides par statut', 'activity' => 'Activité récente', 'all' => 'Tout voir',
        'system' => 'Système', 'no_activity' => 'Aucune activité pour le moment.',
    ],
    'kpi' => [
        'users' => 'Utilisateurs', 'users_hint' => ':t touristes, :g guides, :n nouveaux en 30 jours', 'guides' => 'Guides vérifiés', 'guides_hint' => ':p en attente, :s suspendus',
        'bookings' => 'Réservations', 'bookings_hint' => ':n demandées aujourd’hui', 'volume' => 'Volume net', 'revenue_hint' => 'Revenu indicatif :amount à :rate %',
    ],
    'attention' => [
        'applications' => 'Candidatures de guides en attente de décision', 'expiring' => 'Demandes qui expirent dans moins de 2 heures', 'refunds_pending' => 'Réservations annulées avec remboursement en attente',
        'stuck_holds' => 'Réservations bancaires gardées pour des réservations closes', 'disabled' => 'Comptes désactivés',
    ],
    'users' => [
        'title' => 'Utilisateurs', 'subtitle' => ':n comptes', 'search' => 'Rechercher', 'search_placeholder' => 'Nom ou e-mail', 'role' => 'Rôle', 'any' => 'Tous',
        'role_tourist' => 'Touriste', 'role_host' => 'Guide', 'role_admin' => 'Admin', 'state' => 'Statut', 'active' => 'Actif', 'disabled' => 'Désactivé', 'filter' => 'Filtrer',
        'col_user' => 'Utilisateur', 'col_bookings' => 'Réservations', 'col_joined' => 'Inscrit le', 'open' => 'Ouvrir', 'none' => 'Aucun utilisateur ne correspond.',
        'back' => 'Tous les utilisateurs', 'public_page' => 'Page publique', 'bookings' => 'Dernières réservations', 'no_bookings' => 'Aucune réservation.', 'history' => 'Historique', 'no_history' => 'Rien d’enregistré.',
        'account' => 'Compte', 'disabled_since' => 'Désactivé le :date.', 'enable' => 'Réactiver le compte', 'disable_hint' => 'Un compte désactivé est déconnecté immédiatement et ne peut plus se connecter. Son historique est conservé.',
        'reason' => 'Motif', 'disable' => 'Désactiver le compte', 'cannot_admin' => 'Les comptes admin ne peuvent pas être désactivés ici.',
        'disabled_done' => 'Compte désactivé et déconnecté.', 'enabled_done' => 'Compte réactivé.',
    ],
    'guides' => [
        'title' => 'Guides', 'subtitle' => ':n guides au total', 'status' => 'Statut', 'col_guide' => 'Guide', 'col_rating' => 'Note', 'col_experiences' => 'Expériences en ligne',
        'col_bookings' => 'Réservations', 'col_cancel' => 'Annulées', 'col_volume' => 'Volume', 'actions' => 'Actions', 'suspend' => 'Suspendre',
    ],
    'revenue' => [
        'title' => 'Revenus', 'subtitle' => 'Entrées et sorties d’argent, 12 derniers mois', 'export' => 'Exporter en CSV', 'paid' => 'Payé par les clients', 'refunded' => 'Remboursé', 'volume' => 'Volume net',
        'revenue' => 'Revenu indicatif (:rate %)',
        'note' => 'Le volume net est ce que les clients ont réellement dépensé (payé moins remboursé). Ahla ne prélève aucune commission dans cette version : le revenu est un chiffre de planification, soit le volume net multiplié par :rate %. Changez le taux avec AHLA_COMMISSION_RATE.',
        'chart' => 'Volume net par mois', 'by_month' => 'Mois par mois', 'col_month' => 'Mois', 'col_payments' => 'Paiements', 'col_revenue' => 'Revenu',
        'top_guides' => 'Meilleurs guides', 'top_cities' => 'Meilleures villes', 'none' => 'Aucun paiement pour le moment.', 'payments_n' => ':n paiements',
    ],
];

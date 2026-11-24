<?php

$adherents = [];
$statistiquesAdherents = [];
$communesAdherents = [];
$formulesAdherents = [];
$reservationsAccueil = [];
$activitesDisponiblesAccueil = [];

if ($onglet === 'adherents') {
    require_once __DIR__ . '/../../model/AdherentRepository.php';

    $depotAdherents = new AdherentRepository($pdo);
    $adherents = $depotAdherents->lister();
    $statistiquesAdherents = $depotAdherents->statistiques();
    $communesAdherents = $depotAdherents->communes();
    $formulesAdherents = $depotAdherents->formulesUtilisees();
}

$statutsAdherent = [
    ['code' => 'actif', 'libelle' => 'Actif'],
    ['code' => 'pause', 'libelle' => 'En pause'],
    ['code' => 'inactif', 'libelle' => 'Inactif'],
    ['code' => 'attente', 'libelle' => 'En attente'],
];

$planningDonnees = [];
if ($onglet === 'creneaux') {
    $planningDonnees = $planning->donneesEmploye((int) $moi);
}

if ($onglet === 'reservations' && $accueil) {
    $stmtReservationsAccueil = $pdo->query(
        "SELECT r.Numres AS id_ligne, r.statut_reservation, u.NumUsers, u.prenomUsers, u.nomUsers, u.mailUsers, u.telUsers,
                ad.matriculeadh, a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, a.date_heure_fin,
                a.capacite_max, s.Numsalle, s.nomsalle, coach.prenomUsers AS coach_prenom, coach.nomUsers AS coach_nom,
                (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite = a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises,
                (SELECT ab.libelle_abo FROM souscrire su JOIN abonnement ab ON ab.Numabo = su.Numabo WHERE su.NumUsers = u.NumUsers AND su.statut_souscription = 'En cours' ORDER BY su.date_debut DESC LIMIT 1) AS libelle_abo,
                (SELECT COUNT(*) FROM reservation ra JOIN activite aa ON aa.Numactivite = ra.Numactivite WHERE ra.NumUsers = u.NumUsers AND ra.statut_reservation <> 'Annulee' AND aa.date_heure_debut >= NOW()) AS reservations_a_venir
           FROM reservation r JOIN adherent ad ON ad.NumUsers = r.NumUsers JOIN utilisateur u ON u.NumUsers = ad.NumUsers
           JOIN activite a ON a.Numactivite = r.Numactivite JOIN salle s ON s.Numsalle = a.Numsalle
           JOIN utilisateur coach ON coach.NumUsers = a.NumUsers
          ORDER BY a.date_heure_debut DESC, u.nomUsers, u.prenomUsers"
    );
    $reservationsAccueil = $stmtReservationsAccueil->fetchAll(PDO::FETCH_ASSOC);
    $activitesDisponiblesAccueil = $pdo->query(
        "SELECT a.Numactivite,a.titre,a.type_activite,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,
                (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite = a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises
           FROM activite a JOIN salle s ON s.Numsalle = a.Numsalle
          WHERE a.date_heure_debut >= NOW() AND s.etatsalle = 'Ouverte'
          ORDER BY a.date_heure_debut LIMIT 200"
    )->fetchAll(PDO::FETCH_ASSOC);
}

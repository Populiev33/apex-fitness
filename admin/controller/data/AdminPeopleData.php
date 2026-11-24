<?php

$utilisateurs = in_array($onglet, ['dashboard', 'utilisateurs', 'coachs', 'maintenance', 'comptes'], true)
    ? Utilisateur::tousLesComptes()
    : [];
$adherents = $onglet === 'adherents' ? $pdo->query(
    "SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers, u.telUsers, u.Date_CreationUsers,
            a.matriculeadh, a.Date_inscriptionadh, a.Date_finadh, a.date_naissance, a.sexe,
            a.adresse_postale, a.photo_profil, a.id_commune,
            COALESCE(c.nom_commune, a.ville) AS nom_commune,
            COALESCE(c.code_postal, a.code_postal) AS code_postal,
            EXISTS (SELECT 1 FROM employe e WHERE e.NumUsers = u.NumUsers) AS est_employe,
            CASE WHEN dernier_abo.NumUsers IS NULL THEN NULL
                 ELSE CONCAT(dernier_abo.libelle_abo, ' • ', dernier_abo.statut_souscription) END AS abonnement,
            dernier_abo.date_fin AS fin_abonnement,
            dernier_abo.date_debut AS debut_abonnement,
            dernier_abo.statut_souscription AS statut_abonnement,
            EXISTS (SELECT 1 FROM souscrire historique WHERE historique.NumUsers = u.NumUsers) AS a_historique_abonnement
       FROM utilisateur u JOIN adherent a ON a.NumUsers = u.NumUsers
       LEFT JOIN commune c ON c.id_commune = a.id_commune
       LEFT JOIN (
            SELECT s.NumUsers, s.date_debut, s.date_fin, s.statut_souscription, ab.libelle_abo
              FROM souscrire s
              JOIN (SELECT NumUsers, MAX(date_debut) AS date_debut FROM souscrire
                     WHERE statut_souscription = 'En cours' AND date_debut <= CURRENT_DATE
                       AND (date_fin IS NULL OR date_fin >= CURRENT_DATE)
                     GROUP BY NumUsers) derniere_date
                ON derniere_date.NumUsers = s.NumUsers AND derniere_date.date_debut = s.date_debut
              JOIN (SELECT NumUsers, date_debut, MAX(Numabo) AS Numabo FROM souscrire
                     WHERE statut_souscription = 'En cours' AND date_debut <= CURRENT_DATE
                       AND (date_fin IS NULL OR date_fin >= CURRENT_DATE)
                     GROUP BY NumUsers, date_debut) dernier_choix
                ON dernier_choix.NumUsers = s.NumUsers AND dernier_choix.date_debut = s.date_debut AND dernier_choix.Numabo = s.Numabo
              JOIN abonnement ab ON ab.Numabo = s.Numabo
             WHERE s.statut_souscription = 'En cours' AND s.date_debut <= CURRENT_DATE
               AND (s.date_fin IS NULL OR s.date_fin >= CURRENT_DATE)
       ) dernier_abo ON dernier_abo.NumUsers = u.NumUsers
      ORDER BY u.nomUsers, u.prenomUsers, u.NumUsers"
)->fetchAll(PDO::FETCH_ASSOC) : [];
$dernierPaiementAdherent = null;
$idAdherentConsulte = $onglet === 'adherents' ? (int) ($_GET['id'] ?? 0) : 0;
if ($idAdherentConsulte > 0) {
    $requeteDernierPaiementAdherent = $pdo->prepare(
        'SELECT date_paiement FROM paiement WHERE NumUsers = ? ORDER BY date_paiement DESC, Numpaiement DESC LIMIT 1'
    );
    $requeteDernierPaiementAdherent->execute([$idAdherentConsulte]);
    $dernierPaiementAdherent = $requeteDernierPaiementAdherent->fetchColumn() ?: null;
}
$aujourdhuiAdherents = new DateTimeImmutable('today');
$debutMoisAdherents = $aujourdhuiAdherents->modify('first day of this month');
$finMoisAdherents = $debutMoisAdherents->modify('first day of next month');
foreach ($adherents as &$adherentRow) {
    $adherentRow['statut_admin'] = $adherentRow['abonnement'] !== null
        ? 'actif'
        : (!empty($adherentRow['a_historique_abonnement']) ? 'inactif' : 'attente');
}
unset($adherentRow);
$statistiquesAdherents = [
    'total' => count($adherents),
    'actifs' => count(array_filter($adherents, static fn(array $row): bool => $row['statut_admin'] === 'actif')),
    'attente' => count(array_filter($adherents, static fn(array $row): bool => $row['statut_admin'] === 'attente')),
    'inactifs' => count(array_filter($adherents, static fn(array $row): bool => $row['statut_admin'] === 'inactif')),
    'fin_mois' => count(array_filter($adherents, static function (array $row) use ($debutMoisAdherents, $finMoisAdherents): bool {
        if ($row['statut_admin'] !== 'actif') return false;
        $dateFin = $row['fin_abonnement'] ?: $row['Date_finadh'];
        if (empty($dateFin)) return false;
        $date = new DateTimeImmutable($dateFin);
        return $date >= $debutMoisAdherents && $date < $finMoisAdherents;
    })),
];
$modeCreationAdherent = $onglet === 'adherents' && ($_GET['adherent'] ?? '') === 'creer';
$communes = $onglet === 'adherents'
    ? $pdo->query('SELECT id_commune, nom_commune, code_postal FROM commune ORDER BY nom_commune')->fetchAll(PDO::FETCH_ASSOC)
    : [];
$activitesCatalogueAdmin = [];
$activiteCatalogueSelectionnee = null;
$vueActiviteCatalogue = (string) ($_GET['vue'] ?? 'voir');
if ($onglet === 'activites') {
    $activitesCatalogueAdmin = ActiviteCatalogue::toutes($pdo);
    $idActiviteSelectionnee = (int) ($_GET['activite_id'] ?? 0);
    if ($idActiviteSelectionnee > 0) $activiteCatalogueSelectionnee = ActiviteCatalogue::trouver($pdo, $idActiviteSelectionnee);
}

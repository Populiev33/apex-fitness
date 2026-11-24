<?php

if ($onglet === 'dashboard') {
$statsRoles = [
    'total'     => count($utilisateurs),
    'admins'    => count(array_filter($utilisateurs, fn($u) => $u['est_admin'])),
    'employes'  => count(array_filter($utilisateurs, fn($u) => $u['est_employe'])),
    'adherents' => count(array_filter($utilisateurs, fn($u) => $u['est_adherent'])),
    'cumuls'    => count(array_filter($utilisateurs, fn($u) => $u['est_employe'] && $u['est_adherent'])),
];
$stats = $statsRoles;

$parActivite = $pdo->query(
  "SELECT a.titre AS libelleact, COUNT(r.Numres) AS n
     FROM activite a
   LEFT JOIN reservation r ON r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee'
   GROUP BY a.Numactivite, a.titre ORDER BY n DESC")->fetchAll();

$statsAudience = $pdo->query(
    "SELECT
        (SELECT COUNT(DISTINCT cible) FROM journal WHERE action = 'VISITE_SITE') AS visiteurs,
        (SELECT COUNT(*) FROM adherent) AS inscrits"
)->fetch(PDO::FETCH_ASSOC) ?: ['visiteurs' => 0, 'inscrits' => 0];

$statsFinances = $pdo->query(
    "SELECT COALESCE(SUM(montant), 0) AS total,
            COALESCE(SUM(CASE WHEN date_paiement >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                              AND date_paiement < DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)
                             THEN montant ELSE 0 END), 0) AS ca_mois,
            COUNT(*) AS transactions
       FROM paiement"
)->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'ca_mois' => 0, 'transactions' => 0];

$statsAdherentsActifs = (int) $pdo->query(
    "SELECT COUNT(DISTINCT NumUsers) FROM souscrire
      WHERE statut_souscription = 'En cours' AND date_debut <= CURRENT_DATE
        AND (date_fin IS NULL OR date_fin >= CURRENT_DATE)"
)->fetchColumn();

$debutGraphique = (new DateTimeImmutable('first day of this month'))->modify('-5 months')->format('Y-m-01');
$requeteRecettes = $pdo->prepare(
    "SELECT DATE_FORMAT(date_paiement, '%Y-%m') AS mois, SUM(montant) AS total
       FROM paiement WHERE date_paiement >= ?
       GROUP BY DATE_FORMAT(date_paiement, '%Y-%m') ORDER BY mois"
);
$requeteRecettes->execute([$debutGraphique]);
$recettes = $requeteRecettes->fetchAll(PDO::FETCH_ASSOC);

} else {
    $statsRoles = $stats = $parActivite = $recettes = $statsAudience = $statsFinances = [];
    $statsAdherentsActifs = 0;
}

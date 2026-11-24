<?php

$totalUtilisateurs = count($utilisateurs ?? []);
$totalAvis = count($avisAdmin ?? []);
$totalAdherents = $statsAdherentsActifs ?? 0;

$totalCA = (float) ($statsFinances['total'] ?? 0);
$caMoisEnCours = (float) ($statsFinances['ca_mois'] ?? 0);
$totalTransactions = (int) ($statsFinances['transactions'] ?? 0);
$panierMoyen = $totalTransactions > 0 ? $totalCA / $totalTransactions : 0;

// Prépare les six derniers mois depuis les recettes réellement enregistrées.
$recettesParMois = [];
foreach ($recettes ?? [] as $recette) {
    $recettesParMois[$recette['mois']] = (float) $recette['total'];
}
$premierMoisGraphique = new DateTimeImmutable('first day of this month -5 months');
$moisLabels = [];
$caData = [];
for ($index = 0; $index < 6; $index++) {
    $mois = $premierMoisGraphique->modify('+' . $index . ' months');
    $cleMois = $mois->format('Y-m');
    $moisLabels[] = $mois->format('m/Y');
    $caData[] = $recettesParMois[$cleMois] ?? 0.0;
}

$topActivites = array_slice($parActivite ?? [], 0, 4);
$maxActiviteCount = max(1, ...array_map(fn($a) => (int) $a['n'], $topActivites ?: [['n' => 1]]));

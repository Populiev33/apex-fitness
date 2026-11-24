<?php

require_once __DIR__ . '/../../model/TableauBordEmploye.php';
require_once __DIR__ . '/../../model/TableauBordAccueil.php';
require_once __DIR__ . '/../../model/TableauBordGeneral.php';

$stats = [];
$remplissage = [];
$donneesTableauAccueil = [];

if ($onglet === 'tableau' && !$maintenance) {
    // Les deux modèles ont le même type : le contrôleur choisit le bon métier,
    // puis appelle la même méthode. C'est le polymorphisme par interface.
    /** @var TableauBordEmploye $tableauBord */
    $tableauBord = $accueil
        ? new TableauBordAccueil()
        : new TableauBordGeneral();

    $donneesTableau = $tableauBord->charger($pdo, (int) $moi);
    $stats = $donneesTableau['stats'];
    $remplissage = $donneesTableau['remplissage'] ?? [];

    if ($accueil) {
        $donneesTableauAccueil = $donneesTableau;
    }
}

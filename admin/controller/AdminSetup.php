<?php

$pdo    = db();
if (in_array($onglet, ['equipements', 'salles'], true)) SchemaInventaireEquipement::ensure($pdo);
$gestionPlanning = $onglet === 'planning' ? new PlanningController() : null;
if ($onglet === 'activites') {
    ActiviteCatalogue::assurer($pdo);
}
if ($onglet === 'adherents') {
    Utilisateur::assurerColonneSexe($pdo);
}
$ongletsGestionEquipe = ['utilisateurs', 'coachs', 'maintenance'];
if (in_array($onglet, $ongletsGestionEquipe, true)) {
    Utilisateur::assurerColonneCommuneEmploye($pdo);
}
$fonctions = in_array($onglet, $ongletsGestionEquipe, true) ? Utilisateur::toutesLesFonctions() : [];
$specialisationsCoachNoms = [
    'Danse fitness', 'Yoga', 'Coach de performance', 'Boxe', 'Coach principal', 'Pilates',
];
if ($onglet === 'coachs') {
    $rechercheFonction = $pdo->prepare('SELECT Numfonc FROM fonction WHERE LOWER(libellefonc) = LOWER(?) LIMIT 1');
    foreach ($specialisationsCoachNoms as $specialisation) {
        $rechercheFonction->execute([$specialisation]);
        if (!$rechercheFonction->fetchColumn()) {
            $pdo->prepare('INSERT INTO fonction (libellefonc) VALUES (?)')->execute([$specialisation]);
        }
    }
    $fonctions = Utilisateur::toutesLesFonctions();
}
$fonctionsCoach = array_values(array_filter(
    $fonctions,
    static fn(array $fonction): bool => in_array(
        mb_strtolower((string) $fonction['libellefonc']),
        array_map('mb_strtolower', $specialisationsCoachNoms),
        true
    )
));
$nomsFonctionsCoachCompat = [...array_map('mb_strtolower', $specialisationsCoachNoms), 'coach'];
$placeholdersFonctionsCoach = implode(',', array_fill(0, count($nomsFonctionsCoachCompat), '?'));
$fonctionsEmployeNoms = array_values(array_filter(
    $fonctions,
    static function (array $fonction): bool {
        $nom = mb_strtolower((string) $fonction['libellefonc']);
        return str_contains($nom, 'maintenance') || str_contains($nom, 'accueil');
    }
));
$idsFonctionsEmploye = array_map('intval', array_column($fonctionsEmployeNoms, 'Numfonc'));
$fonctionMaintenance = null;
foreach ($fonctions as $fonction) {
    if (str_contains(strtolower($fonction['libellefonc']), 'maintenance')) {
        $fonctionMaintenance = $fonction;
        break;
    }
}

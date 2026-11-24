<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/function.php';

$pdo = db();

$json = file_get_contents('https://geo.api.gouv.fr/communes?fields=nom,code,codesPostaux');
$communes = json_decode($json, true);

if (!is_array($communes)) {
    fwrite(STDERR, "Echec de recuperation des communes.\n");
    exit(1);
}

$pdo->beginTransaction();
$insert = $pdo->prepare(
    'INSERT INTO commune (id_commune, nom_commune, code_postal) VALUES (:id, :nom, :cp)
     ON DUPLICATE KEY UPDATE nom_commune = VALUES(nom_commune), code_postal = VALUES(code_postal)'
);

$id = 1;
foreach ($communes as $c) {
    $nom = $c['nom'] ?? '';
    $cps = $c['codesPostaux'] ?? [];
    if ($nom === '' || empty($cps)) {
        continue;
    }
    foreach ($cps as $cp) {
        $insert->execute(['id' => $id++, 'nom' => $nom, 'cp' => $cp]);
    }
}

$pdo->commit();
echo "Import termine : " . ($id - 1) . " lignes.\n";

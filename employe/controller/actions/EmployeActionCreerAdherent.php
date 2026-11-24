<?php

if ($action === 'creer_adherent_accueil') {
    if (!$accueil || $onglet !== 'adherents') {
        http_response_code(403);
        exit('Action réservée à l’employé d’accueil.');
    }

    try {
        $idAdherent = Utilisateur::inscrireAdherent($_POST);
        $telephone = trim((string) ($_POST['tel'] ?? ''));

        $pdo->prepare('UPDATE utilisateur SET telUsers = ? WHERE NumUsers = ?')
            ->execute([$telephone !== '' ? $telephone : null, $idAdherent]);

        journalise('CREATION_ADHERENT_ACCUEIL', 'NumUsers=' . $idAdherent);
        flash('succes', 'Le compte adhérent a été créé.');
    } catch (Throwable $erreur) {
        flash('erreur', $erreur->getMessage());
    }

    redirige('employe/espace.php?onglet=adherents');
}

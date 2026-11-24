<?php

if ($onglet === 'adherents' && $action === 'creer_adherent_admin') {
    try {
        $adherentId = Utilisateur::inscrireAdherent($_POST);
        db()->prepare('UPDATE utilisateur SET telUsers = ? WHERE NumUsers = ?')
            ->execute([trim((string) ($_POST['tel'] ?? '')) ?: null, $adherentId]);
        journalise('CREATION_ADHERENT_ADMIN', 'NumUsers=' . $adherentId);
        flash('succes', 'Le compte adhérent a été créé.');
        redirige('admin/espace.php?onglet=adherents&id=' . $adherentId . '&vue=voir');
    } catch (Throwable $exception) {
        flash('erreur', $exception->getMessage());
        redirige('admin/espace.php?onglet=adherents&adherent=creer');
    }
}

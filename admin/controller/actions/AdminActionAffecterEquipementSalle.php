<?php

if ($onglet === 'salles' && $action === 'affecter_equipement_salle') {
    $idEquipement = filter_var($_POST['equipement'] ?? null, FILTER_VALIDATE_INT);
    $idSalle = filter_var($_POST['salle'] ?? null, FILTER_VALIDATE_INT);
    try {
        if (!$idEquipement || $idEquipement < 1 || !$idSalle || $idSalle < 1) {
            throw new RuntimeException('Choisissez un matériel et une salle valides.');
        }
        $materiel = $pdo->prepare('SELECT nomequip FROM equipement WHERE Numequip = ?');
        $materiel->execute([$idEquipement]);
        $nomEquipement = $materiel->fetchColumn();
        $salle = $pdo->prepare('SELECT nomsalle FROM salle WHERE Numsalle = ?');
        $salle->execute([$idSalle]);
        $nomSalle = $salle->fetchColumn();
        if ($nomEquipement === false || $nomSalle === false) throw new RuntimeException('Le matériel ou la salle n’existe plus.');
        $pdo->prepare('UPDATE equipement SET Numsalle = ? WHERE Numequip = ?')->execute([$idSalle, $idEquipement]);
        journalise('AFFECTATION_EQUIPEMENT_SALLE', 'Numequip=' . (int) $idEquipement . '; Numsalle=' . (int) $idSalle);
        flash('succes', $nomEquipement . ' attribué à ' . $nomSalle . '.');
    } catch (PDOException $exception) {
        error_log('Affectation du matériel impossible : ' . $exception->getMessage());
        flash('erreur', 'Le matériel n’a pas pu être attribué à cette salle.');
    } catch (Throwable $exception) {
        flash('erreur', $exception->getMessage());
    }
    redirige('admin/espace.php?onglet=salles');
}

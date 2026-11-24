<?php

if (!$maintenance && PlanningController::estAction('employe', $action)) {
        $semaineRetour = (string) ($_POST['semaine'] ?? '');
        $retourPlanning = 'employe/espace.php?onglet=creneaux';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $semaineRetour)) {
            $retourPlanning .= '&semaine=' . rawurlencode($semaineRetour);
        }
        $planning->traiter('employe', (int) $moi, $retourPlanning . '#planning-calendrier');
    }

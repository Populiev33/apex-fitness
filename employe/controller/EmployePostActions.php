<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

  require __DIR__ . '/actions/EmployeActionDevenirAdherent.php';
  require __DIR__ . '/actions/EmployeActionCreerAdherent.php';
  $actionsTechnicien = [
    'avancer_maintenance', 'prendre_ticket', 'detacher_ticket', 'modifier_ticket', 'modifier_materiel',
    'declarer_utilisation_materiel', 'commander_materiel', 'commenter_ticket',
  ];
  require __DIR__ . '/actions/EmployeActionAction02.php';  require __DIR__ . '/actions/EmployeActionAction03.php';
  require __DIR__ . '/actions/EmployeActionFermerTicket.php';
  require __DIR__ . '/actions/EmployeActionCommenterTicket.php';
  require __DIR__ . '/actions/EmployeActionDeclarerUtilisationMateriel.php';
  require __DIR__ . '/actions/EmployeActionCommanderMateriel.php';
  require __DIR__ . '/actions/EmployeActionModifierMateriel.php';
  require __DIR__ . '/actions/EmployeActionRepondreMessage.php';
  require __DIR__ . '/actions/EmployeActionAccueilAnnulerReservation.php';
  require __DIR__ . '/actions/EmployeActionRepondreMessage2.php';
  require __DIR__ . '/actions/EmployeActionSupprimerMessage.php';
    /* ---- planning : creer / supprimer un creneau ------------------- */
    require __DIR__ . '/actions/EmployeActionAction13.php';
    /* ---- declarer une panne ---------------------------------------- */
    require __DIR__ . '/actions/EmployeActionDeclarerPanne.php';
    require __DIR__ . '/actions/EmployeActionFermerTicket2.php';
    require __DIR__ . '/actions/EmployeActionPrendreTicket.php';
    require __DIR__ . '/actions/EmployeActionDetacherTicket.php';
    require __DIR__ . '/actions/EmployeActionModifierTicket.php';
    /* ---- faire avancer une demande de maintenance ------------------- */
    require __DIR__ . '/actions/EmployeActionAvancerMaintenance.php';
    /* ---- repondre a une question de FAQ ----------------------------- */
}

/* =====================================================================
   DONNEES
   ===================================================================== */

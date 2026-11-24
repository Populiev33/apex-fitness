<?php

$messages = $onglet === 'messages'
    ? $pdo->query('SELECT * FROM message_contact ORDER BY date_envoi DESC LIMIT 30')->fetchAll()
    : [];
$avisAdmin = [];
$statistiquesAvisAdherents = ['moyenne' => null, 'nombre' => 0];
if ($onglet === 'avis') {
    $avisAdmin = (new AvisController())->index()['avis'] ?? [];
} elseif ($onglet === 'adherents') {
    $statistiquesAvisAdherents = $pdo->query(
        "SELECT AVG(a.noteavis) AS moyenne, COUNT(*) AS nombre
           FROM avis a
           JOIN interaction_adherent i ON i.NumInteraction = a.NumInteraction
          WHERE i.type_interaction = 'AVIS'"
    )->fetch(PDO::FETCH_ASSOC) ?: $statistiquesAvisAdherents;
}

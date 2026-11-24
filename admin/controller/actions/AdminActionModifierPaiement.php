<?php

if ($onglet === 'paiements' && $action === 'modifier_paiement_admin') {
    require_once __DIR__ . '/../../model/AdminPaymentEditor.php';

    $paymentId = (int) ($_POST['paiement'] ?? 0);
    try {
        (new AdminPaymentEditor())->update(
            $pdo,
            $paymentId,
            trim((string) ($_POST['montant'] ?? '')),
            trim((string) ($_POST['date_paiement'] ?? '')),
            trim((string) ($_POST['moyen_paiement'] ?? ''))
        );
        journalise('MODIFICATION_PAIEMENT', 'Numpaiement=' . $paymentId);
        unset($_SESSION['admin_paiement_modif_saisie']);
        flash('succes', 'Les informations du paiement ont été mises à jour.');
    } catch (Throwable $exception) {
        $_SESSION['admin_paiement_modif_saisie'] = [
            'id' => $paymentId,
            'montant' => trim((string) ($_POST['montant'] ?? '')),
            'date_paiement' => trim((string) ($_POST['date_paiement'] ?? '')),
            'moyen_paiement' => trim((string) ($_POST['moyen_paiement'] ?? '')),
        ];
        flash('erreur', $exception->getMessage());
    }

    redirige('admin/espace.php?onglet=paiements&vue=' . (isset($_SESSION['admin_paiement_modif_saisie']) ? 'modifier' : 'voir') . '&paiement=' . $paymentId);
}

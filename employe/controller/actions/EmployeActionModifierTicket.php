<?php

if ($maintenance && $action === 'modifier_ticket') {
    $idTicket = filter_var($_POST['maintenance'] ?? null, FILTER_VALIDATE_INT);
    $urlRetour = 'employe/espace.php?onglet=maintenance';

    try {
        if (!$idTicket || $idTicket < 1) {
            throw new InvalidArgumentException('Ticket invalide.');
        }

        require_once __DIR__ . '/../../model/TicketMaintenance.php';
        (new TicketMaintenance($pdo))->modifier((int) $idTicket, (int) $moi, $_POST);
        journalise('MODIFICATION_TICKET_MAINTENANCE', 'Nummaint=' . (int) $idTicket);
        flash('succes', 'Les informations du ticket ont été enregistrées.');
    } catch (InvalidArgumentException | RuntimeException $exception) {
        if ($exception instanceof PDOException) {
            error_log('Erreur SQL pendant la modification du ticket : ' . $exception->getMessage());
            flash('erreur', 'Le ticket n’a pas pu être modifié. Réessaie.');
        } else {
            flash('erreur', $exception->getMessage());
        }
    } catch (Throwable $exception) {
        error_log('Modification du ticket impossible : ' . $exception->getMessage());
        flash('erreur', 'Le ticket n’a pas pu être modifié. Réessaie.');
    }

    redirige($urlRetour . '&ticket=' . (int) $idTicket . '&vue_ticket=modifier');
}

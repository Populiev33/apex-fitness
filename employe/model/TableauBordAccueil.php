<?php

declare(strict_types=1);

require_once __DIR__ . '/TableauBordEmploye.php';

/** Données propres au poste d'accueil : membres, planning, tickets et activité récente. */
final class TableauBordAccueil implements TableauBordEmploye
{
    public function charger(PDO $pdo, int $idEmploye): array
    {
        return [
            'stats' => $this->chargerIndicateurs($pdo),
            'planning' => $this->chargerPlanningDuJour($pdo),
            'activiteRecente' => $this->chargerActiviteRecente($pdo),
        ];
    }

    private function chargerIndicateurs(PDO $pdo): array
    {
        $adherentsActifs = (int) $pdo->query(
            "SELECT COUNT(DISTINCT NumUsers) FROM souscrire
              WHERE statut_souscription = 'En cours'
                AND date_debut <= CURRENT_DATE
                AND (date_fin IS NULL OR date_fin >= CURRENT_DATE)"
        )->fetchColumn();

        $reservationsAujourdhui = (int) $pdo->query(
            "SELECT COUNT(*) FROM reservation r
               JOIN activite a ON a.Numactivite = r.Numactivite
              WHERE DATE(a.date_heure_debut) = CURRENT_DATE
                AND r.statut_reservation <> 'Annulee'"
        )->fetchColumn();

        $ticketsOuverts = (int) $pdo->query(
            "SELECT COUNT(*) FROM demande_maintenance d
               JOIN statut_maintenance sm ON sm.Numstatut = d.Numstatut
               JOIN statut s ON s.Numstatut = sm.Numstatut
              WHERE s.libelle_statut <> 'Terminee'"
        )->fetchColumn();

        $avis = $pdo->query('SELECT AVG(noteavis) AS moyenne, COUNT(*) AS nombre FROM avis')
            ->fetch(PDO::FETCH_ASSOC);

        return [
            'adherentsActifs' => $adherentsActifs,
            'reservationsAujourdhui' => $reservationsAujourdhui,
            'ticketsOuverts' => $ticketsOuverts,
            'noteMoyenne' => $avis && $avis['nombre'] > 0 ? (float) $avis['moyenne'] : null,
            'nombreAvis' => (int) ($avis['nombre'] ?? 0),
        ];
    }

    private function chargerPlanningDuJour(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT a.Numactivite, a.titre, a.date_heure_debut, a.date_heure_fin,
                    a.capacite_max, s.nomsalle,
                    (SELECT COUNT(*) FROM reservation r
                      WHERE r.Numactivite = a.Numactivite
                        AND r.statut_reservation <> 'Annulee') AS participants
               FROM activite a
               JOIN salle s ON s.Numsalle = a.Numsalle
              WHERE DATE(a.date_heure_debut) = CURRENT_DATE
              ORDER BY a.date_heure_debut
              LIMIT 5"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function chargerActiviteRecente(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT j.action, j.date_action, u.prenomUsers, u.nomUsers
               FROM journal j
               LEFT JOIN utilisateur u ON u.NumUsers = j.NumUsers
              WHERE j.action IN (
                    'CREATION_ADHERENT_ADMIN', 'CREATION_ADHERENT_ACCUEIL', 'AJOUT_ROLE_ADHERENT',
                    'RESERVATION', 'CREATION_RESERVATION_ADMIN',
                    'ANNULATION', 'ANNULATION_RESERVATION_ADMIN',
                    'CREATION_MAINTENANCE', 'MESSAGE_CONTACT',
                    'PAIEMENT_ABONNEMENT', 'AVIS_SALLE'
              )
              ORDER BY j.date_action DESC, j.Numjournal DESC
              LIMIT 5"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}

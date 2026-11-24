<?php

declare(strict_types=1);

require_once __DIR__ . '/TableauBordEmploye.php';

/** Tableau de bord simple utilisé par les employés qui ne sont pas à l'accueil. */
final class TableauBordGeneral implements TableauBordEmploye
{
    public function charger(PDO $pdo, int $idEmploye): array
    {
        $stats = [
            'adherents' => (int) $pdo->query(
                'SELECT COUNT(*) FROM adherent WHERE Date_finadh IS NULL OR Date_finadh >= CURDATE()'
            )->fetchColumn(),
            'reservations' => (int) $pdo->query(
                "SELECT COUNT(*) FROM reservation r
                   JOIN activite a ON a.Numactivite = r.Numactivite
                  WHERE DATE(a.date_heure_debut) = CURDATE()
                    AND r.statut_reservation <> 'Annulee'"
            )->fetchColumn(),
            'creneaux' => (int) $pdo->query(
                'SELECT COUNT(*) FROM activite WHERE DATE(date_heure_debut) = CURDATE()'
            )->fetchColumn(),
            'dispo' => $this->compterEquipements($pdo, 'Disponible'),
            'maint' => $this->compterEquipements($pdo, 'Maintenance'),
            'hs' => $this->compterEquipements($pdo, 'Hors service'),
        ];

        $remplissage = $pdo->query(
            "SELECT a.titre AS libelleact, a.capacite_max,
                    (SELECT COUNT(*) FROM reservation r
                      WHERE r.Numactivite = a.Numactivite
                        AND r.statut_reservation <> 'Annulee') AS places_prises
               FROM activite a
              WHERE DATE(a.date_heure_debut) = CURDATE()
              ORDER BY a.date_heure_debut"
        )->fetchAll(PDO::FETCH_ASSOC);

        return ['stats' => $stats, 'remplissage' => $remplissage];
    }

    private function compterEquipements(PDO $pdo, string $statut): int
    {
        $requete = $pdo->prepare(
            'SELECT COUNT(*) FROM equipement e
               JOIN statut s ON s.Numstatut = e.Numstatut
              WHERE s.libelle_statut = ?'
        );
        $requete->execute([$statut]);

        return (int) $requete->fetchColumn();
    }
}

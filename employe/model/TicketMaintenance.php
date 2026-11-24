<?php

declare(strict_types=1);

/** Modèle : règles et accès aux données d’un ticket de maintenance. */
final class TicketMaintenance
{
    private const PRIORITES_AUTORISEES = ['Basse', 'Moyenne', 'Haute', 'Urgente'];

    public function __construct(private PDO $pdo)
    {
    }

    /** Modifie la description et la priorité d’un ticket pris en charge par le technicien. */
    public function modifier(int $idTicket, int $idTechnicien, array $saisie): void
    {
        $description = trim((string) ($saisie['description'] ?? ''));
        $priorite = (string) ($saisie['priorite'] ?? '');

        if ($description === '' || mb_strlen($description) < 5 || mb_strlen($description) > 2000) {
            throw new InvalidArgumentException('La description doit contenir entre 5 et 2 000 caractères.');
        }
        if (!in_array($priorite, self::PRIORITES_AUTORISEES, true)) {
            throw new InvalidArgumentException('Choisissez une priorité valide.');
        }

        $this->pdo->beginTransaction();
        try {
            $requete = $this->pdo->prepare(
                "SELECT d.NumUsers_technicien, d.Numstatut, s.libelle_statut
                   FROM demande_maintenance d
                   JOIN statut s ON s.Numstatut = d.Numstatut
                  WHERE d.Nummaint = ? AND s.type_statut = 'MAINTENANCE'
                  FOR UPDATE"
            );
            $requete->execute([$idTicket]);
            $ticket = $requete->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                throw new OutOfBoundsException('Ticket introuvable.');
            }
            if ((int) $ticket['NumUsers_technicien'] !== $idTechnicien) {
                throw new RuntimeException('Prends en charge ce ticket avant de le modifier.');
            }
            if (in_array($ticket['libelle_statut'], ['À valider', 'Terminee'], true)) {
                throw new RuntimeException('Un ticket terminé ne peut plus être modifié.');
            }

            $this->pdo->prepare(
                'UPDATE demande_maintenance
                    SET description_problememaint = ?, priorite = ?
                  WHERE Nummaint = ?'
            )->execute([$description, $priorite, $idTicket]);
            $this->pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?)'
            )->execute(['Description ou priorité du ticket modifiée.', $idTicket, $idTechnicien, (int) $ticket['Numstatut']]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}

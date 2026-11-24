<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';

/** Modèle : notifications des adhérents. */
final class Notification
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= db();
    }

    /** Crée une notification (ignorée si la même existe déjà pour cette réservation et ce type). */
    public function creer(int $adherentId, int $reservationId, string $type, string $titre, string $message): void
    {
        $query = $this->pdo->prepare(
            'INSERT IGNORE INTO notification_adherent (NumUsers, Numres, type_notification, titre, message)
             VALUES (?, ?, ?, ?, ?)'
        );
        $query->execute([$adherentId, $reservationId, $type, $titre, $message]);
    }

    public function pourAdherent(int $adherentId, int $limit = 20): array
    {
        $this->genererRappelsDus();

        $query = $this->pdo->prepare(
            'SELECT Numnotification AS id, type_notification AS type,
                    titre AS title, message, date_creation AS created_at,
                    date_lecture AS read_at
             FROM notification_adherent
             WHERE NumUsers = ?
             ORDER BY date_creation DESC, Numnotification DESC
             LIMIT ?'
        );
        $query->bindValue(1, $adherentId, PDO::PARAM_INT);
        $query->bindValue(2, $limit, PDO::PARAM_INT);
        $query->execute();

        $notifications = $query->fetchAll();
        foreach ($this->messagesNonLus($adherentId) as $message) {
            $notifications[] = [
                'id' => -(int) $message['IdMessage'],
                'type' => 'MESSAGE',
                'title' => 'Nouveau message de l’accueil',
                'message' => $message['message'],
                'created_at' => $message['date_envoi'],
                'read_at' => null,
            ];
        }
        usort($notifications, static fn(array $a, array $b): int => strcmp((string) $b['created_at'], (string) $a['created_at']) ?: ((int) $b['id'] <=> (int) $a['id']));

        return array_slice($notifications, 0, $limit);
    }

    public function compterNonLues(int $adherentId): int
    {
        $query = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notification_adherent
             WHERE NumUsers = ? AND date_lecture IS NULL'
        );
        $query->execute([$adherentId]);

        return (int) $query->fetchColumn() + $this->compterMessagesNonLus($adherentId);
    }

    public function preparerRappels(): int
    {
        return $this->genererRappelsDus();
    }

    public function marquerCommeLue(int $adherentId, int $notificationId): bool
    {
        if ($notificationId < 0) {
            $query = $this->pdo->prepare(
                'UPDATE message_adherent SET lu = 1
                 WHERE IdMessage = ? AND NumUsers_destinataire = ? AND lu = 0'
            );
            $query->execute([abs($notificationId), $adherentId]);
            return $query->rowCount() > 0;
        }

        $query = $this->pdo->prepare(
            'UPDATE notification_adherent
             SET date_lecture = NOW()
             WHERE Numnotification = ? AND NumUsers = ? AND date_lecture IS NULL'
        );
        $query->execute([$notificationId, $adherentId]);

        return $query->rowCount() > 0;
    }

    private function genererRappelsDus(): int
    {
        $created = $this->pdo->exec(
            "INSERT IGNORE INTO notification_adherent
                (NumUsers, Numres, type_notification, titre, message)
             SELECT r.NumUsers, r.Numres, 'RAPPEL_SEANCE', 'Rappel de séance',
                    CONCAT('Votre séance « ', a.titre, ' » commence le ',
                           DATE_FORMAT(a.date_heure_debut, '%d/%m à %Hh%i'), '.')
             FROM reservation r
             JOIN activite a ON a.Numactivite = r.Numactivite
             WHERE r.statut_reservation <> 'Annulee'
               AND a.date_heure_debut > NOW()
               AND a.date_heure_debut <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
               AND NOT EXISTS (
                   SELECT 1 FROM notification_adherent n
                   WHERE n.Numres = r.Numres AND n.type_notification = 'RAPPEL_SEANCE'
               )"
        );

        return $created === false ? 0 : $created;
    }

    private function messagesNonLus(int $adherentId): array
    {
        if (!$this->tableMessagesExiste()) return [];
        $query = $this->pdo->prepare(
            "SELECT m.IdMessage, m.message, m.date_envoi
             FROM message_adherent m
             JOIN employe e ON e.NumUsers = m.NumUsers_emetteur
             JOIN fonction f ON f.Numfonc = e.Numfonc
             WHERE m.NumUsers_destinataire = ? AND m.lu = 0 AND f.libellefonc = 'Accueil'
             ORDER BY m.date_envoi DESC, m.IdMessage DESC
             LIMIT 20"
        );
        $query->execute([$adherentId]);
        return $query->fetchAll();
    }

    private function compterMessagesNonLus(int $adherentId): int
    {
        if (!$this->tableMessagesExiste()) return 0;
        $query = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM message_adherent m
             JOIN employe e ON e.NumUsers = m.NumUsers_emetteur
             JOIN fonction f ON f.Numfonc = e.Numfonc
             WHERE m.NumUsers_destinataire = ? AND m.lu = 0 AND f.libellefonc = 'Accueil'"
        );
        $query->execute([$adherentId]);
        return (int) $query->fetchColumn();
    }

    private function tableMessagesExiste(): bool
    {
        $query = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $query->execute(['message_adherent']);
        return (bool) $query->fetchColumn();
    }
}

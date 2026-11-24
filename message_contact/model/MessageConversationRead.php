<?php

declare(strict_types=1);

trait MessageConversationRead
{
    public function getAllFor(int $userId): array
    {
        $this->assureTable();
        if (!$this->isAccueil($userId)) return [];
        $query = $this->pdo->prepare(
            'SELECT m.*, u.prenomUsers, u.nomUsers
             FROM message_adherent m
             JOIN utilisateur u ON u.NumUsers = m.NumUsers_emetteur
             WHERE m.NumUsers_emetteur = ? OR m.NumUsers_destinataire = ?
             ORDER BY m.date_envoi ASC, m.IdMessage ASC'
        );
        $query->execute([$userId, $userId]);
        return $query->fetchAll();
    }

    /** Marque comme lus les messages reçus dans la conversation ouverte. */
    public function markAsRead(int $userId, array $messageIds): void
    {
        if (!$this->isAccueil($userId)) {
            throw new RuntimeException('La messagerie est réservée à l’employé d’accueil.');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $messageIds), static fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = $this->pdo->prepare(
            "UPDATE message_adherent SET lu = 1
              WHERE NumUsers_destinataire = ? AND lu = 0
                AND IdMessage IN ($placeholders)"
        );
        $query->execute(array_merge([$userId], $ids));
    }

    public function getMemberSince(int $userId, int $memberId): ?string
    {
        if (!$this->isAccueil($userId)) {
            return null;
        }

        $query = $this->pdo->prepare('SELECT Date_inscriptionadh FROM adherent WHERE NumUsers = ?');
        $query->execute([$memberId]);
        $date = $query->fetchColumn();

        return $date !== false ? (string) $date : null;
    }

    private function isAccueil(int $userId): bool
    {
        $query = $this->pdo->prepare(
            "SELECT 1 FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
             WHERE e.NumUsers=? AND f.libellefonc='Accueil'"
        );
        $query->execute([$userId]);
        return (bool) $query->fetchColumn();
    }
}

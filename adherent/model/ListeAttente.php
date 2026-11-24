<?php
declare(strict_types=1);

/** Modèle : liste d'attente des activités. */
final class ListeAttente
{
    /** Numéros des adhérents en attente pour une activité, du plus ancien au plus récent. */
    public function adherentsEnAttente(int $activityId): array
    {
        $q = db()->prepare(
            'SELECT NumUsers FROM liste_attente WHERE Numactivite=? ORDER BY date_inscription, Numattente'
        );
        $q->execute([$activityId]);

        return array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
    }
}
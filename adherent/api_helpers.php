<?php

require_once __DIR__ . '/model/Notification.php';
require_once __DIR__ . '/model/ListeAttente.php';

/** Envoie une réponse JSON et arrête le script. */
function apiReply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Met en forme une séance pour le calendrier (présentation JSON uniquement). */
function apiSession(array $row): array
{
    $start = str_replace(' ', 'T', (string)$row['date_heure_debut']);
    $end = str_replace(' ', 'T', (string)$row['date_heure_fin']);
    $category = (string)$row['type_activite'];
    $capacity = (int)$row['capacite_max'];
    $taken = (int)$row['places_prises'];
    $booked = !empty($row['Numres']);
    return [
        'id'=>(string)$row['Numactivite'], 'activity_id'=>(int)$row['Numactivite'],
        'reservation_id'=>(int)($row['Numres'] ?? 0), 'title'=>$row['titre'],
        'category'=>$category, 'coach'=>$row['coach'], 'coach_full'=>$row['coach'],
        'level'=>(string)($row['niveau'] ?? ''), 'room'=>$row['nomsalle'],
        'date'=>substr($start,0,10), 'start'=>$start, 'end'=>$end,
        'capacity'=>$capacity, 'booked'=>$taken, 'remaining'=>max(0,$capacity-$taken),
        'is_booked'=>$booked, 'is_past'=>strtotime($start)<time(),
        'is_waitlisted'=>(bool)($row['en_attente'] ?? false),
        'can_cancel'=>strtotime($start)-time() >= 2*3600,
        'color'=>CATEGORIES[$category]['color'] ?? '#4F7DFF', 'image'=>CAT_IMAGES[$category] ?? '',
    ];
}

/** Crée une notification pour un adhérent (délègue au modèle). */
function notifyMember(int $memberId, int $reservationId, string $type, string $title, string $message): void
{
    (new Notification())->creer($memberId, $reservationId, $type, $title, $message);
}

/**
 * Promeut le premier adhérent de la liste d'attente quand une place se libère.
 * Retourne le numéro de l'adhérent promu, ou null si personne n'est promu.
 *
 * TEMPORAIRE : la promotion automatique est désactivée en attendant la reconstruction
 * de cette fonction (création de la réservation et notification de l'adhérent promu).
 */
function promoteWaitlist(int $activityId, PlanningController $planning): ?int
{
    return null;
}
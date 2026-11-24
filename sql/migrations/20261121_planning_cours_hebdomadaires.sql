-- Programme les activités du catalogue chaque semaine pendant 12 semaines.
-- Chaque série partage un id_serie pour permettre sa gestion depuis le planning.
-- La migration est idempotente et ignore les créneaux déjà occupés.
INSERT INTO activite
    (titre, type_activite, description, niveau, date_heure_debut, date_heure_fin,
     capacite_max, Numsalle, NumUsers, id_serie)
SELECT catalogue.titre,
       catalogue.type_activite,
       catalogue.description,
       'Tous niveaux',
       TIMESTAMP(DATE_ADD(calendrier.lundi_suivant, INTERVAL (semaine.numero * 7 + cours.jour) DAY), cours.debut),
       TIMESTAMP(DATE_ADD(calendrier.lundi_suivant, INTERVAL (semaine.numero * 7 + cours.jour) DAY), cours.fin),
       LEAST(cours.capacite, salle.capacitesalle),
       salle.Numsalle,
       coach.NumUsers,
       MD5(CONCAT('athletix-hebdo-', cours.type_activite, '-', cours.jour, '-', cours.debut))
FROM (
    SELECT 0 AS numero UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
    UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11
) AS semaine
CROSS JOIN (
    SELECT 0 AS jour, 'yoga' AS type_activite, 'Studio zen' AS salle,
           '09:00:00' AS debut, '10:00:00' AS fin, 14 AS capacite
    UNION ALL SELECT 1, 'cross', 'Zone cross', '18:00:00', '19:00:00', 12
    UNION ALL SELECT 2, 'pilates', 'Studio zen', '12:00:00', '13:00:00', 14
    UNION ALL SELECT 3, 'boxe', 'Salle boxe', '18:00:00', '19:00:00', 12
    UNION ALL SELECT 4, 'danse', 'Studio danse', '17:00:00', '18:00:00', 16
    UNION ALL SELECT 5, 'abdos', 'Plateau muscu', '10:00:00', '11:00:00', 20
) AS cours
CROSS JOIN (
    SELECT DATE_ADD(CURRENT_DATE, INTERVAL (7 - WEEKDAY(CURRENT_DATE)) DAY) AS lundi_suivant
) AS calendrier
JOIN activite_catalogue catalogue ON catalogue.type_activite = cours.type_activite
JOIN salle ON salle.nomsalle = cours.salle AND salle.etatsalle = 'Ouverte'
JOIN employe coach ON coach.NumUsers = (
    SELECT e.NumUsers
    FROM employe e
    JOIN fonction f ON f.Numfonc = e.Numfonc
    WHERE f.libellefonc <> 'Technicien maintenance'
    ORDER BY e.NumUsers
    LIMIT 1
)
WHERE NOT EXISTS (
    SELECT 1
    FROM activite existante
    WHERE (existante.Numsalle = salle.Numsalle OR existante.NumUsers = coach.NumUsers)
      AND existante.date_heure_debut < TIMESTAMP(
          DATE_ADD(calendrier.lundi_suivant, INTERVAL (semaine.numero * 7 + cours.jour) DAY),
          cours.fin
      )
      AND existante.date_heure_fin > TIMESTAMP(
          DATE_ADD(calendrier.lundi_suivant, INTERVAL (semaine.numero * 7 + cours.jour) DAY),
          cours.debut
      )
);

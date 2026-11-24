<?php

declare(strict_types=1);

require_once __DIR__ . '/StatutAdherent.php';

/** Centralise les lectures en base utilisées par l'écran de gestion des adhérents. */
final class AdherentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function lister(): array
    {
        $requete = $this->pdo->query(
            "SELECT ad.NumUsers, ad.matriculeadh, ad.Date_inscriptionadh,
                    ad.Date_finadh, ad.adresse_postale, ad.date_naissance,
                    ad.sexe, ad.ville, ad.code_postal, ad.id_commune,
                    u.nomUsers, u.prenomUsers, u.mailUsers, u.telUsers,
                    c.nom_commune,
                    (SELECT ab.libelle_abo
                       FROM souscrire s
                       JOIN abonnement ab ON ab.Numabo = s.Numabo
                      WHERE s.NumUsers = ad.NumUsers
                        AND s.statut_souscription = 'En cours'
                        AND s.date_debut <= CURRENT_DATE
                        AND (s.date_fin IS NULL OR s.date_fin >= CURRENT_DATE)
                      ORDER BY s.date_debut DESC, s.Numabo DESC
                      LIMIT 1) AS formule_active,
                    (SELECT s.statut_souscription
                       FROM souscrire s
                      WHERE s.NumUsers = ad.NumUsers
                      ORDER BY s.date_debut DESC, s.Numabo DESC
                      LIMIT 1) AS derniere_souscription,
                    EXISTS (SELECT 1 FROM souscrire historique
                             WHERE historique.NumUsers = ad.NumUsers) AS a_historique,
                    EXISTS (SELECT 1 FROM souscrire active
                             WHERE active.NumUsers = ad.NumUsers
                               AND active.statut_souscription = 'En cours'
                               AND active.date_debut <= CURRENT_DATE
                               AND (active.date_fin IS NULL OR active.date_fin >= CURRENT_DATE)) AS abonnement_actif
               FROM adherent ad
               JOIN utilisateur u ON u.NumUsers = ad.NumUsers
               LEFT JOIN commune c ON c.id_commune = ad.id_commune
              ORDER BY ad.Date_inscriptionadh DESC, u.nomUsers, u.prenomUsers"
        );

        $adherents = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($adherents as &$adherent) {
            $adherent['statut'] = FabriqueStatutAdherent::creer(
                (bool) $adherent['abonnement_actif'],
                $adherent['derniere_souscription'],
                (bool) $adherent['a_historique']
            );
            $adherent['commune_affichee'] = $adherent['nom_commune'] ?: $adherent['ville'];
        }
        unset($adherent);

        return $adherents;
    }

    public function statistiques(): array
    {
        $requete = $this->pdo->query(
            "SELECT
                (SELECT COUNT(DISTINCT NumUsers) FROM souscrire
                  WHERE statut_souscription = 'En cours'
                    AND date_debut <= CURRENT_DATE
                    AND (date_fin IS NULL OR date_fin >= CURRENT_DATE)) AS actifs,
                (SELECT COUNT(*) FROM adherent
                  WHERE Date_inscriptionadh >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                    AND Date_inscriptionadh < DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)) AS nouveaux,
                (SELECT COUNT(DISTINCT NumUsers) FROM souscrire
                  WHERE statut_souscription = 'En cours'
                    AND date_debut <= CURRENT_DATE
                    AND date_fin BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)) AS renouvellements,
                (SELECT AVG(noteavis) FROM avis) AS note_moyenne,
                (SELECT COUNT(*) FROM avis) AS nombre_avis"
        );

        $stats = $requete->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats['actifs'] = (int) ($stats['actifs'] ?? 0);
        $stats['nouveaux'] = (int) ($stats['nouveaux'] ?? 0);
        $stats['renouvellements'] = (int) ($stats['renouvellements'] ?? 0);
        $stats['nombre_avis'] = (int) ($stats['nombre_avis'] ?? 0);
        $stats['note_moyenne'] = $stats['note_moyenne'] !== null ? (float) $stats['note_moyenne'] : null;

        return $stats;
    }

    public function communes(): array
    {
        return $this->pdo->query(
            'SELECT DISTINCT COALESCE(c.nom_commune, a.ville) AS nom_commune
               FROM adherent a LEFT JOIN commune c ON c.id_commune = a.id_commune
              WHERE COALESCE(c.nom_commune, a.ville) IS NOT NULL
                AND COALESCE(c.nom_commune, a.ville) <> \'\'
              ORDER BY nom_commune'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function formulesUtilisees(): array
    {
        return $this->pdo->query(
            "SELECT DISTINCT ab.libelle_abo
               FROM souscrire s JOIN abonnement ab ON ab.Numabo = s.Numabo
              WHERE s.statut_souscription = 'En cours'
                AND s.date_debut <= CURRENT_DATE
                AND (s.date_fin IS NULL OR s.date_fin >= CURRENT_DATE)
              ORDER BY ab.libelle_abo"
        )->fetchAll(PDO::FETCH_COLUMN);
    }
}

<?php

declare(strict_types=1);

/** Contrat commun aux tableaux de bord des différents métiers salariés. */
interface TableauBordEmploye
{
    /** Retourne les données que la vue du tableau de bord doit afficher. */
    public function charger(PDO $pdo, int $idEmploye): array;
}

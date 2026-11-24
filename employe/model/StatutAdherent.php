<?php

declare(strict_types=1);

/** Les états partagent les mêmes méthodes, mais chacun fournit son libellé et sa classe CSS. */
interface StatutAdherent
{
    public function code(): string;

    public function libelle(): string;

    public function classeCss(): string;
}

final class AdherentActif implements StatutAdherent
{
    public function code(): string { return 'actif'; }
    public function libelle(): string { return 'Actif'; }
    public function classeCss(): string { return 'is-actif'; }
}

final class AdherentEnPause implements StatutAdherent
{
    public function code(): string { return 'pause'; }
    public function libelle(): string { return 'En pause'; }
    public function classeCss(): string { return 'is-pause'; }
}

final class AdherentInactif implements StatutAdherent
{
    public function code(): string { return 'inactif'; }
    public function libelle(): string { return 'Inactif'; }
    public function classeCss(): string { return 'is-inactif'; }
}

final class AdherentEnAttente implements StatutAdherent
{
    public function code(): string { return 'attente'; }
    public function libelle(): string { return 'En attente'; }
    public function classeCss(): string { return 'is-attente'; }
}

/** Choisit l'état métier à partir des souscriptions réellement enregistrées. */
final class FabriqueStatutAdherent
{
    public static function creer(bool $abonnementActif, ?string $derniereSouscription, bool $aHistorique): StatutAdherent
    {
        if ($abonnementActif) {
            return new AdherentActif();
        }
        if ($derniereSouscription === 'Suspendue') {
            return new AdherentEnPause();
        }
        if ($aHistorique) {
            return new AdherentInactif();
        }

        return new AdherentEnAttente();
    }
}

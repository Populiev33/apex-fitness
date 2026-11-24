<?php

declare(strict_types=1);

/** Contrat commun aux badges de lecture d'une conversation. */
interface EtatConversation
{
    public function libelle(): string;

    public function classeCss(): string;

    public function estNonLu(): bool;
}

final class ConversationNonLue implements EtatConversation
{
    public function libelle(): string { return 'Non lu'; }
    public function classeCss(): string { return 'is-non-lu'; }
    public function estNonLu(): bool { return true; }
}

final class ConversationLue implements EtatConversation
{
    public function libelle(): string { return 'Lu'; }
    public function classeCss(): string { return 'is-lu'; }
    public function estNonLu(): bool { return false; }
}

/** Crée l'objet d'état adapté; la vue utilise ensuite la même interface. */
final class FabriqueEtatConversation
{
    public static function creer(bool $nonLue): EtatConversation
    {
        return $nonLue ? new ConversationNonLue() : new ConversationLue();
    }
}

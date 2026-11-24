<?php

require_once __DIR__ . '/../model/AccueilActivites.php';

/** Fournit les données nécessaires à la page d’accueil publique. */
final class AccueilController
{
    private AccueilActivites $activites;

    public function __construct(?AccueilActivites $activites = null)
    {
        $this->activites = $activites ?? new AccueilActivites();
    }

    /** Données transmises à la vue : le routeur transforme chaque clé en variable (ici $activites). */
    public function index(): array
    {
        return ['activites' => $this->getAll()];
    }

    public function getAll(): array
    {
        return $this->activites->toutes();
    }
}
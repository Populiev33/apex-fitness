<?php

declare(strict_types=1);

/**
 * Gestion de l'authentification et des rôles utilisateur
 */

/**
 * Vérifie si un utilisateur est connecté.
 *
 * @return bool true si l'utilisateur a une session active
 *
 * @see utilisateur()
 * @see a_role()
 */
function connecte(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Retourne les informations de l'utilisateur connecté.
 *
 * @return array|null Les données de l'utilisateur ou null si non connecté
 *
 * @see connecte()
 */
function utilisateur(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Vérifie si l'utilisateur possède un rôle spécifique.
 *
 * @param string $role Le rôle à vérifier (ADMIN, EMPLOYE, ADHERENT)
 * @return bool true si l'utilisateur a le rôle, false sinon
 *
 * @see connecte()
 */
function a_role(string $role): bool
{
    return connecte() && in_array($role, $_SESSION['user']['roles'], true);
}

/**
 * Récupère la fonction de l'employé connecté.
 *
 * @param int|null $numUsers L'identifiant de l'utilisateur (utilise celui de la session si null)
 * @return string|null La libellé de la fonction ou null
 *
 * @see fonction_employe()
 */
function fonction_employe(?int $numUsers = null): ?string
{
    $numUsers ??= (int) utilisateur()['NumUsers'];
    $query = db()->prepare(
        'SELECT f.libellefonc'
        . ' FROM employe e JOIN fonction f ON f.Numfonc = e.Numfonc'
        . ' WHERE e.NumUsers = ?'
    );
    $query->execute([$numUsers]);
    return $query->fetchColumn() ?: null;
}

/**
 * Vérifie si l'utilisateur connecté est un technicien de maintenance.
 *
 * @return bool true si c'est un technicien maintenance
 *
 * @see a_role()
 * @see fonction_employe()
 */
function est_technicien_maintenance(): bool
{
    return a_role('EMPLOYE') && fonction_employe() === 'Technicien maintenance';
}

/**
 * Détermine si l'utilisateur connecté fait partie de l'équipe (interface dédiée).
 *
 * Seuls ADMIN, EMPLOYE et ADHERENT sont des rôles : « maintenance » et « accueil »
 * sont des fonctions d'employé (table fonction), pas des rôles.
 * Le résultat est mis en cache pour la durée de la requête (un seul accès à la base).
 *
 * @return string|null « Administration », « Maintenance », « Accueil » ou null
 *
 * @see a_role()
 * @see fonction_employe()
 */
function profil_equipe(): ?string
{
    static $profil = false; // false = pas encore calculé (null est un résultat valide)

    if ($profil === false) {
        $profil = null;
        if (a_role('ADMIN')) {
            $profil = 'Administration';
        } elseif (a_role('EMPLOYE')) {
            $fonction = strtolower((string) fonction_employe());
            if ($fonction === 'technicien maintenance') {
                $profil = 'Maintenance';
            } elseif (strpos($fonction, 'accueil') !== false) {
                $profil = 'Accueil';
            }
        }
    }

    return $profil;
}

/**
 * Exige qu'un utilisateur soit connecté et possède le rôle requis.
 *
 * @param string $role Le rôle requis
 *
 * @see connecte()
 * @see a_role()
 */
function exige_role(string $role): void
{
    if (!connecte()) {
        flash('info', 'Connectez-vous pour accéder à cette page.');
        redirige('utilisateur/connexion.php?retour=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
    if (!a_role($role) && !a_role('ADMIN')) {
        http_response_code(403);
        flash('erreur', "Votre compte ne dispose pas du rôle $role.");
        redirige('accueil/index.php');
    }
}

/**
 * Déconnecte l'utilisateur actuel.
 *
 * @see journalise()
 */
function deconnexion(): void
{
    if (connecte()) {
        journalise('DECONNEXION', utilisateur()['mailUsers']);
    }
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'] ?: '/',
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => true,
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
    $_SESSION = [];
    session_destroy();
}
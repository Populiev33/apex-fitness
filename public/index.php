<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/function.php';

// Compte une visite par session pour estimer les visiteurs uniques du site.
if (empty($_SESSION['site_visit_logged'])) {
    journalise('VISITE_SITE', hash('sha256', session_id()));
    $_SESSION['site_visit_logged'] = true;
}

/**
 * Table des routes : un module n'existe que s'il est déclaré ici.
 *
 *  'acces'   : 'public'   -> accessible à tous
 *              'connecte' -> utilisateur connecté, quel que soit son rôle
 *              'ADMIN', 'EMPLOYE', 'ADHERENT' -> rôle exigé
 *              ['EMPLOYE', 'ADMIN'] -> l'un de ces rôles suffit
 *  'actions' : (facultatif) règle d'accès propre à certaines actions du module
 *  'layout'  : (facultatif) 'public' ou 'application'. Par défaut : 'public' si le module
 *              est public, 'application' sinon. Un contrôleur peut aussi le changer
 *              en retournant la clé 'layout'.
 *
 * Tout module non déclaré renvoie une 404 : impossible d'oublier de le protéger.
 */
const ROUTES = [
    // --- Pages publiques ---
    'accueil'         => ['acces' => 'public'],
    'abonnement'      => ['acces' => 'public'],
    'abonnement_plus' => ['acces' => 'public'],
    'avis'            => ['acces' => 'public', 'actions' => ['admin' => 'ADMIN']],
    'faq'             => ['acces' => 'public'],
    'message_contact' => ['acces' => 'public'],
    'offre'           => ['acces' => 'public'],
    'freemium'        => ['acces' => 'public'],
    'commune'         => ['acces' => 'public'],
    'souscrire'       => ['acces' => 'public'],

    // --- Compte : connexion et inscription ouvertes, le reste réservé aux connectés ---
    'utilisateur'     => [
        'acces'   => 'connecte',
        'layout'  => 'application',
        'actions' => ['connexion' => 'public', 'inscription' => 'public', 'deconnexion' => 'public'],
    ],

    // --- Réservé aux adhérents ---
    'adherent'        => ['acces' => 'ADHERENT'],

    // --- Réservé aux employés ---
    'employe'         => ['acces' => 'EMPLOYE'],

    // --- Réservé aux administrateurs ---
    // L'action 'espace' sert aussi l'espace employé : le contrôleur admin DOIT limiter
    // les onglets accessibles aux employés (voir espace() dans le contrôleur admin).
    'admin'           => [
        'acces'   => 'ADMIN',
        'actions' => ['espace' => ['EMPLOYE', 'ADMIN']],
    ],
    'fournisseur'     => ['acces' => 'ADMIN'],
    'statut'          => ['acces' => 'ADMIN'],
    'statut_maintenance' => ['acces' => 'ADMIN'],
    'type_equipement' => ['acces' => 'ADMIN'],

    // --- Réservé aux utilisateurs connectés (à affiner selon tes règles métier) ---
    'activite'             => ['acces' => 'connecte'],
    'equipe'               => ['acces' => 'connecte'],
    'interaction_adherent' => ['acces' => 'connecte'],
    'paiement'             => ['acces' => 'connecte'],
    'planning'             => ['acces' => 'connecte'],
    'qr_code'          => ['acces' => 'connecte'],
    'qr_code'              => ['acces' => 'connecte'],
    'reservation'          => ['acces' => 'connecte'],
    'salle'                => ['acces' => 'connecte'],
];

// Actions par défaut, pour les contrôleurs qui ne déclarent pas de constante ACTIONS.
// À terme, déclare ACTIONS dans chaque contrôleur puis réduis cette liste à ['index'].
const ACTIONS_AUTORISEES = ['index', 'espace', 'connexion', 'inscription', 'deconnexion'];

/**
 * Affiche un message d'erreur simple et arrête le script.
 * Le détail technique va dans le journal du serveur, jamais à l'écran.
 */
function afficherErreur(int $code, string $titre, string $message, string $detail = ''): never
{
    if ($detail !== '') {
        error_log('[routeur] ' . $detail);
    }
    http_response_code($code);
    echo '<h1>' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '</h1>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

/** Retourne [module, action] déduits de l'URL. */
function lireRoute(): array
{
    $chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($chemin)) {
        afficherErreur(400, 'Adresse invalide', 'L\'adresse demandée est invalide.');
    }

    $base = baseUrl();
    if ($base !== '' && str_starts_with($chemin, $base . '/')) {
        $chemin = substr($chemin, strlen($base));
    } elseif ($base !== '' && $chemin === $base) {
        $chemin = '/';
    }
    $chemin = rawurldecode($chemin);

    $route = $_GET['route'] ?? '';
    if (is_string($route) && $route !== '') {
        $chemin = '/' . ltrim($route, '/');
    }
    $chemin = preg_replace('#^/index\.php(?=/|$)#', '', $chemin) ?? '';

    $segments = explode('/', trim($chemin, '/'));
    $module = $segments[0] !== '' ? $segments[0] : 'accueil';
    $action = basename($segments[1] ?? '', '.php');
    if ($action === '') {
        $action = 'index';
    }

    return [$module, $action];
}

/** Règle d'accès d'une action : celle de l'action si elle existe, sinon celle du module. */
function regleAcces(string $module, string $action): string|array
{
    return ROUTES[$module]['actions'][$action] ?? ROUTES[$module]['acces'];
}

/** Layout par défaut d'un module. */
function layoutParDefaut(string $module): string
{
    $route = ROUTES[$module];

    return $route['layout'] ?? ($route['acces'] === 'public' ? 'public' : 'application');
}

/** Applique la règle d'accès : laisse passer, redirige vers la connexion, ou refuse selon le rôle. */
function verifierAcces(string|array $regle): void
{
    if ($regle === 'public') {
        return;
    }

    if (!connecte()) {
        flash('erreur', 'Connectez-vous pour accéder à cette page.');
        redirige('utilisateur/connexion');
        exit;
    }

    if ($regle === 'connecte') {
        return;
    }

    // Un rôle ('ADMIN') ou une liste de rôles (['EMPLOYE', 'ADMIN']) : un seul suffit.
    $roles = (array) $regle;
    foreach ($roles as $role) {
        if (a_role($role)) {
            return;
        }
    }

    exige_role($roles[0]); // aucun rôle ne correspond : refus géré comme avant
}

/** Charge et instancie le contrôleur d'un module. */
function chargerControleur(string $module, string $dossier): object
{
    $camel = str_replace(' ', '', ucwords(str_replace('_', ' ', $module)));
    $classes = array_unique([$camel . 'Controller', ucfirst($module) . 'Controller']);

    // Noms de fichiers possibles : AbonnementController.php, ou <module>.php (ex. planning/controller/planning.php)
    foreach (array_merge($classes, [$module]) as $nomFichier) {
        $fichier = $dossier . '/controller/' . $nomFichier . '.php';
        if (!is_file($fichier)) {
            continue;
        }
        require_once $fichier;
        foreach ($classes as $classe) {
            if (class_exists($classe)) {
                return new $classe();
            }
        }
    }
    afficherErreur(
        500,
        'Erreur',
        'Une erreur est survenue. Réessayez plus tard.',
        'Aucun contrôleur valide pour le module « ' . $module . ' ».'
    );
}

/** Actions accessibles par URL : ACTIONS_URL ou ACTIONS (constante publique du contrôleur), sinon la liste par défaut. */
function actionsAutorisees(object $controleur): array
{
    foreach (['ACTIONS_URL', 'ACTIONS'] as $nom) {
        $constante = $controleur::class . '::' . $nom;
        if (defined($constante)) {
            return constant($constante);
        }
    }

    return ACTIONS_AUTORISEES;
}

/**
 * Retourne le fichier de vue correspondant à l'action.
 * Un contrôleur peut imposer sa vue en retournant la clé 'vue' (ex. 'public').
 */
function trouverVue(string $dossier, string $action, ?string $vue = null): string
{
    if ($vue !== null && $vue !== '') {
        $noms = [basename($vue, '.php')];
    } else {
        $noms = $action === 'index' ? ['index', 'catalogue', 'public', 'contact'] : [$action];
    }

    foreach ($noms as $nom) {
        $fichier = $dossier . '/view/' . $nom . '.php';
        if (is_file($fichier)) {
            return $fichier;
        }
    }
    afficherErreur(
        500,
        'Erreur',
        'Une erreur est survenue. Réessayez plus tard.',
        'Aucune vue pour l\'action « ' . $action . ' » dans ' . $dossier . '/view.'
    );
}

/**
 * Affiche la vue (avec ou sans layout) dans une portée isolée :
 * les données du contrôleur deviennent des variables, sans écraser celles du routeur.
 *
 * Layouts :
 *  - 'application' : espace connecté (topbar + sidebar + footer)
 *  - 'site'        : pages publiques (site_head + footer), la vue ne contient que son contenu
 *  - autre         : la vue gère elle-même son habillage (anciennes vues publiques)
 */
function afficherPage(string $__vue, array $__donnees, string $__layout): void
{
    extract($__donnees, EXTR_SKIP);

    if ($__layout === 'application') {
        require_once __DIR__ . '/../layout/topbar.php';
        require $__vue;
        require_once __DIR__ . '/../layout/sidebar.php';
        require_once __DIR__ . '/../layout/footer.php';
        return;
    }

    if ($__layout === 'site') {
        require __DIR__ . '/../layout/site_head.php';
        require $__vue;
        require __DIR__ . '/../layout/footer.php';
        return;
    }

    require $__vue;
}

// 1. Route : module + action déduits de l'URL
[$module, $action] = lireRoute();

if (!isset(ROUTES[$module])) {
    afficherErreur(404, 'Page introuvable', 'La page demandée n\'existe pas.', 'Module inconnu : ' . $module);
}

// 2. Contrôle d'accès, avant toute exécution de code métier
verifierAcces(regleAcces($module, $action));

// 3. Contrôleur : il prépare les données
$dossier = __DIR__ . '/../' . $module;
$controleur = chargerControleur($module, $dossier);

if (!in_array($action, actionsAutorisees($controleur), true) || !is_callable([$controleur, $action])) {
    afficherErreur(404, 'Page introuvable', 'La page demandée n\'existe pas.', 'Action refusée : ' . $module . '/' . $action);
}

$donnees = $controleur->$action();
$donnees = is_array($donnees) ? $donnees : [];

// 4. Vue : elle affiche uniquement les données reçues
$fichierVue = trouverVue($dossier, $action, is_string($donnees['vue'] ?? null) ? $donnees['vue'] : null);

$donnees['titrePage'] = $donnees['titrePage'] ?? $module;
$donnees['pageActive'] = $donnees['pageActive'] ?? $module;
$donnees += ['controller' => $controleur]; // compatibilité avec les vues qui utilisent $controller
$layout = $donnees['layout'] ?? layoutParDefaut($module);

afficherPage($fichierVue, $donnees, $layout);

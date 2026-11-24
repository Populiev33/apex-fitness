<?php

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../../avis/controller/AvisController.php';
require_once __DIR__ . '/../../utilisateur/controller/UtilisateurController.php';
require_once __DIR__ . '/../../activite/model/ActiviteCatalogue.php';
require_once __DIR__ . '/../../salle/model/SalleAdmin.php';
require_once __DIR__ . '/../../employe/service/SchemaInventaireEquipement.php';
require_once __DIR__ . '/../../planning/controller/planning.php';

/** Espace administrateur (tableau de bord à onglets), partagé avec les employés. */
final class AdminController
{
    public const ACTIONS = ['index', 'espace', 'facture'];

    /**
     * Onglets accessibles à un employé. Tous les autres sont réservés aux administrateurs.
     * Les noms doivent correspondre aux valeurs de $onglet définies dans AdminRequest.php.
     */
    private const ONGLETS_EMPLOYE = [
        'accueil', 'planning', 'reservations', 'adherents',
        'tickets', 'questions', 'messages',
    ];

    public function __construct()
    {
        // Le routeur applique déjà la règle d'accès (ROUTES). Ce contrôle est une seconde
        // barrière : exige_role('EMPLOYE') laisse passer les employés ET les administrateurs.
        exige_role('EMPLOYE');
    }

    public function index(): array
    {
        return $this->tableauDeBord();
    }

    /** Page d'arrivée après la connexion (/admin/espace). */
    public function espace(): array
    {
        return $this->tableauDeBord();
    }

    /** Télécharge la facture d’un paiement depuis l’espace administrateur. */
    public function facture(): never
    {
        $paymentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$paymentId || $paymentId < 1) {
            http_response_code(400);
            exit('Identifiant de facture invalide.');
        }

        $query = db()->prepare('SELECT NumUsers FROM paiement WHERE Numpaiement = ?');
        $query->execute([$paymentId]);
        $memberId = $query->fetchColumn();
        if (!$memberId) {
            http_response_code(404);
            exit('Facture introuvable.');
        }

        require_once __DIR__ . '/../../adherent/controller/AdherentController.php';
        (new AdherentController())->telechargerFacture((int) $memberId, (int) $paymentId);
    }

    /** Prépare toutes les données du tableau de bord pour la vue. */
    private function tableauDeBord(): array
    {
        $dossier = __DIR__;

        require $dossier . '/AdminRequest.php';      // définit $onglet, $vue...

        // Un employé n'accède qu'à ses onglets : contrôle fait AVANT de traiter un POST
        // ou de charger des données, pour qu'un onglet interdit ne soit jamais exécuté.
        if (!a_role('ADMIN') && !in_array($onglet ?? '', self::ONGLETS_EMPLOYE, true)) {
            error_log('[admin] onglet refusé pour un employé : ' . var_export($onglet ?? null, true));
            // Redirection hors de l'espace filtré : jamais vers admin/espace, sinon boucle infinie.
            flash('erreur', 'Cette page est réservée aux administrateurs.');
            redirige('accueil');
            exit;
        }

        require $dossier . '/AdminSetup.php';        // tables, colonnes, $fonctions
        require $dossier . '/AdminPostActions.php';  // traite les POST (redirige)
        require $dossier . '/AdminData.php';         // données de chaque onglet
        require $dossier . '/AdminNavigation.php';   // $sidebarItems, $topbar...

        $vars = get_defined_vars();
        unset($vars['dossier']);

        return $vars + [
            'titrePage'  => 'Administration',
            'pageActive' => 'admin',
            'layout'     => 'autonome',
        ];
    }
}

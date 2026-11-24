<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/employe.php';

/** Relie les routes CRUD des employés à leur modèle métier et prépare l'espace employé. */
final class EmployeController extends ControleurGenerique
{
    /** Actions accessibles par URL (/employe/espace.php, /employe/profil/index.php). */
    public const ACTIONS = ['index', 'espace', 'profil'];

    public function __construct()
    {
        parent::__construct(Employe::class);
    }

    /**
     * Page « Mon profil » de l'employé de maintenance et ses actions JSON
     * (?action=profile, ?action=profile_photo).
     * Le contrôleur du profil écrit lui-même la page ou la réponse JSON :
     * le routeur ne doit donc rendre ni vue ni layout (d'où le exit).
     */
    public function profil(): never
{
    require_once __DIR__ . '/../model/EmployeProfil.php';

    $numUsers = (int) ($_SESSION['user']['NumUsers'] ?? 0);    $modele   = new EmployeProfil(db());
    $action = (string) ($_GET['action'] ?? '');
    if ($action === 'profile' || $action === 'profile_photo') {
        $this->actionJsonProfil($modele, $action, $numUsers);
    }

    // Envoi classique du formulaire : permet aussi l’enregistrement sans JavaScript.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verifie_csrf();
            $modele->modifier($numUsers, $_POST);
            flash('succes', 'Tes informations ont été enregistrées.');
        } catch (InvalidArgumentException $exception) {
            flash('erreur', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log('Modification du profil employé impossible : ' . $exception->getMessage());
            flash('erreur', 'Les informations n’ont pas pu être enregistrées. Réessaie.');
        }
        redirige('employe/profil/index.php');
    }

    $this->afficherProfil($modele, $numUsers);
}

private function actionJsonProfil(EmployeProfil $modele, string $action, int $numUsers): never
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new InvalidArgumentException('Méthode non autorisée.');
        }
        verifie_csrf();

        if ($action === 'profile') {
            $donnees = ['profile' => $modele->modifier($numUsers, $_POST)];
        } else {
            if (!isset($_FILES['photo'])) {
                throw new InvalidArgumentException('Aucun fichier reçu.');
            }
            $photo = $modele->changerPhoto($numUsers, $_FILES['photo']);
            $donnees = ['photo' => $photo, 'photo_url' => url($photo)];
        }

        echo json_encode(['success' => true] + $donnees, JSON_UNESCAPED_UNICODE);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (OutOfBoundsException $e) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

private function afficherProfil(EmployeProfil $modele, int $numUsers): never
{
    $ligne = $modele->profil($numUsers);
    if ($ligne === null) {
        http_response_code(404);
        exit('Profil introuvable.');
    }

    $employe = $ligne;
    $user = [
        'nom'           => $ligne['nom'],
        'prenom'        => $ligne['prenom'],
        'email'         => $ligne['email'],
        'telephone'     => (string) ($ligne['telephone'] ?? ''),
        'avatar'        => (string) ($ligne['photo_profil'] ?? ''),
        'nom_commune'   => (string) ($ligne['nom_commune'] ?? ''),
        'code_postal'   => (string) ($ligne['code_postal'] ?? ''),
        'membre_depuis' => $ligne['date_embauche'] ? dateFr($ligne['date_embauche']) : '',
    ];
    $commune = [
        'id_commune'  => $ligne['id_commune'],
        'nom_commune' => $ligne['nom_commune'],
        'code_postal' => $ligne['code_postal'],
    ];
    $activites = $modele->activiteMaintenance($numUsers);
    $urlApi    = url('employe/profil/index.php');
    $jetonCsrf = csrf();
    $flashMessages = flashs();

    // Le profil partage la navigation de l’espace employé : chaque fonction
    // affiche ses propres entrées, mais garde le même contrat de variables.
    $maintenance = est_technicien_maintenance();
    $accueil = stripos((string) $ligne['fonction'], 'accueil') !== false;
    $onglet = 'profil';
    $employeUser = ['prenomUsers' => $ligne['prenom'], 'nomUsers' => $ligne['nom']];
    require __DIR__ . '/data/EmployeNavigationData.php';
    $topbarTitle = 'Mon profil';
    $topbarContext = 'Espace employé';

    require __DIR__ . '/../view/profil.php';
    exit;
}
    public function espace(): array
    {
        require_once __DIR__ . '/../../message_contact/controller/MessageController.php';
        require_once __DIR__ . '/../../planning/controller/planning.php';
        require_once __DIR__ . '/../service/SchemaInventaireEquipement.php';
        require_once __DIR__ . '/../../utilisateur/model/utilisateur.php';

        require __DIR__ . '/EmployeRequest.php';
        require __DIR__ . '/EmployePostActions.php';
        require __DIR__ . '/EmployeData.php';
        if ($maintenance && $onglet === 'materiel') {
            require __DIR__ . '/data/EmployeEquipmentViewData.php';
        }
        if ($onglet === 'maintenance' || ($accueil && $onglet === 'suivi-tickets')) {
            require __DIR__ . '/data/EmployeTicketViewData.php';
        }

        // Onglet « creneaux » : mêmes données que le planning de l'administration
        if ($onglet === 'creneaux') {
            $controleurPlanning = new PlanningController();
            $planningDonneesAdmin = $controleurPlanning->donneesAdmin((string) ($_GET['semaine'] ?? ''));

            $vuePlanning = (string) ($_GET['vue'] ?? '');
            $planningVue = in_array($vuePlanning, ['ajouter', 'modifier'], true) ? 'formulaire' : 'grille';

            // Créneau à modifier : on le retrouve parmi ceux de la semaine affichée
            $creneauAModifier = null;
            if ($vuePlanning === 'modifier') {
                $idModifier = (int) ($_GET['modifier'] ?? 0);
                foreach ($planningDonneesAdmin['creneaux_semaine'] as $creneauSemaine) {
                    if ((int) $creneauSemaine['Numactivite'] === $idModifier) {
                        $creneauAModifier = $creneauSemaine;
                        break;
                    }
                }
            }
        }

        // Toutes les variables préparées par ces fichiers partent vers la vue.
        // 'layout' => 'page' : les vues espace-* ont déjà leur propre en-tête et pied de page.
        return get_defined_vars() + ['layout' => 'page'];
    }
}

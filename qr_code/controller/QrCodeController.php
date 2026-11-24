<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../model/qr_code.php';

/**
 * CONTRÔLEUR — module qr_code. Ni SQL, ni règle métier, ni mise en forme :
 * il lit la requête, interroge les modèles (GenerateurQrCode, Abonnement) et choisit la vue.
 *
 *  index.php?action=image        : l'adhérent affiche le QR de sa carte (PNG)
 *  index.php?action=verification : page de contrôle de l'accueil
 *  index.php?action=verifier     : contrôle AJAX d'une valeur scannée (JSON)
 */
final class QrCodeController
{
    private GenerateurQrCode $modele;

    public function __construct()
    {
        $this->modele = new GenerateurQrCode();
    }

    public function index(): void
    {
        $this->executer();
    }

    public function executer(): void
    {
        match ($_GET['action'] ?? 'verification') {
            'image'    => $this->image(),
            'verifier' => $this->verifier(),
            default    => $this->verification(),
        };
    }

    /* ------------------------------- Actions ------------------------------- */

    /** GET : QR de la carte de l'adhérent connecté (l'abonnement est contrôlé à l'accueil). */
    private function image(): void
    {
        exige_role('ADHERENT');

        $valeur = $this->modele->valeurCarte(db(), (int) utilisateur()['NumUsers']);
        if ($valeur === null) {
            http_response_code(404);
            return;
        }

        $png = (string) $this->rendre('image', ['valeur' => $valeur]);
        header('Content-Type: image/png');
        header('Cache-Control: private, no-store');
        header('Content-Length: ' . strlen($png));
        echo $png;
        exit;
    }

    /** GET : page de contrôle (accueil). */
    private function verification(): void
    {
        exige_role('EMPLOYE');

        $this->page('verification', ['title' => 'Contrôle d\'accès · ATHLETIX']);
    }

    /** POST (AJAX) : contrôle une valeur scannée. */
    private function verifier(): void
    {
        exige_role('EMPLOYE');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            profil_json(['ok' => false, 'message' => 'Méthode non autorisée.'], 405);
            return;
        }
        if (!isset($_POST['csrf']) || !hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) $_POST['csrf'])) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'CSRF invalide.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $valeur = trim((string) ($_POST['valeur'] ?? ''));
        if ($valeur === '' || strlen($valeur) > 150) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->resultat('INVALIDE'), JSON_UNESCAPED_UNICODE);
            return;
        }

        $res = $this->modele->controler(db(), $valeur);
        $statut = $res['statut'];
        $formule = null;

        // Un QR valide ne suffit pas : l'abonnement doit l'être aussi.
        if ($statut === 'VALIDE') {
            $formule = $this->abonnementActif((int) $res['numUsers']);
            if ($formule === null) {
                $statut = 'ABONNEMENT_EXPIRE';
            }
        }

        journalise(
            $statut === 'VALIDE' ? 'QR_VALIDE' : 'QR_REFUSE',
            'Statut ' . $statut . ($res['numUsers'] !== null ? ', adhérent ' . $res['numUsers'] : '')
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->resultat($statut, $res['membre'], $formule), JSON_UNESCAPED_UNICODE);
    }

    /* ------------------------ Collaboration avec Abonnement ------------------------ */

    /**
     * Libellé de la formule si l'abonnement courant est actif, sinon null.
     * Seul endroit qui dépend du modèle Abonnement : à adapter ici si ses méthodes diffèrent.
     */
    private function abonnementActif(int $numUsers): ?string
    {
        $abonnement = (new Abonnement())->courantPourAdherent($numUsers);

        return Abonnement::estActif($abonnement)
            ? (string) ($abonnement['libelle_abo'] ?? 'Adhérent')
            : null;
    }

    /* ------------------------------ Rendu des vues ------------------------------ */

    /** Exécute view/<vue>.php avec $data ; renvoie son `return`, sinon ce qu'elle a affiché. */
    private function rendre(string $vue, array $data): mixed
    {
        $fichier = __DIR__ . '/../view/' . $vue . '.php';

        $executer = static function (string $__fichier, array $__data): mixed {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                $retour = require $__fichier;
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            $sortie = (string) ob_get_clean();
            return $retour === 1 ? $sortie : $retour;
        };

        return $executer($fichier, $data);
    }

    /** Page HTML : la vue est insérée dans view/layout.php. */
    private function page(string $vue, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        $content = (string) $this->rendre($vue, $data);
        echo (string) $this->rendre('layout', $data + ['content' => $content]);
    }

    /** Données JSON du contrôle (vue resultat.php). */
    private function resultat(string $statut, ?array $membre = null, ?string $formule = null): array
    {
        return $this->rendre('resultat', compact('statut', 'membre', 'formule'));
    }
}
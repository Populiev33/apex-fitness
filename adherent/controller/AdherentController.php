<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/model/AdherentEspace.php';
require_once dirname(__DIR__) . '/service/StockagePhotoProfil.php';
require_once dirname(__DIR__, 2) . '/admin/controller/ControleurGenerique.php';
require_once dirname(__DIR__) . '/model/adherent.php';
require_once dirname(__DIR__, 2) . '/abonnement/controller/AbonnementController.php';
require_once dirname(__DIR__, 2) . '/abonnement/model/ContratAbonnement.php';
require_once dirname(__DIR__, 2) . '/paiement/controller/PaiementController.php';

final class AdherentController extends ControleurGenerique
{
    /** Actions accessibles par URL (index.php?route=adherent/<action>). */
    public const ACTIONS = [
        'espace', 'planning', 'reservations', 'abonnement', 'profil', 'avis', 'messagerie',
        'offres', 'paiement', 'api', 'facturePdf', 'notification_api',
    ];

    /** Sous-actions JSON du planning (paramètre ?action= de /adherent/api). */
    private const ACTIONS_API = [
        'events', 'my', 'reservation_history', 'waitlist_join', 'waitlist_leave',
        'next', 'book', 'cancel', 'profile', 'profile_photo', 'messages', 'unread_messages',
    ];

    /** Clé de session du contrat accepté avant le paiement. */
    private const CLE_CONTRAT = 'abonnement_contrat_accepte';

    public function __construct(private ?AdherentEspace $space = null)
    {
        parent::__construct(Adherent::class);
        $this->space ??= new AdherentEspace();
    }

    /** Page d'arrivée après la connexion (/adherent/espace). */
    public function espace(): array
    {
        return $this->donneesPage('Mon espace', 'espace');
    }

    public function planning(): array
    {
        return $this->donneesPage('Planning', 'planning');
    }

    public function reservations(): array
    {
        return $this->donneesPage('Réservations', 'reservations');
    }

    public function abonnement(): array
    {
        return $this->donneesProfil((int) utilisateur()['NumUsers']);
    }

    /** Page « Mon profil » : identité, carte d'adhérent (QR) et résumé de l'abonnement. */
    public function profil(): array
    {
        exige_role('ADHERENT');

        return $this->donneesPage('Mon profil', 'profil')
            + $this->donneesProfil((int) utilisateur()['NumUsers']);
    }

    public function avis(): array
    {
        return $this->donneesPage('Mon avis', 'avis');
    }

    public function messagerie(): array
    {
        return $this->donneesPage('Messagerie', 'messagerie');
    }

    /** API JSON du planning (/adherent/api?action=events, book, cancel...). */
    public function api(): never
    {
        exige_role('ADHERENT');

        require_once dirname(__DIR__) . '/includes/planning/data.php';
        require_once dirname(__DIR__, 2) . '/planning/controller/planning.php';

        header('Content-Type: application/json; charset=utf-8');

        // Variables attendues par les fichiers api/actions/*.php
        $id = (int) utilisateur()['NumUsers'];
        $space = $this->space;
        $adherentController = $this;
        $planning = new PlanningController();
        $input = json_decode(file_get_contents('php://input') ?: '[]', true);
        if (!is_array($input)) {
            $input = [];
        }
        $action = (string) ($_GET['action'] ?? $input['action'] ?? '');

        require_once dirname(__DIR__) . '/api_helpers.php';

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $token = (string) ($input['csrf'] ?? $_POST['csrf'] ?? '');
                if ($token === '' || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $token)) {
                    apiReply(['error' => 'Session expirée. Rechargez la page.'], 419);
                }
            }

            if (!in_array($action, self::ACTIONS_API, true)) {
                apiReply(['error' => 'Action inconnue.'], 404);
            }

            require dirname(__DIR__) . '/api/actions/' . $action . '.php';
        } catch (InvalidArgumentException | RuntimeException $exception) {
            apiReply(['error' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            error_log('[Adherent API] ' . $exception->getMessage());
            apiReply(['error' => 'Une erreur est survenue. Réessayez.'], 500);
        }

        exit;
    }

    /** Endpoint JSON de la cloche de notifications. */
    public function notification_api(): never
    {
        require __DIR__ . '/../notification_api.php';
    }

    /** Étapes 1 et 2 : choix de la formule, puis informations et contrat (/adherent/offres). */
    public function offres(): array
    {
        exige_role('ADHERENT');

        $abonnements = new AbonnementController();
        $offres = $abonnements->getAll();
        $memberId = (int) utilisateur()['NumUsers'];
        $aboCourant = $abonnements->get($memberId);
        $memberProfile = $this->space->profil($memberId);
        $activeSubscription = $this->space->abonnement($memberId);

        $requestedStep = $_GET['step'] ?? 'selection';
        $selectedOffer = $this->trouverOffreParId($offres, $_GET['plan'] ?? null);
        $subscriptionError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'accept_contract') {
            verifie_csrf();
            $postedOffer = $this->trouverOffreParId($offres, $_POST['plan'] ?? null);

            if ($postedOffer === null) {
                $subscriptionError = 'Cette formule n’est plus disponible. Choisissez une formule disponible.';
            } elseif (($_POST['accept_contract'] ?? null) !== '1') {
                $subscriptionError = 'Veuillez accepter le règlement intérieur et le contrat pour continuer.';
                $selectedOffer = $postedOffer;
                $requestedStep = 'information';
            } else {
                $changeRequested = $activeSubscription !== null && $aboCourant !== (int) $postedOffer['Numabo'];
                $contractContent = ContratAbonnement::contenu($postedOffer, $changeRequested);
                $_SESSION[self::CLE_CONTRAT] = [
                    'user_id'     => $memberId,
                    'plan_id'     => (int) $postedOffer['Numabo'],
                    'version'     => ContratAbonnement::VERSION,
                    'content'     => $contractContent,
                    'hash'        => hash('sha256', $contractContent),
                    'accepted_at' => time(),
                    'change'      => $changeRequested,
                ];
                redirige('adherent/paiement');
            }
        }

        $isInformationStep = $requestedStep === 'information' && $selectedOffer !== null;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $requestedStep === 'information' && !$isInformationStep) {
            flash('erreur', 'La formule demandée n’est plus disponible. Veuillez en choisir une autre.');
            redirige('adherent/offres');
        }

        $selectedPlanIsChange = $selectedOffer !== null
            && $activeSubscription !== null
            && $aboCourant !== (int) $selectedOffer['Numabo'];
        $selectedContract = $selectedOffer !== null
            ? ContratAbonnement::contenu($selectedOffer, $selectedPlanIsChange)
            : '';

        // Lien « Choisir cette formule » de chaque offre (étape 2).
        $urlsChoixFormule = [];
        foreach ($offres as $offre) {
            $numero = (int) $offre['Numabo'];
            $urlsChoixFormule[$numero] = $this->lienEspaceAdherent('offres', ['step' => 'information', 'plan' => $numero]);
        }

        return [
            'titrePage'                   => 'Offres',
            'pageActive'                  => 'abonnement',
            'layout'                      => 'aucun',
            'activeNav'                   => 'abonnement',
            'catalogueDansEspaceAdherent' => true,
            'offres'                      => $offres,
            'aboCourant'                  => $aboCourant,
            'memberProfile'               => $memberProfile,
            'selectedOffer'               => $selectedOffer,
            'selectedContract'            => $selectedContract,
            'isInformationStep'           => $isInformationStep,
            'subscriptionError'           => $subscriptionError,
            'reglementSalle'              => ContratAbonnement::reglementSalle(),
            'urlsChoixFormule'            => $urlsChoixFormule,
            'offresRetour'                => url('adherent/abonnement'),
            'offerFlashes'                => flashs(),
        ];
    }

    /** Étape 3 : paiement de la formule (/adherent/paiement). */
    public function paiement(): array
    {
        exige_role('ADHERENT');

        $paiements = new PaiementController();
        $memberId = (int) utilisateur()['NumUsers'];
        $contrat = $_SESSION[self::CLE_CONTRAT] ?? null;
        $planId = is_array($contrat) ? filter_var($contrat['plan_id'] ?? null, FILTER_VALIDATE_INT) : false;

        $offreSelectionnee = null;
        if (is_int($planId)) {
            foreach ($paiements->offres() as $offre) {
                if ((int) $offre['Numabo'] === $planId) {
                    $offreSelectionnee = $offre;
                    break;
                }
            }
        }

        $contratValide = is_array($contrat)
            && (int) ($contrat['user_id'] ?? 0) === $memberId
            && is_int($planId)
            && (int) ($contrat['plan_id'] ?? 0) === $planId
            && (int) ($contrat['accepted_at'] ?? 0) >= time() - 1800
            && (int) ($contrat['accepted_at'] ?? 0) <= time()
            && $offreSelectionnee !== null;
        if ($contratValide) {
            $contratActuel = ContratAbonnement::contenu($offreSelectionnee, !empty($contrat['change']));
            $contratValide = ($contrat['version'] ?? null) === ContratAbonnement::VERSION
                && is_string($contrat['content'] ?? null)
                && hash_equals($contratActuel, $contrat['content'])
                && is_string($contrat['hash'] ?? null)
                && hash_equals(hash('sha256', $contratActuel), $contrat['hash']);
        }
        if (!$contratValide) {
            unset($_SESSION[self::CLE_CONTRAT]);
            flash('erreur', 'Veuillez choisir une formule et accepter le règlement intérieur avant de poursuivre.');
            if (is_int($planId) && $offreSelectionnee !== null) {
                header('Location: ' . $this->lienEspaceAdherent('offres', ['step' => 'information', 'plan' => $planId]));
                exit;
            }
            redirige('adherent/offres');
        }

        $erreur = null;
        $changement = !empty($contrat['change']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifie_csrf();
            $planPoste = filter_var($_POST['abonnement'] ?? null, FILTER_VALIDATE_INT);
            if ($planPoste !== $planId) {
                $erreur = 'La formule sélectionnée ne correspond pas au contrat accepté.';
            } else {
                try {
                    $montant = $paiements->payer(
                        $memberId,
                        $planId,
                        (string) ($_POST['moyen'] ?? ''),
                        $changement,
                        (string) $contrat['version'],
                        (string) $contrat['content'],
                        (string) $contrat['hash'],
                        (int) $contrat['accepted_at']
                    );
                    unset($_SESSION[self::CLE_CONTRAT]);
                    journalise('PAIEMENT_ABONNEMENT', 'NumUsers=' . $memberId);
                    flash('succes', $changement
                        ? 'Votre changement est bien enregistré. Votre ancien abonnement se termine fin de mois et le nouveau commencera au début du mois prochain.'
                        : 'Paiement de ' . euros((float) $montant) . ' enregistré. Votre abonnement est actif immédiatement.');
                    redirige('adherent/abonnement');
                } catch (Throwable $exception) {
                    $erreur = $exception->getMessage();
                }
            }
        }

        $prix = match ($offreSelectionnee['type_abo']) {
            'BASIC' => (float) $offreSelectionnee['prix_annuel'],
            'PLUS'  => (float) $offreSelectionnee['prix_mensuel_plus'],
            default => (float) $offreSelectionnee['prix_mensuel_reduit'],
        };

        return [
            'titrePage'             => 'Paiement',
            'pageActive'            => 'abonnement',
            'layout'                => 'aucun',
            'activeNav'             => 'abonnement',
            'erreur'                => $erreur,
            'offreSelectionnee'     => $offreSelectionnee,
            'prix'                  => $prix,
            'periode'               => $offreSelectionnee['type_abo'] === 'BASIC' ? '/ an' : '/ mois',
            'moyenSelectionne'      => is_string($_POST['moyen'] ?? null) ? $_POST['moyen'] : 'CB',
            'versionContrat'        => (string) $contrat['version'],
            'urlRetourInformations' => $this->lienEspaceAdherent('offres', ['step' => 'information', 'plan' => $planId]),
        ];
    }

    /**
     * Téléchargement du PDF d'une facture (/adherent/facturePdf?id=...).
     * Action sans paramètre : le routeur l'appelle directement.
     * L'adhérent connecté ne peut télécharger que ses propres factures.
     */
    public function facturePdf(): never
    {
        exige_role('ADHERENT');

        $paymentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$paymentId || $paymentId < 1) {
            http_response_code(400);
            exit('Identifiant de facture invalide.');
        }

        $this->telechargerFacture((int) utilisateur()['NumUsers'], $paymentId);
        exit;
    }

    /** Données communes aux pages de l'espace adhérent. */
    private function donneesPage(string $titre, string $pageActive): array
    {
        return [
            'titrePage'  => $titre,
            'pageActive' => $pageActive,
            'layout'     => 'aucun',
        ];
    }

    /**
     * Données de la vue « Mon profil » : $user, $abonnement et $commune.
     * Le QR de la carte est servi par le module qr_code (il crée la carte au premier affichage).
     */
    private function donneesProfil(int $memberId): array
    {
        $profile = $this->space->profil($memberId);
        $subscription = $this->space->abonnement($memberId);

        $historique = array_map(static fn(array $paiement): array => [
            'id'         => (int) $paiement['Numpaiement'],
            'raw_date'   => $paiement['date_paiement'],
            'date'       => dateFr($paiement['date_paiement']),
            'prestation' => $paiement['libelle_abo'],
            'montant'    => number_format((float) $paiement['montant'], 2, ',', ' ') . ' €',
            'statut'     => 'Payé',
        ], $this->paiements($memberId));

        $moisTotal = 0;
        $moisRestants = 0;
        if ($subscription && $subscription['date_fin']) {
            $debut = new DateTimeImmutable($subscription['date_debut']);
            $fin = new DateTimeImmutable($subscription['date_fin']);
            $aujourdhui = new DateTimeImmutable('today');
            $moisTotal = max(1, (int) ceil(($fin->getTimestamp() - $debut->getTimestamp()) / (30 * 86400)));
            $moisRestants = max(0, (int) ceil(($fin->getTimestamp() - $aujourdhui->getTimestamp()) / (30 * 86400)));
        }

        $user = [
            'prenom'          => $profile['prenom'] ?? '',
            'nom'             => $profile['nom'] ?? '',
            'email'           => $profile['email'] ?? '',
            'telephone'       => $profile['telephone'] ?? '',
            'id_commune'      => $profile['id_commune'] ?? '',
            'nom_commune'     => $profile['nom_commune'] ?? '',
            'code_postal'     => $profile['code_postal'] ?? '',
            'adresse_postale' => $profile['adresse_postale'] ?? '',
            'ville'           => $profile['nom_commune'] ?? '',
            'date_naissance'  => $profile['date_naissance'] ?? '',
            'sexe'            => $profile['sexe'] ?? '',
            'membre_depuis'   => dateFr($profile['inscrit_le'] ?? $profile['cree_le'] ?? null),
            'num_membre'      => $profile['matricule'] ?? '',
            'avatar'          => $profile['photo_profil'] ?? '',
            'qr_image'        => url('qr_code/index.php?action=image'),
            'initiale'        => mb_strtoupper(mb_substr($profile['prenom'] ?? '?', 0, 1)) . '.',
        ];

        return [
            'layout' => 'aucun',
            'titrePage' => 'Mon abonnement',
            'pageActive' => 'abonnement',
            'user'       => $user,
            'commune'    => [
                'id_commune'  => $user['id_commune'],
                'nom_commune' => $user['nom_commune'],
                'code_postal' => $user['code_postal'],
            ],
            'abonnement' => [
                'formule'            => $subscription['libelle_abo'] ?? 'Aucun abonnement actif',
                'statut'             => $subscription ? 'Actif' : ($this->space->aHistoriqueAbonnement($memberId) ? 'Inactif' : 'En attente'),
                'duree'              => $subscription ? ucfirst($subscription['periodicite']) : '—',
                'date_debut'         => isset($subscription['date_debut']) ? dateFr($subscription['date_debut']) : '—',
                'prochaine_echeance' => isset($subscription['date_fin']) ? dateFr($subscription['date_fin']) : '—',
                'renouvellement'     => '',
                'mois_restants'      => $moisRestants,
                'mois_total'         => $moisTotal,
                'acces'              => [],
                'historique'         => $historique,
            ],
        ];
    }

    /** Retourne l'offre dont le numéro correspond à $id, ou null. */
    private function trouverOffreParId(array $offres, mixed $id): ?array
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) {
            return null;
        }
        foreach ($offres as $offre) {
            if ((int) $offre['Numabo'] === $id) {
                return $offre;
            }
        }

        return null;
    }

    /** Construit le lien d'une action adhérent, avec ses paramètres éventuels. */
    private function lienEspaceAdherent(string $action, array $parametres = []): string
    {
        $lien = url('adherent/' . $action);
        if ($parametres === []) {
            return $lien;
        }

        return $lien . (str_contains($lien, '?') ? '&' : '?') . http_build_query($parametres);
    }

    public function historiqueReservations(int $memberId): array
    {
        return $this->space->historiqueReservations($memberId);
    }

    public function paiements(int $memberId): array
    {
        return $this->space->paiements($memberId);
    }

    public function facture(int $memberId, int $paymentId): ?array
    {
        return $this->space->paiement($memberId, $paymentId);
    }

    public function messagesNonLus(int $memberId): int
    {
        return $this->space->messagesNonLus($memberId);
    }

    public function changerPhotoProfil(int $memberId, array $upload): string
    {
        $storage = new StockagePhotoProfil();
        $newPath = $storage->store($upload);
        try {
            $oldPath = $this->space->updateProfilePhoto($memberId, $newPath);
            $storage->remove($oldPath);
            return $newPath;
        } catch (Throwable $exception) {
            $storage->remove($newPath);
            throw $exception;
        }
    }

    public function telechargerFacture(int $memberId, int $paymentId): void
    {
        $invoice = $this->facture($memberId, $paymentId);
        if (!$invoice) {
            http_response_code(404);
            exit('Facture introuvable.');
        }
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $safe = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $facture = $invoice;
        ob_start();
        require dirname(__DIR__, 2) . '/facture/templates/facture.php';
        $html = (string) ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $dompdf->stream('facture-Athletix.pdf', ['Attachment' => true]);
        exit;
    }
}

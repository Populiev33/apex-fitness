<?php

declare(strict_types=1);

require_once __DIR__ . '/../model/ContactMessage.php';

/** Affiche le formulaire de contact et enregistre les messages reçus. */
final class MessageContactController
{
    /** Actions accessibles par URL (lues par le routeur public/index.php). */
    public const ACTIONS = ['index'];

    /** Coordonnées du club affichées à côté du formulaire : [icône, libellé, lignes]. */
    private const INFOS = [
        ['map-pin', 'Adresse', ['12 Rue des Sports', '75000 Paris']],
        ['phone', 'Téléphone', ['+33 1 23 45 67 89', 'Lun - Ven : 8h - 20h']],
        ['mail', 'Email', ['contact@athletix.fr', 'Réponse sous 24h']],
        ['clock', 'Horaires', ['Lun - Ven : 6h - 22h', 'Sam - Dim : 8h - 20h']],
    ];

    /** Sujets proposés dans la liste déroulante. */
    private const SUJETS = ['Abonnements', 'Activités & cours', 'Coaching personnalisé', 'Laisser un avis', 'Autre demande'];

    public function __construct(private ?ContactMessage $messages = null)
    {
        $this->messages ??= new ContactMessage();
    }

    /**
     * Point d'entrée appelé par le routeur.
     * Prépare le formulaire, traite l'envoi et retourne les données de la vue.
     */
    public function index(): array
    {
        $form = $this->valeursInitiales();
        $erreurs = [];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verifie_csrf();

            $form = [
                'nom' => trim((string) ($_POST['nom'] ?? '')),
                'mail' => trim((string) ($_POST['mail'] ?? '')),
                'sujet' => trim((string) ($_POST['sujet'] ?? '')),
                'message' => trim((string) ($_POST['message'] ?? '')),
            ];

            $erreurs = $this->enregistrer($form);
            if (!$erreurs) {
                flash('succes', 'Votre message a bien été envoyé.');
                redirige('message_contact');
            }
        }

        return [
            'titrePage' => 'Contact',
            'pageActive' => 'contact',
            'layout' => 'site',
            'bodyClass' => 'page-contact',
            'infos' => self::INFOS,
            'sujets' => self::SUJETS,
            'donnees' => $form,
            'erreurs' => $erreurs,
        ];
    }

    /** Valeurs par défaut du formulaire (pré-remplies si l'utilisateur est connecté). */
    private function valeursInitiales(): array
    {
        $connecte = connecte();
        $user = $connecte ? utilisateur() : [];

        return [
            'nom' => $connecte ? trim(($user['prenomUsers'] ?? '') . ' ' . ($user['nomUsers'] ?? '')) : '',
            'mail' => $connecte ? (string) ($user['mailUsers'] ?? '') : '',
            'sujet' => (string) ($_GET['sujet'] ?? ''),
            'message' => '',
        ];
    }

    /** Valide puis enregistre le message. Retourne les erreurs (tableau vide si succès). */
    private function enregistrer(array $data): array
    {
        $errors = [];
        if (mb_strlen($data['nom']) < 2) {
            $errors['nom'] = 'Indiquez votre nom.';
        }
        if (!filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) {
            $errors['mail'] = 'Adresse e-mail invalide.';
        }
        if (mb_strlen($data['sujet']) < 3) {
            $errors['sujet'] = 'Résumez votre demande.';
        }
        if (mb_strlen($data['message']) < 15) {
            $errors['message'] = '15 caractères minimum.';
        }
        if ($errors) {
            return $errors;
        }

        $messageId = $this->messages->create($data);
        journalise('MESSAGE_CONTACT', 'Nummessage=' . $messageId);

        return [];
    }
}
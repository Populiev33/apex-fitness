<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/FaqDomain.php';

/** Coordonne la consultation, la création et la gestion des questions. */
final class FaqController
{
    /** Actions accessibles par URL (lues par le routeur public/index.php). */
    public const ACTIONS = ['index', 'public'];

    private Faq $faq;

    public function __construct(?Faq $faq = null)
    {
        $this->faq = $faq ?? new Faq();
    }

    /**
     * Point d'entrée appelé par le routeur.
     * Prépare toutes les données de view/public.php et traite l'envoi d'une question.
     */
    public function index(): array
    {
        $erreur = null;
        $peutPoserQuestion = connecte() && a_role('ADHERENT') && !a_role('EMPLOYE');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verifie_csrf();
            if (!$peutPoserQuestion) {
                http_response_code(403);
                $erreur = 'Seuls les adhérents non employés peuvent poser une question.';
            } else {
                try {
                    $this->creer(
                        (int) utilisateur()['NumUsers'],
                        (string) ($_POST['question'] ?? '')
                    );
                    flash('succes', 'Merci, votre question a bien été envoyée.');
                    redirige('faq');
                } catch (Throwable $exception) {
                    $erreur = $exception->getMessage();
                }
            }
        }

        $questions = $this->faq->publiees();

        return [
            'titrePage' => 'FAQ',
            'pageActive' => 'faq',
            'erreur' => $erreur,
            'peutPoserQuestion' => $peutPoserQuestion,
            'questions' => $questions,
            'groupes' => $this->grouperParCategorie($questions),
        ];
    }

    /** Alias : permet aussi l'ancienne URL faq/public.php. */
    public function public(): array
    {
        return $this->index();
    }

    public function questions(): array
    {
        return $this->faq->toutes();
    }

    public function creer(int $numUtilisateur, string $question): int
    {
        $question = trim($question);
        if (mb_strlen($question) < 10) {
            throw new InvalidArgumentException('Formulez une question de 10 caractères minimum.');
        }
        return $this->faq->creer($numUtilisateur, $question);
    }

    public function repondre(int $numInteraction, string $reponse, string $categorie): void
    {
        $this->faq->repondre($numInteraction, trim($reponse), trim($categorie));
    }

    public function supprimer(int $numInteraction): void
    {
        $this->faq->supprimer($numInteraction);
    }

    public function grouperParCategorie(array $questions): array
    {
        $groupes = [];
        foreach ($questions as $question) {
            $groupes[$question['categorie']][] = $question;
        }

        return $groupes;
    }

    public function traiterCrud(): array
    {
        return (new ControleurGenerique(Faq::class))->traiter();
    }
}
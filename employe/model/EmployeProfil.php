<?php

declare(strict_types=1);

require_once __DIR__ . '/../../adherent/service/StockagePhotoProfil.php';

/**
 * MODÈLE — profil d'un employé : accès aux données et règles de validation.
 * Aucune sortie HTTP ni HTML.
 */
final class EmployeProfil
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Identité, poste et commune d'un employé, ou null s'il n'existe pas. */
    public function profil(int $numUsers): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT u.nomUsers AS nom, u.prenomUsers AS prenom,
                    u.mailUsers AS email, u.telUsers AS telephone,
                    e.matriculeemp AS matricule, e.Date_embaucheemp AS date_embauche,
                    f.libellefonc AS fonction, e.id_commune, e.photo_profil,
                    c.nom_commune, c.code_postal
               FROM utilisateur u
               JOIN employe e ON e.NumUsers = u.NumUsers
               JOIN fonction f ON f.Numfonc = e.Numfonc
          LEFT JOIN commune c ON c.id_commune = e.id_commune
              WHERE u.NumUsers = ?'
        );
        $requete->execute([$numUsers]);
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $ligne;
    }

    /** Les 5 dernières interventions de maintenance de l'employé. */
    public function activiteMaintenance(int $numUsers): array
    {
        $requete = $this->pdo->prepare(
            'SELECT i.Nummaint, i.date_intervention, i.commentaire, s.libelle_statut,
                    eq.nomequip
               FROM intervention i
               JOIN demande_maintenance d ON d.Nummaint = i.Nummaint
               JOIN equipement eq ON eq.Numequip = d.Numequip
               JOIN statut s ON s.Numstatut = i.Numstatut
              WHERE i.NumUsers = ?
           ORDER BY i.date_intervention DESC
              LIMIT 5'
        );
        $requete->execute([$numUsers]);

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Chemin de la photo actuelle ('' si aucune), ou null si l'employé n'existe pas. */
    public function photoProfil(int $numUsers): ?string
    {
        $requete = $this->pdo->prepare('SELECT photo_profil FROM employe WHERE NumUsers = ?');
        $requete->execute([$numUsers]);
        $valeur = $requete->fetchColumn();

        return $valeur === false ? null : (string) $valeur;
    }

    /**
     * Enregistre la nouvelle photo, supprime l'ancienne et renvoie le nouveau chemin.
     *
     * @throws OutOfBoundsException     si l'employé n'existe pas
     * @throws InvalidArgumentException si le fichier est refusé
     */
    public function changerPhoto(int $numUsers, array $fichier): string
    {
        $ancienne = $this->photoProfil($numUsers);
        if ($ancienne === null) {
            throw new OutOfBoundsException('Profil employé introuvable.');
        }

        $stockage = new StockagePhotoProfil();
        $nouvelle = $stockage->store($fichier);
        try {
            $this->pdo->prepare('UPDATE employe SET photo_profil = ? WHERE NumUsers = ?')
                ->execute([$nouvelle, $numUsers]);
        } catch (Throwable $exception) {
            $stockage->remove($nouvelle);
            throw $exception;
        }
        $stockage->remove($ancienne !== '' ? $ancienne : null);

        return $nouvelle;
    }

    /**
     * Valide la saisie puis met à jour l'utilisateur et sa commune.
     *
     * @return array{nom: string, prenom: string, email: string, telephone: string, id_commune: int, nom_commune: string, code_postal: string}
     * @throws InvalidArgumentException si la saisie est invalide
     */
    public function modifier(int $numUsers, array $saisie): array
    {
        $nom = $saisie['nom'] ?? null;
        $prenom = $saisie['prenom'] ?? null;
        $email = $saisie['email'] ?? null;
        $telephone = $saisie['telephone'] ?? '';
        $communeId = filter_var($saisie['id_commune'] ?? null, FILTER_VALIDATE_INT);

        if (!is_string($nom) || !is_string($prenom) || !is_string($email) || !is_string($telephone)) {
            throw new InvalidArgumentException('Données de profil invalides.');
        }
        $nom = trim($nom);
        $prenom = trim($prenom);
        $email = trim($email);
        $telephone = trim($telephone);

        if ($nom === '' || mb_strlen($nom) > 60 || $prenom === '' || mb_strlen($prenom) > 60) {
            throw new InvalidArgumentException('Le nom et le prénom sont obligatoires (60 caractères maximum).');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }
        if ($telephone !== '') {
            if (!preg_match('/^[0-9 ]+$/', $telephone)) {
                throw new InvalidArgumentException('Saisissez uniquement les chiffres du numéro de téléphone.');
            }
            $chiffres = str_replace(' ', '', $telephone);
            if (strlen($chiffres) < 6 || strlen($chiffres) > 10) {
                throw new InvalidArgumentException('Le numéro doit contenir entre 6 et 10 chiffres.');
            }
            $telephone = trim(chunk_split($chiffres, 2, ' '));
        }
        if (!$communeId || $communeId < 1) {
            throw new InvalidArgumentException('Sélectionnez une commune dans la liste proposée.');
        }

        $commune = $this->commune($communeId);
        if ($commune === null) {
            throw new InvalidArgumentException('La commune sélectionnée est introuvable.');
        }
        if ($this->emailUtilise($email, $numUsers)) {
            throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'UPDATE utilisateur
                    SET nomUsers = ?, prenomUsers = ?, mailUsers = ?, telUsers = ?
                  WHERE NumUsers = ?'
            )->execute([$nom, $prenom, $email, $telephone !== '' ? $telephone : null, $numUsers]);
            $this->pdo->prepare('UPDATE employe SET id_commune = ? WHERE NumUsers = ?')
                ->execute([$communeId, $numUsers]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

        return [
            'nom'         => $nom,
            'prenom'      => $prenom,
            'email'       => $email,
            'telephone'   => $telephone,
            'id_commune'  => $communeId,
            'nom_commune' => $commune['nom_commune'],
            'code_postal' => $commune['code_postal'],
        ];
    }

    private function commune(int $communeId): ?array
    {
        $requete = $this->pdo->prepare('SELECT nom_commune, code_postal FROM commune WHERE id_commune = ?');
        $requete->execute([$communeId]);
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $ligne;
    }

    private function emailUtilise(string $email, int $numUsers): bool
    {
        $requete = $this->pdo->prepare('SELECT 1 FROM utilisateur WHERE mailUsers = ? AND NumUsers <> ?');
        $requete->execute([$email, $numUsers]);

        return (bool) $requete->fetchColumn();
    }
}
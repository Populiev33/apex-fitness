<?php

declare(strict_types=1);

final class StockagePhotoProfil
{
    private const MAX_SIZE = 5 * 1024 * 1024;
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function store(array $upload): string
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException($this->uploadError((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        $temporaryPath = (string) ($upload['tmp_name'] ?? '');
        $size = (int) ($upload['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_SIZE) {
            throw new InvalidArgumentException('La photo ne doit pas dépasser 5 Mo.');
        }
        if (!is_uploaded_file($temporaryPath)) {
            throw new InvalidArgumentException('Le fichier transmis n’est pas une photo valide.');
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($temporaryPath);
        $imageInfo = @getimagesize($temporaryPath);
        if (!isset(self::MIME_EXTENSIONS[$mimeType]) || !$imageInfo || $imageInfo['mime'] !== $mimeType) {
            throw new InvalidArgumentException('Format accepté : JPG, PNG ou WEBP.');
        }
        if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000 || $imageInfo[0] * $imageInfo[1] > 20_000_000) {
            throw new InvalidArgumentException('La résolution de la photo est trop élevée.');
        }

        $relativeDirectory = 'assets/uploads/profiles';
        $targetDirectory = dirname(__DIR__, 2) . '/public/' . $relativeDirectory;
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException('Le dossier des photos de profil ne peut pas être créé.');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mimeType];
        if (!move_uploaded_file($temporaryPath, $targetDirectory . '/' . $fileName)) {
            throw new RuntimeException('La photo n’a pas pu être enregistrée.');
        }

        return $relativeDirectory . '/' . $fileName;
    }

    public function remove(?string $relativePath): void
    {
        if (!$relativePath || !preg_match('~^assets/uploads/profiles/[a-f0-9]{32}\.(?:jpg|png|webp)$~', $relativePath)) {
            return;
        }
        $filePath = dirname(__DIR__, 2) . '/public/' . $relativePath;
        if (is_file($filePath)) unlink($filePath);
    }

    private function uploadError(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La photo dépasse la limite de taille configurée par le serveur.',
            UPLOAD_ERR_PARTIAL => 'Le téléversement de la photo est incomplet.',
            UPLOAD_ERR_NO_FILE => 'Choisissez une photo avant de continuer.',
            default => 'Le téléversement de la photo a échoué.',
        };
    }

    /** Limite sûre pour tenir compte des réglages PHP du serveur et du plafond applicatif. */
    public static function limiteUpload(): int
    {
        $octets = static function (string|false $valeur): int {
            if ($valeur === false || !preg_match('/^\s*(\d+)\s*([KMG]?)\s*$/i', $valeur, $parties)) {
                return 0;
            }
            $multiplicateur = match (strtoupper($parties[2] ?? '')) {
                'G' => 1_073_741_824,
                'M' => 1_048_576,
                'K' => 1_024,
                default => 1,
            };
            return (int) $parties[1] * $multiplicateur;
        };

        $limite = self::MAX_SIZE;
        $uploadMax = $octets(ini_get('upload_max_filesize'));
        $postMax = $octets(ini_get('post_max_size'));
        if ($uploadMax > 0) $limite = min($limite, $uploadMax - 65_536);
        if ($postMax > 0) $limite = min($limite, $postMax - 131_072);

        return max(65_536, $limite);
    }
}

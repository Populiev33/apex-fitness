<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/commune.php';

final class CommuneController
{
    public const ACTIONS_URL = ['recherche'];

    public function recherche(): array
    {
        require __DIR__ . '/recherche.php';
        exit;
    }
}

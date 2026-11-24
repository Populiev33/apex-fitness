<?php

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../../paiement/controller/PaiementController.php';

exige_role('ADHERENT');
(new PaiementController())->afficher();
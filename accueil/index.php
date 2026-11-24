<?php
require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/AccueilController.php';

$activites = (new AccueilController())->getAll();
$titrePage = 'Accueil';
$pageActive = 'accueil';

   ini_set('display_errors', '1');
   error_reporting(E_ALL);
   try {
       $test = (new AccueilController())->index();
       var_dump(count($test));
   } catch (Throwable $e) {
       die('ERREUR : ' . $e->getMessage());
   }
require __DIR__ . '/view/index.php';
<?php
// Layout « site » : en-tête commun des pages publiques.
// Variables fournies par le routeur : $titrePage, $pageActive, et optionnellement $bodyClass.
$bodyClass = $bodyClass ?? 'page-' . ($pageActive ?? 'public');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titrePage) ?> · ATHLETIX</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/pages/athletix_public.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/site_nav.css?v=4') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip" href="#contenu">Aller au contenu</a>
<?php require_once __DIR__ . '/site_nav.php'; ?>
<?php $fl = flashs(); if ($fl): ?>
<div class="wrap flashs">
<?php foreach ($fl as $f): ?>
<p class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
<main id="contenu">
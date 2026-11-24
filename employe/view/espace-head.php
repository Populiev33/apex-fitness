<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titrePage) ?> - <?= e(APP_NOM) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages/admin.css')) ?>">
  <?php if ($accueil && $onglet === 'tableau'): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/employe-dashboard.css')) ?>">
  <?php endif; ?>
  <?php if ($onglet === 'adherents'): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/employe-members.css')) ?>">
  <?php endif; ?>
  <?php if ($accueil && $onglet === 'messages'): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/employe-messages.css')) ?>">
  <?php endif; ?>
  <?php if ($onglet === 'creneaux'): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/planning.css')) ?>">
  <?php endif; ?>
  <?php if ($onglet === 'creer-ticket'): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/employe-create-ticket.css')) ?>">
  <?php endif; ?>
</head>
<body class="admin-page employee-page<?= $maintenance ? ' maintenance-page' : '' ?><?= $accueil && $onglet === 'tableau' ? ' accueil-dashboard-page' : '' ?><?= $onglet === 'adherents' ? ' employee-members-page' : '' ?><?= $accueil && $onglet === 'messages' ? ' employee-messages-page' : '' ?><?= $accueil && $onglet === 'reservations' ? ' admin-reservations-page' : '' ?>">
<div class="admin-shell">
  <?php require __DIR__ . '/../../layout/sidebar.php'; ?>
  <div class="admin-main">
    <?php require __DIR__ . '/../../layout/topbar.php'; ?>
    <?php if ($flashMessages): ?>
      <div class="admin-flashes">
        <?php foreach ($flashMessages as $message): ?>
          <p class="flash flash-<?= e($message['type']) ?>"><?= e($message['message']) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <main class="admin-content">

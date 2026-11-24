<?php
/**
 * VUE — « Mon profil » de l'employé de maintenance. HTML uniquement : aucune requête, aucune logique métier.
 *
 * @var array  $employe    profil complet (fonction, matricule…)
 * @var array  $user       identité préparée pour l'affichage
 * @var array  $commune    commune (utilisée par commune/view/champ.php)
 * @var array  $activites  dernières interventions
 * @var string $urlApi     URL des actions JSON du contrôleur
 * @var string $jetonCsrf  jeton CSRF des appels JSON
 * + variables de layout/sidebar.php et layout/topbar.php
 */

// Onglets de la sidebar du technicien de maintenance (mêmes onglets que l'espace employé).
// Si le contrôleur a déjà défini $sidebarItems, on ne l'écrase pas.
if (empty($sidebarItems)) {
    $sidebarItems = [
        ['id' => 'tableau',     'href' => url('employe/espace.php?onglet=tableau'),     'icon' => 'home', 'label' => 'Tableau de bord'],
        ['id' => 'maintenance', 'href' => url('employe/espace.php?onglet=maintenance'), 'icon' => 'tool', 'label' => 'Maintenance'],
        ['id' => 'materiel',    'href' => url('employe/espace.php?onglet=materiel'),    'icon' => 'bag',  'label' => 'Matériel'],
        ['id' => 'profil',      'href' => url('employe/profil/index.php'),              'icon' => 'user', 'label' => 'Mon profil'],
    ];
}
$sidebarActive = 'profil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon profil – ATHLETIX</title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/commune/commune.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/pages/admin.css')) ?>">
<script src="<?= e(url('assets/js/commune/commune.js')) ?>" defer></script>
<script src="<?= e(url('assets/js/profil-photo.js')) ?>" defer></script>
<script src="<?= e(url('assets/js/employe/EmployeProfil.js')) ?>" defer></script>
</head>
<body class="admin-page employee-page maintenance-page maintenance-profile-page">

<div class="admin-shell">
    <?php require __DIR__ . '/../../layout/sidebar.php'; ?>

    <div class="admin-main">
        <?php require __DIR__ . '/../../layout/topbar.php'; ?>

        <main class="admin-content maintenance-profile-content"
              data-profil-employe
              data-api="<?= e($urlApi) ?>"
              data-csrf="<?= e($jetonCsrf) ?>"
              data-max-photo-bytes="<?= StockagePhotoProfil::limiteUpload() ?>">

            <?php if (!empty($flashMessages)): ?>
                <div class="admin-flashes" role="status">
                    <?php foreach ($flashMessages as $message): ?>
                        <p class="flash flash-<?= e($message['type']) ?>"><?= e($message['message']) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <header class="employee-profile-heading">
                <h2>Mon profil</h2>
                <p>Gérez vos informations et vos préférences.</p>
            </header>

            <div class="employee-profile-grid">
                <section class="employee-profile-card employee-profile-identity" aria-label="Informations du compte">
                    <div class="employee-profile-avatar">
                        <img id="profileAvatar" src="<?= $user['avatar'] ? e(url($user['avatar'])) : '' ?>" alt="Photo de profil" <?= $user['avatar'] ? '' : 'hidden' ?>>
                        <span id="profileAvatarFallback" <?= $user['avatar'] ? 'hidden' : '' ?>><?= e(mb_strtoupper(mb_substr($user['prenom'] ?: '?', 0, 1) . mb_substr($user['nom'] ?: '', 0, 1))) ?></span>
                        <form id="photoForm" class="employee-photo-form" enctype="multipart/form-data">
                            <?= champ_csrf() ?>
                            <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/png,image/webp" hidden>
                            <label for="photoUpload" title="Modifier la photo de profil" aria-label="Modifier la photo de profil"><i class="fa-solid fa-camera" aria-hidden="true"></i></label>
                        </form>
                    </div>
                    <div class="employee-identity-details">
                        <h3 id="employeeProfileName"><?= e(trim($user['prenom'] . ' ' . $user['nom'])) ?></h3>
                        <span class="employee-role-badge"><i class="fa-solid fa-wrench" aria-hidden="true"></i> <?= e($employe['fonction']) ?></span>
                        <p><i class="fa-solid fa-envelope" aria-hidden="true"></i><span id="profileContactEmail"><?= e($user['email']) ?></span></p>
                        <p><i class="fa-solid fa-phone" aria-hidden="true"></i><span id="profileContactPhone"><?= e($user['telephone'] ?: 'Téléphone non renseigné') ?></span></p>
                        <p><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span id="employeeProfileLocation"><?= e(trim($user['nom_commune'] . ' ' . $user['code_postal']) ?: 'Lieu non renseigné') ?></span></p>
                    </div>
                </section>

                <section class="employee-profile-card employee-profile-information" aria-labelledby="employee-info-title">
                    <header class="employee-profile-section-heading">
                        <h3 id="employee-info-title"><i class="fa-solid fa-gear" aria-hidden="true"></i> Mes informations</h3>
                        <button class="employee-profile-save" type="submit" form="profileForm"><i class="fa-solid fa-check" aria-hidden="true"></i> Enregistrer les modifications</button>
                    </header>
                    <p class="employee-profile-readonly">Poste : <?= e($employe['fonction']) ?> · Matricule : <?= e($employe['matricule']) ?> · Employé depuis le <?= e($user['membre_depuis']) ?></p>
                    <form id="profileForm" class="employee-profile-form" method="post" action="<?= e(url('employe/profil/index.php')) ?>">
                        <?= champ_csrf() ?>
                        <div class="form-grid">
                            <?php foreach (['nom' => 'Nom', 'prenom' => 'Prénom', 'telephone' => 'Téléphone', 'email' => 'Email'] as $cle => $libelle): ?>
                                <label class="field">
                                    <span class="field-label"><?= e($libelle) ?></span>
                                    <input class="field-value"
                                           type="<?= $cle === 'email' ? 'email' : ($cle === 'telephone' ? 'tel' : 'text') ?>"
                                           name="<?= e($cle) ?>" value="<?= e($user[$cle]) ?>"
                                           <?= $cle !== 'telephone' ? 'required' : '' ?>
                                           <?php if ($cle === 'telephone'): ?>inputmode="numeric" autocomplete="tel-national" maxlength="14" placeholder="06 12 34 56 78"<?php endif; ?>>
                                </label>
                            <?php endforeach; ?>
                            <?php include __DIR__ . '/../../commune/view/champ.php'; ?>
                        </div>
                        <div class="employee-profile-form-actions">
                            <p>Tu peux mettre à jour tes coordonnées et ta commune ici.</p>
                        </div>
                    </form>
                    <p id="profileMessage" class="employee-profile-message" role="status" aria-live="polite"></p>
                </section>

                <section class="employee-profile-card employee-profile-tools" aria-labelledby="employee-tools-title">
                    <h3 id="employee-tools-title"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Mes outils et accès</h3>
                    <div class="employee-tool-links">
                        <a href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>"><i class="fa-solid fa-ticket" aria-hidden="true"></i><strong>Gérer mes tickets</strong><span>Voir et traiter les demandes</span><b aria-hidden="true">→</b></a>
                        <a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>"><i class="fa-solid fa-cube" aria-hidden="true"></i><strong>Gérer le matériel</strong><span>Consulter l’inventaire</span><b aria-hidden="true">→</b></a>
                    </div>
                </section>

                <section class="employee-profile-card employee-profile-activity" aria-labelledby="employee-activity-title">
                    <h3 id="employee-activity-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Mon activité récente</h3>
                    <?php if (!$activites): ?>
                        <p class="employee-profile-empty">Aucune intervention enregistrée pour le moment.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($activites as $activite): ?>
                                <li>
                                    <span class="employee-activity-dot" aria-hidden="true"></span>
                                    <span class="employee-activity-copy">
                                        <strong>Ticket #TKT-<?= (int) $activite['Nummaint'] ?> <?= e(mb_strtolower($activite['libelle_statut'])) ?> — <?= e($activite['nomequip']) ?></strong>
                                        <?php if (trim((string) $activite['commentaire']) !== ''): ?><small><?= e($activite['commentaire']) ?></small><?php endif; ?>
                                    </span>
                                    <time><?= e(dateFr($activite['date_intervention'], true)) ?></time>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>

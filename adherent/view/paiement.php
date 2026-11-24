<?php require_once dirname(__DIR__, 2) . '/public/includes/public_icons.php'; ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paiement – ATHLETIX</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/athletix_public.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/adherent/abonnement.css?v=20261101-4')) ?>">
    <style>
        .payment-main{min-width:0;min-height:100vh;width:calc(100% - 250px);margin-left:250px;padding:22px 36px 60px}
        .payment-main > .account-subscriptions{padding-top:18px}
        @media(max-width:860px){.payment-main{width:100%;margin-left:0;padding:18px 16px 48px}}
    </style>
</head>
<body class="member-subscription">
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="payment-main">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <div class="account-subscriptions">
            <nav class="subscription-steps progress-step-3" aria-label="Étapes de gestion de l’abonnement">
                <div class="subscription-step is-complete"><span>1</span><strong>Choix de la formule</strong></div>
                <div class="subscription-step is-complete"><span>2</span><strong>Informations</strong></div>
                <div class="subscription-step is-active" aria-current="step"><span>3</span><strong>Paiement</strong></div>
                <div class="subscription-step"><span>4</span><strong>Confirmation</strong></div>
            </nav>
            <section class="account-information-hero">
                <p class="eyebrow">Étape 3 sur 4</p>
                <h1>Règlement de votre formule</h1>
                <p>Votre règlement intérieur et votre contrat ont été acceptés. Choisissez un moyen de paiement pour terminer.</p>
            </section>
            <section class="account-information-content">
                <?php if ($erreur !== null): ?><p class="account-contract-error" role="alert"><?= e($erreur) ?></p><?php endif; ?>
                <div class="account-information-layout">
                    <section class="account-information-card payment-form-card">
                        <h2><?= publicIcon('lock', 'icon') ?> Moyen de paiement</h2>
                        <form method="post" class="account-payment-form">
                            <?= champ_csrf() ?>
                            <input type="hidden" name="abonnement" value="<?= (int) $offreSelectionnee['Numabo'] ?>">
                            <label for="moyen">Sélectionnez un moyen de paiement</label>
                            <select id="moyen" name="moyen" required>
                                <?php foreach (['CB' => 'Carte bancaire', 'Virement' => 'Virement', 'Prelevement' => 'Prélèvement', 'Especes' => 'Espèces à l’accueil'] as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= $moyenSelectionne === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="account-payment-note">Le paiement enregistré confirme l’activation de la formule selon les conditions acceptées.</p>
                            <div class="account-information-actions">
                                <a class="btn btn-outline" href="<?= e($urlRetourInformations) ?>">Retour aux informations</a>
                                <button class="btn btn-primary" type="submit">Confirmer le paiement <?= publicIcon('arrow-right', 'icon') ?></button>
                            </div>
                        </form>
                    </section>
                    <aside class="account-information-summary">
                        <h2><?= publicIcon('info', 'icon') ?> Récapitulatif</h2>
                        <p class="account-selected-plan-name"><?= e($offreSelectionnee['libelle_abo']) ?></p>
                        <p class="account-selected-plan-price"><?= euros($prix) ?> <?= e($periode) ?></p>
                        <p><?= e($offreSelectionnee['description_abo'] ?? '') ?></p>
                        <div class="account-summary-row">
                            <span><?= publicIcon('shield-check', 'icon') ?> Contrat</span>
                            <strong>Accepté · v<?= e($versionContrat) ?></strong>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
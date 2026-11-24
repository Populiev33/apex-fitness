<?php
$nombreAdherents = count($adherents);
$nouvelAdherent = (bool) array_filter(
    $flashMessages ?? [],
    static fn(array $message): bool => ($message['type'] ?? '') === 'erreur'
);
?>
<section class="employee-members" aria-labelledby="employee-members-title">
  <header class="employee-members-hero">
    <p class="employee-members-eyebrow">ESPACE EMPLOYÉ</p>
    <h2 id="employee-members-title">Gestion des <span>adhérents</span></h2>
    <p>Consultez les formules, les coordonnées et les statuts des membres.</p>
  </header>

  <section class="employee-members-stats" aria-label="Indicateurs des adhérents">
    <article><span class="member-stat-icon is-green" aria-hidden="true">♟</span><div><strong><?= (int) $statistiquesAdherents['actifs'] ?></strong><span>Adhérents actifs</span><small>Abonnement en cours</small></div></article>
    <article><span class="member-stat-icon is-green" aria-hidden="true">▦</span><div><strong><?= (int) $statistiquesAdherents['nouveaux'] ?></strong><span>Nouveaux ce mois-ci</span><small>Selon la date d’inscription</small></div></article>
    <article><span class="member-stat-icon is-orange" aria-hidden="true">★</span><div><strong><?= $statistiquesAdherents['note_moyenne'] !== null ? e(number_format($statistiquesAdherents['note_moyenne'], 1, ',', '')) . ' / 5' : '—' ?></strong><span>Satisfaction moyenne</span><small><?= (int) $statistiquesAdherents['nombre_avis'] ?> avis enregistrés</small></div></article>
    <article><span class="member-stat-icon is-green" aria-hidden="true">◷</span><div><strong><?= (int) $statistiquesAdherents['renouvellements'] ?></strong><span>À renouveler sous 30 jours</span><small>Abonnements en cours</small></div></article>
  </section>

  <section class="employee-members-toolbar" aria-label="Rechercher et filtrer les adhérents">
    <label class="member-search"><span aria-hidden="true">⌕</span><input id="member-search" type="search" placeholder="Rechercher un adhérent (nom, e-mail, téléphone…)" autocomplete="off"></label>
    <select id="member-status-filter" aria-label="Filtrer par statut">
      <option value="">Tous les statuts</option>
      <?php foreach ($statutsAdherent as $statut): ?><option value="<?= e($statut['code']) ?>"><?= e($statut['libelle']) ?></option><?php endforeach; ?>
    </select>
    <select id="member-plan-filter" aria-label="Filtrer par formule">
      <option value="">Toutes les formules</option>
      <?php foreach ($formulesAdherents as $formule): ?><option value="<?= e(mb_strtolower($formule)) ?>"><?= e($formule) ?></option><?php endforeach; ?>
      <option value="aucune">Aucune formule active</option>
    </select>
    <select id="member-city-filter" aria-label="Filtrer par commune">
      <option value="">Toutes les communes</option>
      <?php foreach ($communesAdherents as $commune): ?><option value="<?= e(mb_strtolower($commune['nom_commune'])) ?>"><?= e($commune['nom_commune']) ?></option><?php endforeach; ?>
      <?php if (array_filter($adherents, static fn(array $adherent): bool => empty($adherent['commune_affichee']))): ?><option value="non-renseignee">Commune non renseignée</option><?php endif; ?>
    </select>
    <?php if ($accueil): ?><button class="member-add-button" type="button" data-open-create-member><span aria-hidden="true">＋</span> Ajouter un adhérent</button><?php endif; ?>
  </section>

  <section class="employee-members-list" aria-labelledby="employee-members-list-title">
    <header class="employee-members-list-header">
      <div><h3 id="employee-members-list-title"><span aria-hidden="true">♟</span> Liste des adhérents</h3><p><strong id="member-total"><?= $nombreAdherents ?></strong> adhérent<?= $nombreAdherents > 1 ? 's' : '' ?> au total</p></div>
      <button type="button" class="member-export-button" id="member-export"><span aria-hidden="true">⇩</span> Exporter CSV</button>
    </header>
    <?php if (!$adherents): ?>
      <p class="member-empty">Aucun adhérent n’est enregistré pour le moment.</p>
    <?php else: ?>
      <div class="employee-members-table-wrap">
        <table class="employee-members-table">
          <thead><tr><th>Nom</th><th>Prénom</th><th>E-mail</th><th>Téléphone</th><th>Formule</th><th>Statut</th><th>Date d’inscription</th><th>Actions</th></tr></thead>
          <tbody id="employee-members-rows">
          <?php foreach ($adherents as $adherent):
            $nomComplet = trim($adherent['nomUsers'] . ' ' . $adherent['prenomUsers']);
            $formule = $adherent['formule_active'] ?: 'Aucune formule active';
            $dateInscription = date('d/m/Y', strtotime($adherent['Date_inscriptionadh']));
            $initiales = mb_strtoupper(mb_substr($adherent['prenomUsers'], 0, 1) . mb_substr($adherent['nomUsers'], 0, 1));
          ?>
            <tr data-member-row
                data-search="<?= e(mb_strtolower($nomComplet . ' ' . $adherent['mailUsers'] . ' ' . ($adherent['telUsers'] ?? '') . ' ' . $adherent['matriculeadh'] . ' ' . ($adherent['commune_affichee'] ?? ''))) ?>"
                data-status="<?= e($adherent['statut']->code()) ?>"
                data-plan="<?= e(mb_strtolower($adherent['formule_active'] ?: 'aucune')) ?>"
                data-city="<?= e(mb_strtolower($adherent['commune_affichee'] ?: 'non-renseignee')) ?>"
                data-name="<?= e($nomComplet) ?>"
                data-email="<?= e($adherent['mailUsers']) ?>"
                data-phone="<?= e($adherent['telUsers'] ?? '') ?>"
                data-member-number="<?= e($adherent['matriculeadh']) ?>"
                data-plan-label="<?= e($formule) ?>"
                data-status-label="<?= e($adherent['statut']->libelle()) ?>"
                data-registration="<?= e($dateInscription) ?>"
                data-address="<?= e(trim(($adherent['adresse_postale'] ?? '') . ', ' . ($adherent['commune_affichee'] ?? '') . ' ' . ($adherent['code_postal'] ?? ''), ', ')) ?>">
              <td><span class="member-name-cell"><span class="member-avatar"><?= e($initiales) ?></span><strong><?= e($adherent['nomUsers']) ?></strong></span></td>
              <td><?= e($adherent['prenomUsers']) ?></td>
              <td><a href="mailto:<?= e($adherent['mailUsers']) ?>"><?= e($adherent['mailUsers']) ?></a></td>
              <td><?= e($adherent['telUsers'] ?: '—') ?></td>
              <td><?= e($formule) ?></td>
              <td><span class="member-status <?= e($adherent['statut']->classeCss()) ?>"><i></i><?= e($adherent['statut']->libelle()) ?></span></td>
              <td><?= e($dateInscription) ?></td>
              <td><div class="member-row-actions"><button type="button" data-view-member aria-label="Voir la fiche de <?= e($nomComplet) ?>" title="Voir la fiche">◉</button><a href="<?= e(url('employe/espace.php?onglet=reservations')) ?>" aria-label="Gérer les réservations de <?= e($nomComplet) ?>" title="Réservations">▦</a></div></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <footer class="employee-members-pagination">
        <span id="member-pagination-label"></span>
        <div><button type="button" id="member-page-previous" aria-label="Page précédente">‹</button><span id="member-page-current"></span><button type="button" id="member-page-next" aria-label="Page suivante">›</button></div>
      </footer>
      <p class="member-no-results" id="member-no-results" hidden>Aucun adhérent ne correspond à ces filtres.</p>
    <?php endif; ?>
  </section>

  <dialog class="member-detail-dialog" id="member-detail-dialog" aria-labelledby="member-detail-title">
    <header><div><p class="employee-members-eyebrow">FICHE ADHÉRENT</p><h3 id="member-detail-title"></h3></div><button type="button" data-close-member aria-label="Fermer">×</button></header>
    <dl>
      <div><dt>Matricule</dt><dd data-member-number-value></dd></div>
      <div><dt>E-mail</dt><dd data-member-email-value></dd></div>
      <div><dt>Téléphone</dt><dd data-member-phone-value></dd></div>
      <div><dt>Formule en cours</dt><dd data-member-plan-value></dd></div>
      <div><dt>Statut</dt><dd data-member-status-value></dd></div>
      <div><dt>Date d’inscription</dt><dd data-member-registration-value></dd></div>
      <div class="member-detail-address"><dt>Adresse</dt><dd data-member-address-value></dd></div>
    </dl>
    <footer><button type="button" class="member-detail-close" data-close-member>Fermer</button></footer>
  </dialog>

  <?php if ($accueil): ?><dialog class="member-create-dialog" id="member-create-dialog" aria-labelledby="member-create-title">
    <header><div><p class="employee-members-eyebrow">NOUVEAU COMPTE</p><h3 id="member-create-title">Ajouter un adhérent</h3></div><button type="button" data-close-create aria-label="Fermer">×</button></header>
    <form method="post" class="member-create-form">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="creer_adherent_accueil">
      <label>Prénom<input name="prenom" required maxlength="60" autocomplete="given-name"></label>
      <label>Nom<input name="nom" required maxlength="60" autocomplete="family-name"></label>
      <label>E-mail<input type="email" name="mail" required maxlength="150" autocomplete="email"></label>
      <label>Téléphone<input type="tel" name="tel" maxlength="20" autocomplete="tel"></label>
      <label>Date de naissance<input name="date_naissance" placeholder="JJ-MM-AAAA" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" required></label>
      <label>Sexe<select name="sexe" required><option value="">Choisir</option><?php foreach (['Femme', 'Homme', 'Autre', 'Préfère ne pas répondre'] as $sexe): ?><option value="<?= e($sexe) ?>"><?= e($sexe) ?></option><?php endforeach; ?></select></label>
      <label class="member-create-wide">Adresse postale<input name="adresse_postale" maxlength="255" required autocomplete="street-address"></label>
      <label class="member-create-wide">Commune<select name="id_commune" required><option value="">Choisir une commune</option><?php foreach ($communesAdherents as $commune): ?><option value="<?= (int) $commune['id_commune'] ?>"><?= e($commune['nom_commune']) ?></option><?php endforeach; ?></select></label>
      <label class="member-create-wide">Mot de passe provisoire<input type="password" name="motdepasse" minlength="8" required autocomplete="new-password"><small>Au moins 8 caractères. Transmettez-le à l’adhérent de manière sécurisée.</small></label>
      <footer class="member-create-wide"><button type="submit" class="member-add-button">Créer le compte</button><button type="button" class="member-detail-close" data-close-create>Annuler</button></footer>
    </form>
  </dialog><?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const rows = Array.from(document.querySelectorAll('[data-member-row]'));
  if (!rows.length) {
    const createDialogWhenEmpty = document.getElementById('member-create-dialog');
    const openCreateWhenEmpty = document.querySelector('[data-open-create-member]');
    if (createDialogWhenEmpty && openCreateWhenEmpty) {
      openCreateWhenEmpty.addEventListener('click', function () { createDialogWhenEmpty.showModal(); });
      document.querySelectorAll('[data-close-create]').forEach(function (button) { button.addEventListener('click', function () { createDialogWhenEmpty.close(); }); });
      <?php if ($nouvelAdherent): ?>createDialogWhenEmpty.showModal();<?php endif; ?>
    }
    return;
  }

  const search = document.getElementById('member-search');
  const statusFilter = document.getElementById('member-status-filter');
  const planFilter = document.getElementById('member-plan-filter');
  const cityFilter = document.getElementById('member-city-filter');
  const pageSize = 8;
  let currentPage = 1;
  let filteredRows = rows;

  function afficherPage() {
    const terme = search.value.trim().toLocaleLowerCase('fr');
    filteredRows = rows.filter(function (row) {
      return row.dataset.search.includes(terme)
        && (!statusFilter.value || row.dataset.status === statusFilter.value)
        && (!planFilter.value || row.dataset.plan === planFilter.value)
        && (!cityFilter.value || row.dataset.city === cityFilter.value);
    });

    const nombrePages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
    currentPage = Math.min(currentPage, nombrePages);
    const debut = (currentPage - 1) * pageSize;
    const visibles = new Set(filteredRows.slice(debut, debut + pageSize));
    rows.forEach(function (row) { row.hidden = !visibles.has(row); });

    document.getElementById('member-no-results').hidden = filteredRows.length > 0;
    document.getElementById('member-pagination-label').textContent = filteredRows.length
      ? 'Affichage de ' + (debut + 1) + ' à ' + Math.min(debut + pageSize, filteredRows.length) + ' sur ' + filteredRows.length + ' adhérents'
      : '0 adhérent';
    document.getElementById('member-page-current').textContent = 'Page ' + currentPage + ' / ' + nombrePages;
    document.getElementById('member-page-previous').disabled = currentPage <= 1;
    document.getElementById('member-page-next').disabled = currentPage >= nombrePages;
  }

  [search, statusFilter, planFilter, cityFilter].forEach(function (control) {
    control.addEventListener('input', function () { currentPage = 1; afficherPage(); });
    control.addEventListener('change', function () { currentPage = 1; afficherPage(); });
  });
  document.getElementById('member-page-previous').addEventListener('click', function () { currentPage--; afficherPage(); });
  document.getElementById('member-page-next').addEventListener('click', function () { currentPage++; afficherPage(); });

  document.getElementById('member-export').addEventListener('click', function () {
    const entetes = ['Nom', 'E-mail', 'Téléphone', 'Formule', 'Statut', 'Date inscription', 'Matricule'];
    const lignes = filteredRows.map(function (row) {
      return [row.dataset.name, row.dataset.email, row.dataset.phone, row.dataset.planLabel, row.dataset.statusLabel, row.dataset.registration, row.dataset.memberNumber];
    });
    const csv = [entetes].concat(lignes).map(function (ligne) {
      return ligne.map(function (valeur) { return '"' + String(valeur || '').replaceAll('"', '""') + '"'; }).join(';');
    }).join('\r\n');
    const lien = document.createElement('a');
    lien.href = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' }));
    lien.download = 'adherents.csv';
    lien.click();
    URL.revokeObjectURL(lien.href);
  });

  const detailDialog = document.getElementById('member-detail-dialog');
  document.querySelectorAll('[data-view-member]').forEach(function (button) {
    button.addEventListener('click', function () {
      const row = button.closest('[data-member-row]');
      detailDialog.querySelector('#member-detail-title').textContent = row.dataset.name;
      detailDialog.querySelector('[data-member-number-value]').textContent = row.dataset.memberNumber;
      detailDialog.querySelector('[data-member-email-value]').textContent = row.dataset.email;
      detailDialog.querySelector('[data-member-phone-value]').textContent = row.dataset.phone || 'Non renseigné';
      detailDialog.querySelector('[data-member-plan-value]').textContent = row.dataset.planLabel;
      detailDialog.querySelector('[data-member-status-value]').textContent = row.dataset.statusLabel;
      detailDialog.querySelector('[data-member-registration-value]').textContent = row.dataset.registration;
      detailDialog.querySelector('[data-member-address-value]').textContent = row.dataset.address || 'Non renseignée';
      detailDialog.showModal();
    });
  });
  document.querySelectorAll('[data-close-member]').forEach(function (button) { button.addEventListener('click', function () { detailDialog.close(); }); });

  const createDialog = document.getElementById('member-create-dialog');
  const openCreate = document.querySelector('[data-open-create-member]');
  if (createDialog && openCreate) {
    openCreate.addEventListener('click', function () { createDialog.showModal(); });
    document.querySelectorAll('[data-close-create]').forEach(function (button) { button.addEventListener('click', function () { createDialog.close(); }); });
    <?php if ($nouvelAdherent): ?>createDialog.showModal();<?php endif; ?>
  }

  afficherPage();
});
</script>

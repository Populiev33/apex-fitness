<?php
$adherentEnEdition = $vueAdherent === 'modifier' && $adherentSelectionne;
$nombreAvisAdherents = (int) ($statistiquesAvisAdherents['nombre'] ?? 0);
$moyenneAvisAdherents = $statistiquesAvisAdherents['moyenne'] ?? null;
?>
<section class="adherents-page">
  <header class="adherents-intro">
    <div>
      <p class="adherents-breadcrumb">Administration <span>›</span> Adhérents</p>
      <h3>Gestion des adhérents</h3>
      <p>Gérez les membres de votre salle de sport.</p>
    </div>
    <label class="adherents-global-search"><span aria-hidden="true">⌕</span><input type="search" id="recherche-adherent-haut" placeholder="Rechercher un adhérent…" autocomplete="off"><kbd>⌘ K</kbd></label>
  </header>

  <div class="adherents-stats" aria-label="Statistiques des adhérents">
    <article><span class="adherents-stat-icon is-green" aria-hidden="true">♟</span><div><strong><?= (int) $statistiquesAdherents['actifs'] ?></strong><span>Adhérents actifs</span><small>sur <?= (int) $statistiquesAdherents['total'] ?> membres</small></div></article>
    <article><span class="adherents-stat-icon is-blue" aria-hidden="true">◷</span><div><strong><?= (int) $statistiquesAdherents['attente'] ?></strong><span>En attente</span><small>Sans abonnement actif</small></div></article>
    <article><span class="adherents-stat-icon is-green" aria-hidden="true">▦</span><div><strong><?= (int) $statistiquesAdherents['fin_mois'] ?></strong><span>Abonnements expirant</span><small>Ce mois-ci</small></div></article>
    <article><span class="adherents-stat-icon is-green" aria-hidden="true">★</span><div><strong><?= $moyenneAvisAdherents !== null ? e(number_format($moyenneAvisAdherents, 1, ',', '')) . '/5' : '—' ?></strong><span>Note moyenne</span><small><?= $nombreAvisAdherents ?> avis publiés</small></div></article>
  </div>

  <nav class="adherents-status-tabs" aria-label="Filtrer par statut">
    <button type="button" class="is-active" data-adherent-status="">Tous <span><?= (int) $statistiquesAdherents['total'] ?></span></button>
    <button type="button" data-adherent-status="actif">Actifs <span><?= (int) $statistiquesAdherents['actifs'] ?></span></button>
    <button type="button" data-adherent-status="attente">En attente <span><?= (int) $statistiquesAdherents['attente'] ?></span></button>
    <button type="button" data-adherent-status="inactif">Inactifs <span><?= (int) $statistiquesAdherents['inactifs'] ?></span></button>
  </nav>

  <div class="adherents-filter-bar">
    <label class="adherents-search"><span aria-hidden="true">⌕</span><input type="search" id="recherche-adherent" placeholder="Rechercher par nom, e-mail, téléphone…" autocomplete="off"></label>
    <select id="filtre-abonnement-adherent" aria-label="Filtrer par abonnement"><option value="">Tous les abonnements</option><?php foreach (array_unique(array_filter(array_column($adherents, 'abonnement'))) as $abonnementFiltre): ?><option value="<?= e(mb_strtolower(explode(' • ', $abonnementFiltre)[0])) ?>"><?= e(explode(' • ', $abonnementFiltre)[0]) ?></option><?php endforeach; ?></select>
    <select id="tri-adherents" aria-label="Trier les adhérents"><option value="nom-asc">Trier par : Nom (A → Z)</option><option value="nom-desc">Nom (Z → A)</option><option value="date-desc">Inscription récente</option><option value="date-asc">Inscription ancienne</option></select>
  </div>

  <div class="adherents-layout <?= ($adherentSelectionne || $modeCreationAdherent) ? 'has-detail' : 'is-full' ?>">
    <div class="adherents-list-wrap">
      <?php if (!$adherents): ?>
        <div class="vide">Aucun adhérent enregistré.</div>
      <?php else: ?>
        <div class="table-scroll adherents-table-scroll">
          <table class="tableau adherents-table" id="table-adherents">
            <thead><tr><th>Nom &amp; prénom</th><th>E-mail</th><th>Téléphone</th><th>Abonnement</th><th>Statut</th><th>Date d’inscription</th><th>Actions</th></tr></thead>
            <tbody id="lignes-adherents">
            <?php foreach ($adherents as $adherent):
              $nomComplet = trim($adherent['prenomUsers'] . ' ' . $adherent['nomUsers']);
              $selectionne = $adherentSelectionne && (int) $adherentSelectionne['NumUsers'] === (int) $adherent['NumUsers'];
            ?>
              <tr class="<?= $selectionne ? 'is-selected' : '' ?>" data-adherent-row data-detail-url="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherent['NumUsers'] . '&vue=voir')) ?>" data-search="<?= e(mb_strtolower($nomComplet . ' ' . $adherent['mailUsers'] . ' ' . ($adherent['telUsers'] ?? '') . ' ' . ($adherent['nom_commune'] ?? ''))) ?>" data-status="<?= e($adherent['statut_admin']) ?>" data-subscription="<?= e(mb_strtolower(explode(' • ', (string) ($adherent['abonnement'] ?? ''))[0])) ?>" data-name="<?= e(mb_strtolower($adherent['nomUsers'] . ' ' . $adherent['prenomUsers'])) ?>" data-id="<?= (int) $adherent['NumUsers'] ?>" data-date="<?= e($adherent['Date_inscriptionadh']) ?>">
                <td>
                  <a class="adherent-person" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherent['NumUsers'] . '&vue=voir')) ?>">
                    <?php if (!empty($adherent['photo_profil'])): ?>
                      <img src="<?= e(url($adherent['photo_profil'])) ?>" alt="">
                    <?php else: ?>
                      <span class="adherent-avatar"><?= e(mb_strtoupper(mb_substr($adherent['prenomUsers'], 0, 1) . mb_substr($adherent['nomUsers'], 0, 1))) ?></span>
                    <?php endif; ?>
                    <span><strong><?= e($nomComplet) ?></strong><small><?= e($adherent['mailUsers']) ?></small></span>
                  </a>
                </td>
                <td><?= e($adherent['mailUsers']) ?></td>
                <td><?= e($adherent['telUsers'] ?: 'Non renseigné') ?></td>
                <td><?= e(explode(' • ', (string) ($adherent['abonnement'] ?? ''))[0] ?: 'Aucun') ?></td>
                <td><span class="adherent-status status-<?= e($adherent['statut_admin']) ?>"><i></i><?= $adherent['statut_admin'] === 'actif' ? 'Actif' : ($adherent['statut_admin'] === 'attente' ? 'En attente' : 'Inactif') ?></span></td>
                <td><?= e(dateFr($adherent['Date_inscriptionadh'])) ?></td>
                <td>
                  <div class="adherents-actions">
                    <a class="adherent-icon-action" aria-label="Lire la fiche de <?= e($nomComplet) ?>" title="Lire" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherent['NumUsers'] . '&vue=voir')) ?>">◉</a>
                    <a class="adherent-icon-action" aria-label="Modifier <?= e($nomComplet) ?>" title="Modifier" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherent['NumUsers'] . '&vue=modifier')) ?>">✎</a>
                    <details class="adherent-row-menu"><summary aria-label="Autres actions" title="Autres actions">⋮</summary><form method="post">
                      <?= champ_csrf() ?>
                      <input type="hidden" name="action" value="supprimer">
                      <input type="hidden" name="utilisateur" value="<?= (int) $adherent['NumUsers'] ?>">
                      <input type="hidden" name="retour" value="adherents">
                      <button class="btn btn-sm btn-danger" type="submit" data-confirme="<?= !empty($adherent['est_employe']) ? 'Supprimer le rôle adhérent, ses réservations, abonnements et son historique financier ? Le compte employé et ses activités seront conservés.' : 'Supprimer définitivement cet adhérent, son compte et son historique de paiements ?' ?>">Supprimer</button>
                    </form></details>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <footer class="adherents-pagination"><span id="adherents-pagination-info"></span><div><button type="button" id="adherents-page-prev" aria-label="Page précédente">‹</button><span id="adherents-pages"></span><button type="button" id="adherents-page-next" aria-label="Page suivante">›</button></div></footer>
        <p class="adherents-empty-filter" id="adherents-filter-empty" hidden>Aucun adhérent ne correspond à ces filtres.</p>
      <?php endif; ?>
    </div>

    <?php if ($adherentSelectionne || $modeCreationAdherent): ?>
    <dialog class="adherent-detail adherent-detail-dialog<?= $adherentSelectionne ? ' has-profile' : '' ?>" id="adherent-detail-dialog" aria-labelledby="adherent-detail-title">
      <?php if ($modeCreationAdherent): ?>
        <div class="adherent-detail-heading">
          <div><p class="admin-eyebrow">NOUVEAU MEMBRE</p><h3 id="adherent-detail-title">Ajouter un adhérent</h3></div>
          <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=adherents')) ?>" aria-label="Fermer">×</a>
        </div>
        <form class="adherent-edit-form" method="post">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" value="creer_adherent_admin">
          <label>Prénom<input name="prenom" required maxlength="60" autocomplete="given-name"></label>
          <label>Nom<input name="nom" required maxlength="60" autocomplete="family-name"></label>
          <label>E-mail<input type="email" name="mail" required maxlength="150" autocomplete="email"></label>
          <label>Téléphone<input type="tel" name="tel" maxlength="20" autocomplete="tel"></label>
          <label>Date de naissance<input name="date_naissance" placeholder="JJ-MM-AAAA" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" required></label>
          <label>Sexe<select name="sexe" required><option value="">Choisir une option</option><?php foreach (['Femme', 'Homme', 'Autre', 'Préfère ne pas répondre'] as $sexe): ?><option value="<?= e($sexe) ?>"><?= e($sexe) ?></option><?php endforeach; ?></select></label>
          <label>Adresse postale<input name="adresse_postale" maxlength="255" required autocomplete="street-address"></label>
          <label>Commune<select name="id_commune" required><option value="">Choisir une commune</option><?php foreach ($communes as $commune): ?><option value="<?= (int) $commune['id_commune'] ?>"><?= e($commune['nom_commune'] . ' (' . $commune['code_postal'] . ')') ?></option><?php endforeach; ?></select></label>
          <label>Mot de passe<input type="password" name="motdepasse" minlength="8" required autocomplete="new-password"></label>
          <div class="adherent-detail-actions"><button class="btn btn-vert" type="submit">Créer l’adhérent</button><a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=adherents')) ?>">Annuler</a></div>
        </form>
      <?php elseif ($adherentEnEdition): ?>
        <?php $nomFiche = trim($adherentSelectionne['prenomUsers'] . ' ' . $adherentSelectionne['nomUsers']); ?>
        <header class="adherent-profile-hero" style="--adherent-cover:url('<?= e(url('assets/img/member-hero.jpg')) ?>')">
          <div class="adherent-profile-avatar"><?php if (!empty($adherentSelectionne['photo_profil'])): ?><img src="<?= e(url($adherentSelectionne['photo_profil'])) ?>" alt=""><?php else: ?><span><?= e(mb_strtoupper(mb_substr($adherentSelectionne['prenomUsers'], 0, 1) . mb_substr($adherentSelectionne['nomUsers'], 0, 1))) ?></span><?php endif; ?></div>
          <div class="adherent-profile-identity"><h3 id="adherent-detail-title"><?= e($nomFiche) ?></h3><p><?= e($adherentSelectionne['mailUsers']) ?></p><span class="adherent-status status-<?= e($adherentSelectionne['statut_admin']) ?>"><i></i><?= $adherentSelectionne['statut_admin'] === 'actif' ? 'Adhérent actif' : ($adherentSelectionne['statut_admin'] === 'inactif' ? 'Adhérent inactif' : 'En attente') ?></span></div>
          <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherentSelectionne['NumUsers'] . '&vue=voir')) ?>" aria-label="Fermer">×</a>
        </header>
        <form class="adherent-edit-form adherent-edit-form-modal" method="post">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" value="modifier_adherent">
          <input type="hidden" name="utilisateur" value="<?= (int) $adherentSelectionne['NumUsers'] ?>">
          <?php foreach ($flashMessages as $message): if (($message['type'] ?? '') === 'erreur'): ?><p class="adherent-edit-error" role="alert"><?= e($message['message']) ?></p><?php endif; endforeach; ?>
          <section class="adherent-modal-card adherent-edit-card"><h4>Informations personnelles</h4>
          <label>Prénom<input name="prenom" value="<?= e($adherentSelectionne['prenomUsers']) ?>" required maxlength="60"></label>
          <label>Nom<input name="nom" value="<?= e($adherentSelectionne['nomUsers']) ?>" required maxlength="60"></label>
          <label>E-mail<input type="email" name="mail" value="<?= e($adherentSelectionne['mailUsers']) ?>" required maxlength="150"></label>
          <label>Téléphone<input type="tel" name="tel" value="<?= e($adherentSelectionne['telUsers'] ?? '') ?>" maxlength="20"></label>
          <label>Date de naissance<input type="date" name="date_naissance" value="<?= e($adherentSelectionne['date_naissance'] ?? '') ?>"></label>
          <label>Sexe
            <select name="sexe">
              <option value="" <?= empty($adherentSelectionne['sexe']) ? 'selected' : '' ?>>Non renseigné</option>
              <?php foreach (['Femme', 'Homme', 'Autre', 'Préfère ne pas répondre'] as $sexe): ?>
                <option value="<?= e($sexe) ?>" <?= ($adherentSelectionne['sexe'] ?? '') === $sexe ? 'selected' : '' ?>><?= e($sexe) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Adresse postale<input name="adresse_postale" value="<?= e($adherentSelectionne['adresse_postale'] ?? '') ?>" maxlength="255"></label>
          <label>Commune
            <select name="id_commune">
              <option value="" <?= empty($adherentSelectionne['id_commune']) ? 'selected' : '' ?>>Commune non renseignée</option>
              <?php foreach ($communes as $commune): ?>
                <option value="<?= (int) $commune['id_commune'] ?>" <?= (int) $commune['id_commune'] === (int) $adherentSelectionne['id_commune'] ? 'selected' : '' ?>><?= e($commune['nom_commune'] . ' (' . $commune['code_postal'] . ')') ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <div class="adherent-edit-dates">
            <label>Inscription<input type="date" name="Date_inscriptionadh" value="<?= e($adherentSelectionne['Date_inscriptionadh']) ?>" required></label>
            <label>Fin d’adhésion<input type="date" name="Date_finadh" value="<?= e($adherentSelectionne['Date_finadh'] ?? '') ?>"></label>
          </div>
          </section>
          <aside class="adherent-modal-card adherent-edit-subscription"><h4>Abonnement actuel</h4><strong><?= e(explode(' • ', (string) ($adherentSelectionne['abonnement'] ?? ''))[0] ?: 'Aucun abonnement') ?></strong><p><?= e($adherentSelectionne['statut_abonnement'] ?: 'Aucune souscription en cours') ?></p><a href="<?= e(url('admin/espace.php?onglet=abonnements&id=' . (int) $adherentSelectionne['NumUsers'])) ?>">Gérer l’abonnement →</a></aside>
          <div class="adherent-detail-actions">
            <button class="btn btn-vert" type="submit">Enregistrer les modifications</button>
            <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherentSelectionne['NumUsers'] . '&vue=voir')) ?>">Annuler</a>
          </div>
        </form>
      <?php else: ?>
        <?php $nomFiche = trim($adherentSelectionne['prenomUsers'] . ' ' . $adherentSelectionne['nomUsers']); ?>
        <header class="adherent-profile-hero" style="--adherent-cover:url('<?= e(url('assets/img/member-hero.jpg')) ?>')">
          <div class="adherent-profile-avatar"><?php if (!empty($adherentSelectionne['photo_profil'])): ?><img src="<?= e(url($adherentSelectionne['photo_profil'])) ?>" alt=""><?php else: ?><span><?= e(mb_strtoupper(mb_substr($adherentSelectionne['prenomUsers'], 0, 1) . mb_substr($adherentSelectionne['nomUsers'], 0, 1))) ?></span><?php endif; ?></div>
          <div class="adherent-profile-identity"><h3 id="adherent-detail-title"><?= e($nomFiche) ?></h3><p><?= e($adherentSelectionne['mailUsers']) ?></p><span class="adherent-status status-<?= e($adherentSelectionne['statut_admin']) ?>"><i></i><?= $adherentSelectionne['statut_admin'] === 'actif' ? 'Adhérent actif' : ($adherentSelectionne['statut_admin'] === 'inactif' ? 'Adhérent inactif' : 'En attente') ?></span></div>
          <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=adherents')) ?>" aria-label="Fermer la fiche">×</a>
        </header>
        <div class="adherent-read-grid">
        <section class="adherent-modal-card"><h4>Informations personnelles</h4><dl class="adherent-info-list">
          <div><dt>E-mail</dt><dd><a href="mailto:<?= e($adherentSelectionne['mailUsers']) ?>"><?= e($adherentSelectionne['mailUsers']) ?></a></dd></div>
          <div><dt>Téléphone</dt><dd><?= e($adherentSelectionne['telUsers'] ?: 'Non renseigné') ?></dd></div>
          <div><dt>Date de naissance</dt><dd><?= e($adherentSelectionne['date_naissance'] ? dateFr($adherentSelectionne['date_naissance']) : 'Non renseignée') ?></dd></div>
          <div><dt>Sexe</dt><dd><?= e($adherentSelectionne['sexe'] ?: 'Non renseigné') ?></dd></div>
          <div><dt>Adresse</dt><dd><?= e($adherentSelectionne['adresse_postale'] ?: 'Non renseignée') ?></dd></div>
          <div><dt>Commune</dt><dd><?= e($adherentSelectionne['nom_commune'] ?: 'Non renseignée') ?><?= $adherentSelectionne['code_postal'] ? ' · ' . e($adherentSelectionne['code_postal']) : '' ?></dd></div>
          <div><dt>Compte créé le</dt><dd><?= e(dateFr($adherentSelectionne['Date_CreationUsers'], true)) ?></dd></div>
          <div><dt>Inscription adhérent</dt><dd><?= e(dateFr($adherentSelectionne['Date_inscriptionadh'])) ?></dd></div>
          <div><dt>Fin d’adhésion</dt><dd><?= e($adherentSelectionne['Date_finadh'] ? dateFr($adherentSelectionne['Date_finadh']) : 'Sans date de fin') ?></dd></div>
        </dl></section>
        <section class="adherent-modal-card adherent-current-subscription"><h4>Abonnement actuel</h4><div class="adherent-plan"><span aria-hidden="true">◆</span><div><strong><?= e(explode(' • ', (string) ($adherentSelectionne['abonnement'] ?? ''))[0] ?: 'Aucun abonnement') ?></strong><small><?= e($adherentSelectionne['statut_abonnement'] ?: 'Aucune souscription en cours') ?></small></div></div><dl class="adherent-info-list"><div><dt>Début</dt><dd><?= e(!empty($adherentSelectionne['debut_abonnement']) ? dateFr($adherentSelectionne['debut_abonnement']) : '—') ?></dd></div><div><dt>Fin prévue</dt><dd><?= e(!empty($adherentSelectionne['fin_abonnement']) ? dateFr($adherentSelectionne['fin_abonnement']) : 'Sans date de fin') ?></dd></div></dl><a class="adherent-subscription-link" href="<?= e(url('admin/espace.php?onglet=abonnements&id=' . (int) $adherentSelectionne['NumUsers'])) ?>">Voir l’abonnement</a></section>
        <section class="adherent-modal-card adherent-history"><h4>Historique récent</h4><div><span><?= e(dateFr($adherentSelectionne['Date_inscriptionadh'])) ?></span><strong>Inscription adhérent</strong><em>Enregistrée</em></div><?php if (!empty($dernierPaiementAdherent)): ?><div><span><?= e(dateFr($dernierPaiementAdherent)) ?></span><strong>Dernier paiement</strong><a href="<?= e(url('admin/espace.php?onglet=paiements')) ?>">Voir les paiements</a></div><?php else: ?><p>Aucun paiement enregistré.</p><?php endif; ?></section>
        <section class="adherent-modal-card adherent-quick-actions"><h4>Actions rapides</h4><a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherentSelectionne['NumUsers'] . '&vue=modifier')) ?>">✎ Modifier le profil</a><a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=abonnements&id=' . (int) $adherentSelectionne['NumUsers'])) ?>">Gérer l’abonnement</a></section>
        </div>
      <?php endif; ?>
    </dialog>
    <?php endif; ?>
  </div>
</section>
<script>
(() => {
  const detail = document.getElementById('adherent-detail-dialog');
  if (detail && typeof detail.showModal === 'function') {
    detail.showModal();
    detail.querySelector('.adherent-close')?.focus();
    const revenirALaListe = () => { window.location.href = <?= json_encode(url('admin/espace.php?onglet=adherents')) ?>; };
    detail.addEventListener('cancel', event => { event.preventDefault(); revenirALaListe(); });
    detail.addEventListener('click', event => { if (event.target === detail) revenirALaListe(); });
  }
  const rows = [...document.querySelectorAll('[data-adherent-row]')];
  if (!rows.length) return;
  const searchFields = [document.getElementById('recherche-adherent'), document.getElementById('recherche-adherent-haut')].filter(Boolean);
  const subscription = document.getElementById('filtre-abonnement-adherent');
  const sort = document.getElementById('tri-adherents');
  const info = document.getElementById('adherents-pagination-info');
  const pageLinks = document.getElementById('adherents-pages');
  const pageSize = 8;
  const stateKey = 'admin-adherents-list-state';
  let restoredState = null;
  try { restoredState = JSON.parse(sessionStorage.getItem(stateKey) || 'null'); } catch (_) {}
  let page = 1;
  let selectedStatus = '';
  let premierAffichage = true;

  if (restoredState) {
    searchFields.forEach(input => { input.value = restoredState.search || ''; });
    if (subscription) subscription.value = restoredState.subscription || '';
    if (sort && restoredState.sort) sort.value = restoredState.sort;
    selectedStatus = restoredState.status || '';
    page = Number(restoredState.page) || 1;
    document.querySelectorAll('[data-adherent-status]').forEach(button => {
      button.classList.toggle('is-active', button.dataset.adherentStatus === selectedStatus);
    });
  }

  function afficher() {
    const term = (searchFields[0]?.value || '').trim().toLocaleLowerCase('fr');
    let visibles = rows.filter(row => row.dataset.search.includes(term)
      && (!selectedStatus || row.dataset.status === selectedStatus)
      && (!subscription?.value || row.dataset.subscription === subscription.value));

    const comparateurs = {
      'nom-asc': (a,b) => a.dataset.name.localeCompare(b.dataset.name, 'fr') || Number(a.dataset.id) - Number(b.dataset.id),
      'nom-desc': (a,b) => b.dataset.name.localeCompare(a.dataset.name, 'fr') || Number(a.dataset.id) - Number(b.dataset.id),
      'date-desc': (a,b) => b.dataset.date.localeCompare(a.dataset.date) || Number(a.dataset.id) - Number(b.dataset.id),
      'date-asc': (a,b) => a.dataset.date.localeCompare(b.dataset.date) || Number(a.dataset.id) - Number(b.dataset.id),
    };
    visibles.sort(comparateurs[sort?.value] || comparateurs['nom-asc']);
    if (premierAffichage) {
      const indexSelectionne = visibles.findIndex(row => row.classList.contains('is-selected'));
      if (indexSelectionne >= 0) page = Math.floor(indexSelectionne / pageSize) + 1;
      premierAffichage = false;
    }
    const nombrePages = Math.max(1, Math.ceil(visibles.length / pageSize));
    page = Math.min(page, nombrePages);
    rows.forEach(row => { row.hidden = true; });
    visibles.slice((page - 1) * pageSize, page * pageSize).forEach(row => {
      row.hidden = false;
      document.getElementById('lignes-adherents').appendChild(row);
    });
    const debut = visibles.length ? (page - 1) * pageSize + 1 : 0;
    info.textContent = `Affichage de ${debut} à ${Math.min(page * pageSize, visibles.length)} sur ${visibles.length} résultats`;
    document.getElementById('adherents-filter-empty').hidden = visibles.length > 0;
    document.getElementById('adherents-page-prev').disabled = page <= 1;
    document.getElementById('adherents-page-next').disabled = page >= nombrePages;
    pageLinks.textContent = `${page} / ${nombrePages}`;
  }

  searchFields.forEach(input => input.addEventListener('input', () => {
    searchFields.forEach(other => { if (other !== input) other.value = input.value; });
    page = 1;
    afficher();
  }));
  [subscription, sort].filter(Boolean).forEach(input => input.addEventListener('change', () => { page = 1; afficher(); }));
  document.querySelectorAll('[data-adherent-status]').forEach(button => button.addEventListener('click', () => {
    selectedStatus = button.dataset.adherentStatus;
    page = 1;
    document.querySelectorAll('[data-adherent-status]').forEach(tab => tab.classList.toggle('is-active', tab === button));
    afficher();
  }));
  document.getElementById('adherents-page-prev').addEventListener('click', () => { page--; afficher(); });
  document.getElementById('adherents-page-next').addEventListener('click', () => { page++; afficher(); });
  window.addEventListener('pagehide', () => {
    if (detail) return;
    try {
      sessionStorage.setItem(stateKey, JSON.stringify({
        search: searchFields[0]?.value || '',
        subscription: subscription?.value || '',
        sort: sort?.value || 'nom-asc',
        status: selectedStatus,
        page,
        scrollY: window.scrollY,
      }));
    } catch (_) {}
  });
  document.addEventListener('keydown', event => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      document.getElementById('recherche-adherent-haut')?.focus();
    }
  });
  afficher();
  if (!detail && restoredState && Number.isFinite(Number(restoredState.scrollY))) {
    requestAnimationFrame(() => window.scrollTo(0, Number(restoredState.scrollY)));
  }
})();
</script>

<?php $modeCreationEmploye = ($_GET['employe'] ?? '') === 'creer'; ?>
<section class="adherents-page employes-page">
  <header class="adherents-intro">
    <div>
      <h3>Répertoire des employés</h3>
      <p>Gérez les équipes Maintenance et Accueil.</p>
    </div>
    <div class="adherents-header-actions">
      <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=utilisateurs&employe=creer')) ?>">Ajouter un employé</a>
    </div>
  </header>

  <div class="adherents-layout <?= ($employeSelectionne || $modeCreationEmploye) ? 'has-detail' : 'is-full' ?>">
    <div class="adherents-list-wrap">
      <div class="adherents-filter-bar employes-filter-bar">
        <label class="adherents-search"><span aria-hidden="true">⌕</span><input type="search" id="recherche-employe" placeholder="Rechercher par nom, e-mail, téléphone…" autocomplete="off"></label>
        <select id="filtre-service-employe" aria-label="Filtrer par service"><option value="">Tous les services</option><?php foreach (array_unique(array_column($employesGestion, 'libellefonc')) as $serviceEmploye): ?><option value="<?= e(mb_strtolower($serviceEmploye)) ?>"><?= e($serviceEmploye) ?></option><?php endforeach; ?></select>
        <select id="tri-employes" aria-label="Trier les employés"><option value="nom-asc">Trier par : Nom (A → Z)</option><option value="nom-desc">Nom (Z → A)</option><option value="date-desc">Embauche récente</option><option value="date-asc">Embauche ancienne</option></select>
      </div>

      <?php if (!$employesGestion): ?>
        <div class="vide">Aucun employé de Maintenance ou d’Accueil n’est enregistré.</div>
      <?php else: ?>
        <div class="table-scroll adherents-table-scroll">
          <table class="tableau adherents-table coachs-table" id="table-employes">
            <thead><tr><th>Nom &amp; prénom</th><th>E-mail</th><th>Téléphone</th><th>Service</th><th>Commune</th><th>Date d’embauche</th><th>Actions</th></tr></thead>
            <tbody id="lignes-employes">
            <?php foreach ($employesGestion as $employeGestion):
              $nomComplet = trim($employeGestion['prenomUsers'] . ' ' . $employeGestion['nomUsers']);
              $selectionne = $employeSelectionne && (int) $employeSelectionne['NumUsers'] === (int) $employeGestion['NumUsers'];
              $lienFiche = url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeGestion['NumUsers'] . '&vue=voir');
            ?>
              <tr class="<?= $selectionne ? 'is-selected' : '' ?>" data-employe-row data-detail-url="<?= e($lienFiche) ?>" data-search="<?= e(mb_strtolower($nomComplet . ' ' . $employeGestion['mailUsers'] . ' ' . ($employeGestion['telUsers'] ?? '') . ' ' . ($employeGestion['libellefonc'] ?? '') . ' ' . ($employeGestion['nom_commune'] ?? ''))) ?>" data-service="<?= e(mb_strtolower($employeGestion['libellefonc'] ?? '')) ?>" data-name="<?= e(mb_strtolower($employeGestion['nomUsers'] . ' ' . $employeGestion['prenomUsers'])) ?>" data-date="<?= e($employeGestion['Date_embaucheemp'] ?? '') ?>">
                <td>
                  <a class="adherent-person" href="<?= e($lienFiche) ?>">
                    <span class="adherent-avatar"><?= e(mb_strtoupper(mb_substr($employeGestion['prenomUsers'], 0, 1) . mb_substr($employeGestion['nomUsers'], 0, 1))) ?></span>
                    <span><strong><?= e($nomComplet) ?></strong><small><?= e($employeGestion['mailUsers']) ?></small></span>
                  </a>
                </td>
                <td><?= e($employeGestion['mailUsers']) ?></td>
                <td><?= e($employeGestion['telUsers'] ?: 'Non renseigné') ?></td>
                <td><?= e($employeGestion['libellefonc']) ?></td>
                <td><?= e($employeGestion['nom_commune'] ?: 'Non renseignée') ?></td>
                <td><?= e(dateFr($employeGestion['Date_embaucheemp'] ?? '')) ?></td>
                <td>
                  <div class="adherents-actions">
                    <a class="adherent-icon-action" aria-label="Lire la fiche de <?= e($nomComplet) ?>" title="Lire" href="<?= e($lienFiche) ?>">◉</a>
                    <a class="adherent-icon-action" aria-label="Modifier <?= e($nomComplet) ?>" title="Modifier" href="<?= e(url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeGestion['NumUsers'] . '&vue=modifier')) ?>">✎</a>
                    <details class="adherent-row-menu"><summary aria-label="Autres actions" title="Autres actions">⋮</summary><form method="post">
                      <?= champ_csrf() ?>
                      <input type="hidden" name="action" value="supprimer">
                      <input type="hidden" name="utilisateur" value="<?= (int) $employeGestion['NumUsers'] ?>">
                      <input type="hidden" name="retour" value="employes">
                      <button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer définitivement le compte de cet employé ?">Supprimer</button>
                    </form></details>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <footer class="adherents-pagination"><span id="employes-pagination-info"></span><div><button type="button" id="employes-page-prev" aria-label="Page précédente">‹</button><span id="employes-pages"></span><button type="button" id="employes-page-next" aria-label="Page suivante">›</button></div></footer>
        <p class="adherents-empty-filter" id="employes-filter-empty" hidden>Aucun employé ne correspond à ces filtres.</p>
      <?php endif; ?>
    </div>

    <?php if ($employeSelectionne || $modeCreationEmploye): ?>
      <dialog class="adherent-detail adherent-detail-dialog<?= $employeSelectionne ? ' has-profile' : '' ?> employe-detail-dialog" id="employe-detail-dialog" aria-labelledby="employe-detail-title">
        <?php if ($modeCreationEmploye): ?>
          <div class="adherent-detail-heading">
            <div><p class="admin-eyebrow">NOUVEAU MEMBRE DE L’ÉQUIPE</p><h3 id="employe-detail-title">Ajouter un employé</h3></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>" aria-label="Fermer">×</a>
          </div>
          <form class="adherent-edit-form" method="post">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="creer_employe">
            <label>Prénom<input name="prenom" required maxlength="60" autocomplete="given-name"></label>
            <label>Nom<input name="nom" required maxlength="60" autocomplete="family-name"></label>
            <label>E-mail<input type="email" name="mail" required maxlength="150" autocomplete="email"></label>
            <label>Téléphone<input type="tel" name="tel" maxlength="20" autocomplete="tel"></label>
            <div class="employe-commune-field">
              <label for="commune">Commune</label>
              <div class="commune-recherche">
                <input type="text" id="commune" value="" data-url="<?= e(url('commune/recherche.php')) ?>" autocomplete="off" placeholder="Saisir au moins 2 caractères" required>
                <div class="commune-resultats" id="commune-resultats" hidden></div>
                <input type="hidden" name="id_commune" id="id_commune" value="">
                <p id="commune-message" aria-live="polite"></p>
              </div>
              <label for="code_postal">Code postal</label>
              <input type="text" id="code_postal" value="" readonly tabindex="-1">
            </div>
            <label>Service
              <select name="fonction" required>
                <option value="">Choisir un service</option>
                <?php foreach ($fonctionsEmployeNoms as $fonction): ?>
                  <option value="<?= (int) $fonction['Numfonc'] ?>"><?= e($fonction['libellefonc']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Mot de passe<input type="password" name="motdepasse" required minlength="8" autocomplete="new-password"></label>
            <label>Confirmer le mot de passe<input type="password" name="confirmation" required minlength="8" autocomplete="new-password"></label>
            <div class="adherent-detail-actions">
              <button class="btn btn-vert" type="submit">Créer le compte employé</button>
              <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>">Annuler</a>
            </div>
          </form>
        <?php elseif (($_GET['vue'] ?? 'voir') === 'modifier'): ?>
          <header class="adherent-profile-hero" style="--adherent-cover:url('<?= e(url('assets/img/member-hero.jpg')) ?>')">
            <div class="adherent-profile-avatar"><?php if (!empty($employeSelectionne['photo_profil'])): ?><img src="<?= e(url($employeSelectionne['photo_profil'])) ?>" alt=""><?php else: ?><span><?= e(mb_strtoupper(mb_substr($employeSelectionne['prenomUsers'], 0, 1) . mb_substr($employeSelectionne['nomUsers'], 0, 1))) ?></span><?php endif; ?></div>
            <div class="adherent-profile-identity"><h3 id="employe-detail-title"><?= e($employeSelectionne['prenomUsers'] . ' ' . $employeSelectionne['nomUsers']) ?></h3><p><?= e($employeSelectionne['mailUsers']) ?></p><span class="adherent-status status-actif"><i></i><?= e($employeSelectionne['libellefonc']) ?></span></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>" aria-label="Fermer">×</a>
          </header>
          <form class="adherent-edit-form adherent-edit-form-modal employe-edit-form-modal" method="post">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="modifier_employe">
            <input type="hidden" name="utilisateur" value="<?= (int) $employeSelectionne['NumUsers'] ?>">
            <?php foreach ($flashMessages as $message): if (($message['type'] ?? '') === 'erreur'): ?><p class="adherent-edit-error" role="alert"><?= e($message['message']) ?></p><?php endif; endforeach; ?>
            <section class="adherent-modal-card adherent-edit-card"><h4>Informations personnelles</h4>
            <label>Prénom<input name="prenom" value="<?= e($employeSelectionne['prenomUsers']) ?>" required maxlength="60"></label>
            <label>Nom<input name="nom" value="<?= e($employeSelectionne['nomUsers']) ?>" required maxlength="60"></label>
            <label>E-mail<input type="email" name="mail" value="<?= e($employeSelectionne['mailUsers']) ?>" required maxlength="150"></label>
            <label>Téléphone<input type="tel" name="tel" value="<?= e($employeSelectionne['telUsers'] ?? '') ?>" maxlength="20"></label>
            <div class="employe-commune-field employe-edit-commune-field">
              <label for="commune">Commune</label>
              <div class="commune-recherche">
                <input type="text" id="commune" value="<?= e($employeSelectionne['nom_commune'] ?? '') ?>" data-url="<?= e(url('commune/recherche.php')) ?>" autocomplete="off" placeholder="Saisir au moins 2 caractères" required>
                <div class="commune-resultats" id="commune-resultats" hidden></div>
                <input type="hidden" name="id_commune" id="id_commune" value="<?= e((string) ($employeSelectionne['id_commune'] ?? '')) ?>">
                <p id="commune-message" aria-live="polite"></p>
              </div>
              <label for="code_postal">Code postal</label>
              <input type="text" id="code_postal" value="<?= e($employeSelectionne['code_postal'] ?? '') ?>" readonly tabindex="-1">
            </div>
            </section>
            <section class="adherent-modal-card employe-edit-work-card"><h4>Informations professionnelles</h4>
            <label>Service
              <select name="Numfonc" required>
                <?php foreach ($fonctionsEmployeNoms as $fonction): ?>
                  <option value="<?= (int) $fonction['Numfonc'] ?>" <?= (int) $fonction['Numfonc'] === (int) $employeSelectionne['Numfonc'] ? 'selected' : '' ?>><?= e($fonction['libellefonc']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Matricule<input name="matriculeemp" value="<?= e($employeSelectionne['matriculeemp'] ?? '') ?>" required maxlength="20"></label>
            <label>Date d’embauche<input type="date" name="Date_embaucheemp" value="<?= e($employeSelectionne['Date_embaucheemp'] ?? '') ?>" required></label>
            </section>
            <div class="adherent-detail-actions">
              <button class="btn btn-vert" type="submit">Enregistrer les modifications</button>
              <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeSelectionne['NumUsers'] . '&vue=voir')) ?>">Annuler</a>
            </div>
          </form>
        <?php else: ?>
          <header class="adherent-profile-hero" style="--adherent-cover:url('<?= e(url('assets/img/member-hero.jpg')) ?>')">
            <div class="adherent-profile-avatar"><?php if (!empty($employeSelectionne['photo_profil'])): ?><img src="<?= e(url($employeSelectionne['photo_profil'])) ?>" alt=""><?php else: ?><span><?= e(mb_strtoupper(mb_substr($employeSelectionne['prenomUsers'], 0, 1) . mb_substr($employeSelectionne['nomUsers'], 0, 1))) ?></span><?php endif; ?></div>
            <div class="adherent-profile-identity"><h3 id="employe-detail-title"><?= e($employeSelectionne['prenomUsers'] . ' ' . $employeSelectionne['nomUsers']) ?></h3><p><?= e($employeSelectionne['mailUsers']) ?></p><span class="adherent-status status-actif"><i></i><?= e($employeSelectionne['libellefonc']) ?></span></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>" aria-label="Fermer la fiche">×</a>
          </header>
          <div class="adherent-read-grid employe-read-grid"><section class="adherent-modal-card"><h4>Informations personnelles</h4><dl class="adherent-info-list">
            <div><dt>E-mail</dt><dd><a href="mailto:<?= e($employeSelectionne['mailUsers']) ?>"><?= e($employeSelectionne['mailUsers']) ?></a></dd></div>
            <div><dt>Téléphone</dt><dd><?= e($employeSelectionne['telUsers'] ?: 'Non renseigné') ?></dd></div>
            <div><dt>Commune</dt><dd><?= e($employeSelectionne['nom_commune'] ?: 'Non renseignée') ?><?= !empty($employeSelectionne['code_postal']) ? ' · ' . e($employeSelectionne['code_postal']) : '' ?></dd></div>
            <div><dt>Compte créé le</dt><dd><?= e(dateFr($employeSelectionne['Date_CreationUsers'] ?? null, true)) ?></dd></div>
          </dl></section><section class="adherent-modal-card employe-work-details"><h4>Informations professionnelles</h4><dl class="adherent-info-list"><div><dt>Service</dt><dd><?= e($employeSelectionne['libellefonc']) ?></dd></div><div><dt>Matricule</dt><dd><?= e($employeSelectionne['matriculeemp'] ?? '') ?></dd></div><div><dt>Date d’embauche</dt><dd><?= e(dateFr($employeSelectionne['Date_embaucheemp'] ?? '')) ?></dd></div></dl></section><section class="adherent-modal-card adherent-quick-actions employe-quick-actions"><h4>Actions rapides</h4><a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeSelectionne['NumUsers'] . '&vue=modifier')) ?>">✎ Modifier le profil</a></section></div>
        <?php endif; ?>
      </dialog>
    <?php endif; ?>
  </div>
</section>
<script>
(() => {
  const detail = document.getElementById('employe-detail-dialog');
  if (detail && typeof detail.showModal === 'function') {
    detail.showModal();
    detail.querySelector('.adherent-close')?.focus();
    const revenirALaListe = () => { window.location.href = <?= json_encode(url('admin/espace.php?onglet=utilisateurs')) ?>; };
    detail.addEventListener('cancel', event => { event.preventDefault(); revenirALaListe(); });
    detail.addEventListener('click', event => { if (event.target === detail) revenirALaListe(); });
  }
  const stateKey = 'admin-employes-list-state';
  const dialog = document.getElementById('employe-detail-dialog');
  const search = document.getElementById('recherche-employe');
  let restoredState = null;
  try { restoredState = JSON.parse(sessionStorage.getItem(stateKey) || 'null'); } catch (_) {}
  const rows=[...document.querySelectorAll('[data-employe-row]')],service=document.getElementById('filtre-service-employe'),sort=document.getElementById('tri-employes'),info=document.getElementById('employes-pagination-info'),pagesOut=document.getElementById('employes-pages'),size=8;
  if (!rows.length || !search || !service || !sort) return;
  if (restoredState) { search.value=restoredState.search||'';service.value=restoredState.service||'';sort.value=restoredState.sort||'nom-asc'; }
  let page=Number(restoredState?.page)||1,first=true;
  function afficher(){const q=search.value.trim().toLocaleLowerCase('fr'),s=service.value,mode=sort.value;let visibles=rows.filter(row=>row.dataset.search.includes(q)&&(!s||row.dataset.service===s));visibles.sort((a,b)=>mode==='nom-desc'?b.dataset.name.localeCompare(a.dataset.name,'fr'):mode==='date-desc'?b.dataset.date.localeCompare(a.dataset.date):mode==='date-asc'?a.dataset.date.localeCompare(b.dataset.date):a.dataset.name.localeCompare(b.dataset.name,'fr'));const tbody=document.getElementById('lignes-employes');[...visibles,...rows.filter(row=>!visibles.includes(row))].forEach(row=>tbody.append(row));if(first&&detail){const index=visibles.findIndex(row=>row.classList.contains('is-selected'));if(index>=0)page=Math.floor(index/size)+1;}first=false;const total=Math.max(1,Math.ceil(visibles.length/size));page=Math.max(1,Math.min(page,total));rows.forEach(row=>row.hidden=true);visibles.slice((page-1)*size,page*size).forEach(row=>row.hidden=false);info.textContent=visibles.length?`Affichage de ${(page-1)*size+1} à ${Math.min(page*size,visibles.length)} sur ${visibles.length} employés`:'0 employé';pagesOut.textContent=`${page} / ${total}`;document.getElementById('employes-filter-empty').hidden=visibles.length>0;document.getElementById('employes-page-prev').disabled=page<=1;document.getElementById('employes-page-next').disabled=page>=total;}
  [search,service,sort].forEach(el=>{el.addEventListener('input',()=>{page=1;afficher()});el.addEventListener('change',()=>{page=1;afficher()})});document.getElementById('employes-page-prev').addEventListener('click',()=>{page--;afficher()});document.getElementById('employes-page-next').addEventListener('click',()=>{page++;afficher()});
  window.addEventListener('pagehide',()=>{if(detail)return;try{sessionStorage.setItem(stateKey,JSON.stringify({search:search.value,service:service.value,sort:sort.value,page,scrollY:window.scrollY}))}catch(_){}});
  afficher();if(!detail&&restoredState)requestAnimationFrame(()=>window.scrollTo(0,Number(restoredState.scrollY)||0));
})();
</script>

<?php
$montantTrenteJours = 0.0;
$dateLimitePaiement = new DateTimeImmutable('-30 days');
$moyensPaiementAdmin = [];
foreach ($historiquePaiements as $lignePaiement) {
    if (new DateTimeImmutable($lignePaiement['date_paiement']) >= $dateLimitePaiement) $montantTrenteJours += (float) $lignePaiement['montant'];
    $moyensPaiementAdmin[$lignePaiement['moyen_paiement']] = true;
}
$paiementsReussisAdmin = count($historiquePaiements);
$paiementCourant = $paiementSelectionneAdmin;
$editionPaiement = $vuePaiements === 'modifier' && $paiementCourant;
$saisiePaiement = $_SESSION['admin_paiement_modif_saisie'] ?? null;
$montantPaiementFormulaire = $paiementCourant ? number_format((float) $paiementCourant['montant'], 2, '.', '') : '';
$datePaiementFormulaire = $paiementCourant ? (new DateTimeImmutable($paiementCourant['date_paiement']))->format('Y-m-d\\TH:i') : '';
$moyenPaiementFormulaire = $paiementCourant['moyen_paiement'] ?? '';
if ($paiementCourant && is_array($saisiePaiement) && (int) ($saisiePaiement['id'] ?? 0) === (int) $paiementCourant['Numpaiement']) {
    $montantPaiementFormulaire = $saisiePaiement['montant'] ?? $montantPaiementFormulaire;
    $datePaiementFormulaire = $saisiePaiement['date_paiement'] ?? $datePaiementFormulaire;
    $moyenPaiementFormulaire = $saisiePaiement['moyen_paiement'] ?? $moyenPaiementFormulaire;
    unset($_SESSION['admin_paiement_modif_saisie']);
}
$referencePaiement = static fn(array $paiement): string => '#PAY-' . str_pad((string) $paiement['Numpaiement'], 4, '0', STR_PAD_LEFT);
$formatMontantPaiement = static fn($montant): string => number_format((float) $montant, 2, ',', ' ') . ' €';
?>
<section class="admin-payments-page-content">
  <header class="admin-payments-heading"><div class="admin-payments-title"><span aria-hidden="true">▣</span><div><h2>Historique des paiements</h2><p>Consultez l’historique des règlements de vos adhérents, recherchez et téléchargez les factures.</p></div></div></header>

  <div class="admin-payment-stats">
    <article class="payment-stat-amount"><span class="payment-stat-icon" aria-hidden="true">▤</span><div><small>MONTANT ENCAISSÉ (30 JOURS)</small><strong><?= e($formatMontantPaiement($montantTrenteJours)) ?></strong><span>Somme des paiements enregistrés</span></div></article>
    <article class="payment-stat-success"><span class="payment-stat-icon" aria-hidden="true">✓</span><div><small>PAIEMENTS ENREGISTRÉS</small><strong><?= $paiementsReussisAdmin ?></strong><span>Règlements présents dans l’historique</span></div></article>
    <article class="payment-stat-methods"><span class="payment-stat-icon" aria-hidden="true">◷</span><div><small>MOYENS DE PAIEMENT</small><strong><?= count($moyensPaiementAdmin) ?></strong><span>Moyens utilisés par les adhérents</span></div></article>
    <article class="payment-stat-invoices"><span class="payment-stat-icon" aria-hidden="true">⇩</span><div><small>FACTURES DISPONIBLES</small><strong><?= $paiementsReussisAdmin ?></strong><span>Téléchargement PDF à la demande</span></div></article>
  </div>

  <div class="admin-payment-filters">
    <label class="admin-payment-search"><span aria-hidden="true">⌕</span><input type="search" id="recherche-paiement" placeholder="Rechercher un adhérent, une référence, une formule…" autocomplete="off"></label>
    <label><small>Formule</small><select id="filtre-paiement-formule"><option value="">Toutes les formules</option><?php foreach (array_unique(array_column($historiquePaiements, 'libelle_abo')) as $formulePaiement): ?><option value="<?= e(mb_strtolower($formulePaiement)) ?>"><?= e($formulePaiement) ?></option><?php endforeach; ?></select></label>
    <label><small>Moyen de paiement</small><select id="filtre-paiement-moyen"><option value="">Tous les moyens</option><?php foreach (array_keys($moyensPaiementAdmin) as $moyenPaiement): ?><option value="<?= e(mb_strtolower($moyenPaiement)) ?>"><?= e($moyenPaiement) ?></option><?php endforeach; ?></select></label>
    <label><small>Période</small><select id="filtre-paiement-periode"><option value="">Toutes les dates</option><option value="30">30 derniers jours</option><option value="90">90 derniers jours</option><option value="365">12 derniers mois</option></select></label>
  </div>

  <div class="admin-payment-layout<?= $vuePaiements === 'liste' ? ' is-list-only' : '' ?>">
    <section class="admin-payment-list">
      <header><div><h3>Liste des paiements</h3><p><?= count($historiquePaiements) ?> paiement<?= count($historiquePaiements) === 1 ? '' : 's' ?></p></div></header>
      <?php if (!$historiquePaiements): ?><div class="admin-payment-empty">Aucun paiement enregistré.</div><?php else: ?>
        <div class="admin-payment-table-scroll"><table class="admin-payment-table"><thead><tr><th>Adhérent</th><th>Formule / abonnement</th><th>Date</th><th>Moyen de paiement</th><th>Montant</th><th>Statut</th><th>Référence</th><th>Actions</th></tr></thead><tbody id="paiements-lignes">
          <?php foreach ($historiquePaiements as $paiement):
            $nomComplet = trim($paiement['prenomUsers'] . ' ' . $paiement['nomUsers']);
            $reference = $referencePaiement($paiement);
            $recherchePaiement = mb_strtolower($nomComplet . ' ' . $paiement['mailUsers'] . ' ' . $paiement['libelle_abo'] . ' ' . $paiement['moyen_paiement'] . ' ' . $reference);
            $datePaiementCourante = new DateTimeImmutable($paiement['date_paiement']);
            $clePaiementSelectionne = $paiementCourant && (int) $paiementCourant['Numpaiement'] === (int) $paiement['Numpaiement'];
            $urlPaiementCourant = url('admin/espace.php?onglet=paiements&vue=voir&paiement=' . (int) $paiement['Numpaiement']);
            $urlModifierPaiement = url('admin/espace.php?onglet=paiements&vue=modifier&paiement=' . (int) $paiement['Numpaiement']);
          ?>
            <tr data-paiement-row data-detail-url="<?= e($urlPaiementCourant) ?>" data-search="<?= e($recherchePaiement) ?>" data-formula="<?= e(mb_strtolower($paiement['libelle_abo'])) ?>" data-method="<?= e(mb_strtolower($paiement['moyen_paiement'])) ?>" data-date="<?= e($datePaiementCourante->format('Y-m-d')) ?>" class="<?= $clePaiementSelectionne ? 'is-selected' : '' ?>">
              <td><span class="payment-member-avatar"><?= e(mb_strtoupper(mb_substr($paiement['prenomUsers'], 0, 1) . mb_substr($paiement['nomUsers'], 0, 1))) ?></span><span class="payment-member-name"><strong><?= e($nomComplet) ?></strong><small><?= e($paiement['mailUsers']) ?></small></span></td>
              <td><strong><?= e($paiement['libelle_abo']) ?></strong><small><?= $paiement['date_debut_abo'] ? e(dateFr($paiement['date_debut_abo'])) . ($paiement['date_fin_abo'] ? ' → ' . e(dateFr($paiement['date_fin_abo'])) : '') : 'Abonnement' ?></small></td>
              <td><strong><?= e(dateFr($paiement['date_paiement'])) ?></strong><small><?= $datePaiementCourante->format('H:i') ?></small></td>
              <td><?= e($paiement['moyen_paiement']) ?></td>
              <td><strong><?= e($formatMontantPaiement($paiement['montant'])) ?></strong></td>
              <td><span class="payment-status"><i></i>Réussi</span></td>
              <td><span class="payment-reference"><?= e($reference) ?></span></td>
              <td><div class="payment-row-actions"><a href="<?= e($urlPaiementCourant) ?>" aria-label="Voir le paiement <?= e($reference) ?>" title="Voir">◉</a><a href="<?= e($urlModifierPaiement) ?>" aria-label="Modifier le paiement <?= e($reference) ?>" title="Modifier">✎</a><a href="<?= e(url('admin/facture.php?id=' . (int) $paiement['Numpaiement'])) ?>" aria-label="Télécharger la facture <?= e($reference) ?>" title="Télécharger le PDF">⇩</a></div></td>
            </tr>
          <?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
      <footer class="admin-payment-pagination"><span id="paiements-pagination-info"></span><div><button type="button" id="paiements-precedent">Précédent</button><span id="paiements-pages"></span><button type="button" id="paiements-suivant">Suivant</button></div></footer>
      <p class="admin-payment-filter-empty" id="paiements-vide" hidden>Aucun paiement ne correspond à ces filtres.</p>
    </section>

    <?php if ($paiementCourant && $vuePaiements !== 'liste'): $reference = $referencePaiement($paiementCourant); $nomMembreCourant = trim($paiementCourant['prenomUsers'] . ' ' . $paiementCourant['nomUsers']); ?>
      <dialog class="admin-payment-detail admin-payment-detail-dialog" id="payment-detail-dialog" aria-labelledby="payment-detail-title">
        <header class="payment-profile-hero" style="--adherent-cover:url('<?= e(url('assets/img/member-hero.jpg')) ?>')">
          <span class="payment-profile-avatar"><?= e(mb_strtoupper(mb_substr($paiementCourant['prenomUsers'], 0, 1) . mb_substr($paiementCourant['nomUsers'], 0, 1))) ?></span>
          <div class="payment-profile-identity"><h3 id="payment-detail-title"><?= e($nomMembreCourant) ?></h3><p><?= e($paiementCourant['mailUsers']) ?></p><span class="payment-status"><i></i><?= e($reference) ?> · Réussi</span></div>
          <a class="payment-profile-close" href="<?= e(url('admin/espace.php?onglet=paiements&vue=liste')) ?>" aria-label="Fermer la fiche">×</a>
        </header>
        <div class="payment-modal-grid">
          <div class="payment-modal-main">
            <section class="payment-detail-total"><div class="payment-success-icon">✓</div><strong>Paiement réussi</strong><b><?= e($formatMontantPaiement($paiementCourant['montant'])) ?></b><span>Référence : <?= e($reference) ?></span></section>
            <section class="payment-modal-card"><h4>Détails du règlement</h4>
              <div class="payment-detail-info"><span class="payment-detail-icon">▦</span><div><small>Formule / abonnement</small><strong><?= e($paiementCourant['libelle_abo']) ?></strong><span><?= $paiementCourant['date_debut_abo'] ? 'Du ' . e(dateFr($paiementCourant['date_debut_abo'])) . ($paiementCourant['date_fin_abo'] ? ' au ' . e(dateFr($paiementCourant['date_fin_abo'])) : '') : 'Période non renseignée' ?></span></div></div>
              <div class="payment-detail-info"><span class="payment-detail-icon">▤</span><div><small>Moyen de paiement</small><strong><?= e($paiementCourant['moyen_paiement']) ?></strong></div></div>
              <div class="payment-detail-info"><span class="payment-detail-icon">▦</span><div><small>Date du paiement</small><strong><?= e(dateFr($paiementCourant['date_paiement'], true)) ?></strong></div></div>
              <div class="payment-detail-info"><span class="payment-detail-icon">€</span><div><small>Montant réglé</small><strong><?= e($formatMontantPaiement($paiementCourant['montant'])) ?></strong></div></div>
            </section>
          </div>
          <aside class="payment-modal-side">
          <?php if ($editionPaiement): ?>
            <form method="post" class="payment-edit-form-modal">
              <?= champ_csrf() ?><input type="hidden" name="action" value="modifier_paiement_admin"><input type="hidden" name="paiement" value="<?= (int) $paiementCourant['Numpaiement'] ?>">
              <?php foreach ($flashMessages as $message): if (($message['type'] ?? '') === 'erreur'): ?><p class="payment-edit-error" role="alert"><?= e($message['message']) ?></p><?php endif; endforeach; ?>
              <h4>Modifier le paiement</h4>
              <label>Montant (€)<input type="number" name="montant" min="0.01" max="999999.99" step="0.01" value="<?= e($montantPaiementFormulaire) ?>" required></label>
              <label>Date et heure<input type="datetime-local" name="date_paiement" max="<?= e(date('Y-m-d\\TH:i')) ?>" value="<?= e($datePaiementFormulaire) ?>" required></label>
              <label>Moyen de paiement<select name="moyen_paiement" required><?php foreach (['CB' => 'Carte bancaire', 'Virement' => 'Virement', 'Prelevement' => 'Prélèvement', 'Especes' => 'Espèces'] as $valeurMoyen => $libelleMoyen): ?><option value="<?= e($valeurMoyen) ?>" <?= $moyenPaiementFormulaire === $valeurMoyen ? 'selected' : '' ?>><?= e($libelleMoyen) ?></option><?php endforeach; ?></select></label>
              <div><a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=paiements&vue=voir&paiement=' . (int) $paiementCourant['Numpaiement'])) ?>">Annuler</a><button class="btn btn-vert" type="submit">Enregistrer</button></div>
            </form>
          <?php else: ?>
            <section class="payment-modal-actions"><h4>Actions rapides</h4><a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=paiements&vue=modifier&paiement=' . (int) $paiementCourant['Numpaiement'])) ?>">✎ Modifier le paiement</a><a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $paiementCourant['NumUsers'] . '&vue=voir')) ?>">♙ Voir la fiche adhérent</a></section>
          <?php endif; ?>
          <section class="payment-invoice-card"><span>▧</span><div><strong>Facture disponible</strong><small>Facture_<?= e(str_replace('#', '', $reference)) ?>.pdf</small></div><a href="<?= e(url('admin/facture.php?id=' . (int) $paiementCourant['Numpaiement'])) ?>">⇩ PDF</a></section>
          </aside>
        </div>
      </dialog>
    <?php elseif ($vuePaiements !== 'liste'): ?><aside class="admin-payment-detail payment-detail-empty"><h3>Aucun paiement sélectionné</h3><p>Les nouveaux règlements apparaîtront ici.</p></aside><?php endif; ?>
  </div>
</section>
<script>
(() => {
  const detail=document.getElementById('payment-detail-dialog');
  if(detail&&typeof detail.showModal==='function'){
    detail.showModal();
    const revenirALaListe=()=>{window.location.href=<?= json_encode(url('admin/espace.php?onglet=paiements&vue=liste')) ?>;};
    detail.addEventListener('cancel',event=>{event.preventDefault();revenirALaListe()});
    detail.addEventListener('click',event=>{if(event.target===detail)revenirALaListe()});
  }
  const rows=[...document.querySelectorAll('[data-paiement-row]')], search=document.getElementById('recherche-paiement'), formula=document.getElementById('filtre-paiement-formule'), method=document.getElementById('filtre-paiement-moyen'), period=document.getElementById('filtre-paiement-periode');
  const info=document.getElementById('paiements-pagination-info'), pageOut=document.getElementById('paiements-pages'),stateKey='admin-paiements-list-state';let page=1;const pageSize=8;let restoredState=null;try{restoredState=JSON.parse(sessionStorage.getItem(stateKey)||'null')}catch(_){}
  if(restoredState){search.value=restoredState.search||'';formula.value=restoredState.formula||'';method.value=restoredState.method||'';period.value=restoredState.period||'';page=Number(restoredState.page)||1;}
  let firstRender=true;
  function render(){const q=search.value.trim().toLocaleLowerCase('fr'),f=formula.value,m=method.value,days=Number(period.value||0),cutoff=days?new Date():null;if(cutoff)cutoff.setDate(cutoff.getDate()-days);let visible=rows.filter(row=>{const date=new Date(row.dataset.date+'T00:00:00');return row.dataset.search.includes(q)&&(!f||row.dataset.formula===f)&&(!m||row.dataset.method===m)&&(!cutoff||date>=cutoff);});if(firstRender&&detail){const selectedIndex=visible.findIndex(row=>row.classList.contains('is-selected'));if(selectedIndex>=0)page=Math.floor(selectedIndex/pageSize)+1;}firstRender=false;const pages=Math.max(1,Math.ceil(visible.length/pageSize));page=Math.max(1,Math.min(page,pages));rows.forEach(row=>row.hidden=true);visible.slice((page-1)*pageSize,page*pageSize).forEach(row=>row.hidden=false);info.textContent=visible.length?`Affichage de ${(page-1)*pageSize+1} à ${Math.min(page*pageSize,visible.length)} sur ${visible.length} paiements`:'0 paiement';pageOut.textContent=`${page} / ${pages}`;document.getElementById('paiements-vide').hidden=visible.length>0;document.getElementById('paiements-precedent').disabled=page<=1;document.getElementById('paiements-suivant').disabled=page>=pages;}
  [search,formula,method,period].forEach(element=>{element.addEventListener('input',()=>{page=1;render()});element.addEventListener('change',()=>{page=1;render()})});document.getElementById('paiements-precedent').addEventListener('click',()=>{page--;render()});document.getElementById('paiements-suivant').addEventListener('click',()=>{page++;render()});
  window.addEventListener('pagehide',()=>{if(detail)return;try{sessionStorage.setItem(stateKey,JSON.stringify({search:search.value,formula:formula.value,method:method.value,period:period.value,page,scrollY:window.scrollY}))}catch(_){}});
  render();if(!detail&&restoredState&&Number.isFinite(Number(restoredState.scrollY)))requestAnimationFrame(()=>window.scrollTo(0,Number(restoredState.scrollY)));
})();
</script>

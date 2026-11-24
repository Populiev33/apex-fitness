
  <section class="maintenance-ticket-page<?= $maintenance ? ' is-technician' : ' is-reception' ?>" aria-label="<?= $maintenance ? 'Mes tickets de maintenance' : 'Suivi de mes tickets' ?>">
    <header class="maintenance-ticket-page-heading">
      <div><?php if ($maintenance): ?><p class="ticket-page-eyebrow">ESPACE MAINTENANCE</p><?php endif; ?><h2><?= $maintenance ? 'Mes tickets' : 'Suivi des tickets' ?></h2><p><?= $maintenance ? 'Retrouvez la file des demandes et suivez l’avancement des interventions.' : 'Consultez l’état de vos demandes et suivez leur avancement.' ?></p></div>
    </header>
    <?php if (!$maintenance): ?>
    <div class="maintenance-ticket-summary">
      <article class="ticket-summary-card ticket-summary-total">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-ticket"></i></span>
        <div><strong><?= count($ticketsPourSuivi) ?></strong><span><?= $maintenance ? 'Tickets dans la file' : 'Mes tickets' ?></span><small><?= $maintenance ? 'Toutes les demandes de maintenance' : 'Demandes signalées par vous' ?></small></div><i class="fa-solid fa-arrow-right ticket-summary-arrow" aria-hidden="true"></i>
      </article>
      <article class="ticket-summary-card ticket-summary-progress">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-wrench"></i></span>
        <div><strong><?= $ticketsEnCoursMaintenance ?></strong><span>En cours</span><small>Interventions en cours</small></div><i class="fa-solid fa-arrow-right ticket-summary-arrow" aria-hidden="true"></i>
      </article>
      <article class="ticket-summary-card ticket-summary-resolved">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
        <div><strong><?= $ticketsResoluMaintenance ?></strong><span>Résolus</span><small><?= $maintenance ? $ticketsResolusCetteSemaine . ' cette semaine' : 'Demandes clôturées' ?></small></div><i class="fa-solid fa-arrow-right ticket-summary-arrow" aria-hidden="true"></i>
      </article>
      <article class="ticket-summary-card ticket-summary-waiting">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
        <div><strong><?= $ticketsEnAttenteMaintenance ?></strong><span>En attente</span><small><?= $maintenance ? 'À prendre en charge ou valider' : 'En attente d’intervention' ?></small></div><i class="fa-solid fa-arrow-right ticket-summary-arrow" aria-hidden="true"></i>
      </article>
    </div>
    <?php endif; ?>

    <?php if (!$ticketsPourSuivi): ?>
      <div class="maintenance-ticket-empty">
        <span aria-hidden="true"><i class="fa-solid fa-ticket"></i></span>
        <h3><?= $maintenance ? 'Aucun ticket pour le moment' : 'Vous n’avez pas encore créé de ticket' ?></h3>
        <p><?= $maintenance ? 'Les demandes de maintenance apparaîtront ici dès leur création.' : 'Les tickets que vous signalez à l’équipe de maintenance apparaîtront ici.' ?></p>
        <?php if (!$maintenance): ?><a class="btn btn-vert" href="<?= e(url('employe/espace.php?onglet=creer-ticket')) ?>">Créer un ticket</a><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="maintenance-ticket-workspace<?= $maintenance ? ' is-technician' : '' ?>">
        <?php require __DIR__ . '/ticket-list.php'; ?>
      </div>

      <?php if ($ticketSelectionne): ?>
        <dialog class="maintenance-ticket-dialog" id="maintenance-ticket-dialog" aria-labelledby="ticket-modal-title" data-list-url="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets)) ?>">
          <header class="maintenance-ticket-modal-hero">
            <span class="maintenance-ticket-modal-avatar" aria-hidden="true">TKT</span>
            <div class="maintenance-ticket-modal-identity">
              <p>FICHE DE SUIVI <span>›</span> TICKET #<?= (int) $ticketSelectionne['Nummaint'] ?></p>
              <h2 id="ticket-modal-title"><?= e($ticketSelectionne['nomequip']) ?></h2>
              <p><?= e(trim($ticketSelectionne['prenomUsers'] . ' ' . $ticketSelectionne['nomUsers'])) ?> · <?= e($ticketSelectionne['libelletypeequip']) ?></p>
              <div class="maintenance-ticket-modal-badges">
                <span class="ticket-priority-badge is-<?= e(mb_strtolower($ticketSelectionne['priorite'])) ?>"><?= e($ticketSelectionne['priorite']) ?> priorité</span>
                <span class="ticket-status-badge"><?= $ticketSelectionne['libelle_statut'] === 'Terminee' ? 'Résolu' : e($ticketSelectionne['libelle_statut']) ?></span>
              </div>
            </div>
            <a class="maintenance-ticket-modal-close" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets)) ?>" aria-label="Fermer la fiche du ticket">×</a>
          </header>
          <aside class="maintenance-ticket-detail-panel" data-ticket-detail-panel aria-label="Détails du ticket">
            <div class="ticket-modal-main-column">
              <?php require __DIR__ . '/ticket-detail-overview.php'; ?>
            </div>
            <div class="ticket-modal-actions-column">
              <?php require __DIR__ . '/ticket-detail-actions.php'; ?>
            </div>
          </aside>
        </dialog>
      <?php endif; ?>

    <?php endif; ?>
  </section>

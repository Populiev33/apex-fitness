<?php
$prenomAccueil = trim((string) ($employeUser['prenomUsers'] ?? ''));
$libellesActions = [
    'CREATION_ADHERENT_ADMIN' => 'Nouvel adhérent ajouté',
    'CREATION_ADHERENT_ACCUEIL' => 'Nouvel adhérent ajouté',
    'AJOUT_ROLE_ADHERENT' => 'Nouvel adhérent inscrit',
    'RESERVATION' => 'Réservation créée',
    'CREATION_RESERVATION_ADMIN' => 'Réservation créée à l’accueil',
    'ANNULATION' => 'Réservation annulée',
    'ANNULATION_RESERVATION_ADMIN' => 'Réservation annulée à l’accueil',
    'CREATION_MAINTENANCE' => 'Nouveau ticket signalé',
    'MESSAGE_CONTACT' => 'Nouveau message reçu',
    'PAIEMENT_ABONNEMENT' => 'Paiement enregistré',
    'AVIS_SALLE' => 'Nouvel avis reçu',
];
?>
<section class="accueil-dashboard" aria-labelledby="accueil-dashboard-title">
  <header class="accueil-hero">
    <p class="accueil-eyebrow">ESPACE ACCUEIL</p>
    <h2 id="accueil-dashboard-title">Bonjour <span><?= e($prenomAccueil ?: 'et bienvenue') ?></span></h2>
    <p>Voici l’activité du club aujourd’hui.</p>
  </header>

  <section class="accueil-kpis" aria-label="Indicateurs du club">
    <article class="accueil-kpi">
      <span class="accueil-kpi-icon is-green" aria-hidden="true">♟</span>
      <div><strong><?= (int) $donneesTableauAccueil['stats']['adherentsActifs'] ?></strong><span>Adhérents actifs</span><small>Abonnement en cours</small></div>
    </article>
    <article class="accueil-kpi">
      <span class="accueil-kpi-icon is-green" aria-hidden="true">▦</span>
      <div><strong><?= (int) $donneesTableauAccueil['stats']['reservationsAujourdhui'] ?></strong><span>Réservations aujourd’hui</span><small>Hors réservations annulées</small></div>
    </article>
    <article class="accueil-kpi">
      <span class="accueil-kpi-icon is-orange" aria-hidden="true">⚑</span>
      <div><strong><?= (int) $donneesTableauAccueil['stats']['ticketsOuverts'] ?></strong><span>Tickets ouverts</span><small>À traiter par l’équipe</small></div>
    </article>
    <article class="accueil-kpi">
      <span class="accueil-kpi-icon is-green" aria-hidden="true">☆</span>
      <div><strong><?= $donneesTableauAccueil['stats']['noteMoyenne'] !== null ? e(number_format($donneesTableauAccueil['stats']['noteMoyenne'], 1, ',', '')) . '<small class="accueil-kpi-unit"> / 5</small>' : '—' ?></strong><span>Satisfaction membre</span><small><?= (int) $donneesTableauAccueil['stats']['nombreAvis'] ?> avis enregistrés</small></div>
    </article>
  </section>

  <div class="accueil-dashboard-grid">
    <div class="accueil-dashboard-left">
      <section class="accueil-panel" aria-labelledby="accueil-planning-title">
        <header class="accueil-panel-header">
          <h3 id="accueil-planning-title"><span aria-hidden="true">▦</span> Planning d’aujourd’hui</h3>
          <a href="<?= e(url('employe/espace.php?onglet=creneaux')) ?>">Voir le planning <span aria-hidden="true">→</span></a>
        </header>
        <?php if (!$donneesTableauAccueil['planning']): ?>
          <p class="accueil-empty">Aucun cours n’est programmé aujourd’hui.</p>
        <?php else: ?>
          <ol class="accueil-agenda">
            <?php foreach ($donneesTableauAccueil['planning'] as $activite):
              $debut = strtotime($activite['date_heure_debut']);
              $fin = strtotime($activite['date_heure_fin']);
              $maintenant = time();
              $statutCours = $maintenant >= $debut && $maintenant < $fin ? 'En cours' : ($maintenant < $debut ? 'À venir' : 'Terminé');
              $classeStatutCours = $maintenant >= $debut && $maintenant < $fin ? 'en-cours' : ($maintenant < $debut ? 'a-venir' : 'termine');
            ?>
              <li class="accueil-agenda-item">
                <time datetime="<?= e(date('c', $debut)) ?>"><?= e(date('H:i', $debut)) ?></time>
                <div class="accueil-agenda-copy">
                  <strong><?= e($activite['titre']) ?></strong>
                  <span><?= (int) $activite['participants'] ?> / <?= (int) $activite['capacite_max'] ?> participants · <?= e($activite['nomsalle']) ?></span>
                </div>
                <span class="accueil-course-status is-<?= e($classeStatutCours) ?>"><?= e($statutCours) ?></span>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </section>

    </div>

    <div class="accueil-dashboard-right">
      <section class="accueil-quick-panel" aria-labelledby="accueil-actions-title">
        <h3 id="accueil-actions-title"><span aria-hidden="true">ϟ</span> Actions rapides</h3>
        <div class="accueil-quick-actions">
          <a href="<?= e(url('employe/espace.php?onglet=reservations')) ?>"><span aria-hidden="true">▦</span>Créer une réservation</a>
          <a href="<?= e(url('employe/espace.php?onglet=creer-ticket')) ?>"><span aria-hidden="true">◇</span>Créer un ticket</a>
          <a href="<?= e(url('employe/espace.php?onglet=adherents')) ?>"><span aria-hidden="true">♟</span>Consulter les adhérents</a>
          <a href="<?= e(url('employe/espace.php?onglet=creneaux')) ?>"><span aria-hidden="true">▥</span>Voir le planning complet</a>
        </div>
      </section>

      <section class="accueil-panel accueil-recent-panel" aria-labelledby="accueil-activity-title">
        <header class="accueil-panel-header">
          <h3 id="accueil-activity-title"><span aria-hidden="true">◷</span> Activité récente</h3>
          <a href="<?= e(url('employe/espace.php?onglet=suivi-tickets')) ?>">Suivi des tickets <span aria-hidden="true">→</span></a>
        </header>
        <?php if (!$donneesTableauAccueil['activiteRecente']): ?>
          <p class="accueil-empty">Aucune activité récente enregistrée.</p>
        <?php else: ?>
          <ul class="accueil-recent-list">
            <?php foreach ($donneesTableauAccueil['activiteRecente'] as $evenement):
              $minutes = max(0, (int) floor((time() - strtotime($evenement['date_action'])) / 60));
              $tempsEcoule = $minutes < 1 ? 'À l’instant' : ($minutes < 60 ? 'Il y a ' . $minutes . ' min' : ($minutes < 1440 ? 'Il y a ' . (int) floor($minutes / 60) . ' h' : dateFr($evenement['date_action'])));
              $auteur = trim((string) ($evenement['prenomUsers'] ?? '') . ' ' . (string) ($evenement['nomUsers'] ?? ''));
            ?>
              <li>
                <span class="accueil-recent-icon" aria-hidden="true">✓</span>
                <span class="accueil-recent-copy"><strong><?= e($libellesActions[$evenement['action']] ?? 'Activité du club') ?></strong><small><?= e($auteur !== '' ? $auteur : 'Équipe ATHLETIX') ?></small></span>
                <time datetime="<?= e(date('c', strtotime($evenement['date_action']))) ?>"><?= e($tempsEcoule) ?></time>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </div>
  </div>
</section>

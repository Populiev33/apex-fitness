
            <?php
            $ticket = $ticketSelectionne;
            $statusClass = match ($ticket['libelle_statut']) {
              'Terminee' => 'resolved',
              'En cours' => 'progress',
              'À valider' => 'validation',
              'En attente de piece' => 'parts',
              default => 'waiting',
            };
            ?>
            <header class="ticket-detail-heading">
              <?php if ($vueEditionTicket): ?>
                <a class="ticket-detail-back" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'])) ?>" aria-label="Retour au détail du ticket"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                <h3><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier le ticket</h3>
                <a class="ticket-detail-edit" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'])) ?>"><i class="fa-regular fa-eye" aria-hidden="true"></i> Voir</a>
              <?php else: ?>
                <a class="ticket-detail-back" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets)) ?>" aria-label="Retour à la liste des tickets"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                <h3><i class="fa-solid fa-ticket" aria-hidden="true"></i> Détails du ticket</h3>
                <?php if ($maintenance && (int) ($ticket['NumUsers_technicien'] ?? 0) === (int) $moi && !in_array($ticket['libelle_statut'], ['À valider', 'Terminee'], true)): ?>
                <a class="ticket-detail-edit" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'] . '&vue_ticket=modifier')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier</a>
                <?php endif; ?>
              <?php endif; ?>
            </header>
            <?php if ($vueEditionTicket): ?>
            <div class="ticket-edit-summary">
              <p class="ticket-detail-id">TKT-<?= (int) $ticket['Nummaint'] ?></p>
              <p><i class="fa-solid fa-cube" aria-hidden="true"></i> <?= e($ticket['nomequip']) ?> · <?= e($ticket['libelletypeequip']) ?></p>
              <span class="ticket-status-badge is-<?= e($statusClass) ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Résolu' : e($ticket['libelle_statut']) ?></span>
            </div>
            <?php if ((int) ($ticket['NumUsers_technicien'] ?? 0) === (int) $moi && !in_array($ticket['libelle_statut'], ['À valider', 'Terminee'], true)): ?>
              <form class="ticket-edit-form" method="post">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="modifier_ticket">
                <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                <label for="ticket-edit-description">Description du problème</label>
                <textarea id="ticket-edit-description" name="description" maxlength="2000" minlength="5" rows="5" required><?= e($ticket['description_problememaint']) ?></textarea>
                <label for="ticket-edit-priority">Priorité</label>
                <select id="ticket-edit-priority" name="priorite" required>
                  <?php foreach (['Basse' => 'Basse', 'Moyenne' => 'Normale', 'Haute' => 'Haute', 'Urgente' => 'Urgente'] as $codePriorite => $libellePriorite): ?>
                    <option value="<?= e($codePriorite) ?>"<?= $ticket['priorite'] === $codePriorite ? ' selected' : '' ?>><?= e($libellePriorite) ?></option>
                  <?php endforeach; ?>
                </select>
                <p class="ticket-edit-help">L’équipement et le demandeur restent liés au ticket pour conserver son historique.</p>
                <button class="ticket-action-primary" type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i> Enregistrer les modifications</button>
              </form>
            <?php elseif ($ticket['NumUsers_technicien'] === null): ?>
              <p class="ticket-edit-help">Prends d’abord ce ticket en charge pour pouvoir modifier sa description et sa priorité.</p>
            <?php elseif ((int) $ticket['NumUsers_technicien'] !== (int) $moi): ?>
              <p class="ticket-edit-help">Ce ticket est pris en charge par <?= e(trim(($ticket['technicien_prenom'] ?? '') . ' ' . ($ticket['technicien_nom'] ?? ''))) ?>. Seul ce technicien peut le modifier.</p>
            <?php else: ?>
              <p class="ticket-edit-help">Ce ticket est en attente de validation ou terminé. Ses informations ne peuvent plus être modifiées.</p>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (!$vueEditionTicket): ?>
            <div class="ticket-detail-title-row">
              <p class="ticket-detail-id">TKT-<?= (int) $ticket['Nummaint'] ?></p>
              <span class="ticket-priority-badge is-<?= e(mb_strtolower($ticket['priorite'])) ?>"><?= e($ticket['priorite']) ?> priorité</span>
            </div>
            <h4 class="ticket-detail-subject"><?= e($ticket['description_problememaint']) ?></h4>
            <?php
            $dateDebutIntervention = null;
            $dateFinIntervention = null;
            foreach ($interventionsTicket as $interventionTicket) {
              if ($dateDebutIntervention === null && in_array($interventionTicket['libelle_statut'], ['En cours', 'En attente de piece', 'À valider', 'Terminee'], true)) {
                $dateDebutIntervention = $interventionTicket['date_intervention'];
              }
              if (in_array($interventionTicket['libelle_statut'], ['À valider', 'Terminee'], true)) {
                $dateFinIntervention = $interventionTicket['date_intervention'];
              }
            }
            $ticketResolu = $ticket['libelle_statut'] === 'Terminee';
            $interventionDemarree = $dateDebutIntervention !== null || in_array($ticket['libelle_statut'], ['En cours', 'En attente de piece', 'À valider', 'Terminee'], true);
            ?>
            <ol class="ticket-progress-steps" aria-label="Avancement du ticket">
              <li class="is-complete"><span><i class="fa-solid fa-check" aria-hidden="true"></i></span><strong>Créé</strong><small><?= dateFr($ticket['date_demandemaint'], true) ?></small></li>
              <li class="<?= $interventionDemarree ? 'is-complete' : '' ?>"><span><?= $interventionDemarree ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : '2' ?></span><strong><?= $interventionDemarree ? 'En cours' : 'En attente' ?></strong><small><?= $dateDebutIntervention ? dateFr($dateDebutIntervention, true) : 'Non renseignée' ?></small></li>
              <li class="<?= $ticketResolu ? 'is-complete' : '' ?>"><span><?= $ticketResolu ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : '3' ?></span><strong><?= $ticketResolu ? 'Résolu' : 'Résolution' ?></strong><small><?= $dateFinIntervention ? dateFr($dateFinIntervention, true) : 'Non renseignée' ?></small></li>
            </ol>
            <dl class="ticket-detail-facts">
              <div><dt><i class="fa-solid fa-wrench" aria-hidden="true"></i></dt><dd>Catégorie : <?= e($ticket['libelletypeequip']) ?></dd></div>
              <div><dt><i class="fa-solid fa-cube" aria-hidden="true"></i></dt><dd>Équipement : <?= e($ticket['nomequip']) ?> <small>(<?= e($ticket['num_inventaire']) ?>)</small></dd></div>
              <div><dt><i class="fa-solid fa-user" aria-hidden="true"></i></dt><dd>Demandeur : <?= e($ticket['prenomUsers'] . ' ' . $ticket['nomUsers']) ?></dd></div>
              <div class="ticket-detail-description"><dt><i class="fa-solid fa-file-lines" aria-hidden="true"></i></dt><dd>Description<p><?= nl2br(e($ticket['description_problememaint'])) ?></p></dd></div>
            </dl>
            <div class="ticket-detail-assignment">
              <h4><i class="fa-solid fa-wrench" aria-hidden="true"></i> Intervention</h4>
              <p><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Technicien assigné :
                <?php if (!$ticket['technicien_nom']): ?>File commune
                <?php elseif ((int) $ticket['NumUsers_technicien'] === (int) $moi): ?>Vous
                <?php else: ?><?= e($ticket['technicien_prenom'] . ' ' . $ticket['technicien_nom']) ?><?php endif; ?>
              </p>
            </div>
            <section class="ticket-history-card" aria-labelledby="ticket-history-title-<?= (int) $ticket['Nummaint'] ?>">
              <h4 id="ticket-history-title-<?= (int) $ticket['Nummaint'] ?>"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historique du ticket</h4>
              <?php if (!$interventionsTicket): ?>
                <p>Aucune intervention enregistrée.</p>
              <?php else: ?>
                <ol>
                  <?php foreach ($interventionsTicket as $evenementTicket): ?>
                    <li>
                      <span class="ticket-history-dot" aria-hidden="true"></span>
                      <div><strong><?= e($evenementTicket['libelle_statut']) ?></strong>
                        <?php if (trim((string) $evenementTicket['commentaire']) !== ''): ?><p><?= nl2br(e($evenementTicket['commentaire'])) ?></p><?php endif; ?>
                        <small><?= e(trim($evenementTicket['prenomUsers'] . ' ' . $evenementTicket['nomUsers'])) ?></small>
                      </div>
                      <time><?= e(dateFr($evenementTicket['date_intervention'], true)) ?></time>
                    </li>
                  <?php endforeach; ?>
                </ol>
              <?php endif; ?>
            </section>
            <?php endif; ?>

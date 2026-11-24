<?php
$conversationSelectionnee = $boiteReception['selected'];
$nombreConversations = count($boiteReception['conversations']);
?>
<section class="employee-messages" aria-labelledby="employee-messages-title">
  <header class="employee-messages-hero">
    <span class="employee-messages-hero-icon" aria-hidden="true">
      <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M9 12.5C9 9.46 11.46 7 14.5 7h31C48.54 7 51 9.46 51 12.5v22C51 37.54 48.54 40 45.5 40H27L15 50v-10h-.5C11.46 40 9 37.54 9 34.5v-22Z"/>
        <path d="M31 35h21a6 6 0 0 1 6 6v9a6 6 0 0 1-6 6h-4l-1 6-8-6H31a6 6 0 0 1-6-6v-4"/>
      </svg>
    </span>
    <div><p class="employee-messages-eyebrow">MESSAGERIE DE L’ACCUEIL</p><h2 id="employee-messages-title">Messages <span>reçus</span></h2><p>Consultez les échanges et répondez aux adhérents.</p></div>
  </header>

  <section class="employee-messages-stats" aria-label="Indicateurs de messagerie">
    <article><span class="message-stat-icon" aria-hidden="true">✉</span><div><strong><?= (int) $boiteReception['stats']['messagesRecus'] ?></strong><span>Messages reçus</span></div></article>
    <article><span class="message-stat-icon" aria-hidden="true">◷</span><div><strong><?= (int) $boiteReception['stats']['enAttente'] ?></strong><span>En attente de réponse</span></div></article>
    <article><span class="message-stat-icon" aria-hidden="true">✓</span><div><strong><?= $boiteReception['stats']['tauxReponse'] !== null ? (int) $boiteReception['stats']['tauxReponse'] . '%' : '—' ?></strong><span>Taux de réponse</span><small>Conversations avec au moins une réponse</small></div></article>
  </section>

  <div class="employee-messages-layout">
    <section class="employee-inbox-panel" aria-label="Liste des conversations">
      <div class="employee-inbox-filters">
        <label><span aria-hidden="true">⌕</span><input id="inbox-search" type="search" placeholder="Rechercher un message…" autocomplete="off"></label>
        <select id="inbox-status" aria-label="Filtrer les conversations"><option value="">Tous les statuts</option><option value="unread">Non lus</option><option value="read">Lus</option></select>
      </div>

      <?php if (!$boiteReception['conversations']): ?>
        <p class="employee-inbox-empty">Aucun message reçu pour le moment.</p>
      <?php else: ?>
        <ul class="employee-inbox-list" id="employee-inbox-list">
          <?php foreach ($boiteReception['conversations'] as $conversation):
            $messageRacine = $conversation['messages'][0];
            $dernierMessage = $conversation['dernierMessage'];
            $nomMembre = trim($messageRacine['prenomUsers'] . ' ' . $messageRacine['nomUsers']);
            $dateMessage = strtotime($dernierMessage['date_envoi']);
            $dateCourante = date('Y-m-d');
            $dateElement = date('Y-m-d', $dateMessage) === $dateCourante
                ? date('H:i', $dateMessage)
                : (date('Y-m-d', $dateMessage) === date('Y-m-d', strtotime('yesterday')) ? 'Hier' : date('d/m', $dateMessage));
          ?>
            <li>
              <a class="employee-inbox-item<?= $conversationSelectionnee && $conversationSelectionnee['id'] === $conversation['id'] ? ' is-selected' : '' ?>" href="<?= e(url('employe/espace.php?onglet=messages&conversation=' . $conversation['id'])) ?>"
                 data-inbox-item data-search="<?= e(mb_strtolower($nomMembre . ' ' . $dernierMessage['message'])) ?>" data-status="<?= $conversation['etat']->estNonLu() ? 'unread' : 'read' ?>">
                <span class="employee-inbox-avatar"><?= e(mb_strtoupper(mb_substr($messageRacine['prenomUsers'], 0, 1) . mb_substr($messageRacine['nomUsers'], 0, 1))) ?></span>
                <span class="employee-inbox-copy"><strong><?= e($nomMembre) ?></strong><small><?= e(mb_strimwidth($dernierMessage['message'], 0, 58, '…')) ?></small></span>
                <span class="employee-inbox-meta"><time datetime="<?= e(date('c', $dateMessage)) ?>"><?= e($dateElement) ?></time><span class="employee-message-state <?= e($conversation['etat']->classeCss()) ?>"><?= e($conversation['etat']->libelle()) ?></span></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="employee-inbox-no-results" id="inbox-no-results" hidden>Aucune conversation ne correspond à la recherche.</p>
      <?php endif; ?>
      <p class="employee-inbox-count"><?= $nombreConversations ?> conversation<?= $nombreConversations > 1 ? 's' : '' ?></p>
    </section>

    <section class="employee-conversation-panel" aria-label="Conversation sélectionnée">
      <?php if (!$conversationSelectionnee): ?>
        <div class="employee-conversation-empty"><span aria-hidden="true">✉</span><h3>Sélectionnez une conversation</h3><p>Choisissez un message dans la liste pour lire l’échange et répondre.</p></div>
      <?php else:
        $messageRacine = $conversationSelectionnee['messages'][0];
        $nomMembre = trim($messageRacine['prenomUsers'] . ' ' . $messageRacine['nomUsers']);
        $dernierMessage = $conversationSelectionnee['dernierMessage'];
      ?>
        <header class="employee-conversation-header">
          <span class="employee-conversation-avatar"><?= e(mb_strtoupper(mb_substr($messageRacine['prenomUsers'], 0, 1) . mb_substr($messageRacine['nomUsers'], 0, 1))) ?></span>
          <div><h3><?= e($nomMembre) ?></h3><p><?= $conversationSelectionnee['dateMembreDepuis'] ? 'Adhérent depuis le ' . e(date('d/m/Y', strtotime($conversationSelectionnee['dateMembreDepuis']))) : 'Adhérent' ?></p></div>
          <span class="employee-message-state <?= e($conversationSelectionnee['etat']->classeCss()) ?>"><?= e($conversationSelectionnee['etat']->libelle()) ?></span>
          <details class="employee-conversation-menu"><summary aria-label="Actions de conversation">⋮</summary><form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="supprimer_message"><input type="hidden" name="message_parent" value="<?= (int) $conversationSelectionnee['id'] ?>"><button type="submit" data-confirme="Supprimer définitivement cette conversation ?">Supprimer la conversation</button></form></details>
        </header>

        <div class="employee-conversation-messages" aria-live="polite">
          <?php foreach ($conversationSelectionnee['messages'] as $message):
            $estEmploye = (int) $message['NumUsers_emetteur'] === (int) $moi;
          ?>
            <article class="employee-message-bubble<?= $estEmploye ? ' is-outgoing' : ' is-incoming' ?>">
              <strong><?= $estEmploye ? 'Vous' : e($message['prenomUsers'] . ' ' . $message['nomUsers']) ?></strong>
              <p><?= nl2br(e($message['message'])) ?></p>
              <time datetime="<?= e(date('c', strtotime($message['date_envoi']))) ?>"><?= e(dateFr($message['date_envoi'], true)) ?></time>
            </article>
          <?php endforeach; ?>
        </div>

        <form method="post" class="employee-message-reply">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" value="repondre_message">
          <input type="hidden" name="message_parent" value="<?= (int) $dernierMessage['IdMessage'] ?>">
          <input type="hidden" name="conversation" value="<?= (int) $conversationSelectionnee['id'] ?>">
          <label class="sr-only" for="employee-message-text">Votre réponse</label>
          <textarea id="employee-message-text" name="message" rows="1" maxlength="2000" placeholder="Écrire votre réponse…" required></textarea>
          <button type="submit"><span aria-hidden="true">➤</span> Envoyer</button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const recherche = document.getElementById('inbox-search');
  const filtreStatut = document.getElementById('inbox-status');
  const lignes = Array.from(document.querySelectorAll('[data-inbox-item]'));
  const aucunResultat = document.getElementById('inbox-no-results');
  if (!recherche || !filtreStatut || !aucunResultat) return;

  function filtrerConversations() {
    const terme = recherche.value.trim().toLocaleLowerCase('fr');
    let nombreVisible = 0;
    lignes.forEach(function (ligne) {
      const correspond = ligne.dataset.search.includes(terme)
        && (!filtreStatut.value || ligne.dataset.status === filtreStatut.value);
      ligne.closest('li').hidden = !correspond;
      if (correspond) nombreVisible++;
    });
    aucunResultat.hidden = nombreVisible > 0;
  }

  recherche.addEventListener('input', filtrerConversations);
  filtreStatut.addEventListener('change', filtrerConversations);
});
</script>

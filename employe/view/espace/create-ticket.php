<section class="employee-ticket-page" aria-labelledby="ticket-page-title">
  <header class="employee-ticket-hero">
    <div class="employee-ticket-hero-icon" aria-hidden="true">
      <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 13h31a6 6 0 0 1 6 6v15a6 6 0 0 1-6 6H24l-10 8v-8h-5a6 6 0 0 1-6-6V19a6 6 0 0 1 6-6Z"/>
        <path d="M28 40v4a6 6 0 0 0 6 6h13l10 8v-8h1a6 6 0 0 0 6-6V29a6 6 0 0 0-6-6h-8"/>
        <path d="M15 25h5m8 0h5m4 12h5m8 0h3"/>
      </svg>
    </div>
    <div>
      <p class="employee-ticket-eyebrow">ESPACE EMPLOYÉ <span>›</span> CRÉER UN TICKET</p>
      <h2 id="ticket-page-title">Créer un <em>ticket</em></h2>
      <p>Signalez un problème matériel à l’équipe ATHLETIX.</p>
    </div>
  </header>

  <div class="employee-ticket-notice" role="status">
    <span class="employee-ticket-notice-icon" aria-hidden="true">i</span>
    <p>Une réponse vous sera apportée dans les plus brefs délais.</p>
    <a href="<?= e(url('employe/espace.php?onglet=suivi-tickets')) ?>">
      <span aria-hidden="true">♧</span> Voir mes tickets <span aria-hidden="true">→</span>
    </a>
  </div>

  <section class="employee-ticket-card" aria-labelledby="ticket-form-title">
    <h3 id="ticket-form-title"><span aria-hidden="true">▤</span> Informations du ticket</h3>
    <?php if ($equipementsTicketables): ?>
      <form method="post" class="employee-ticket-form">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="declarer_panne">

        <div class="employee-ticket-fields">
          <div class="employee-ticket-field">
            <label for="ticket-equipement">Équipement concerné <span>*</span></label>
            <select id="ticket-equipement" name="equipement" required>
              <option value="">Sélectionnez un équipement</option>
              <?php foreach ($equipementsTicketables as $equipement): ?>
                <option value="<?= (int) $equipement['Numequip'] ?>">
                  <?= e($equipement['nomequip'] . ' — ' . $equipement['num_inventaire'] . ' · ' . $equipement['libelle_statut']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <fieldset class="employee-ticket-priorities">
            <legend>Niveau de priorité</legend>
            <label class="employee-ticket-priority is-selected">
              <input type="radio" name="priorite" value="Moyenne" checked>
              <span aria-hidden="true">ϟ</span> Normale
            </label>
            <label class="employee-ticket-priority">
              <input type="radio" name="priorite" value="Haute">
              <span aria-hidden="true">!</span> Haute
            </label>
            <label class="employee-ticket-priority is-urgent">
              <input type="radio" name="priorite" value="Urgente">
              <span aria-hidden="true">♨</span> Urgente
            </label>
          </fieldset>

          <div class="employee-ticket-field employee-ticket-description">
            <label for="ticket-description">Description du problème <span>*</span></label>
            <textarea id="ticket-description" name="description" maxlength="1000" minlength="5" rows="5" placeholder="Décrivez le problème en détail (ex. : appareil qui ne démarre pas, bruit inhabituel…)" required></textarea>
            <small><span data-ticket-description-count>0</span> / 1000 caractères</small>
          </div>
        </div>

        <div class="employee-ticket-submit-row">
          <p><span>*</span> Champ obligatoire</p>
          <button type="submit"><span aria-hidden="true">➤</span> Envoyer le ticket <span aria-hidden="true">→</span></button>
        </div>
      </form>
    <?php else: ?>
      <div class="employee-ticket-empty" role="status">
        <span aria-hidden="true">✓</span>
        <div>
          <strong><?= $equipements
            ? 'Aucun équipement disponible pour un nouveau ticket.'
            : 'Aucun équipement n’est enregistré dans l’inventaire.' ?></strong>
          <p><?= $equipements
            ? 'Tous les équipements ont déjà un ticket en cours.'
            : 'L’équipe peut ajouter du matériel depuis la gestion de l’inventaire.' ?></p>
        </div>
      </div>
    <?php endif; ?>
  </section>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var description = document.getElementById('ticket-description');
    var compteur = document.querySelector('[data-ticket-description-count]');
    if (description && compteur) {
        description.addEventListener('input', function () {
            compteur.textContent = String(description.value.length);
        });
    }
    document.querySelectorAll('.employee-ticket-priority input').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.employee-ticket-priority').forEach(function (item) {
                item.classList.toggle('is-selected', item.contains(radio) && radio.checked);
            });
        });
    });
});
</script>

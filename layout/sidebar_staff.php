<?php
/**
 * Sidebar de l'équipe : administrateur, technicien maintenance et agent d'accueil.
 *
 * Incluse par sidebar.php : même contrat de variables que la sidebar classique
 * ($sidebarItems, $sidebarActive, $sidebarHome, $sidebarLabel, $sidebarSubtitle,
 * $sidebarBrand, $sidebarMember*), plus $sidebarProfil (« Administration », « Maintenance »
 * ou « Accueil », fourni par profil_equipe()) utilisé comme sous-titre par défaut.
 *
 * Le logo ATHLETIX est toujours affiché (le $sidebarBrand des autres espaces est ignoré ici).
 *
 * Variables optionnelles propres à ce design :
 *  - $sidebarPhoto  : URL de l'image affichée en bas de la barre. Par défaut commune/img/sidebar-athlete.jpg ;
 *                     passer '' pour n'afficher que le dégradé. Si le fichier est absent, seul le dégradé s'affiche.
 *  - $sidebarLogout : URL du lien « Déconnexion ».
 *  - $item['badge'] : entier affiché dans une pastille rouge à droite de l'item (ex. messages non lus).
 */
$sidebarItems = $sidebarItems ?? [];
$sidebarActive = $sidebarActive ?? '';
$sidebarHome = $sidebarHome ?? url('accueil/index.php');
$sidebarLabel = $sidebarLabel ?? 'Navigation';
$sidebarPhoto = $sidebarPhoto ?? url('/assets/img/sidebar.png');
$sidebarLogout = $sidebarLogout ?? url('utilisateur/deconnexion.php');
$sidebarMemberEnabled = $sidebarMemberEnabled ?? false;
$sidebarMemberUnlocked = $sidebarMemberUnlocked ?? a_role('ADHERENT');
$sidebarMemberAction = $sidebarMemberAction ?? url('employe/espace.php');
$sidebarSubtitle = $sidebarSubtitle ?? $sidebarProfil ?? 'Espace équipe';

// Icônes pleines (fill = currentColor) ; certaines restent en trait via leurs propres attributs.
$staffIcons = [
    'home' => '<path d="M12 3 2 11.5h3V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-8.5h3z"/>',
    'calendar' => '<path fill-rule="evenodd" d="M8 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1zM5 10v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8zm3 2h2v2H8zm3 0h2v2h-2zm3 0h2v2h-2zm-6 3h2v2H8zm3 0h2v2h-2z"/>',
    'play' => '<path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM10 8l6 4-6 4z"/>',
    'check' => '<path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm4.7 7.7-5.5 5.5a1 1 0 0 1-1.4 0l-2.5-2.5a1 1 0 1 1 1.4-1.4l1.8 1.8 4.8-4.8a1 1 0 0 1 1.4 1.4z"/>',
    'bag' => '<path d="M6 8h12l1.2 12.2a1 1 0 0 1-1 1.1H5.8a1 1 0 0 1-1-1.1z"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M9 8V6.5a3 3 0 0 1 6 0V8"/>',
    'chart' => '<rect x="3" y="12" width="4" height="9" rx="1"/><rect x="10" y="3" width="4" height="18" rx="1"/><rect x="17" y="8" width="4" height="13" rx="1"/>',
    'card' => '<path fill-rule="evenodd" d="M5 5h14a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3zM4 9v1.5h16V9zm2 5.5V16h4v-1.5z"/>',
    'message' => '<path d="M12 3C6.5 3 2 6.6 2 11c0 2.3 1.2 4.4 3.2 5.8-.2 1.2-.8 2.4-1.7 3.5a.5.5 0 0 0 .5.8c2-.3 3.7-1 4.9-1.8.9.2 1.9.3 3.1.3 5.5 0 10-3.6 10-8s-4.5-8-10-8z"/>',
    'user' => '<circle cx="12" cy="7.5" r="4.5"/><path d="M3.5 21c0-4.2 3.8-6.5 8.5-6.5s8.5 2.3 8.5 6.5a.5.5 0 0 1-.5.5h-16a.5.5 0 0 1-.5-.5z"/>',
    'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.6 3.1-5.5 7-5.5s7 1.9 7 5.5v.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5z"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17.5 14c2.9 0 4.5 1.4 4.5 4v.5a.5.5 0 0 1-.5.5H18c.1-1.8-.5-3.4-1.7-4.5.4-.3.9-.5 1.2-.5z"/>',
    'activity' => '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-8 4 16 3-8h4"/>',
    'building' => '<path fill-rule="evenodd" d="M5 3h10a1 1 0 0 1 1 1v6h3a1 1 0 0 1 1 1v10h1.5a.5.5 0 0 1 0 1h-19a.5.5 0 0 1 0-1H4V4a1 1 0 0 1 1-1zm2 3v2h2V6zm4 0v2h2V6zM7 11v2h2v-2zm4 0v2h2v-2zm-4 4v2h2v-2zm4 0v2h2v-2z"/>',
    'tool' => '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M14 6a5 5 0 0 0-6 6L3 17l4 4 5-5a5 5 0 0 0 6-6l-3 3-4-4z"/>',
    'plus' => '<path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 5h-2v4H7v2h4v4h2v-4h4v-2h-4z"/>',
    'help' => '<g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.5 1.8c-1.2 1.2-2 1.5-2 3M12 17.5v.1"/></g>',
    'star' => '<path d="m12 2.5 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.3l-5.8 3.1 1.1-6.5L2.6 9.3l6.5-.9z"/>',
    'lock' => '<path fill-rule="evenodd" d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm-3 8V7a3 3 0 0 1 6 0v3z"/>',
    'logout' => '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
];

$staffIcon = static function (string $name) use ($staffIcons): string {
    return '<svg class="sb-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
        . ($staffIcons[$name] ?? $staffIcons['home']) . '</svg>';
};
?>
<aside class="sidebar sb-staff" id="sidebar" data-testid="sidebar">
  <?php if ($sidebarPhoto): ?>
    <div class="sb-photo" style="--sb-photo:url('<?= e($sidebarPhoto) ?>')" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="sb-tint" aria-hidden="true"></div>

  <a href="<?= e($sidebarHome) ?>" class="sb-brand" data-testid="logo-link" aria-label="ATHLETIX, tableau de bord">
    <svg class="sb-logo" viewBox="0 0 40 40" aria-hidden="true">
      <path d="M20 3 37 37H28L20 20 12 37H3Z" fill="var(--sb-lime)"/>
      <path d="M20 20 24.5 29H15.5Z" fill="var(--sb-cut)"/>
    </svg>
    <span class="sb-brand-name">ATHLETI<b>X</b></span>
    <span class="sb-brand-sub"><?= e($sidebarSubtitle) ?></span>
  </a>

  <nav class="sb-nav" aria-label="<?= e($sidebarLabel) ?>">
    <?php foreach ($sidebarItems as $item): ?>
      <?php
        $active = $sidebarActive === $item['id'];
        $badge = isset($item['badge']) ? (int) $item['badge'] : 0;
      ?>
      <a href="<?= e($item['href']) ?>" class="sb-link<?= $active ? ' active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> data-testid="nav-<?= e($item['id']) ?>">
        <?= $staffIcon($item['icon']) ?>
        <span><?= e($item['label']) ?></span>
        <?php if ($badge > 0): ?>
          <span class="sb-badge" data-testid="nav-badge-<?= e($item['id']) ?>"><?= $badge > 99 ? '99+' : $badge ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>

    <?php if ($sidebarMemberEnabled && !$sidebarMemberUnlocked): ?>
      <details class="sb-dropdown" data-testid="nav-member-space-dropdown">
        <summary class="sb-link">
          <?= $staffIcon('lock') ?>
          <span>Espace membre</span>
          <small class="sb-lock-label">Verrouillé</small>
          <svg class="sb-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <div class="sb-dropdown-menu">
          <form method="post" action="<?= e($sidebarMemberAction) ?>" onsubmit="return window.confirm('Confirmez-vous votre souhait de devenir membre ? Votre compte employé sera conservé et un espace adhérent sera ajouté.');">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="devenir_adherent">
            <button type="submit" data-testid="become-member-button">Devenir membre</button>
          </form>
        </div>
      </details>
    <?php endif; ?>
  </nav>

  <div class="sb-foot">
    <a href="<?= e($sidebarLogout) ?>" class="sb-link sb-logout" data-testid="logout-link">
      <?= $staffIcon('logout') ?>
      <span>Déconnexion</span>
    </a>
  </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('sidebar');
    var button = document.getElementById('burger');
    if (!sidebar || !button) return;

    button.addEventListener('click', function () {
        var ouvert = sidebar.classList.toggle('open');
        button.setAttribute('aria-expanded', String(ouvert));
    });

    document.addEventListener('click', function (event) {
        if (!sidebar.classList.contains('open')) return;
        if (sidebar.contains(event.target) || button.contains(event.target)) return;
        sidebar.classList.remove('open');
        button.setAttribute('aria-expanded', 'false');
    });
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
#sidebar,#sidebar div,#sidebar nav,#sidebar a,#sidebar span,#sidebar b,#sidebar small,#sidebar form,#sidebar button,#sidebar details,#sidebar summary{all:revert;box-sizing:border-box;margin:0;padding:0}
#sidebar{--sb-width:250px;--sb-accent:#2f6bff;--sb-accent-soft:#4f8bff;--sb-lime:#c6f432;--sb-cut:#111d3b;--sb-line:rgba(255,255,255,.09);width:var(--sb-width);padding:clamp(16px,3.2vh,30px) 14px 14px;background:linear-gradient(180deg,#111d3b 0%,#0c1428 55%,#0a1020 100%);border-right:1px solid var(--sb-line);display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;z-index:40;overflow:hidden;font:500 15px/1.4 Inter,system-ui,-apple-system,"Segoe UI",sans-serif;text-align:left;color:#e8edf8;transition:transform .3s ease}
#sidebar a{color:inherit;text-decoration:none;cursor:pointer}
#sidebar svg{display:block;flex-shrink:0;max-width:none}

/* Image du bas (déjà teintée en bleu) + voile pour garder les libellés lisibles */
#sidebar .sb-photo{position:absolute;inset:auto 0 0 0;height:62%;background:var(--sb-photo) center bottom/cover no-repeat;opacity:.85;pointer-events:none;-webkit-mask-image:linear-gradient(180deg,transparent 0%,#000 38%);mask-image:linear-gradient(180deg,transparent 0%,#000 38%)}
#sidebar .sb-tint{position:absolute;inset:0;pointer-events:none;background:linear-gradient(180deg,rgba(17,29,59,0) 45%,rgba(8,13,27,.55) 100%)}

/* En-tête : logo ATHLETIX */
#sidebar .sb-brand{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:8px;padding:0 8px clamp(14px,3.2vh,30px);text-align:center}
#sidebar .sb-logo{width:46px;height:46px}
#sidebar .sb-brand-name{font-size:24px;font-weight:800;letter-spacing:.3px;line-height:1.1;color:#f2f4f1}
#sidebar .sb-brand-name b{font-weight:inherit;color:var(--sb-lime)}
#sidebar .sb-brand-sub{font-size:15px;font-weight:400;line-height:1.2;color:#d3dbef}

/* Navigation */
#sidebar .sb-nav{position:relative;z-index:1;display:flex;flex:1 1 auto;flex-direction:column;gap:clamp(2px,.7vh,8px);min-height:0;overflow-y:auto;scrollbar-width:none}
#sidebar .sb-nav::-webkit-scrollbar{display:none}
#sidebar .sb-link{display:flex;align-items:center;gap:14px;padding:clamp(8px,1.5vh,14px) 16px;border-radius:12px;font-size:15px;font-weight:500;color:#e8edf8;transition:background-color .2s,color .2s,box-shadow .2s}
#sidebar .sb-link:hover{background:rgba(255,255,255,.07);color:#fff}
#sidebar .sb-link:focus-visible{outline:2px solid #9db9ff;outline-offset:2px}
#sidebar .sb-link.active{background:var(--sb-accent);color:#fff;box-shadow:0 6px 18px rgba(47,107,255,.38)}
#sidebar .sb-link span{font-size:inherit}
#sidebar .sb-ico{width:22px;height:22px}
#sidebar .sb-badge{margin-left:auto;min-width:22px;height:22px;padding:0 6px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;background:#ef4444;color:#fff;font-size:12px;font-weight:700;line-height:1}

/* Espace membre (compte employé) */
#sidebar .sb-dropdown{flex:0 0 auto}
#sidebar .sb-dropdown summary{list-style:none;cursor:pointer}
#sidebar .sb-dropdown summary::-webkit-details-marker{display:none}
#sidebar .sb-caret{width:16px;height:16px;margin-left:auto;transition:transform .2s ease}
#sidebar .sb-dropdown[open] .sb-caret{transform:rotate(180deg)}
#sidebar .sb-lock-label{margin-left:auto;color:#9fb0d6;font-size:10px;white-space:nowrap}
#sidebar .sb-dropdown[open] .sb-lock-label{margin-left:0}
#sidebar .sb-dropdown-menu{padding:4px 0 8px 52px}
#sidebar .sb-dropdown-menu button{display:block;width:100%;padding:10px 12px;border:0;border-radius:8px;background:transparent;color:#e8edf8;font:inherit;font-size:13px;text-align:left;cursor:pointer}
#sidebar .sb-dropdown-menu button:hover{background:rgba(255,255,255,.07);color:var(--sb-accent-soft)}

/* Pied : déconnexion (sans fond : l'image passe derrière jusqu'au bas de la barre) */
#sidebar .sb-foot{position:relative;z-index:1;margin-top:8px}
#sidebar .sb-logout:hover{background:rgba(239,68,68,.16);color:#ffd6d6}

@media(max-width:860px){#sidebar{transform:translateX(-100%)}#sidebar.open{transform:none;box-shadow:20px 0 60px rgba(0,0,0,.6)}}
</style>
<?php
$planningBase = 'employe/espace.php?onglet=creneaux';
if ($planningVue === 'formulaire') {
    require __DIR__ . '/../../../admin/view/planning-form.php';
} else {
    require __DIR__ . '/../../../planning/view/planning.php';
}
?>

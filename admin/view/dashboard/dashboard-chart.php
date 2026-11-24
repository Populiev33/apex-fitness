    <div class="pro-grid-main">
        <div class="pro-card">
            <div class="pro-card-header">
                <h3>Chiffre d’affaires par mois</h3>
                <span class="pro-card-tag">6 derniers mois</span>
            </div>
            <p class="sub-text">Total des paiements enregistrés chaque mois.</p>

            <?php
            $largeurTotal = 850;
            $hauteurTotal = 280;
            $margeR = 30;
            $margeL = 75;
            $margeT = 20;
            $margeB = 45;
            $largeurDessin = $largeurTotal - $margeL - $margeR;
            $hauteurDessin = $hauteurTotal - $margeT - $margeB;
            $maxCA = max(1, ...$caData);
            $points = [];
            $nbPoints = count($moisLabels);
            foreach ($moisLabels as $index => $mois) {
                $x = $margeL + ($nbPoints > 1 ? ($index / ($nbPoints - 1)) * $largeurDessin : $largeurDessin / 2);
                $y = $margeT + $hauteurDessin - (($caData[$index] / $maxCA) * $hauteurDessin);
                $points[] = [round($x, 1), round($y, 1)];
            }
            ?>

            <div class="chart-container-svg">
                <svg viewBox="0 0 <?= $largeurTotal ?> <?= $hauteurTotal ?>" class="pro-line-svg" role="img" aria-label="Chiffre d’affaires mensuel en euros sur les six derniers mois">
                    <?php for ($i = 0; $i <= 4; $i++):
                        $valeur = ($maxCA / 4) * (4 - $i);
                        $y = $margeT + ($hauteurDessin / 4) * $i;
                    ?>
                        <line x1="<?= $margeL ?>" y1="<?= $y ?>" x2="<?= $largeurTotal - $margeR ?>" y2="<?= $y ?>" stroke="#f1f5f9" stroke-width="1"/>
                        <text x="<?= $margeL - 8 ?>" y="<?= $y + 4 ?>" font-size="10" fill="#64748b" text-anchor="end"><?= number_format($valeur, 0, ',', ' ') ?> €</text>
                    <?php endfor; ?>
                    <line x1="<?= $margeL ?>" y1="<?= $margeT + $hauteurDessin ?>" x2="<?= $largeurTotal - $margeR ?>" y2="<?= $margeT + $hauteurDessin ?>" stroke="#cbd5e1" stroke-width="1.5"/>
                    <polyline fill="none" stroke="#F59E0B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="<?= implode(' ', array_map(static fn(array $point): string => $point[0] . ',' . $point[1], $points)) ?>" />
                    <?php foreach ($points as $index => [$x, $y]): ?>
                        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="4" fill="#F59E0B"><title><?= e($moisLabels[$index]) ?> : <?= number_format((float) $caData[$index], 2, ',', ' ') ?> €</title></circle>
                        <text x="<?= $x ?>" y="<?= $margeT + $hauteurDessin + 22 ?>" font-size="10" fill="#64748b" text-anchor="middle"><?= e($moisLabels[$index]) ?></text>
                    <?php endforeach; ?>
                </svg>
            </div>
        </div>

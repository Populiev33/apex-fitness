        <!-- Audience et inscriptions -->
        <div class="pro-card">
            <div class="pro-card-header">
                <h3>Audience du site</h3>
                <span class="pro-card-tag">Comptes</span>
            </div>
            <div class="pro-list">
                <div class="pro-list-item">
                    <div>
                        <strong>Sessions visiteurs</strong>
                        <span class="sub-text">Sessions enregistrées depuis l’activation du suivi</span>
                    </div>
                    <div class="badge-count"><?= (int) ($statsAudience['visiteurs'] ?? 0) ?></div>
                </div>
                <div class="pro-list-item">
                    <div>
                        <strong>Adhérents inscrits</strong>
                        <span class="sub-text">Total réel dans la base adhérents</span>
                    </div>
                    <div class="badge-count"><?= (int) ($statsAudience['inscrits'] ?? 0) ?></div>
                </div>
            </div>
        </div>

    </div>


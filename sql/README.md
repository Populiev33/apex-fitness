# Schéma et migrations SQL

- `apex_fitness.sql` décrit la base initiale.
- `migrations/` contient les évolutions incrémentales classées par date.
- Appliquer les migrations dans l’ordre et vérifier les migrations déjà exécutées avant toute reprise; ne pas modifier `vendor/`.
- `20261121_planning_cours_hebdomadaires.sql` ajoute des cours hebdomadaires issus du catalogue pour les 12 prochaines semaines.

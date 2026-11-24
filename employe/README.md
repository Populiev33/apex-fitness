# Espace employé

- `espace.php` gère les onglets et actions selon le rôle employé; les vues spécialisées sont dans `view/`.
- `EmployeController` et `model/employe.php` traitent les données du personnel.
- `profil.php` et `profil_api.php` gèrent le profil; les services sous `service/` portent le stockage d’images et la préparation du schéma d’inventaire.
- Les modifications d’onglets ou de rôles doivent préserver les contrôles d’accès de `espace.php`.

## Tableau de bord d’accueil (repères BTS SIO)

- **Modèle** : `model/TableauBordAccueil.php` lit les indicateurs, le planning et le journal avec PDO.
- **Vue** : `view/espace/dashboard-accueil.php` affiche les données et les liens d’action; elle ne fait pas de requête SQL.
- **Contrôleur** : `controller/data/EmployeStatsData.php` choisit le modèle en fonction du métier de l’employé.
- **Polymorphisme** : `TableauBordAccueil` et `TableauBordGeneral` implémentent tous deux `TableauBordEmploye`. Le contrôleur appelle `charger()` sans dépendre de la classe concrète.
- **CSS** : `public/assets/css/pages/employe-dashboard.css` contient les styles propres au tableau de bord d’accueil.

Pour la gestion des adhérents, `controller/data/EmployeMembersData.php` prépare les données, `model/AdherentRepository.php` interroge la base et `view/espace/members.php` les affiche. Les objets de statut de `model/StatutAdherent.php` appliquent le polymorphisme : la vue appelle les mêmes méthodes (`libelle()`, `classeCss()`) pour un adhérent actif, en pause, inactif ou en attente.

Pour la boîte de réception, `message_contact/model/Message.php` lit et met à jour les messages, `message_contact/controller/MessageController.php` prépare les conversations, et `view/espace/messages.php` affiche la liste et le fil de discussion. `message_contact/model/EtatConversation.php` fournit les états « lu » et « non lu » derrière la même interface.

Pour ajouter un nouveau type de tableau de bord, créer une classe qui implémente `TableauBordEmploye`, puis l’ajouter au choix du modèle dans `EmployeStatsData.php`.

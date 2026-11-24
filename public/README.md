# Point d’entrée HTTP

- `index.php` valide et normalise le chemin demandé, puis le mappe explicitement vers une page interne.
- `includes/public_icons.php` fournit les icônes réutilisables.
- `.htaccess` configure la réécriture; `.user.ini` contient une configuration locale à ne pas modifier lors du refactoring.
Le routeur fait quatre choses dans cet ordre : il lit la route, vérifie l’accès via la table ROUTES, appelle le contrôleur (dont l’action doit être dans sa whitelist), puis affiche la vue dans le bon layout. Si on te demande pourquoi paiement n’est pas accessible sans connexion, tu pointes la ligne de la table.
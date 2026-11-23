# PLAN D’IMPLÉMENTATION — JEUX ASSEMBLÉES 2026

**Fichier :** `IMPLEMENTATION_PLAN_JEUX_ASSEMBLEES_2026.md`  
**Version :** 1.1 — correction du parcours centralisé `login.php`  
**Référence absolue :** `CADRAGE_JEUX_ASSEMBLEES_2026_v2.md`  
**Environnement :** PHP natif 8.4, JSON, hébergement IONOS  
**Ticket / référence :** non fourni

---

## 1. Contexte et référence

### 1.1 Objectif

Mettre en œuvre l’application numérique de l’escape game JEUX ASSEMBLÉES 2026 : quatre équipes et quatre portes indépendantes, validation serveur des codes de cinq symboles, supervision, chronométrage, validation de l’année annoncée oralement, messages ciblés, tableau de bord en lecture seule, archivage et réinitialisation.

Les énigmes physiques, documents papier, pictogrammes et découvertes d’indices restent hors du suivi numérique.

### 1.2 Priorité des sources

1. `CADRAGE_JEUX_ASSEMBLEES_2026_v2.md` est la vérité fonctionnelle et technique absolue.
2. `instructions_globales_projets.md` fixe les règles de développement, sécurité, documentation et tests.
3. Le présent document organise les travaux et ne peut pas réduire ni modifier V2.
4. Toute modification du cadrage V2 nécessite une validation explicite.

### 1.3 Contraintes confirmées

- PHP natif 8.4, fichiers JSON, hébergement IONOS.
- Aucun framework imposé et aucune base de données relationnelle.
- Trois pages fonctionnelles PHP (supervision, tableau de bord et porte des étoiles) et une page technique commune `public/login.php`, qui centralise l'authentification et les redirections. `login.php` n'est pas une quatrième page fonctionnelle du jeu.
- Données JSON et `.env` hors de la racine publique.
- Conservation maximale du HTML Stargate et de ses animations.
- Aucune suppression de fichier n’est prévue.

### 1.4 Informations non définies à ne pas inventer

Les quatre séquences exactes, les phrases finales, la correspondance exacte des années, la durée initiale du compte à rebours, le rôle éventuel des deux ESP32, le scénario de réparation après explosion, la visibilité des messages fermés dans l’historique équipe, le format d’affichage des temps et les navigateurs/appareils du jour du jeu restent à confirmer aux étapes concernées.

---

## 2. Synthèse de la solution technique

### 2.1 Architecture cible

```text
projet/
├── public/
│   ├── login.php
│   ├── supervision.php
│   ├── tableau-de-bord.php
│   ├── porte-des-etoiles.php
│   └── assets/
│       ├── css/
│       └── js/
├── src/
├── data/
│   ├── equipe-1.json
│   ├── equipe-2.json
│   ├── equipe-3.json
│   ├── equipe-4.json
│   ├── suivi-partie.json
│   ├── messages.json
│   └── archives/
└── .env
```

Le découpage précis de `src/`, des ressources et des opérations serveur sera arrêté après inspection du projet. Aucun nom de classe, méthode, fonction ou endpoint ne sera inventé avant cet inventaire.

### 2.2 Responsabilités

| Élément | Responsabilité |
|---|---|
| `public/login.php` | Point d'entrée unique de connexion : réception de la destination initiale, traitement des clés d'accès ou de la connexion administrateur, création de session et redirection vers la destination nettoyée. |
| `public/porte-des-etoiles.php` | Vérification de la session d'équipe, porte Stargate, soumission de code, résultat visuel et notifications. |
| `public/supervision.php` | Vérification de la session administrateur, suivi des équipes, commandes de partie, chronomètre, années, messages, reset. |
| `public/tableau-de-bord.php` | Vérification de la session/droit d'accès puis projection en lecture seule des quatre équipes et de l'état global. |
| `data/equipe-1.json` à `equipe-4.json` | Code, phrase finale, année attendue et paramètres propres à l’équipe. |
| `data/suivi-partie.json` | État global, temps, tentatives, portes et progression numérique. |
| `data/messages.json` | Messages et fermeture indépendante par destinataire. |
| `.env` | Identifiants et clés d’accès. |
| `src/` | Logique serveur, sécurité, persistance et traitements métier selon la cartographie validée. |

### 2.3 Principes

- Validation des codes, années, autorisations et états exclusivement côté serveur.
- Aucun code ni aucune année attendue transmis au navigateur.
- Chemins de fichiers fixés par l’application.
- Verrouillage des écritures JSON concurrentes et écriture atomique lorsque cela est adapté.
- Actualisation des trois pages fonctionnelles toutes les cinq secondes sans rechargement complet.
- Messages affichés comme texte brut et données HTML échappées.
- Protection CSRF des actions qui modifient l’état.
- Sessions liées au rôle ou à l’équipe.
- Préservation du Stargate existant ; tout changement visuel doit être justifié et validé.
- Aucun framework, paquet ou service externe ajouté sans validation.

---

## 3. Fichiers et modules impactés

| Chemin relatif | Action | Rôle |
|---|---|---|
| `public/login.php` | Créer | Point d'entrée commun d'authentification, traitement des clés et redirection vers la page demandée. |
| `public/supervision.php` | Créer ou adapter selon l’existant | Interface privée de pilotage. |
| `public/tableau-de-bord.php` | Créer ou adapter selon l’existant | Projection en lecture seule. |
| `public/porte-des-etoiles.php` | Créer ou adapter selon l’existant | Interface d’une équipe. |
| `public/assets/css/` | Adapter/créer après inventaire | Styles des pages et états visuels. |
| `public/assets/js/` | Adapter/créer après inventaire | Interactions et actualisation. |
| `src/` | Définir après inventaire | Composants PHP serveur. |
| `data/equipe-1.json` à `equipe-4.json` | Créer ou initialiser | Configurations d’équipe. |
| `data/suivi-partie.json` | Créer ou initialiser | État de partie et chronométrage. |
| `data/messages.json` | Créer ou initialiser | Messages et états par destinataire. |
| `data/archives/` | Créer si absent | Archives de parties. |
| `.env` | Adapter uniquement si nécessaire | Identifiants et clés privées. |

### 3.1 HTML Stargate

Fichier de référence : `Stargate_V81_jambes_blanches_cables_genoux.html`. Son emplacement réel doit être relevé pendant l’étape 0. Les scènes 3D, modèles, matériaux, symboles, console, animations, chevrons, horizon, effets sonores/visuels, robot, explosion, blocage, réparation et responsive sont à préserver autant que possible.

### 3.2 Cartographie obligatoire après inspection

Pour chaque fichier existant, l’étape 0 dressera la liste des classes, fonctions et méthodes réelles, leurs responsabilités, appels et dépendances, ainsi que les éléments à conserver, modifier ou créer. Les signatures nouvelles, endpoints et contrats navigateur/serveur seront proposés puis soumis à validation. Cette cartographie complétera la présente liste.

**Aucune suppression n’est prévue.** Toute suppression éventuelle devra faire l’objet d’une proposition distincte et d’un accord explicite.

---

## 4. Étapes d’implémentation séquentielles

**Règle :** traiter une seule étape ou sous-étape à la fois. Ne jamais anticiper l’étape suivante. Les cases restent décochées jusqu’à réalisation et validation. Avant tout code, présenter les signatures, rôles, logiques, entrées/sorties et impacts, puis attendre l’accord explicite.

### Étape 0 — Reconnaissance du projet et état initial

**Objectif :** connaître l’existant avant de fixer les fichiers et méthodes à modifier.

**Fichiers :** ensemble du projet ; en particulier HTML Stargate, PHP, JS, CSS, JSON et `.env`.

#### 0.1 Inventaire

- [ ] **Rôle :** relever l’état réel du projet sans le modifier.
- [ ] **Actions :** inventorier l’arborescence ; localiser pages PHP, HTML Stargate, ressources, JSON et documentation ; repérer `.env` sans afficher ses secrets.
- [ ] **Entrées :** répertoire du projet.
- [ ] **Sorties :** liste des fichiers et de leurs rôles apparents.
- [ ] **Validation préalable :** lecture seule.
- [ ] **Impact :** aucun changement.

#### 0.2 Inspection technique

- [ ] **Rôle :** comprendre les mécanismes existants.
- [ ] **Actions :** relever les classes, fonctions et méthodes PHP réelles ; inspecter les scripts Stargate ; identifier sélection des symboles, ouverture, blocage, explosion, réparation, chargement des ressources, actualisation et responsive.
- [ ] **Entrées :** fichiers inventoriés.
- [ ] **Sorties :** cartographie des dépendances et comportements.
- [ ] **Validation préalable :** aucune modification.
- [ ] **Impact :** aucun changement.

#### 0.3 Environnement et référence initiale

- [ ] **Rôle :** établir une base de comparaison.
- [ ] **Actions :** confirmer PHP 8.4 dans l’environnement de développement ; relever le répertoire public réel chez IONOS ; relever navigateurs/appareils lorsqu’ils seront communiqués ; exécuter les vérifications disponibles sans modifier les données ; consigner les erreurs préexistantes.
- [ ] **Entrées :** environnement et informations d’hébergement disponibles.
- [ ] **Sorties :** fiche d’état initial.
- [ ] **Validation préalable :** aucune correction opportuniste.
- [ ] **Impact :** aucun changement.

#### 0.4 Cartographie cible

- [ ] **Rôle :** fixer le périmètre technique réel.
- [ ] **Actions :** établir la liste exhaustive des fichiers à créer/modifier ; lister les méthodes existantes touchées ; proposer les nouvelles classes/méthodes avec signatures ; définir contrats d’échange, droits d’accès et chemins privés ; expliquer les écarts avec l’arborescence V2.
- [ ] **Entrées :** résultats 0.1 à 0.3.
- [ ] **Sorties :** cartographie technique et liste de fichiers finalisées.
- [ ] **Validation préalable :** accord explicite sur fichiers, signatures et contrats.
- [ ] **Impact :** aucun code modifié.

**Acceptation étape 0 :** fichiers utiles localisés, dépendances Stargate connues, méthodes réelles listées, nouvelles signatures et opérations proposées sans supposition, cartographie validée.

### Étape 1 — Contrats des données JSON et configuration

**Objectif :** définir les structures de données avant leur utilisation.

**Fichiers :** six JSON actifs, `.env` et composants de lecture/validation définis à l’étape 0.

#### 1.1 Configurations d’équipe

- [ ] **Rôle :** décrire les données permanentes de chaque équipe.
- [ ] **Actions :** définir les champs code final, phrase finale, année attendue et paramètres propres ; conserver l’alphabet des neuf symboles ; prévoir cinq symboles ordonnés par équipe ; ne pas reprendre les anciennes séquences HTML.
- [ ] **Entrées :** décisions V2 et valeurs communiquées.
- [ ] **Sorties :** quatre contrats JSON.
- [ ] **Validation préalable :** quatre séquences, phrases et années confirmées.
- [ ] **Impact :** paramètres permanents définis.

#### 1.2 Suivi de partie

- [ ] **Rôle :** définir l’état global et les données de suivi.
- [ ] **Actions :** prévoir états En attente, En cours, En pause, Terminée ; horodatages démarrage/pause/reprise/fin ; durée configurée ; temps écoulé et restant, y compris négatif ; tentatives, états de portes, validation d’année et temps de résolution par équipe.
- [ ] **Entrées :** règles V2.
- [ ] **Sorties :** contrat JSON du suivi.
- [ ] **Validation préalable :** durée initiale et règles de temps confirmées.
- [ ] **Impact :** structure de suivi définie.

#### 1.3 Messages

- [ ] **Rôle :** décrire les messages et leur état par destinataire.
- [ ] **Actions :** définir identifiant, contenu, destinataires et fermeture indépendante ; conserver l’historique superviseur ; ne pas inventer la règle d’historique côté équipe.
- [ ] **Entrées :** cadrage V2.
- [ ] **Sorties :** contrat JSON messages.
- [ ] **Validation préalable :** visibilité d’un message fermé dans l’historique équipe confirmée.
- [ ] **Impact :** structure définie.

#### 1.4 Configuration privée

- [ ] **Rôle :** définir les paramètres d’accès.
- [ ] **Actions :** prévoir identifiant/mot de passe supervision, clé tableau de bord et quatre clés d’équipe ; documenter leur chargement ; ne jamais inscrire de valeur secrète dans le code ou les journaux.
- [ ] **Entrées :** règles d’accès V2.
- [ ] **Sorties :** contrat `.env`.
- [ ] **Validation préalable :** valeurs fournies par le propriétaire et fichier hors racine publique.
- [ ] **Impact :** aucune clé divulguée.

**Acceptation étape 1 :** six contrats JSON définis, valeurs métier validées, durée confirmée, configuration privée documentée sans secrets.

### Étape 2 — Persistance JSON

**Objectif :** assurer des lectures et écritures fiables.

#### 2.1 Lecture

- [ ] **Rôle :** lire les fichiers privés de façon contrôlée.
- [ ] **Actions :** utiliser les chemins internes autorisés ; détecter fichier absent, illisible ou JSON invalide ; traiter les erreurs sans exposer chemins ni données.
- [ ] **Entrées :** chemin interne autorisé.
- [ ] **Sorties :** données décodées ou erreur maîtrisée.
- [ ] **Validation préalable :** contrats étape 1 validés.
- [ ] **Impact :** lecture fiable.

#### 2.2 Écriture

- [ ] **Rôle :** préserver l’intégrité en cas d’accès simultanés.
- [ ] **Actions :** verrouiller les mises à jour ; utiliser une écriture atomique lorsque adaptée ; contrôler erreurs ; ne pas écraser silencieusement un JSON invalide.
- [ ] **Entrées :** données validées.
- [ ] **Sorties :** JSON mis à jour ou erreur contrôlée.
- [ ] **Validation préalable :** stratégie de verrouillage approuvée.
- [ ] **Impact :** persistance robuste.

#### 2.3 Tests de persistance

- [ ] **Rôle :** vérifier les cas nominaux et dégradés.
- [ ] **Actions :** tester lecture/écriture, fichier absent, JSON invalide, permissions, accès concurrents et interruption d’écriture.
- [ ] **Entrées :** composants de persistance et données isolées.
- [ ] **Sorties :** résultats de tests.
- [ ] **Validation préalable :** environnement de test distinct des données réelles.
- [ ] **Impact :** aucune donnée réelle touchée.

### Étape 3 — Point d’entrée commun, authentification, sessions et CSRF

**Objectif :** centraliser toute connexion dans `public/login.php`. Les pages fonctionnelles ne présentent pas directement de formulaire de connexion et ne créent pas elles-mêmes une session à partir d'une clé.

**Fichiers :** `public/login.php`, `public/supervision.php`, `public/tableau-de-bord.php`, `public/porte-des-etoiles.php`, `.env` et composants de session définis à l'étape 0.

#### 3.1 Redirection des pages protégées

- [ ] **Rôle :** imposer le passage par le point d'entrée commun sans afficher de formulaire de connexion sur les pages fonctionnelles.
- [ ] **Actions :** à chaque accès à `supervision.php`, `tableau-de-bord.php` ou `porte-des-etoiles.php`, vérifier l'existence d'une session valide et adaptée à la page. Si la session est absente ou insuffisante, rediriger vers `login.php` en transmettant dans un paramètre GET dédié l'URL d'origine complète : chemin local et totalité des paramètres GET. La page demandée ne doit pas être affichée avant authentification.
- [ ] **Entrées :** page demandée, paramètres GET et session courante.
- [ ] **Sorties :** page fonctionnelle affichée si la session est autorisée, sinon redirection vers `login.php` avec la destination initiale.
- [ ] **Validation préalable :** définir la liste blanche des pages locales autorisées comme destinations.
- [ ] **Impact :** parcours d'authentification unique et conservation de la destination demandée.

#### 3.2 Transmission et validation de la clé

- [ ] **Rôle :** faire traiter par `login.php` toute clé présente dans l'URL d'une page fonctionnelle, quelle que soit la page initialement demandée.
- [ ] **Actions :** si l'URL d'origine contient le paramètre `cle`, la page fonctionnelle transmet la destination complète à `login.php` pour que celui-ci traite la clé. `login.php` détermine le type d'accès correspondant à la clé fournie et la valide selon les clés définies dans `.env`. Si la clé est valide, créer la session correspondant au droit obtenu : administrateur, tableau de bord ou équipe/groupe concerné. Reconstruire ensuite l'URL d'origine en conservant tous ses paramètres GET à l'identique, à l'exception de toutes les occurrences de `cle`, puis rediriger vers cette URL nettoyée.
- [ ] **Entrées :** URL d'origine et clé fournie.
- [ ] **Sorties :** session créée puis redirection vers la destination initiale sans `cle`, ou affichage de `login.php` en cas d'échec.
- [ ] **Validation préalable :** confirmer la correspondance exacte entre chaque clé de `.env`, le rôle créé et les pages accessibles.
- [ ] **Impact :** la clé n'est plus présente dans l'URL après traitement réussi.

#### 3.3 Clé absente ou incorrecte

- [ ] **Rôle :** permettre une authentification manuelle sans perdre la destination initiale.
- [ ] **Actions :** si aucune clé n'est présente, rediriger vers `login.php` avec l'URL d'origine ; si une clé est incorrecte, afficher sur `login.php` un message d'échec ; dans les deux cas, proposer le parcours de connexion administrateur et la saisie d'une clé d'accès ; après authentification réussie, revenir à l'URL d'origine en conservant ses paramètres sauf `cle`.
- [ ] **Entrées :** URL d'origine, éventuelle clé invalide et données de connexion.
- [ ] **Sorties :** formulaire/message de connexion ou session puis redirection.
- [ ] **Validation préalable :** libellés et présentation du formulaire approuvés.
- [ ] **Impact :** l'utilisateur peut se connecter ou saisir une clé sans ressaisir sa destination.

#### 3.4 Identification du rôle sur chaque page

- [ ] **Rôle :** déterminer les droits à partir de la session et jamais à partir d'un paramètre d'URL.
- [ ] **Actions :** chacune des trois pages fonctionnelles vérifie la session avant de produire son contenu ou de traiter une action. La session indique si l'utilisateur est administrateur ou appartient à une équipe/groupe, ainsi que le droit d'accès au tableau de bord. `porte-des-etoiles.php` utilise exclusivement l'équipe liée à la session pour sélectionner les données ; `supervision.php` exige une session administrateur ; `tableau-de-bord.php` exige le droit associé à son mode d'accès. Modifier `equipe` dans l'URL après authentification ne doit jamais changer l'équipe de la session : la valeur est ignorée ou la requête est refusée.
- [ ] **Entrées :** session PHP côté serveur.
- [ ] **Sorties :** contexte d'accès autorisé ou refusé.
- [ ] **Validation préalable :** matrice des rôles, clés et pages autorisées approuvée.
- [ ] **Impact :** isolation des équipes et des fonctions d'administration.

#### 3.5 Sécurité des redirections et des sessions

- [ ] **Rôle :** empêcher les détournements de redirection et d'identité.
- [ ] **Actions :** accepter uniquement des destinations locales appartenant à la liste des pages autorisées ; refuser les URL absolues externes, schémas, chemins inattendus et tentatives de traversée ; encoder/décoder proprement l'URL d'origine dans le paramètre GET dédié ; reconstruire les paramètres sans perte ni duplication inattendue et retirer toutes les occurrences de `cle` de la destination finale ; ne jamais utiliser une destination fournie par l'utilisateur comme URL absolue de redirection ; renouveler l'identifiant de session après authentification ; configurer les cookies HttpOnly, Secure si HTTPS et SameSite adapté ; contrôler les autorisations côté serveur ; protéger les actions d'écriture contre les CSRF.
- [ ] **Entrées :** URL d'origine, session et requêtes.
- [ ] **Sorties :** redirection locale sûre et session protégée.
- [ ] **Validation préalable :** nom du paramètre GET transportant la destination et liste blanche approuvés.
- [ ] **Impact :** prévention des redirections ouvertes, de la fixation de session et des usurpations.

**Acceptation étape 3 :** aucun accès direct non authentifié aux pages fonctionnelles ; toute connexion passe par `login.php` ; une clé valide présente dans l'URL de n'importe quelle page demandée est traitée centralement ; une clé absente ou incorrecte conduit à `login.php` avec la destination initiale et, en cas d'erreur, le message d'échec ; après connexion, retour à la destination initiale avec tous ses paramètres sauf `cle` ; rôle/équipe déterminé uniquement par la session.

### Étape 4 — Contrats d’échange et traitements communs

**Objectif :** documenter les échanges navigateur/serveur avant leur implémentation.

#### 4.1 Contrats des opérations

- [ ] **Rôle :** rendre explicites les opérations de lecture et d’écriture.
- [ ] **Actions :** définir pour chaque opération méthode HTTP, paramètres, session requise, contrôle CSRF, données retournées et erreurs ; filtrer les données selon rôle/équipe ; exclure codes et années attendues des réponses navigateur.
- [ ] **Entrées :** V2 et cartographie étape 0.
- [ ] **Sorties :** contrats documentés.
- [ ] **Validation préalable :** validation explicite de chaque opération.
- [ ] **Impact :** échanges stabilisés.

#### 4.2 Logique commune

- [ ] **Rôle :** appliquer uniformément validation, erreurs et sécurité.
- [ ] **Actions :** implémenter uniquement les composants approuvés ; centraliser les textes applicatifs dans constantes/fichier dédié ; échapper les sorties HTML ; traiter les messages en texte brut ; documenter en français toute classe, méthode, fonction, interface et type créé/modifié.
- [ ] **Entrées :** contrats et signatures approuvés.
- [ ] **Sorties :** traitements partagés testables.
- [ ] **Validation préalable :** signature et logique présentées puis acceptées avant codage.
- [ ] **Impact :** règles homogènes.

### Étape 5 — Portes Stargate et codes

**Objectif :** relier les quatre portes au serveur en préservant le visuel existant.

#### 5.1 Contexte d’équipe

- [ ] **Rôle :** charger les paramètres de l’équipe authentifiée.
- [ ] **Actions :** prendre l’équipe depuis la session ; charger uniquement les données nécessaires à l’affichage ; ne transmettre ni code officiel ni année attendue.
- [ ] **Entrées :** session équipe.
- [ ] **Sorties :** données non sensibles.
- [ ] **Validation préalable :** tests d’isolation.
- [ ] **Impact :** affichage propre à l’équipe.

#### 5.2 Sélection et soumission

- [ ] **Rôle :** permettre la saisie de cinq symboles.
- [ ] **Actions :** conserver les neuf symboles ; permettre sélection/effacement ; exiger cinq symboles distincts ; rejeter incomplet et doublon ; compter uniquement la soumission effective d’une séquence complète.
- [ ] **Entrées :** séquence navigateur.
- [ ] **Sorties :** résultat et compteur selon règles.
- [ ] **Validation préalable :** retours visuels et contrat de soumission validés.
- [ ] **Impact :** comptage conforme.

#### 5.3 Validation serveur

- [ ] **Rôle :** comparer la séquence à la configuration privée.
- [ ] **Actions :** distinguer code exact, cinq bons symboles mauvais ordre et combinaison incorrecte ; enregistrer état et tentative ; bloquer toute progression si partie non En cours ; ne jamais révéler le code attendu.
- [ ] **Entrées :** séquence complète, session équipe, état global.
- [ ] **Sorties :** résultat fonctionnel.
- [ ] **Validation préalable :** quatre codes officiels confirmés.
- [ ] **Impact :** validation serveur et portes indépendantes.

#### 5.4 Effets visuels

- [ ] **Rôle :** déclencher le scénario Stargate correspondant.
- [ ] **Actions :** code exact : ouverture et phrase finale ; bons symboles mauvais ordre : ouverture, robot sur passerelle puis explosion ; combinaison incorrecte : tentative d’ouverture puis porte bloquée ; préserver animations et effets ; ne modifier la réparation qu’après inspection et validation du scénario.
- [ ] **Entrées :** résultat serveur.
- [ ] **Sorties :** séquence visuelle correspondante.
- [ ] **Validation préalable :** correspondance résultat/animation vérifiée.
- [ ] **Impact :** expérience existante conservée.

#### 5.5 Tests des portes

- [ ] **Rôle :** vérifier codes et indépendance.
- [ ] **Actions :** tester quatre codes exacts, permutations, combinaisons incorrectes, symboles invalides, incomplets, doublons, compteurs, persistance après rechargement, isolation, pause, attente et fin.
- [ ] **Entrées :** données de test.
- [ ] **Sorties :** rapport de tests.
- [ ] **Validation préalable :** environnement isolé.
- [ ] **Impact :** aucune donnée de partie réelle modifiée.

### Étape 6 — Cycle de vie et chronométrage

**Objectif :** gérer les quatre états et les deux compteurs.

#### 6.1 États

- [ ] **Rôle :** appliquer En attente, En cours, En pause, Terminée.
- [ ] **Actions :** initialiser en attente ; démarrage réservé au superviseur ; pause/reprise ; tout arrêt termine ; aucune reprise après fin ; résultats consultables.
- [ ] **Entrées :** action autorisée et état courant.
- [ ] **Sorties :** état enregistré.
- [ ] **Validation préalable :** transitions approuvées.
- [ ] **Impact :** cycle de vie serveur.

#### 6.2 Compteurs

- [ ] **Rôle :** calculer compte à rebours et temps écoulé.
- [ ] **Actions :** utiliser durée JSON ; démarrer ensemble ; suspendre pendant pause ; reprendre sans remise à zéro ; exclure pauses ; continuer en négatif après zéro ; ne pas arrêter automatiquement à zéro.
- [ ] **Entrées :** durée et horodatages.
- [ ] **Sorties :** deux valeurs temporelles.
- [ ] **Validation préalable :** durée et format d’affichage confirmés.
- [ ] **Impact :** temps cohérents.

#### 6.3 Arrêt

- [ ] **Rôle :** appliquer les deux causes de fin prévues.
- [ ] **Actions :** arrêt manuel après confirmation ; fin automatique à la quatrième année correctement validée ; bloquer nouvelles soumissions et validations après fin ; supervision consultative, reset seul autorisé.
- [ ] **Entrées :** confirmation ou quatrième résolution.
- [ ] **Sorties :** état Terminée et horodatage de fin.
- [ ] **Validation préalable :** règles d’arrêt approuvées.
- [ ] **Impact :** aucune reprise après arrêt.

### Étape 7 — Validation de l’année finale

#### 7.1 Saisie et comparaison

- [ ] **Rôle :** valider l’année annoncée oralement.
- [ ] **Actions :** sélectionner équipe ; saisir année ; comparer côté serveur ; permettre correction après erreur ; bloquer si partie non En cours, terminée ou équipe déjà résolue.
- [ ] **Entrées :** équipe et année saisie.
- [ ] **Sorties :** résultat correct/incorrect.
- [ ] **Validation préalable :** années attendues confirmées.
- [ ] **Impact :** aucune année attendue exposée.

#### 7.2 Affichage et persistance

- [ ] **Rôle :** rendre le résultat visible et définitif.
- [ ] **Actions :** mauvaise année : scintillement rouge ; bonne année : scintillement vert puis bloc vert permanent avec « Énigme résolue » ; enregistrer année saisie, instant et temps de résolution ; rendre la réussite irréversible jusqu’au reset.
- [ ] **Entrées :** résultat serveur.
- [ ] **Sorties :** état persistant et retour visuel.
- [ ] **Validation préalable :** états visuels approuvés.
- [ ] **Impact :** résolution durable.

#### 7.3 Temps de résolution et fin auto

- [ ] **Rôle :** calculer le temps individuel et terminer la partie au bon moment.
- [ ] **Actions :** calculer depuis le démarrage jusqu’à validation correcte, pauses exclues ; pas de temps final pour équipe non résolue ; terminer lorsque les quatre équipes sont résolues.
- [ ] **Entrées :** horodatages et pauses.
- [ ] **Sorties :** temps individuel et éventuelle fin.
- [ ] **Validation préalable :** calcul approuvé.
- [ ] **Impact :** résultats temporels conformes.

### Étape 8 — Supervision

#### 8.1 Suivi des équipes

- [ ] **Rôle :** présenter l’état des quatre équipes.
- [ ] **Actions :** afficher état de porte, tentatives, résolution d’année et état global ; ne proposer ni ouverture manuelle de porte ni validation manuelle de code Stargate.
- [ ] **Entrées :** suivi autorisé.
- [ ] **Sorties :** vue superviseur.
- [ ] **Validation préalable :** données et libellés approuvés.
- [ ] **Impact :** supervision conforme à V2.

#### 8.2 Commandes de partie

- [ ] **Rôle :** piloter chronomètre et état.
- [ ] **Actions :** démarrer, pause, reprendre, arrêt avec confirmation ; afficher les deux compteurs et l’état ; désactiver les commandes incompatibles avec l’état.
- [ ] **Entrées :** commandes superviseur authentifié.
- [ ] **Sorties :** état actualisé.
- [ ] **Validation préalable :** étape 6 validée.
- [ ] **Impact :** commandes cohérentes.

#### 8.3 Années et résultats finaux

- [ ] **Rôle :** gérer validation orale et restitution.
- [ ] **Actions :** sélectionner équipe, saisir année, afficher résultat ; à la fin afficher « PARTIE TERMINÉE », temps final, statut de chaque équipe, temps de résolution, tentatives et année validée pour les équipes résolues ; conserver historique messages.
- [ ] **Entrées :** résultats enregistrés.
- [ ] **Sorties :** écran de résultats.
- [ ] **Validation préalable :** format des temps confirmé.
- [ ] **Impact :** bilan consultable après fin.

### Étape 9 — Tableau de bord

- [ ] **Rôle :** présenter la projection en lecture seule.
- [ ] **Actions :** session issue de `login.php` ; afficher les quatre équipes, état de chaque porte, nombre de tentatives, indicateur porte ouverte, résolution de l’année et état global ; n’afficher aucun code d’activation ni réponse attendue ; aucune commande de contrôle.
- [ ] **Entrées :** suivi filtré.
- [ ] **Sorties :** projection en lecture seule.
- [ ] **Validation préalable :** liste V2 vérifiée.
- [ ] **Impact :** aucune fuite d’information sensible et aucune écriture possible.

### Étape 10 — Messages et notifications

#### 10.1 Envoi

- [ ] **Rôle :** permettre au superviseur de cibler une ou plusieurs équipes.
- [ ] **Actions :** sélectionner destinataires, saisir texte, valider contenu et destinataires, enregistrer message et état par destinataire ; réserver l’envoi au superviseur.
- [ ] **Entrées :** texte et sélection.
- [ ] **Sorties :** message enregistré.
- [ ] **Validation préalable :** contrat JSON messages approuvé.
- [ ] **Impact :** envoi ciblé.

#### 10.2 Affichage équipe

- [ ] **Rôle :** afficher et fermer les notifications.
- [ ] **Actions :** retourner uniquement les messages de la session ; afficher en texte brut ; rester visible jusqu’à fermeture ; enregistrer la fermeture pour le seul destinataire ; permettre consultation/fermeture en pause.
- [ ] **Entrées :** session équipe et messages.
- [ ] **Sorties :** notifications et états.
- [ ] **Validation préalable :** règle d’historique des messages fermés confirmée.
- [ ] **Impact :** fermeture indépendante.

#### 10.3 Historique

- [ ] **Rôle :** conserver la traçabilité.
- [ ] **Actions :** rendre l’historique consultable en supervision après fin ; le préserver jusqu’à archivage.
- [ ] **Entrées :** `messages.json`.
- [ ] **Sorties :** historique.
- [ ] **Validation préalable :** présentation validée.
- [ ] **Impact :** conservation jusqu’au reset.

### Étape 11 — Actualisation à cinq secondes

#### 11.1 Actualisation des données

- [ ] **Rôle :** synchroniser les trois pages fonctionnelles sans rechargement complet.
- [ ] **Actions :** actualiser état global, compteurs, pause/reprise/fin, messages, notifications, tentatives, portes et validations d’années ; filtrer selon rôle et équipe.
- [ ] **Entrées :** réponses serveur.
- [ ] **Sorties :** interfaces actualisées toutes les cinq secondes.
- [ ] **Validation préalable :** contrats lecture validés.
- [ ] **Impact :** cohérence des interfaces.

#### 11.2 Erreurs de communication

- [ ] **Rôle :** maintenir un affichage maîtrisé en cas d’échec.
- [ ] **Actions :** traiter réseau indisponible, réponse invalide et session expirée ; ne pas transformer une erreur de lecture en écriture ; ne pas révéler d’information technique.
- [ ] **Entrées :** erreurs de communication.
- [ ] **Sorties :** état d’interface maîtrisé.
- [ ] **Validation préalable :** comportement d’erreur approuvé.
- [ ] **Impact :** robustesse.

### Étape 12 — Archivage et réinitialisation

#### 12.1 Archivage

- [ ] **Rôle :** préserver la partie avant reset.
- [ ] **Actions :** demander confirmation ; créer `archives/AAAA-MM-JJ_HH-MM/` ; archiver les six JSON actifs ; vérifier présence et lisibilité ; ne pas modifier `.env`.
- [ ] **Entrées :** six JSON et confirmation.
- [ ] **Sorties :** archive complète.
- [ ] **Validation préalable :** stratégie d’échec partiel approuvée.
- [ ] **Impact :** données historiques conservées.

#### 12.2 Réinitialisation

- [ ] **Rôle :** préparer une nouvelle partie.
- [ ] **Actions :** recréer les quatre JSON équipe à l’identique ; réinitialiser suivi ; créer messages vides ; mettre En attente ; arrêter et remettre chronomètre à sa valeur initiale ; ne pas démarrer automatiquement ; conserver `.env` intact.
- [ ] **Entrées :** archive vérifiée.
- [ ] **Sorties :** six JSON actifs prêts pour une nouvelle partie.
- [ ] **Validation préalable :** comparaison des configurations avant/après.
- [ ] **Impact :** nouveau jeu en attente.

#### 12.3 Tests reset

- [ ] **Rôle :** vérifier absence de perte.
- [ ] **Actions :** vérifier les six archives, égalité des quatre configurations, suivi initial, messages vides, état En attente, chronomètre arrêté et `.env` inchangé.
- [ ] **Entrées :** données de test isolées.
- [ ] **Sorties :** rapport.
- [ ] **Validation préalable :** environnement isolé.
- [ ] **Impact :** données réelles préservées.

### Étape 13 — Tests et recette

#### 13.1 Tests d’intégration et sécurité

- [ ] **Rôle :** vérifier les échanges et contrôles.
- [ ] **Actions :** tester clés absentes/invalides, sessions croisées, modification URL, authentification, CSRF, absence de code/année attendus côté navigateur, échappement HTML, texte brut, écritures concurrentes, erreurs JSON, inaccessibilité publique de `.env` et `data/`.
- [ ] **Entrées :** application intégrée de test.
- [ ] **Sorties :** rapport d’intégration/sécurité.
- [ ] **Validation préalable :** secrets de test uniquement.
- [ ] **Impact :** aucune exposition ni écriture non autorisée.

#### 13.2 Recette fonctionnelle et visuelle

- [ ] **Rôle :** valider les parcours réels.
- [ ] **Actions :** tester `login.php` et les trois pages fonctionnelles sur les navigateurs/appareils communiqués ; vérifier responsive, scène 3D, modèles, matériaux, symboles, console, animations, chevrons, horizon, sons, robot, explosion, blocage, réparation selon scénario confirmé, scintillements année, actualisation cinq secondes, pause et fin.
- [ ] **Entrées :** application complète et appareils cibles.
- [ ] **Sorties :** procès-verbal de recette.
- [ ] **Validation préalable :** liste des appareils et navigateurs communiquée.
- [ ] **Impact :** validation avant déploiement.

**Acceptation étape 13 :** tests d'intégration, de sécurité et recette fonctionnelle exécutés ; aucun défaut bloquant ; parcours de connexion et redirections conformes ; portes indépendantes ; chronométrage conforme ; validations définitives ; messages ciblés ; archivage vérifié ; actualisation fonctionnelle et expérience Stargate préservée.

### Étape 14 — Documentation et déploiement

#### 14.1 Documentation

- [ ] **Rôle :** permettre la maintenance et l’exploitation.
- [ ] **Actions :** documenter arborescence, responsabilités, contrats JSON, noms des paramètres `.env` sans valeurs, parcours d’accès, commandes, sauvegarde/restauration ; documenter en français chaque classe, interface, méthode, fonction et type créé/modifié.
- [ ] **Entrées :** implémentation validée.
- [ ] **Sorties :** documentation technique.
- [ ] **Validation préalable :** comparaison avec le code réel.
- [ ] **Impact :** exploitation documentée.

#### 14.2 Préparation IONOS

- [ ] **Rôle :** déployer sans exposer les données privées.
- [ ] **Actions :** confirmer document root ; placer pages/ressources dans `public/` ; maintenir `src/`, `data/`, `.env` hors racine publique ; vérifier permissions JSON, HTTPS et cookies ; vérifier chemins et réaliser tests de fumée.
- [ ] **Entrées :** accès et informations IONOS du propriétaire.
- [ ] **Sorties :** application déployée et vérifiée.
- [ ] **Validation préalable :** procédure de sauvegarde et déploiement approuvée.
- [ ] **Impact :** mise en service sécurisée.

---

## 5. Stratégie globale de tests

### 5.1 Niveaux

1. Tests d’intégration des pages, sessions, JSON et traitements serveur.
2. Tests de sécurité des accès, sessions, CSRF, isolation et exposition de données.
3. Tests fonctionnels et visuels de `login.php`, des trois pages fonctionnelles et de l’expérience Stargate.

Aucun test unitaire n'est prévu pour ce projet. Les vérifications sont réalisées au niveau intégration, sécurité et parcours fonctionnels, avec une recette manuelle consignée.

### 5.2 Matrice minimale

| Domaine | Cas nominaux | Erreurs et limites |
|---|---|---|
| Codes | Code exact de chaque équipe | Mauvais ordre, combinaison incorrecte, symbole invalide, incomplet, doublon |
| Tentatives | Soumission complète | Sélection/effacement non comptés, incomplet rejeté |
| Portes | Quatre portes indépendantes | Aucune action croisée entre équipes |
| Partie | Attente, démarrage, pause, reprise, fin | Action interdite selon état |
| Chronomètre | Temps écoulé et compte à rebours | Pause exclue, négatif, zéro sans arrêt |
| Année | Bonne année | Mauvaise année, correction, validation définitive |
| Fin automatique | Quatre années validées | Pas d’arrêt avant la quatrième |
| Messages | Envoi ciblé | Destinataire incorrect, fermeture indépendante |
| Accès | Sessions valides | Clé invalide, URL modifiée, session croisée, CSRF |
| JSON | Lecture/écriture | Absent, invalide, permissions, concurrence |
| Archivage | Six fichiers archivés | Échec partiel, archive incomplète, reset incorrect |
| Tableau de bord | États V2 affichés | Aucun code/réponse ni commande |
| Actualisation | Mise à jour toutes les 5 s | Réseau, réponse invalide, session expirée |
| Stargate | Animations préservées | Régression visuelle, sonore ou responsive |

### 5.3 Conditions de validation

Chaque test doit être reproductible. Les données de test sont séparées des données réelles. Les tests de concurrence n’utilisent jamais les fichiers de production. Toute anomalie est documentée avec étapes de reproduction et résultat attendu. Un test non exécuté ne peut pas être déclaré réussi.

---

## 6. Précautions transverses

### 6.1 Régressions

- Préserver le HTML Stargate et ses comportements.
- Ne pas réécrire une fonctionnalité existante sans nécessité démontrée.
- Aucune refactorisation opportuniste.
- Modifier uniquement le périmètre validé.
- Comparer les comportements visuels avant/après.
- Ne supprimer aucun fichier sans accord explicite.

### 6.2 Sécurité

- `.env` et JSON hors racine publique.
- Aucun code ou année attendue envoyé au navigateur.
- Autorisations contrôlées côté serveur à chaque opération.
- Isolation stricte des équipes.
- Protection CSRF des écritures.
- Échappement HTML et messages en texte brut.
- Validation stricte des entrées.
- Chemins de fichiers fixés par l’application.
- Aucune donnée sensible dans les journaux.
- Aucune divulgation de chemins, secrets ou détails techniques dans les erreurs publiques.

### 6.3 Intégrité et performance

- Verrouillage des mises à jour JSON et écriture atomique lorsque adaptée.
- Vérifier l’archive avant reset.
- Recréer les quatre JSON équipe à l’identique.
- Ne pas modifier automatiquement codes, phrases, années ou paramètres.
- Ne pas modifier `.env` lors du reset.
- Transmettre uniquement les données nécessaires à chaque page.
- Respecter l’actualisation à cinq secondes sans rechargement complet.
- Préserver la fluidité de Stargate.
- Ne pas ajouter de dépendance sans validation.

### 6.4 Qualité et documentation

- Commentaires/docblocks en français pour chaque classe, interface, méthode, fonction et type créé/modifié.
- Textes applicatifs dans les constantes ou fichier dédié conformément aux instructions globales.
- Méthodes simples, lisibles et limitées à une responsabilité.
- Pas de vérifications redondantes sans nécessité.
- Code complet, sans omission ni TODO générique.
- Après chaque sous-étape : expliquer fichiers modifiés, raisons, impacts et procédure exacte de validation.

---

## 7. Règles d’exécution

1. Ce plan est subordonné au cadrage V2.
2. Aucune étape ne commence sans validation explicite.
3. Une seule sous-étape est traitée à la fois.
4. Avant chaque développement, présenter signature, rôle, logique, entrées/sorties et impacts puis attendre l’accord.
5. Aucun code avant validation du cadrage et du plan.
6. Aucune modification hors périmètre approuvé.
7. Toute ambiguïté est signalée, jamais comblée par supposition.
8. Les cases sont cochées uniquement après réalisation et validation.
9. Une étape est terminée seulement lorsque ses critères d’acceptation sont atteints.
10. Tout code livré est complet, documenté et accompagné de ses tests/procédures de validation.

---

## 8. Points à confirmer

| Point | Étape concernée | Décision attendue |
|---|---|---|
| Quatre séquences de cinq symboles | 1 | Valeurs exactes validées |
| Quatre phrases finales | 1 | Source et texte exacts |
| Années attendues par équipe | 1 | Correspondance exacte |
| Durée initiale du compte à rebours | 1 | Valeur communiquée |
| Rôle des deux ESP32 | 0 puis 4 | Aucun rôle inventé |
| Réparation après explosion | 0 puis 5 | Vérification HTML et validation |
| Historique d’un message fermé côté équipe | 1 puis 10 | Règle confirmée |
| Format d’affichage des temps | 6 | Format validé |
| Navigateurs et appareils du jeu | 0 puis 13 | Liste communiquée |

---

**Fin du document.**  
**Aucune implémentation ne commence avant validation explicite de ce plan et de la cartographie technique issue de l’étape 0.**

# PLAN D'IMPLÉMENTATION — `public/login.php`

**Projet :** JEUX ASSEMBLÉES 2026  
**Référence fonctionnelle :** `CADRAGE_JEUX_ASSEMBLEES_2026_v3.md`, version 3.0  
**Périmètre :** création du point d'entrée unique d'authentification `public/login.php` et des composants PHP strictement nécessaires à son fonctionnement.  
**Statut :** plan de réalisation — aucun code n'est produit dans ce document.

---

## 1. Contexte & Référence

### 1.1 Objectif

Créer la page `public/login.php`, seul point d'entrée chargé de traiter les authentifications de l'application.

La page doit permettre :
- l'accès à la porte de l'équipe 1, 2, 3 ou 4 avec la clé propre à cette équipe ;
- l'accès au tableau de bord avec sa clé dédiée et l'identité `equipe=0` ;
- l'accès à la supervision avec l'identifiant et le mot de passe administrateur ;
- le traitement d'une demande provenant d'une page fonctionnelle, avec conservation de sa destination et de ses paramètres GET utiles ;
- une connexion directe lorsque `login.php` est ouvert sans URL d'origine ;
- la création d'une session PHP contenant une identité fiable ;
- la suppression de `cle` de l'URL après authentification réussie.

### 1.2 Documents de référence et ordre de priorité

1. `CADRAGE_JEUX_ASSEMBLEES_2026_v3.md` — référence fonctionnelle et technique de l'application.
2. `instructions_globales_projets.md` — règles de réalisation, de documentation et de progression.
3. Le présent plan — source de vérité technique pour l'exécution de la fonctionnalité `login.php`.

Aucune règle fonctionnelle du cadrage n'est modifiée par ce plan.

### 1.3 Règles du cadrage applicables à `login.php`

- L'authentification est centralisée dans `login.php` (§ 5.1 et § 11.1).
- Les pages fonctionnelles sont `porte-des-etoiles.php`, `tableau-de-bord.php` et `supervision.php`.
- Une page fonctionnelle sans session autorisée transmet à `login.php` l'URL d'origine et ses paramètres GET.
- La destination est conservée pendant la connexion puis restaurée après authentification, avec suppression du paramètre `cle`.
- La clé présentée doit correspondre à la page demandée, au paramètre `equipe` et au type d'accès.
- `equipe=0` identifie exclusivement l'accès au tableau de bord ; ce n'est pas une cinquième équipe.
- Les identifiants directs des équipes sont `equipe1`, `equipe2`, `equipe3` et `equipe4`.
- La supervision exige les identifiants administrateur.
- L'identité de session détermine le rôle et, pour les équipes, le numéro d'équipe.
- Les secrets sont conservés dans `.env`, hors de `public/`.
- Les cookies de session utilisent `HttpOnly`, `Secure` lorsque le site est servi en HTTPS et un `SameSite` adapté.
- La session est une session navigateur, sans connexion persistante, et doit être protégée contre la fixation de session.
- Une URL de retour externe ou altérée ne doit jamais provoquer de redirection externe.
- Les clés ne doivent apparaître ni dans les journaux ni dans les messages d'erreur.
- Les sorties HTML dynamiques sont échappées ; les messages applicatifs sont affichés comme du texte brut.
- Les entrées sont validées côté serveur.
- Aucune fonctionnalité dépendant d'une version PHP non vérifiée ne doit être choisie.

### 1.4 Périmètre strict

**Inclus :**
- traitement GET et POST de `public/login.php` ;
- affichage des formulaires d'accès ;
- lecture des secrets depuis `.env` ;
- vérification des clés et des identifiants administrateur ;
- validation de la destination de retour ;
- création de la session ;
- redirection interne après authentification ;
- affichage d'un message d'échec sans divulgation de secret ;
- protection CSRF du formulaire POST ;
- sécurisation des paramètres de session et des sorties HTML.

**Exclus :**
- création de `supervision.php`, `tableau-de-bord.php` et `porte-des-etoiles.php` ;
- contrôle de session dans les trois pages fonctionnelles (elles appliqueront ultérieurement les identités créées ici) ;
- déconnexion administrateur, dont l'action est portée par la supervision selon le cadrage ;
- gestion des fichiers JSON, du chronomètre, des énigmes, des scores, des messages et des ESP32 ;
- création ou modification des clés et secrets dans `.env` ;
- mise en place d'un système de connexion persistante ;
- ajout d'une base de données ou d'un framework PHP.

### 1.5 Absence de tests

À la demande explicite du propriétaire du projet, aucune activité de test ne figure dans ce plan : pas de tests unitaires, d'intégration, de sécurité, manuels ou visuels ; aucun fichier de test et aucune commande de test ne seront ajoutés ou exécutés.

---

## 2. Synthèse de la solution technique

### 2.1 Emplacement et architecture

Arborescence cible utilisée pour cette fonctionnalité :

```text
projet/
├── public/
│   └── login.php
├── src/
│   ├── Authentification.php
│   └── ConstantesAuthentification.php
└── .env
```

Fichiers :
- `public/login.php` : contrôleur de la requête et rendu de la page de connexion.
- `src/Authentification.php` : fonctions de chargement de configuration, validation de l'accès, gestion de session et redirection.
- `src/ConstantesAuthentification.php` : textes et messages affichés par la page.

Le découpage dans `src/` respecte l'arborescence du cadrage et évite de mélanger le traitement HTTP, les règles d'authentification et les textes applicatifs. Aucun framework ni paquet tiers n'est introduit.

### 2.2 Accès pris en charge

| Type d'accès | Données présentées | Destination sans URL d'origine | Identité créée |
|---|---|---|---|
| Équipe 1 | Identifiant `equipe1` et clé de l'équipe 1 | `porte-des-etoiles.php?equipe=1` | équipe 1 |
| Équipe 2 | Identifiant `equipe2` et clé de l'équipe 2 | `porte-des-etoiles.php?equipe=2` | équipe 2 |
| Équipe 3 | Identifiant `equipe3` et clé de l'équipe 3 | `porte-des-etoiles.php?equipe=3` | équipe 3 |
| Équipe 4 | Identifiant `equipe4` et clé de l'équipe 4 | `porte-des-etoiles.php?equipe=4` | équipe 4 |
| Tableau de bord | Clé dédiée au tableau de bord | `tableau-de-bord.php?equipe=0` | accès tableau de bord, `equipe=0` |
| Administrateur | Identifiant et mot de passe administrateur | `supervision.php` | administrateur |

Le cadrage ne fixe pas le libellé exact des champs, ni le nom de la variable `.env` contenant la clé du tableau de bord, ni les noms des variables de l'identifiant et du mot de passe administrateur. Ces éléments devront être repris des noms réellement retenus dans le fichier `.env` du projet ; ils ne seront pas renommés silencieusement par l'implémentation.

### 2.3 Présentation des formulaires

La page propose trois zones d'accès distinctes afin de ne pas confondre les types d'identifiants :

1. **Connexion d'une équipe**
   - champ `identifiant` ;
   - valeurs acceptées : `equipe1`, `equipe2`, `equipe3`, `equipe4` ;
   - champ `cle` ;
   - la clé doit correspondre exactement à l'équipe désignée par l'identifiant.

2. **Accès au tableau de bord**
   - champ `cle` dédiée au tableau de bord ;
   - aucune identité d'équipe n'est demandée ;
   - l'identité créée est l'accès tableau de bord correspondant à `equipe=0`.

3. **Connexion administrateur**
   - champ `identifiant` administrateur ;
   - champ `mot_de_passe` administrateur ;
   - l'identité créée est administrateur.

Les noms ci-dessus désignent les champs HTTP du formulaire, pas des noms de variables `.env`. Les libellés visibles et messages proviennent de `ConstantesAuthentification.php`.

### 2.4 Contrat des identités de session

La session ne contient que l'identité validée côté serveur. Elle ne reprend jamais un rôle ou un numéro d'équipe fourni librement par le navigateur.

| Accès authentifié | Valeur de rôle | Valeur `equipe` |
|---|---|---:|
| Administrateur | `administrateur` | `null` |
| Équipe 1 | `equipe` | `1` |
| Équipe 2 | `equipe` | `2` |
| Équipe 3 | `equipe` | `3` |
| Équipe 4 | `equipe` | `4` |
| Tableau de bord | `tableau_de_bord` | `0` |

Structure contractuelle de `$_SESSION['authentification']` :

```php
// Administrateur
[
    'role' => 'administrateur',
    'equipe' => null,
]

// Équipe
[
    'role' => 'equipe',
    'equipe' => 1, // valeur 1, 2, 3 ou 4
]

// Tableau de bord
[
    'role' => 'tableau_de_bord',
    'equipe' => 0,
]
```

Aucune clé ni aucun mot de passe n'est conservé dans la session.

### 2.5 Contrat des destinations

Seules les destinations internes suivantes sont autorisées :

- `porte-des-etoiles.php`, avec un paramètre `equipe` entier compris entre 1 et 4 ;
- `tableau-de-bord.php`, avec `equipe=0` ;
- `supervision.php`, sans paramètre d'équipe.

Règles :
- toute destination absolue, externe, relative à un autre répertoire ou contenant une autorité/host est refusée ;
- toute page non listée est refusée ;
- `cle` est supprimé de la destination finale ;
- les autres paramètres GET sont conservés uniquement après validation de leur cohérence avec la page et l'identité authentifiée ;
- l'identité de session prévaut toujours sur une valeur GET ;
- la redirection de secours sans destination d'origine dépend du type d'accès : porte de l'équipe, tableau de bord ou supervision.

### 2.6 Configuration `.env`

Le cadrage impose les données suivantes dans `.env`, hors de `public/` :
- identifiant administrateur ;
- mot de passe administrateur ;
- clé dédiée au tableau de bord ;
- `CLE_EQUIPE_1` ;
- `CLE_EQUIPE_2` ;
- `CLE_EQUIPE_3` ;
- `CLE_EQUIPE_4`.

Les clés sont renseignées manuellement par le propriétaire du projet et peuvent être modifiées entre deux parties. La fonctionnalité de connexion ne les génère pas et ne les modifie pas.

Le chargement doit être adapté au format réel du `.env` du projet. Le cadrage ne spécifie pas le format de sérialisation ni les noms exacts des deux variables administrateur et de la variable de clé du tableau de bord : ces noms doivent être lus dans le fichier de configuration effectivement fourni, sans afficher leurs valeurs dans les sorties ou journaux. Aucun nom de variable non confirmé ne doit être inventé dans le code.

### 2.7 Compatibilité PHP

Avant toute écriture de code, relever la version PHP effectivement activée chez IONOS. Les syntaxes, fonctions natives et options de session retenues doivent être compatibles avec cette version. Aucune version minimale ne sera présumée. Les signatures proposées dans ce plan n'imposent donc pas de typage scalaire ni de type de retour PHP ; les contrats de types seront documentés en français dans les blocs PHPDoc, puis les déclarations typées ne seront ajoutées que si la version constatée les prend en charge.

---

## 3. Fichiers et modules impactés

| Chemin | Action | Responsabilité |
|---|---|---|
| `public/login.php` | Créer | Point d'entrée HTTP, orchestration GET/POST et affichage |
| `src/Authentification.php` | Créer | Configuration, contrôle des accès, sessions, destinations et redirections |
| `src/ConstantesAuthentification.php` | Créer | Libellés, textes d'aide et messages de la page |
| `.env` | Lire uniquement | Secrets d'authentification existants ; aucune valeur modifiée |
| `public/supervision.php` | Aucun changement dans ce périmètre | Consommera ultérieurement l'identité administrateur |
| `public/tableau-de-bord.php` | Aucun changement dans ce périmètre | Consommera ultérieurement l'identité `tableau_de_bord` / `equipe=0` |
| `public/porte-des-etoiles.php` | Aucun changement dans ce périmètre | Consommera ultérieurement l'identité équipe 1 à 4 |

### 3.1 Dépendances et règles de frontière

- `login.php` charge les deux fichiers PHP de `src/`.
- `Authentification.php` ne produit pas de HTML.
- `ConstantesAuthentification.php` ne contient aucune logique d'authentification.
- Aucun fichier de données JSON n'est lu ou modifié par la page de connexion.
- Aucun secret n'est placé dans `public/`, dans le HTML rendu, dans une URL de redirection ou dans un journal.
- Aucun chemin de fichier n'est construit à partir d'une donnée reçue du navigateur.

---

## 4. Découpage séquentiel

**Règle d'exécution :** une seule sous-étape est traitée à la fois. Avant chaque sous-étape de codage, l'action, la signature, le rôle, les entrées, les sorties et les impacts sont présentés. L'écriture du code attend la validation explicite du propriétaire du projet. Après réalisation, le bilan est présenté et la case correspondante est cochée.

### Étape 1 — Préparer l'environnement et figer les contrats

#### 1.1 Vérifier l'environnement PHP

- [X] **Objectif :** connaître la version PHP réellement disponible chez IONOS avant de choisir une syntaxe ou une API.
- **Fichier concerné :** aucun fichier applicatif modifié.
- **Actions :**
  1. relever la version PHP configurée dans l'espace IONOS : 8.4;
  2. relever si le site est servi en HTTPS : oui;
  3. relever le chemin réel du répertoire public et du répertoire projet : ja_simplifier\public ;
  4. confirmer que `.env` se trouve hors du répertoire public : oui  : ja_simplifier\.
- **Validation préalable :** communiquer la version PHP et confirmer les chemins d'hébergement.
- **Bilan post-réalisation :** noter les valeurs confirmées dans le suivi du projet ; ne pas créer de code tant que la version PHP n'est pas connue.

#### 1.2 Relever les noms réels des variables `.env`

- [X] **Objectif :** établir le mapping exact entre les données prévues par le cadrage et les variables présentes dans `.env`.
- **Fichier concerné :** `.env`, lecture seule.
- **Données à identifier :**
  - identifiant administrateur ;
  - mot de passe administrateur ;
  - clé tableau de bord ;
  - `CLE_EQUIPE_1` à `CLE_EQUIPE_4`.
- **Actions :**
  1. vérifier les noms des variables sans afficher ni copier leurs valeurs dans les échanges ;
  2. identifier le format réel du fichier ;
  3. confirmer la méthode de lecture compatible avec ce format ;
  4. vérifier que le fichier n'est pas sous `public/`.
- **Validation préalable :** confirmer les noms et le format sans divulguer les secrets.
- **Bilan post-réalisation :** mapping confirmé : `IDENTIFIANT_SUPERVISION` pour l'identifiant administrateur, `MOT_DE_PASSE_SUPERVISION` pour son mot de passe, `CLE_TABLEAU_DE_BORD` pour la clé du tableau de bord et `CLE_EQUIPE_1` à `CLE_EQUIPE_4` pour les clés des équipes. Le fichier utilise des affectations `NOM="valeur"` et se trouve à la racine `ja_simplifier\`, hors de `public/`. La méthode de lecture retenue est `parse_ini_file()` avec `INI_SCANNER_RAW`. Les valeurs des secrets n'ont pas été affichées ni modifiées.

#### 1.3 Figer les contrats HTTP et d'identité

- [X] **Objectif :** fixer les noms des champs POST, les valeurs d'identifiant et les structures de session avant leur implémentation.
- **Fichiers concernés :** aucun fichier modifié.
- **Contrats :**
  - équipe : champs POST `identifiant` (`equipe1`, `equipe2`, `equipe3` ou `equipe4`) et `cle`, libellés « Identifiant de l'équipe » et « Clé de l'équipe » ;
  - tableau de bord : champ POST `cle`, libellé « Clé du tableau de bord » ;
  - administrateur : champs POST `identifiant` et `mot_de_passe`, libellés « Identifiant administrateur » et « Mot de passe » ;
  - bouton des formulaires : « Se connecter » ;
  - destination d'origine : paramètre `destination` contenant une URL relative interne encodée, conservée dans un champ POST homonyme pendant le parcours de connexion ;
  - paramètres GET de la porte : `equipe` obligatoire, compris entre 1 et 4 ; `cle` peut être transmise pour l'authentification mais est supprimée après succès ; aucun autre paramètre n'est défini à ce stade ;
  - paramètres GET du tableau de bord : `equipe=0` obligatoire ; `cle` peut être transmise pour l'authentification mais est supprimée après succès ; aucun autre paramètre n'est défini à ce stade ;
  - paramètres GET de la supervision : aucun ;
  - session : `$_SESSION['authentification']` selon le contrat de la section 2.4.
- **Actions :**
  1. confirmer les champs POST et les libellés ;
  2. confirmer le format de transmission de la destination d'origine utilisé par les pages fonctionnelles ;
  3. confirmer les noms exacts des paramètres GET conservés par chaque page, sans présumer de paramètres métier non décrits ici.
- **Validation préalable :** valider les contrats avant le développement.
- **Bilan post-réalisation :** le propriétaire du projet a validé les champs POST, leurs libellés, le bouton commun « Se connecter », le paramètre de retour `destination`, les paramètres GET autorisés et la structure de session déjà définie en section 2.4. Les pages fonctionnelles n'existant pas encore, ce contrat servira de référence lors de leur réalisation. Aucun code applicatif n'a été créé ou modifié.

### Étape 2 — Créer les constantes d'authentification

#### 2.1 Créer `src/ConstantesAuthentification.php`

- [X] **Objectif :** centraliser tous les libellés et messages affichés par `login.php`, conformément aux Instructions Globales.
- **Fichier créé :** `src/ConstantesAuthentification.php`.
- **Structure prévue :** classe `ConstantesAuthentification`.
- **Constantes à définir :**
  - titre de la page ;
  - titres des trois zones de connexion ;
  - libellé du champ identifiant ;
  - libellé du champ clé ;
  - libellé du champ mot de passe ;
  - libellé du bouton de connexion ;
  - message d'identifiants ou clé incorrects ;
  - message de destination invalide ;
  - message de formulaire invalide ;
  - message de jeton CSRF invalide ;
  - message d'indisponibilité de configuration ;
  - textes d'aide et libellés accessibles.
- **Actions méthode par méthode :**
  1. créer la classe ;
  2. déclarer les constantes de textes en français ;
  3. ne placer aucun secret ou nom de variable sensible avec sa valeur dans cette classe ;
  4. documenter la classe en français selon les conventions PHP retenues.
- **Validation préalable :** confirmer la liste des messages avant création.
- **Bilan post-réalisation :** `src/ConstantesAuthentification.php` contient les titres, libellés, messages d'erreur, textes d'aide et libellés accessibles validés. La classe est documentée en français et ne contient aucun secret. Le contrôleur `login.php` n'étant pas créé dans cette sous-étape, l'utilisation des constantes par l'interface sera réalisée à l'étape prévue pour ce contrôleur.

### Étape 3 — Créer le module d'authentification

#### 3.1 Créer `src/Authentification.php`

- [X] **Objectif :** isoler les règles d'authentification et de session de la présentation HTTP.
- **Fichier créé :** `src/Authentification.php`.
- **Structure prévue :** classe `Authentification`, méthodes statiques sans état métier persistant.
- **Dépendances :** PHP natif uniquement ; `ConstantesAuthentification.php` n'est pas requis par cette classe.
- **Bilan post-réalisation :** la classe finale `Authentification` est créée et documentée en français. Elle ne contient encore aucune méthode, propriété ou dépendance ; les règles seront ajoutées dans les sous-étapes prévues à cet effet.

#### 3.2 Charger la configuration

- [X] **Méthode prévue :** `public static function chargerConfiguration($cheminEnv)`
- **Rôle :** lire les paramètres requis dans `.env` en respectant le format réel confirmé à l'étape 1.2.
- **Entrée :** chemin absolu ou chemin résolu côté serveur vers `.env`, jamais fourni par le navigateur.
- **Sortie :** tableau associatif contenant les paramètres nécessaires à l'authentification.
- **Règles :**
  1. vérifier que le fichier existe et est lisible ;
  2. utiliser le mode de lecture adapté au format confirmé ;
  3. vérifier la présence de toutes les variables requises ;
  4. ne jamais renvoyer les valeurs sensibles dans un message ou une sortie HTML ;
  5. ne jamais journaliser le contenu du fichier ;
  6. signaler une configuration incomplète par une erreur contrôlée.
- **Validation préalable :** format `.env`, chemin réel et mapping validés.
- **Bilan post-réalisation :** la méthode lit le fichier avec `parse_ini_file()` et `INI_SCANNER_RAW`, vérifie les sept variables requises et refuse les valeurs absentes, non textuelles ou vides. Elle ne retourne que ces paramètres et signale les erreurs avec une exception générique sans chemin ni secret. La syntaxe PHP a été vérifiée ; aucun test n'a été exécuté conformément à la stratégie du projet.

#### 3.3 Initialiser la session sécurisée

- [X] **Méthode prévue :** `public static function initialiserSessionSecurisee()`
- **Rôle :** configurer les paramètres du cookie puis démarrer la session navigateur.
- **Entrée :** aucune ; utilise le contexte serveur HTTPS déterminé par l'application.
- **Sortie :** session PHP démarrée avec les paramètres de cookie prévus.
- **Règles :**
  1. configurer `HttpOnly` ;
  2. configurer `Secure` lorsque le site est servi en HTTPS ;
  3. définir `SameSite` selon le fonctionnement de l'application ;
  4. démarrer la session avant tout envoi de contenu HTML ;
  5. empêcher la fixation de session en renouvelant l'identifiant au moment de l'authentification réussie ;
  6. ne pas mettre en place de connexion persistante.
- **Validation préalable :** version PHP confirmée et comportement HTTPS connu.
- **Bilan post-réalisation :** la méthode configure un cookie de session navigateur (`lifetime=0`, `path=/`, `HttpOnly`, `SameSite=Lax` et `Secure` lorsque `$_SERVER['HTTPS']` vaut `on` ou `1`), puis démarre la session. Elle refuse une session indisponible/déjà démarrée ou des en-têtes déjà envoyés et signale les échecs par une exception générique. Le renouvellement de l'identifiant reste prévu à l'étape 3.8. La syntaxe PHP a été vérifiée ; aucun test n'a été exécuté conformément à la stratégie du projet.

#### 3.4 Normaliser et contrôler la destination de retour

- [X] **Méthode prévue :** `public static function normaliserDestination($destinationBrute)`
- **Rôle :** convertir la destination reçue en une représentation interne contrôlée.
- **Entrée :** destination transmise par une page fonctionnelle, ou `null` si `login.php` est ouvert directement.
- **Sortie :** structure contenant la page autorisée et ses paramètres GET validés, ou un état indiquant qu'aucune destination d'origine exploitable n'est disponible.
- **Règles :**
  1. n'accepter que `porte-des-etoiles.php`, `tableau-de-bord.php` et `supervision.php` ;
  2. refuser les URL externes, les schémas, les hôtes, les chemins absolus et les traversées de répertoire ;
  3. valider `equipe` selon la page ;
  4. refuser `porte-des-etoiles.php` si `equipe` n'est pas compris entre 1 et 4 ;
  5. refuser `tableau-de-bord.php` si `equipe` n'est pas exactement égal à 0 ;
  6. refuser un paramètre `equipe` sur `supervision.php` ;
  7. retirer `cle` de la représentation conservée ;
  8. ne conserver que les autres paramètres GET dont la cohérence est validée ;
  9. ne jamais construire un chemin de fichier à partir de cette valeur.
- **Validation préalable :** confirmer le nom exact du paramètre utilisé par les pages fonctionnelles pour transmettre l'URL d'origine.
- **Bilan post-réalisation :** `normaliserDestination()` retourne `null` en accès direct, ou une structure contenant uniquement la page autorisée et ses paramètres normalisés. Les destinations externes, les paramètres inattendus ou dupliqués et les valeurs d'équipe incohérentes déclenchent une exception générique. `cle` n'est jamais conservée. La syntaxe PHP a été vérifiée ; aucun test n'a été exécuté conformément à la stratégie du projet.

#### 3.5 Déterminer l'identité depuis un formulaire

- [X] **Méthode prévue :** `public static function determinerIdentiteDepuisFormulaire($donnees, $configuration)`
- **Rôle :** identifier le type d'accès et vérifier les secrets présentés dans le formulaire direct.
- **Entrées :** données POST déjà reçues par le contrôleur et configuration privée chargée depuis `.env`.
- **Sortie :** structure d'identité conforme à la section 2.4, ou `null` si l'authentification échoue.
- **Règles :**
  1. si `identifiant` vaut `equipe1`, comparer `cle` exclusivement à `CLE_EQUIPE_1` ;
  2. appliquer la même correspondance pour `equipe2`/`CLE_EQUIPE_2`, `equipe3`/`CLE_EQUIPE_3`, `equipe4`/`CLE_EQUIPE_4` ;
  3. pour le tableau de bord, comparer la clé présentée exclusivement à la clé dédiée au tableau de bord ;
  4. pour l'administrateur, comparer l'identifiant et le mot de passe aux données administrateur de `.env` ;
  5. ne jamais accepter une clé d'équipe pour le tableau de bord ou la supervision ;
  6. ne jamais accepter la clé du tableau de bord pour une porte d'équipe ;
  7. ne jamais confondre les identifiants administrateur avec une identité d'équipe ;
  8. ne jamais retourner les secrets dans l'identité ;
  9. ne pas distinguer publiquement une erreur d'identifiant d'une erreur de secret.
- **Validation préalable :** mapping `.env` et contrats des trois formulaires confirmés.
- **Bilan post-réalisation :** la méthode distingue les trois formulaires par leurs champs d'identification, compare les secrets avec `hash_equals()` et retourne uniquement une identité conforme au contrat. Les données manquantes, mal typées, ambiguës ou incorrectes produisent `null`, sans divulguer ni distinguer les motifs d'échec. La configuration validée en amont n'est pas recontrôlée. La syntaxe PHP a été vérifiée ; aucun test n'a été exécuté conformément à la stratégie du projet.

#### 3.6 Déterminer l'identité depuis une clé d'URL

- [X] **Méthode prévue :** `public static function determinerIdentiteDepuisCleUrl($destination, $cle, $configuration)`
- **Rôle :** traiter le cas où la page fonctionnelle a transmis une clé dans l'URL d'origine.
- **Entrées :** destination déjà normalisée, clé reçue, configuration privée.
- **Sortie :** identité correspondante ou `null`.
- **Règles :**
  1. porte équipe 1 : seule `CLE_EQUIPE_1` est admise ;
  2. porte équipe 2 : seule `CLE_EQUIPE_2` est admise ;
  3. porte équipe 3 : seule `CLE_EQUIPE_3` est admise ;
  4. porte équipe 4 : seule `CLE_EQUIPE_4` est admise ;
  5. tableau de bord : seule la clé dédiée au tableau de bord est admise et `equipe=0` est obligatoire ;
  6. supervision : aucune clé d'équipe ni clé de tableau de bord n'est admise ;
  7. une clé absente, invalide ou incohérente ne crée aucune session.
- **Validation préalable :** destination normalisée et mapping `.env` validés.
- **Bilan post-réalisation :** la clé est comparée avec `hash_equals()` uniquement à la clé associée à la page et à l'équipe de la destination normalisée. La méthode retourne l'identité correspondante sans secret ou `null` en cas d'échec ; une clé d'URL ne donne aucun accès à la supervision. La syntaxe PHP a été vérifiée ; aucun test n'a été exécuté conformément à la stratégie du projet.

#### 3.7 Vérifier la compatibilité destination / identité

- [X] **Méthode prévue :** `public static function destinationAutoriseePourIdentite($destination, $identite)`
- **Rôle :** empêcher qu'une authentification valide pour un accès soit utilisée pour une autre ressource.
- **Entrées :** destination normalisée et identité créée côté serveur.
- **Sortie :** booléen.
- **Règles :**
  - équipe 1 à 4 : uniquement `porte-des-etoiles.php` avec le même numéro d'équipe ;
  - tableau de bord : uniquement `tableau-de-bord.php` avec `equipe=0` ;
  - administrateur : uniquement `supervision.php` dans le cadre du routage défini par le cadrage ;
  - toute autre combinaison est refusée.
- **Validation préalable :** structure d'identité et représentation de destination validées.
- **Bilan post-réalisation :** la méthode retourne `true` uniquement lorsque le rôle, la page et, pour l'équipe ou le tableau de bord, le numéro d'équipe correspondent. Une destination absente, une identité incomplète ou toute combinaison incompatible retourne `false`. La syntaxe PHP a été vérifiée ; aucun test n'a été exécuté conformément à la stratégie du projet.

#### 3.8 Enregistrer l'identité en session

- [X] **Méthode prévue :** `public static function creerSession($identite)`
- **Rôle :** renouveler l'identifiant de session et enregistrer l'identité authentifiée.
- **Entrée :** identité issue exclusivement des méthodes de validation.
- **Sortie :** session mise à jour.
- **Règles :**
  1. refuser toute structure d'identité qui ne respecte pas les rôles et numéros autorisés ;
  2. renouveler l'identifiant de session pour prévenir la fixation ;
  3. enregistrer l'identité sous `$_SESSION['authentification']` ;
  4. ne stocker ni clé ni mot de passe ;
  5. ne pas lire le rôle ou l'équipe depuis GET pour construire cette identité.
- **Validation préalable :** structure d'identité contractuelle validée.
- **Bilan post-réalisation :** la méthode accepte uniquement les identités contractuelles sans champ supplémentaire, renouvelle l'identifiant PHP avec suppression de l'ancienne session et enregistre une structure d'identité reconstruite sans secret. Elle refuse les identités invalides et les sessions inactives par exceptions génériques ; aucune donnée GET n'est consultée. La syntaxe PHP a été vérifiée ; aucun test n'a été exécuté conformément à la stratégie du projet.

#### 3.9 Générer et vérifier le jeton CSRF

- [ ] **Méthode prévue :** `public static function genererJetonCsrf()`
- **Rôle :** créer un jeton aléatoire lié à la session pour protéger les soumissions POST.
- **Entrée :** session active.
- **Sortie :** jeton opaque stocké côté session et retourné pour l'inclusion dans le formulaire.
- **Méthode prévue :** `public static function verifierJetonCsrf($jetonSoumis)`
- **Rôle :** vérifier le jeton reçu avec le formulaire.
- **Entrée :** jeton POST éventuel.
- **Sortie :** booléen.
- **Règles :**
  1. générer le jeton avec une source aléatoire cryptographiquement sûre disponible dans la version PHP cible ;
  2. ne jamais placer le jeton dans une URL ;
  3. comparer de manière sûre le jeton soumis et celui de session ;
  4. refuser le traitement POST si le jeton est absent ou invalide.
- **Validation préalable :** version PHP confirmée et fonctions natives disponibles identifiées.
- **Bilan post-réalisation :** chaque formulaire de connexion est protégé contre les soumissions forgées.

#### 3.10 Construire la destination finale

- [ ] **Méthode prévue :** `public static function construireDestinationFinale($destination, $identite)`
- **Rôle :** produire uniquement une URL relative interne après contrôle de compatibilité.
- **Entrées :** destination normalisée et identité de session validée.
- **Sortie :** chemin relatif de la page autorisée avec paramètres GET utiles, sans `cle`.
- **Règles :**
  1. appeler le contrôle de compatibilité avant construction ;
  2. supprimer définitivement `cle` ;
  3. conserver uniquement les paramètres validés ;
  4. imposer le numéro d'équipe issu de l'identité pour la porte ;
  5. imposer `equipe=0` pour le tableau de bord ;
  6. ne pas ajouter de paramètre d'équipe à la supervision ;
  7. ne jamais retourner une URL absolue.
- **Validation préalable :** contrat des paramètres GET et compatibilité destination/identité validés.
- **Bilan post-réalisation :** la destination finale est interne et ne contient aucun secret.

#### 3.11 Rediriger vers une destination interne

- [ ] **Méthode prévue :** `public static function redirigerVers($destination)`
- **Rôle :** envoyer l'en-tête de redirection HTTP puis arrêter l'exécution.
- **Entrée :** destination relative produite par `construireDestinationFinale()` ou destination de secours définie par le type d'identité.
- **Sortie :** aucune ; la méthode termine la requête après l'envoi de la redirection.
- **Règles :**
  1. n'accepter que les destinations internes déjà validées ;
  2. envoyer la redirection avant tout contenu HTML ;
  3. arrêter immédiatement le script après l'en-tête ;
  4. ne jamais réinjecter une chaîne fournie librement par l'utilisateur dans l'en-tête `Location`.
- **Validation préalable :** contrat des destinations finales validé.
- **Bilan post-réalisation :** toutes les redirections sont internes, cohérentes et sans clé dans l'URL.

### Étape 4 — Créer le contrôleur et la page `login.php`

#### 4.1 Créer le point d'entrée `public/login.php`

- [ ] **Objectif :** orchestrer le traitement HTTP et afficher les formulaires de connexion.
- **Fichier créé :** `public/login.php`.
- **Responsabilités :**
  1. charger les dépendances depuis `src/` avec des chemins déterminés côté serveur ;
  2. initialiser la session avant toute sortie ;
  3. charger la configuration privée ;
  4. récupérer et normaliser la destination d'origine ;
  5. traiter une clé présente dans l'URL d'origine, si elle existe ;
  6. traiter les formulaires POST ;
  7. vérifier le jeton CSRF avant tout traitement POST ;
  8. créer la session uniquement après authentification réussie et compatibilité de destination ;
  9. rediriger vers la destination finale sans `cle` ;
  10. afficher les formulaires en cas d'accès direct, de clé absente ou d'échec.
- **Validation préalable :** dépendances, signatures et contrats des méthodes de la section 3 validés.
- **Bilan post-réalisation :** le fichier reste un contrôleur lisible et ne réimplémente pas les règles métier déjà centralisées dans `Authentification`.

#### 4.2 Traiter une requête GET avec une destination et une clé

- [ ] **Objectif :** authentifier automatiquement une demande dont l'URL d'origine contient une clé.
- **Actions :**
  1. lire la destination transmise ;
  2. la normaliser ;
  3. extraire la clé de la requête d'origine sans la recopier dans les messages ;
  4. vérifier la clé selon la page et `equipe` ;
  5. vérifier la compatibilité de l'identité et de la destination ;
  6. créer la session ;
  7. construire l'URL finale sans `cle` ;
  8. rediriger.
- **Échec :** rester sur la page de connexion ou y rediriger ; afficher un message générique et conserver la destination interne validée pour permettre une nouvelle tentative.
- **Validation préalable :** format exact de transmission de l'URL d'origine confirmé.
- **Bilan post-réalisation :** une clé valide et cohérente ouvre la bonne ressource ; une clé incohérente ne crée aucune session.

#### 4.3 Traiter une requête GET sans clé exploitable

- [ ] **Objectif :** afficher les possibilités de connexion tout en conservant la destination d'origine.
- **Actions :**
  1. vérifier si une session existante est déjà présente ;
  2. si la session permet l'accès à la destination, rediriger vers cette destination après contrôle ;
  3. sinon afficher les trois zones de connexion ;
  4. conserver uniquement la destination interne validée ;
  5. ne pas exposer la clé reçue ou absente dans le HTML.
- **Validation préalable :** règles de compatibilité entre session existante et destination confirmées.
- **Bilan post-réalisation :** l'absence de clé n'efface pas le parcours demandé et n'accorde aucun accès.

#### 4.4 Traiter les formulaires POST

- [ ] **Objectif :** traiter séparément les trois types d'accès.
- **Actions :**
  1. vérifier la méthode HTTP ;
  2. vérifier le jeton CSRF ;
  3. identifier le formulaire soumis sans faire confiance à un rôle ou à un numéro d'équipe transmis ;
  4. récupérer les champs attendus ;
  5. appeler la méthode de validation adaptée ;
  6. si l'identité est absente, afficher le message générique d'échec ;
  7. si une destination d'origine existe, vérifier sa compatibilité ;
  8. si aucune destination n'existe, choisir la destination de secours correspondant à l'identité ;
  9. créer la session ;
  10. rediriger.
- **Règles spécifiques :**
  - une connexion directe `equipe1` ouvre la porte de l'équipe 1 ;
  - `equipe2`, `equipe3` et `equipe4` suivent la même règle ;
  - une clé de tableau de bord ouvre le tableau de bord avec `equipe=0` ;
  - une connexion administrateur ouvre la supervision ;
  - une identité valide mais incompatible avec la destination d'origine est refusée pour cette destination.
- **Validation préalable :** noms de champs POST et correspondance des formulaires confirmés.
- **Bilan post-réalisation :** chaque formulaire ne peut créer que le rôle qui lui correspond.

#### 4.5 Afficher les formulaires et les messages

- [ ] **Objectif :** afficher une interface de connexion claire sans divulguer d'information sensible.
- **Actions :**
  1. afficher les trois zones définies en section 2.3 ;
  2. inclure un jeton CSRF dans chaque formulaire POST ;
  3. utiliser les constantes de `ConstantesAuthentification.php` ;
  4. échapper toutes les valeurs dynamiques réinjectées dans les attributs ou le contenu HTML ;
  5. afficher les messages comme du texte brut ;
  6. ne jamais réafficher une clé ou un mot de passe après échec ;
  7. ne pas révéler si l'identifiant administrateur ou le secret est celui qui est incorrect.
- **Validation préalable :** textes et libellés validés à l'étape 2.
- **Bilan post-réalisation :** le HTML n'expose aucun secret et tous les messages applicatifs sont externalisés.

### Étape 5 — Sécuriser le traitement de bout en bout

#### 5.1 Vérifier la protection des secrets et des erreurs

- [ ] **Objectif :** respecter les exigences de sécurité du cadrage.
- **Fichiers concernés :** `public/login.php`, `src/Authentification.php`.
- **Actions :**
  1. ne jamais écrire une clé ou un mot de passe dans les journaux ;
  2. ne jamais inclure une clé dans un message d'erreur ;
  3. ne pas afficher le contenu de `.env` ;
  4. ne pas afficher les chemins serveur ni les détails d'exception ;
  5. afficher un message applicatif générique si la configuration est indisponible ;
  6. conserver les détails techniques hors de la réponse HTTP.
- **Validation préalable :** politique de gestion des erreurs et absence de journalisation des secrets confirmées.
- **Bilan post-réalisation :** les erreurs visibles ne révèlent ni secret, ni chemin serveur, ni détail interne.

#### 5.2 Verrouiller la destination et les paramètres

- [ ] **Objectif :** empêcher les redirections externes et les changements de rôle par GET.
- **Fichiers concernés :** `src/Authentification.php`, `public/login.php`.
- **Actions :**
  1. limiter les destinations aux trois pages fonctionnelles ;
  2. valider `equipe` selon la page ;
  3. supprimer `cle` de toute destination finale ;
  4. ne pas reprendre un rôle depuis GET ou POST ;
  5. imposer l'équipe depuis l'identité validée ;
  6. refuser une destination altérée ou externe.
- **Validation préalable :** liste des destinations et contrats d'identité validés.
- **Bilan post-réalisation :** les paramètres navigateur ne permettent ni redirection externe ni élévation/changement de rôle.

#### 5.3 Verrouiller les sessions et les formulaires

- [ ] **Objectif :** appliquer les protections de session et CSRF prévues.
- **Fichiers concernés :** `src/Authentification.php`, `public/login.php`.
- **Actions :**
  1. configurer les attributs du cookie ;
  2. renouveler l'identifiant de session lors de l'authentification ;
  3. ne stocker que l'identité ;
  4. vérifier le jeton CSRF avant tout POST ;
  5. ne pas activer de session persistante.
- **Validation préalable :** version PHP et contexte HTTPS confirmés.
- **Bilan post-réalisation :** les règles de session et de protection CSRF sont appliquées sans introduire de mécanisme de connexion persistante.

### Étape 6 — Clôturer le périmètre de `login.php`

#### 6.1 Relecture de conformité au cadrage

- [ ] **Objectif :** s'assurer que le code livré reste strictement dans le périmètre de `login.php`.
- **Fichiers concernés :** les trois fichiers créés pour cette fonctionnalité.
- **Points de relecture :**
  - authentification centralisée ;
  - quatre identifiants d'équipe `equipe1` à `equipe4` ;
  - clé dédiée au tableau de bord et `equipe=0` ;
  - authentification administrateur ;
  - destination d'origine conservée ;
  - clé retirée de l'URL finale ;
  - cohérence page / équipe / clé / identité ;
  - session créée côté serveur ;
  - cookie conforme au cadrage ;
  - destination de retour strictement interne ;
  - secrets absents du HTML, des URLs finales, des messages et des journaux ;
  - textes applicatifs externalisés ;
  - documentation PHP en français sur chaque classe et méthode créée.
- **Validation préalable :** relecture documentaire du périmètre et des fichiers concernés.
- **Bilan post-réalisation :** liste précise des fichiers créés et de leurs responsabilités ; aucune autre page PHP n'a été créée ou modifiée.

#### 6.2 Mettre à jour le plan

- [ ] **Objectif :** refléter fidèlement l'avancement réel.
- **Actions :**
  1. cocher uniquement les sous-étapes effectivement réalisées et validées ;
  2. conserver les sous-étapes non commencées décochées ;
  3. noter les décisions validées et les éventuelles questions encore ouvertes ;
  4. ne pas annoncer une étape comme terminée si son code n'a pas été écrit et relu.
- **Bilan post-réalisation :** le plan reste la source de vérité technique de cette fonctionnalité.

---

## 5. Stratégie de tests

**Aucun test ne sera créé, planifié ou exécuté.**

Conformément à la demande explicite du propriétaire du projet :
- aucun test unitaire ;
- aucun test d'intégration ;
- aucun test de sécurité ;
- aucun scénario de recette manuelle ;
- aucune vérification visuelle ou de compatibilité ;
- aucun fichier, outil ou script de test ;
- aucune commande de test.

Cette section remplace, pour le présent plan, les activités de vérification et de recette mentionnées de manière générale dans le cadrage.

---

## 6. Précautions et points de vigilance

### 6.1 Ne pas déduire les droits depuis le navigateur

- `equipe`, le rôle et le type d'accès ne sont jamais considérés comme fiables lorsqu'ils proviennent directement de GET ou POST.
- Le rôle et l'équipe sont construits uniquement après validation côté serveur.
- Une session équipe ne permet pas d'accéder à la supervision.
- Une clé d'équipe ne donne pas accès au tableau de bord ou à la supervision.
- La clé du tableau de bord ne donne pas accès à une porte d'équipe.

### 6.2 Ne pas laisser fuiter les secrets

- `.env` reste hors de `public/`.
- Les clés ne figurent plus dans l'URL après authentification.
- Les clés et mots de passe ne sont ni journalisés ni réaffichés.
- Les erreurs techniques ne révèlent pas de chemin serveur, de secret ou de code.
- Les formulaires ne préremplissent jamais un secret après échec.

### 6.3 Ne pas créer de redirection ouverte

- La destination est limitée aux trois pages fonctionnelles.
- Une URL externe, absolue, altérée ou contenant une traversée de répertoire est refusée.
- La destination finale est construite côté serveur à partir de la liste blanche.
- Les paramètres GET conservés sont validés avec la page et l'identité.

### 6.4 Respecter l'ordre d'exécution PHP

- La session et les en-têtes sont traités avant tout rendu HTML.
- Une redirection interrompt immédiatement l'exécution.
- Les dépendances sont chargées depuis des chemins serveur déterminés par l'application.
- Aucun chemin de fichier n'est construit depuis une entrée utilisateur.

### 6.5 Respecter la version PHP de l'hébergement

La version PHP IONOS doit être confirmée avant le premier code. Les signatures et appels natifs seront écrits uniquement avec des fonctionnalités disponibles dans cette version.

### 6.6 Respecter la progression séquentielle

- Une seule sous-étape à la fois.
- Présentation de la signature et du contrat avant toute écriture.
- Attente de l'accord explicite avant de coder.
- Aucun changement hors périmètre.
- Toute classe et toute méthode créée comporte une documentation en français.
- Aucun message de commit n'est proposé tant que le code n'a pas été effectivement réalisé et relu comme sain.

---

**Fin du plan d'implémentation de `public/login.php`.**

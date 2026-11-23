# PLAN D’IMPLÉMENTATION — JEUX ASSEMBLÉES 2026

**Fichier cible :** `IMPLEMENTATION_PLAN_JEUX_ASSEMBLEES_2026.md` (racine du dépôt)  
**Dépôt :** `https://github.com/corbidev/ja_simplifie`  
**Branche consultée :** `main`  
**Référence :** `instructions/CADRAGE_JEUX_ASSEMBLEES_2026_v3.md`  
**Instructions :** `instructions/instructions_globales_projets.md` et `instructions/3_execution_etape_plan.md`  
**Version du plan :** 1.0 — 23 novembre 2026  
**Statut :** proposition à valider avant toute implémentation.

---

## 1. Contexte et références

### 1.1 Objectif

Simplifier et adapter le dispositif Stargate existant pour le jeu JEUX ASSEMBLÉES 2026. L'application comporte quatre pages PHP :

1. `login.php` : point d'entrée unique de toute authentification ;
2. `supervision.php` : pilotage administrateur ;
3. `tableau-de-bord.php` : affichage synthétique en lecture seule ;
4. `porte-des-etoiles.php` : interface Stargate propre à chacune des quatre équipes.

Le dépôt est préparé pour PHP 8.4, des fichiers JSON et un hébergement mutualisé IONOS. Aucun framework PHP, aucune base SQL, aucun worker et aucun traitement asynchrone ne sont présents dans la structure consultée. Le plan conserve une architecture PHP native et n'introduit aucune dépendance non validée.

### 1.2 Documents de référence

| Source | Utilisation |
|---|---|
| `instructions/CADRAGE_JEUX_ASSEMBLEES_2026_v3.md` | Référence fonctionnelle et technique. |
| `instructions/instructions_globales_projets.md` | Règles impératives de travail, de sécurité, de documentation et de progression. |
| `instructions/3_execution_etape_plan.md` | Protocole d'exécution d'une étape isolée. |
| `instructions/Stargate_V81_jambes_blanches_cables_genoux.html` | Référence visuelle et comportementale de la porte. |
| `data/equipe-1.json` à `data/equipe-4.json` | Configurations initiales des équipes. |
| `data/suivi-partie.json` | État initial de la partie et des équipes. |
| `data/messages.json` | Collection initiale des messages. |
| `env.example` | Noms des variables d'environnement, valeurs fictives. |

`LISEZ-MOI.md` fait encore référence au cadrage V2 ; il devra être actualisé pour refléter V3 après validation du plan.

### 1.3 État constaté dans le dépôt

L'arborescence `main` consultée contient `.env`, `env.example`, les six JSON, `instructions/`, `public/`, `public/assets/css/`, `public/assets/js/` et `src/`. Les répertoires `public/`, CSS, JS et `src/` ne contiennent que leurs fichiers `.keep`. Les quatre pages PHP et les classes applicatives restent à créer.

Les six JSON sont déjà présents : ils ne doivent pas être recréés aveuglément. Leurs structures actuelles sont la base de travail. Les fichiers `equipe-N.json` comportent les propriétés `equipe`, `reference_historique`, `annee_attendue`, `code_final`, `phrase_finale` et `parametres`. Les valeurs `annee_attendue`, `code_final` et `phrase_finale` sont nulles.

Le `.env` consulté dans le dépôt ne contient que des valeurs `A_REMPLACER`. Le fichier réel utilisé localement ou sur le serveur doit néanmoins rester privé et être exclu du suivi Git.

### 1.4 Périmètre fonctionnel confirmé

- Quatre équipes, quatre portes indépendantes.
- Neuf symboles : `☉`, `△`, `◇`, `⌘`, `☽`, `✶`, `⊕`, `∿`, `⋈`.
- Cinq symboles distincts par code ; les quatre équipes utilisent le même ensemble dans un ordre différent.
- Trois résultats : code exact ; cinq bons symboles dans le mauvais ordre ; combinaison incorrecte.
- Validation des codes exclusivement côté serveur.
- Préservation de la scène 3D, des objets, matériaux, chevrons, horizon, sons, robot, explosion, blocage, réparation et responsive.
- Seul le superviseur démarre, met en pause, reprend et arrête la partie.
- Le compte à rebours continue sous zéro ; zéro ne termine pas la partie.
- Les pauses sont exclues du temps écoulé et du temps de résolution.
- Une année correctement validée est définitive jusqu'à la réinitialisation.
- Fin de partie par arrêt manuel ou validation des quatre années.
- Actualisation des trois pages fonctionnelles toutes les cinq secondes.
- Six fichiers JSON actifs, archivés lors de la réinitialisation.
- Secrets conservés dans `.env`, hors de la racine publique.

### 1.5 Hors périmètre

Les énigmes physiques, pictogrammes, documents papier, indices de position et progression physique ne sont pas gérés par l'application. Le domaine et le DNS sont gérés séparément. Aucun rôle ESP32 n'est ajouté tant que leur usage dans la version simplifiée n'est pas confirmé.

### 1.6 Dérogation explicite : suppression de tous les tests

Le propriétaire demande de supprimer **tous les tests**. Cette décision déroge à la section 6 des Instructions Globales et à la section 15 du cadrage V3, qui prévoyaient encore des vérifications d'intégration, de sécurité, fonctionnelles, visuelles et de compatibilité.

En conséquence, aucun test unitaire, d'intégration, de sécurité, fonctionnel, visuel, manuel ou automatisé n'est prévu, créé ou exécuté. Aucun dossier de tests, fichier de test, framework, script, commande ou pipeline de tests ne doit être ajouté. Cette dérogation concerne uniquement les tests : toutes les exigences de sécurité et de robustesse du code demeurent obligatoires.

---

## 2. Synthèse de la solution technique

### 2.1 Architecture

Architecture PHP native en couches simples :

- **Pages publiques** : quatre points d'entrée HTTP minces dans `public/`.
- **Bootstrap** : configuration, chargement des classes et construction des dépendances.
- **Sécurité** : authentification, sessions, rôles, URL de retour et CSRF.
- **Contrôleurs** : réception des requêtes et préparation des réponses.
- **Services métier** : codes Stargate, cycle de partie, années, messages, archivage.
- **Dépôts** : accès aux six JSON.
- **Persistance** : lecture/écriture JSON avec verrouillage et remplacement atomique.
- **Front-end** : CSS et JavaScript séparés ; le navigateur présente les décisions du serveur.
- **Scène Stargate** : intégration ciblée du HTML existant sans réécriture des animations.

Aucune base SQL, aucun framework, aucun daemon, aucun worker, Redis ou système asynchrone n'est introduit.

### 2.2 Arborescence cible

```text
ja_simplifie/
├── .env                                  # local/serveur uniquement, non versionné
├── .gitignore                            # à créer
├── env.example                           # existant, valeurs fictives
├── IMPLEMENTATION_PLAN_JEUX_ASSEMBLEES_2026.md
├── LISEZ-MOI.md                          # existant, à actualiser
├── public/
│   ├── login.php                         # à créer
│   ├── supervision.php                   # à créer
│   ├── tableau-de-bord.php               # à créer
│   ├── porte-des-etoiles.php             # à créer
│   └── assets/
│       ├── css/
│       │   ├── application.css           # à créer
│       │   ├── connexion.css             # à créer
│       │   ├── supervision.css            # à créer
│       │   ├── tableau-de-bord.css        # à créer
│       │   └── porte-des-etoiles.css      # à extraire/adapter
│       └── js/
│           ├── connexion.js               # à créer
│           ├── supervision.js             # à créer
│           ├── tableau-de-bord.js         # à créer
│           └── porte-des-etoiles.js       # à intégrer au moteur existant
├── src/
│   ├── bootstrap.php                      # à créer
│   ├── Configuration/ConfigurationApplication.php
│   ├── Securite/
│   │   ├── GestionSession.php
│   │   ├── ServiceAuthentification.php
│   │   ├── GestionUrlRetour.php
│   │   └── GestionJetonCsrf.php
│   ├── Persistance/StockageJson.php
│   ├── Depot/
│   │   ├── DepotEquipes.php
│   │   ├── DepotPartie.php
│   │   └── DepotMessages.php
│   ├── Service/
│   │   ├── ServiceCodePorte.php
│   │   ├── ServicePartie.php
│   │   ├── ServiceAnnee.php
│   │   ├── ServiceMessages.php
│   │   └── ServiceArchivage.php
│   ├── Controleur/
│   │   ├── ControleurConnexion.php
│   │   ├── ControleurPorte.php
│   │   ├── ControleurSupervision.php
│   │   └── ControleurTableauDeBord.php
│   └── Support/
│       ├── TextesApplication.php
│       └── EchappementHtml.php
└── data/
    ├── equipe-1.json                     # existant
    ├── equipe-2.json                     # existant
    ├── equipe-3.json                     # existant
    ├── equipe-4.json                     # existant
    ├── suivi-partie.json                 # existant
    ├── messages.json                     # existant
    └── archives/                          # existant
```

Le document root IONOS doit pointer vers `public/`. Les répertoires `src/`, `data/` et le fichier `.env` doivent rester hors de toute racine accessible par HTTP. Toute contrainte d'hébergement imposant une autre disposition doit être présentée et approuvée avant modification.

### 2.3 Bootstrap et configuration

Le dépôt ne comporte pas de `composer.json` ni d'autoloader. Le plan prévoit un chargement explicite et maîtrisé des classes depuis `src/bootstrap.php`, sans ajouter Composer ou une dépendance tierce.

Le bootstrap détermine la racine à partir de `__DIR__`, charge `.env`, configure la session avant toute sortie, charge les classes et construit les services. Il ne produit aucune sortie HTML.

Le fichier `.env` contient les noms déjà présents dans `env.example` :
`IDENTIFIANT_SUPERVISION`, `MOT_DE_PASSE_SUPERVISION`, `CLE_TABLEAU_DE_BORD`, `CLE_EQUIPE_1` à `CLE_EQUIPE_4`.

### 2.4 Identité de session et droits

Identités proposées :

- administrateur : `role=administrateur`, `equipe=null` ;
- équipe : `role=equipe`, `equipe=1..4` ;
- tableau de bord : `role=tableau_de_bord`, `equipe=0`.

`login.php` est l'unique point d'authentification. Les pages fonctionnelles déterminent les droits exclusivement depuis la session, jamais depuis un paramètre GET. La supervision exige l'administrateur ; la porte exige une session équipe ; le tableau de bord exige l'identité tableau de bord. L'accès administrateur au tableau de bord ou à une porte reste à confirmer.

### 2.5 Persistance JSON

Les six JSON existants sont conservés. Tous les accès passent par `StockageJson`. Les écritures doivent couvrir toute la séquence lecture-modification-écriture par un verrou, écrire dans un fichier temporaire du même répertoire, vérifier l'écriture puis remplacer le fichier actif de manière atomique lorsque le système de fichiers le permet. Les chemins sont issus d'une liste blanche du code, jamais d'une entrée utilisateur.

### 2.6 Actualisation

Les trois pages fonctionnelles actualisent leurs données toutes les cinq secondes sans rechargement complet. Les réponses ne contiennent que les données nécessaires à l'affichage. Aucun code attendu, secret ou année attendue n'est transmis au navigateur.

---

## 3. Fichiers, classes, méthodes et fonctions

Les signatures suivantes sont des propositions de conception, pas des éléments existants. Elles devront être validées avant toute implémentation, conformément aux Instructions Globales.

### 3.1 Pages PHP

| Fichier | Responsabilité |
|---|---|
| `public/login.php` | Appeler le contrôleur de connexion, afficher le formulaire ou rediriger. |
| `public/supervision.php` | Contrôler le rôle administrateur, traiter les commandes et afficher la supervision. |
| `public/tableau-de-bord.php` | Contrôler le rôle tableau de bord et afficher une synthèse en lecture seule. |
| `public/porte-des-etoiles.php` | Contrôler l'équipe de session et afficher la scène Stargate de cette équipe. |

Les pages ne contiennent aucune règle métier ni accès direct aux fichiers JSON.

### 3.2 Configuration et bootstrap

**`ConfigurationApplication`**

- `public static function charger(string $racineProjet): self`
- `public function obtenir(string $nom): string`
- `public function obtenirCheminDonnees(string $nomFichier): string`
- `public function obtenirRacineProjet(): string`

Charge `.env`, vérifie les variables nécessaires, centralise les chemins et refuse tout nom de fichier hors liste blanche.

**`src/bootstrap.php`**

- `function chargerContexteApplication(): array`

Charge les classes, configure la session, construit les dépôts et services puis retourne le contexte applicatif. Aucun rendu HTML.

### 3.3 Sécurité

**`GestionSession`**

- `demarrer(): void`
- `obtenirIdentite(): ?array`
- `definirIdentite(array $identite): void`
- `renouvelerIdentifiant(): void`
- `detruire(): void`

Configure les cookies `HttpOnly`, `Secure` si HTTPS et `SameSite` adapté ; renouvelle l'identifiant après authentification ; stocke une identité normalisée ; détruit la session à la déconnexion administrateur.

**`ServiceAuthentification`**

- `authentifierCleEquipe(int $equipe, string $cle): ?array`
- `authentifierCleTableau(string $cle): ?array`
- `authentifierAdministrateur(string $identifiant, string $motDePasse): bool`
- `authentifierIdentifiantEquipe(string $identifiant, string $cle): ?array`

Vérifie les clés propres à chaque rôle et équipe. Prend en charge la connexion directe avec l'identifiant `equipe1` à `equipe4`. Ne conserve jamais la clé reçue dans la session ni dans les logs.

**`GestionUrlRetour`**

- `extraireDestination(?string $url): string`
- `validerDestination(string $url): string`
- `retirerCle(string $url): string`
- `verifierCoherence(string $destination, ?int $equipe, array $identite): bool`

N'autorise que les trois pages fonctionnelles, refuse les destinations externes ou altérées, conserve les paramètres GET utiles et supprime `cle` après authentification.

**`GestionJetonCsrf`**

- `generer(): string`
- `verifier(?string $jeton): bool`

Génère et vérifie un jeton aléatoire associé à la session pour chaque action POST modifiant l'état.

### 3.4 Persistance et dépôts

**`StockageJson`**

- `lire(string $nomFichier): array`
- `lireAvecVerrou(string $nomFichier): array`
- `ecrire(string $nomFichier, array $donnees): void`
- `modifier(string $nomFichier, callable $miseAJour): array`

Liste blanche des six fichiers, verrouillage, gestion des erreurs JSON, écriture temporaire et remplacement atomique.

**`DepotEquipes`**

- `obtenir(int $equipe): array`
- `enregistrer(int $equipe, array $donnees): void`
- `obtenirToutes(): array`

Accède uniquement aux quatre fichiers d'équipe et ne retourne jamais les codes ou années attendues dans les données publiques.

**`DepotPartie`**

- `obtenir(): array`
- `enregistrer(array $donnees): void`
- `modifier(callable $miseAJour): array`

Centralise les accès à `suivi-partie.json`.

**`DepotMessages`**

- `obtenir(): array`
- `enregistrer(array $donnees): void`
- `modifier(callable $miseAJour): array`

Centralise les accès à `messages.json` et préserve la fermeture indépendante par destinataire.

### 3.5 Services métier

**`ServiceCodePorte`**

- `valider(int $equipe, array $symboles): array`
- `verifierSequence(array $symboles): bool`
- `comparerSequence(array $saisie, array $attendue): string`

Vérifie cinq symboles autorisés et distincts, compare la séquence au code serveur et retourne l'un des résultats `code_correct`, `mauvais_ordre`, `code_incorrect` ou `saisie_invalide`. Une tentative complète validée incrémente le compteur ; sélection et effacement ne sont pas des tentatives. Seule la porte de l'équipe authentifiée est modifiée. Toute progression est refusée si la partie n'est pas en cours.

**`ServicePartie`**

- `obtenirEtat(): array`
- `demarrer(): array`
- `mettreEnPause(): array`
- `reprendre(): array`
- `terminer(): array`
- `calculerChronometrage(?int $instantCourant = null): array`
- `enregistrerResolution(int $equipe, int $instantValidation): array`
- `verifierFinAutomatique(): bool`

Gère les états `En attente`, `En cours`, `En pause`, `Terminée`, les transitions, horodatages, temps écoulé, temps restant, cumul des pauses et fin automatique. Le serveur est la source de vérité. Le compte à rebours peut devenir négatif sans terminer la partie.

**`ServiceAnnee`**

- `valider(int $equipe, string $anneeSaisie): array`
- `obtenirAnneeAttendue(int $equipe): int`

Compare côté serveur, autorise la correction après une mauvaise saisie, rend la validation correcte définitive, enregistre l'instant et le temps de résolution, puis demande la vérification de fin automatique. Refuse toute validation pendant une pause ou après la fin.

**`ServiceMessages`**

- `envoyer(array $equipes, string $contenu): array`
- `obtenirPourEquipe(int $equipe): array`
- `fermerPourEquipe(string $identifiantMessage, int $equipe): void`
- `obtenirHistorique(): array`

Gère l'envoi ciblé, la fermeture indépendante par destinataire et l'historique. Le texte est traité comme texte brut. La visibilité d'un message fermé dans l'historique d'équipe reste à confirmer.

**`ServiceArchivage`**

- `archiverEtReinitialiser(): string`
- `creerRepertoireArchive(): string`
- `copierFichiersActifs(string $repertoireArchive): void`
- `reinitialiserSuivi(): void`
- `reinitialiserMessages(): void`

Crée `data/archives/AAAA-MM-JJ_HH-MM/`, archive les six JSON, conserve les configurations d'équipe à l'identique, réinitialise suivi et messages, laisse la partie en attente et ne modifie jamais `.env`. La stratégie en cas d'échec partiel doit être décidée avant codage.

### 3.6 Contrôleurs

**`ControleurConnexion`**

- `afficher(array $requete): array`
- `traiter(array $donnees, array $destination): array`
- `determinerDestination(array $identite, array $destination): string`

Traite les clés et identifiants, l'accès direct et l'URL d'origine, puis crée la session et redirige sans `cle`.

**`ControleurPorte`**

- `afficher(): array`
- `soumettreCode(array $donnees): array`
- `obtenirEtat(): array`
- `fermerNotification(string $identifiantMessage): void`

Utilise l'équipe de session, transmet les soumissions au service de code, bloque la progression selon l'état de partie et ne fournit que les données de cette équipe.

**`ControleurSupervision`**

- `afficher(): array`
- `traiterAction(string $action, array $donnees): array`
- `obtenirEtat(): array`
- `deconnecter(): void`

Réserve les commandes à l'administrateur : démarrer, pause, reprise, arrêt, validation d'année, messages, réinitialisation et déconnexion. Aucune ouverture manuelle de porte ni validation manuelle de code.

**`ControleurTableauDeBord`**

- `afficher(): array`
- `obtenirEtat(): array`

Fournit une synthèse en lecture seule, sans code ni réponse attendue.

### 3.7 Support, textes et échappement

**`TextesApplication`** centralise tous les libellés, messages d'erreur et textes applicatifs en français.

**`EchappementHtml`**

- `public static function echapper(string $valeur): string`

Encode les valeurs dynamiques pour le contexte HTML en UTF-8.

### 3.8 JavaScript

| Fichier | Fonctions prévues |
|---|---|
| `connexion.js` | `initialiserFormulaireConnexion()` |
| `supervision.js` | `chargerEtatSupervision()`, `envoyerActionSupervision(action, donnees)`, `afficherEtatSupervision(donnees)`, `initialiserActualisationSupervision()` |
| `tableau-de-bord.js` | `chargerEtatTableauDeBord()`, `afficherEtatTableauDeBord(donnees)`, `initialiserActualisationTableauDeBord()` |
| `porte-des-etoiles.js` | `selectionnerSymbole(symbole)`, `effacerSelection()`, `soumettreCode()`, `afficherResultatCode(resultat)`, `chargerNotifications()`, `fermerNotification(identifiantMessage)`, `initialiserActualisationPorte()` |

Chaque fonction créée ou modifiée reçoit un JSDoc en français. JavaScript ne contient aucun code secret et ne décide jamais de la validité d'un code ou d'une année.

### 3.9 CSS et HTML Stargate

Les styles sont séparés dans les cinq fichiers CSS de l'arborescence. Le HTML V81 est parcouru intégralement avant extraction : identifiants DOM, sélecteurs, fonctions JavaScript, événements, objets Three.js, animations et sons sont inventoriés. Le bloc `<style>` placé après `</html>` dans la référence doit être repositionné sans altérer ses règles visuelles. La dépendance Three.js existante est conservée tant qu'un changement n'est pas validé.

### 3.10 Contrats JSON existants

| Fichier | État observé / évolution prévue |
|---|---|
| `equipe-1.json` à `equipe-4.json` | Conserver les propriétés présentes ; renseigner code, phrase et année uniquement après confirmation. |
| `suivi-partie.json` | Préserver partie, horodatages, chronométrage et état des quatre équipes ; définir l'emplacement exact de la durée initiale. |
| `messages.json` | Préserver la collection `messages` ; définir la structure de fermeture par destinataire avant codage. |

### 3.11 Documentation obligatoire

DocBlock PHP et JSDoc JavaScript en français pour chaque classe, méthode, fonction, interface et type créé ou modifié. Ajouter `@param`, `@return` et `@throws` lorsqu'ils sont pertinents. Aucune documentation formelle n'est requise pour les variables locales, attributs et constantes.

---

## 4. Étapes d'implémentation séquentielles

**Règle impérative : une seule étape/sous-étape à la fois.** Avant de coder chaque méthode ou fonctionnalité, présenter signature, rôle, logique, dépendances et impacts. Attendre l'accord explicite. Ne jamais anticiper l'étape suivante.

### Étape 0 — Préparation et contrats

#### 0.1 Inventaire du dépôt
- [ ] Relever l'arborescence et confirmer les fichiers réellement présents.
- [ ] Confirmer l'absence des quatre pages PHP et des classes applicatives.
- [ ] Relever les structures des six JSON sans les modifier.
- [ ] Relever les versions PHP disponibles et les dépendances présentes.
- **Livrable :** inventaire factuel de l'état initial.

#### 0.2 Inventaire du moteur Stargate
- [ ] Parcourir le HTML V81 en entier.
- [ ] Inventorier identifiants DOM, CSS, fonctions JS, événements, objets Three.js et séquences visuelles.
- [ ] Repérer sélection, validation, ouverture, robot, explosion, blocage, réparation et reset.
- [ ] Repérer les dépendances externes.
- [ ] Distinguer reset visuel de la porte et réinitialisation globale.
- **Livrable :** cartographie précise des éléments à préserver.

#### 0.3 Confirmation des données métier
- [ ] Confirmer les quatre codes de cinq symboles.
- [ ] Obtenir les quatre phrases finales exactes.
- [ ] Confirmer les années attendues et leur correspondance avec les phrases.
- [ ] Confirmer la durée initiale du compte à rebours.
- [ ] Confirmer le rôle éventuel des ESP32.
- [ ] Confirmer l'action des joueurs pour réparer après explosion.
- [ ] Confirmer la visibilité des notifications fermées dans l'historique d'équipe.
- [ ] Confirmer le format d'affichage des temps.
- [ ] Identifier les appareils et navigateurs du jour du jeu.
- [ ] Confirmer les droits administrateur sur tableau de bord et portes.
- **Critère :** aucune donnée métier manquante n'est inventée.

#### 0.4 Validation des signatures et contrats
- [ ] Présenter les signatures de la section 3.
- [ ] Valider les responsabilités et dépendances.
- [ ] Définir les formats exacts des réponses HTTP/JSON.
- [ ] Définir les actions POST, paramètres et transitions d'état.
- [ ] Définir les textes applicatifs dans `TextesApplication`.
- [ ] Valider le chargement des classes et les chemins privés.
- [ ] Confirmer le document root IONOS.
- **Critère :** signatures et contrats approuvés explicitement.

#### 0.5 Protection des secrets
- [ ] Confirmer le document root `public/`.
- [ ] Créer `.gitignore` et exclure `.env`, fichiers temporaires JSON et sauvegardes locales.
- [ ] Retirer `.env` du suivi Git en conservant la copie locale.
- [ ] Conserver `env.example` avec des valeurs fictives.
- [ ] Si un secret réel a été publié, le renouveler.
- **Fichiers :** `.gitignore` à créer, `.env` à dé-suivre, `env.example` à conserver.
- **Précaution :** retirer `.env` du dernier commit ne supprime pas ses versions historiques ; les secrets réels publiés doivent être renouvelés.

### Étape 1 — Configuration et bootstrap

#### 1.1 `ConfigurationApplication`
- [ ] Créer `src/Configuration/ConfigurationApplication.php`.
- [ ] Implémenter `charger(string $racineProjet): self`.
- [ ] Lire `.env` depuis la racine déterminée par le serveur.
- [ ] Parser les lignes, ignorer commentaires et lignes vides.
- [ ] Refuser les variables obligatoires absentes sans révéler leur valeur.
- [ ] Implémenter `obtenir(string $nom): string`.
- [ ] Implémenter `obtenirCheminDonnees(string $nomFichier): string` avec liste blanche.
- [ ] Implémenter `obtenirRacineProjet(): string`.
- **Critère :** configuration centralisée, chemins maîtrisés, aucun secret codé en dur.

#### 1.2 `GestionSession`
- [ ] Créer `src/Securite/GestionSession.php`.
- [ ] Implémenter `demarrer(): void` après configuration des cookies.
- [ ] Configurer `HttpOnly`, `Secure` sous HTTPS et `SameSite` adapté.
- [ ] Implémenter `obtenirIdentite(): ?array`, `definirIdentite(array $identite): void`, `renouvelerIdentifiant(): void` et `detruire(): void`.
- [ ] Ne prévoir aucune connexion persistante.
- **Critère :** identité de session explicite, non dérivée de GET.

#### 1.3 Bootstrap
- [ ] Créer `src/bootstrap.php`.
- [ ] Implémenter `chargerContexteApplication(): array`.
- [ ] Déterminer les chemins à partir de `__DIR__`.
- [ ] Charger les classes dans l'ordre approuvé.
- [ ] Construire configuration, dépôts, services et contrôleurs.
- [ ] Configurer puis démarrer la session.
- [ ] Ne produire aucune sortie HTML.
- **Critère :** contexte commun disponible aux pages publiques.

### Étape 2 — Persistance JSON et dépôts

#### 2.1 `StockageJson`
- [ ] Créer `src/Persistance/StockageJson.php`.
- [ ] Implémenter `lire(string $nomFichier): array`.
- [ ] Implémenter `lireAvecVerrou(string $nomFichier): array`.
- [ ] Implémenter `ecrire(string $nomFichier, array $donnees): void`.
- [ ] Implémenter `modifier(string $nomFichier, callable $miseAJour): array`.
- [ ] Limiter l'accès à une liste blanche des six JSON.
- [ ] Couvrir la séquence lecture-modification-écriture par verrou.
- [ ] Écrire dans un fichier temporaire du même répertoire, vérifier l'écriture et remplacer atomiquement lorsque possible.
- [ ] Libérer les ressources et supprimer les fichiers temporaires en cas d'erreur.
- **Critère :** aucun chemin utilisateur, aucune écriture partielle volontaire, erreurs non divulgatrices.

#### 2.2 Dépôts
- [ ] Créer `src/Depot/DepotEquipes.php` avec `obtenir(int $equipe): array`, `enregistrer(int $equipe, array $donnees): void`, `obtenirToutes(): array`.
- [ ] Créer `src/Depot/DepotPartie.php` avec `obtenir(): array`, `enregistrer(array $donnees): void`, `modifier(callable $miseAJour): array`.
- [ ] Créer `src/Depot/DepotMessages.php` avec les mêmes opérations de lecture, écriture et modification.
- [ ] Préserver les propriétés JSON existantes.
- [ ] Ne pas exposer codes et années attendues aux contrôleurs publics.
- **Critère :** les services et contrôleurs n'accèdent pas directement aux fichiers.

### Étape 3 — Authentification centralisée

#### 3.1 `ServiceAuthentification`
- [ ] Créer `src/Securite/ServiceAuthentification.php`.
- [ ] Implémenter les quatre signatures de la section 3.3.
- [ ] Vérifier strictement les clés équipe, tableau de bord et administrateur.
- [ ] Associer `equipe=0` exclusivement à la clé tableau de bord.
- [ ] Refuser les clés incompatibles avec la page demandée.
- [ ] Ne jamais stocker ni journaliser une clé reçue.
- **Critère :** authentification valide produit une identité structurée ; sinon refus.

#### 3.2 `GestionUrlRetour`
- [ ] Créer `src/Securite/GestionUrlRetour.php`.
- [ ] Implémenter `extraireDestination()`, `validerDestination()`, `retirerCle()` et `verifierCoherence()`.
- [ ] Autoriser seulement les trois pages fonctionnelles.
- [ ] Refuser hôte externe, schéma externe, chemin traversant ou ressource non autorisée.
- [ ] Conserver les paramètres GET utiles, retirer `cle`.
- **Critère :** aucune redirection externe et aucune clé dans l'URL finale.

#### 3.3 `GestionJetonCsrf`
- [ ] Créer `src/Securite/GestionJetonCsrf.php`.
- [ ] Implémenter `generer(): string` avec une source cryptographiquement sûre.
- [ ] Implémenter `verifier(?string $jeton): bool` avec comparaison sûre.
- [ ] Associer le jeton à la session.
- [ ] Prévoir son usage pour chaque POST modifiant l'état.
- **Critère :** contrat CSRF défini pour toutes les actions d'écriture.

#### 3.4 Connexion
- [ ] Créer `src/Controleur/ControleurConnexion.php`.
- [ ] Implémenter `afficher(array $requete): array`, `traiter(array $donnees, array $destination): array`, `determinerDestination(array $identite, array $destination): string`.
- [ ] Créer `public/login.php`.
- [ ] Gérer connexion avec URL d'origine et connexion directe.
- [ ] Prendre en charge l'administrateur, la clé tableau de bord et les identifiants `equipe1` à `equipe4` avec leurs clés.
- [ ] Renouveler l'identifiant de session après succès.
- [ ] Rediriger vers une destination cohérente sans paramètre `cle`.
- [ ] Externaliser les textes dans `TextesApplication`.
- **Critère :** `login.php` est l'unique point d'authentification.

#### 3.5 Protection des pages
- [ ] Définir le mécanisme commun de contrôle de rôle approuvé à l'étape 0.4.
- [ ] Créer `public/supervision.php` avec accès administrateur.
- [ ] Créer `public/tableau-de-bord.php` avec accès tableau de bord.
- [ ] Créer `public/porte-des-etoiles.php` avec accès équipe.
- [ ] Rediriger les sessions absentes/inadaptées vers login avec l'URL d'origine.
- [ ] Ne jamais utiliser GET pour changer l'identité.
- **Critère :** chaque page exige le rôle de session prévu.

### Étape 4 — Cycle de vie et chronométrage

#### 4.1 Contrat JSON de chronométrage
- [ ] Définir l'emplacement exact de la durée initiale dans le JSON, sans inventer sa valeur.
- [ ] Préserver les propriétés existantes de `suivi-partie.json`.
- [ ] Définir les horodatages de démarrage, pause, reprise et fin.
- [ ] Définir le cumul des pauses et la représentation du temps restant négatif.
- **Critère :** contrat de chronométrage approuvé.

#### 4.2 `ServicePartie`
- [ ] Créer `src/Service/ServicePartie.php`.
- [ ] Implémenter `obtenirEtat(): array`, `demarrer(): array`, `mettreEnPause(): array`, `reprendre(): array`, `terminer(): array`.
- [ ] Implémenter `calculerChronometrage(?int $instantCourant = null): array`.
- [ ] Implémenter `enregistrerResolution(int $equipe, int $instantValidation): array`.
- [ ] Implémenter `verifierFinAutomatique(): bool`.
- [ ] Appliquer les transitions autorisées.
- [ ] Exclure les pauses du temps écoulé et des temps de résolution.
- [ ] Ne pas terminer la partie au passage à zéro.
- [ ] Bloquer toute progression en pause ou après fin.
- **Critère :** états, transitions et chronométrage conformes au cadrage.

### Étape 5 — Validation serveur des portes

#### 5.1 Paramètres d'équipe
- [ ] Charger code et phrase depuis le JSON privé correspondant à l'identité de session.
- [ ] Ne jamais injecter le code dans le HTML ou JavaScript.
- [ ] Ne jamais inclure code ou phrase non révélée dans une réponse de lecture.
- **Critère :** les données sensibles restent côté serveur.

#### 5.2 `ServiceCodePorte`
- [ ] Créer `src/Service/ServiceCodePorte.php`.
- [ ] Implémenter `valider(int $equipe, array $symboles): array`, `verifierSequence(array $symboles): bool`, `comparerSequence(array $saisie, array $attendue): string`.
- [ ] Refuser séquence incomplète, doublons ou symboles hors liste.
- [ ] Distinguer code exact, bons symboles mauvais ordre et combinaison incorrecte.
- [ ] Incrémenter uniquement pour une séquence complète soumise.
- [ ] Modifier uniquement l'équipe de la session.
- [ ] Refuser la progression si la partie n'est pas en cours.
- **Critère :** validation serveur, trois résultats distincts et portes indépendantes.

#### 5.3 `ControleurPorte`
- [ ] Créer `src/Controleur/ControleurPorte.php`.
- [ ] Implémenter `afficher(): array`, `soumettreCode(array $donnees): array`, `obtenirEtat(): array`, `fermerNotification(string $identifiantMessage): void`.
- [ ] Définir les actions POST et exiger CSRF.
- [ ] Retourner uniquement l'état de l'équipe authentifiée.
- [ ] Prévoir une réponse de lecture pour l'actualisation.
- **Critère :** aucun traitement métier de porte dans la page PHP ou le navigateur.

### Étape 6 — Intégration Stargate

#### 6.1 Extraction maîtrisée
- [ ] Créer `public/assets/css/porte-des-etoiles.css` à partir du HTML de référence.
- [ ] Créer/intégrer `public/assets/js/porte-des-etoiles.js`.
- [ ] Conserver identifiants DOM, dimensions, objets, matériaux, animations, sons et responsive.
- [ ] Repositionner le bloc `<style>` final sans changer ses règles.
- [ ] Conserver la dépendance Three.js existante.
- [ ] Ne supprimer aucune fonction avant d'en avoir établi les usages.
- **Critère :** séparation des ressources sans refonte visuelle.

#### 6.2 Branchement de la validation serveur
- [ ] Recenser les usages réels de `TEAM_CODES`, `TEAM_URL_CODE_MAP`, `getConnectedTeam()`, `isTeamCodeValid()` et `identifyTeamFromCode()`.
- [ ] Supprimer du navigateur les anciens codes en clair.
- [ ] Remplacer la validation locale par l'appel serveur.
- [ ] Fournir au moteur uniquement le résultat serveur et l'identifiant d'équipe issu de la session.
- [ ] Code exact : ouverture et phrase finale de l'équipe.
- [ ] Bons symboles dans le mauvais ordre : ouverture, robot, explosion.
- [ ] Combinaison incorrecte : tentative d'ouverture et porte bloquée.
- [ ] Préserver le mécanisme de réparation/reset visuel.
- **Critère :** le navigateur ne décide jamais de la validité du code.

#### 6.3 Page de porte
- [ ] Rendre la scène dans `public/porte-des-etoiles.php`.
- [ ] Injecter uniquement les paramètres d'affichage non sensibles.
- [ ] Ajouter l'interface de notifications.
- [ ] Afficher la pause et bloquer visuellement la progression, en plus du blocage serveur.
- **Critère :** scène existante intégrée à la session d'équipe.

### Étape 7 — Année finale

#### 7.1 `ServiceAnnee`
- [ ] Créer `src/Service/ServiceAnnee.php`.
- [ ] Implémenter `valider(int $equipe, string $anneeSaisie): array` et `obtenirAnneeAttendue(int $equipe): int`.
- [ ] Comparer l'année côté serveur.
- [ ] Permettre la correction d'une mauvaise saisie.
- [ ] Rendre une validation correcte définitive jusqu'à réinitialisation.
- [ ] Enregistrer instant et temps de résolution.
- [ ] Déclencher la vérification de fin automatique.
- [ ] Refuser validation en pause ou après fin.
- **Critère :** résolution définitive et cohérente avec les états de partie.

#### 7.2 Présentation supervision
- [ ] Ajouter un bloc de saisie par équipe.
- [ ] Afficher retour rouge si erreur, scintillement vert puis vert permanent « Énigme résolue » si succès.
- [ ] Désactiver la validation après réussite et fin de partie.
- [ ] Afficher le temps selon le format confirmé à l'étape 0.3.
- **Critère :** validation lisible et irréversible après réussite.

### Étape 8 — Messages

#### 8.1 `ServiceMessages`
- [ ] Créer `src/Service/ServiceMessages.php`.
- [ ] Implémenter `envoyer(array $equipes, string $contenu): array`, `obtenirPourEquipe(int $equipe): array`, `fermerPourEquipe(string $identifiantMessage, int $equipe): void`, `obtenirHistorique(): array`.
- [ ] Générer un identifiant unique par message.
- [ ] Conserver un état de fermeture distinct par destinataire.
- [ ] Valider les destinataires 1 à 4.
- [ ] Traiter le contenu en texte brut.
- [ ] Préserver l'historique de supervision.
- **Critère :** fermeture d'un message pour une équipe sans effet sur les autres.

#### 8.2 Interfaces
- [ ] Ajouter l'envoi ciblé dans `supervision.php`.
- [ ] Permettre la sélection d'une ou plusieurs équipes.
- [ ] Afficher l'historique et les destinataires.
- [ ] Afficher les messages destinés à l'équipe dans la porte.
- [ ] Ajouter la fermeture individuelle.
- [ ] Appliquer la règle de visibilité après fermeture confirmée.
- [ ] Protéger les écritures par CSRF.
- **Critère :** messages isolés par destinataire.

### Étape 9 — Supervision

#### 9.1 `ControleurSupervision`
- [ ] Créer `src/Controleur/ControleurSupervision.php`.
- [ ] Implémenter `afficher(): array`, `traiterAction(string $action, array $donnees): array`, `obtenirEtat(): array`, `deconnecter(): void`.
- [ ] Autoriser uniquement les commandes définies par le cadrage.
- [ ] Refuser les modifications après fin, sauf réinitialisation.
- [ ] Ne pas ajouter d'ouverture manuelle de porte ni validation manuelle de code.

#### 9.2 Interface `supervision.php`
- [ ] Afficher état global et deux compteurs.
- [ ] Ajouter Démarrer, Pause, Reprendre, Arrêter avec confirmation.
- [ ] Afficher les quatre équipes, portes et tentatives.
- [ ] Ajouter saisie/validation d'année.
- [ ] Ajouter messages et historique.
- [ ] Ajouter réinitialisation avec confirmation.
- [ ] Ajouter déconnexion administrateur.
- [ ] Après fin, afficher un mode consultation ; seule la réinitialisation reste modifiable.
- **Critère :** pilotage réservé à l'administrateur et limité au cadrage.

### Étape 10 — Tableau de bord

#### 10.1 `ControleurTableauDeBord`
- [ ] Créer `src/Controleur/ControleurTableauDeBord.php`.
- [ ] Implémenter `afficher(): array` et `obtenirEtat(): array`.
- [ ] Préparer état global, portes, tentatives et résolution.
- [ ] Exclure codes, phrases non révélées, années attendues et secrets.

#### 10.2 Interface
- [ ] Créer la synthèse des quatre équipes dans `tableau-de-bord.php`.
- [ ] Afficher état global, état de chaque porte, tentatives, porte ouverte et résolution année.
- [ ] N'ajouter aucune commande.
- **Critère :** page strictement en lecture seule et sans réponse attendue.

### Étape 11 — Actualisation cinq secondes

- [ ] Définir les contrats GET de lecture pour supervision, tableau de bord et porte.
- [ ] Limiter chaque réponse aux données affichables.
- [ ] Créer les fonctions de lecture et d'affichage dans les trois fichiers JS.
- [ ] Actualiser à intervalle de cinq secondes.
- [ ] Ne pas lancer une nouvelle lecture si la précédente est en cours.
- [ ] Mettre à jour uniquement les zones nécessaires.
- [ ] Arrêter l'actualisation lorsque la page est quittée.
- **Critère :** affichages actualisés sans rechargement complet et sans écriture involontaire.

### Étape 12 — Archivage et réinitialisation

#### 12.1 Service
- [ ] Créer `src/Service/ServiceArchivage.php`.
- [ ] Implémenter `archiverEtReinitialiser(): string`, `creerRepertoireArchive(): string`, `copierFichiersActifs(string $repertoireArchive): void`, `reinitialiserSuivi(): void`, `reinitialiserMessages(): void`.
- [ ] Créer `data/archives/AAAA-MM-JJ_HH-MM/`.
- [ ] Archiver les six JSON actifs.
- [ ] Recréer les quatre JSON équipe avec exactement leurs valeurs précédentes.
- [ ] Réinitialiser le suivi avec la durée configurée.
- [ ] Réinitialiser les messages à une collection vide.
- [ ] Ne pas modifier `.env`.
- [ ] Laisser l'état « En attente », chronomètre arrêté, sans démarrage automatique.
- [ ] Définir avant codage la conduite en cas d'échec partiel.
- **Critère :** archive des six JSON et nouvelle partie initialisée sans perte de configuration.

#### 12.2 Intégration
- [ ] Ajouter confirmation explicite dans la supervision.
- [ ] Réserver l'action à l'administrateur.
- [ ] Exiger CSRF.
- [ ] Afficher un résultat sans chemin privé.
- **Critère :** réinitialisation accessible uniquement depuis la supervision autorisée.

### Étape 13 — Documentation et préparation IONOS

#### 13.1 Documentation
- [ ] Actualiser `LISEZ-MOI.md` selon V3.
- [ ] Décrire les quatre pages et leurs droits.
- [ ] Décrire `.env` à partir de `env.example` sans publier de secret.
- [ ] Documenter le document root `public/`.
- [ ] Documenter la protection de `src/`, `data/` et `.env`.
- [ ] Décrire les six JSON et l'archivage.
- [ ] Décrire les dépendances externes réellement conservées.
- [ ] Documenter en français les classes, méthodes et fonctions.

#### 13.2 Hébergement
- [ ] Confirmer la version PHP IONOS.
- [ ] Confirmer le document root.
- [ ] Préparer `.env` directement sur l'hébergement.
- [ ] Définir les permissions minimales d'écriture pour JSON et archives.
- [ ] Vérifier que données et secrets ne sont pas accessibles par HTTP.
- [ ] Utiliser HTTPS pour le cookie `Secure`.
- [ ] Ne pas ajouter cron, daemon ou worker non demandé.

#### 13.3 Restitution
- [ ] Décrire les fichiers ajoutés, modifiés ou supprimés.
- [ ] Expliquer les méthodes et fonctions livrées.
- [ ] Expliquer les choix techniques et effets de bord évités.
- [ ] Fournir une revue précise des changements.
- [ ] Ne proposer un commit Conventional Commits que si le code est sain et sans erreur connue.

---

## 5. Stratégie de tests — aucun test

**Décision explicite du propriétaire : aucun test ne doit être prévu, créé ou exécuté.**

Sont donc exclus :
- tests unitaires ;
- tests d'intégration ;
- tests de sécurité ;
- tests fonctionnels ;
- recette manuelle ou automatisée ;
- tests visuels et de compatibilité ;
- dossiers, fichiers, dépendances ou frameworks de tests ;
- commandes, scripts ou pipelines de tests.

Cette section déroge explicitement à la section 6 des Instructions Globales et à la section 15 du cadrage V3. Aucun autre élément des exigences de sécurité du cadrage n'est supprimé : les protections d'accès, sessions, CSRF, échappement, validation d'entrée, protection des chemins et intégrité JSON restent obligatoires.

Les critères de clôture des étapes portent uniquement sur le périmètre documentaire approuvé, les fichiers livrés et la revue des modifications. Cette revue ne doit pas être présentée comme un test ou une recette.

---

## 6. Précautions et points de vigilance

### 6.1 Informations métier non fournies

Les points suivants restent bloquants pour les étapes qui en dépendent :

1. quatre codes définitifs de cinq symboles ;
2. quatre phrases finales exactes ;
3. années attendues et correspondance avec les phrases ;
4. durée initiale du compte à rebours ;
5. rôle éventuel des ESP32 ;
6. action exacte des joueurs pour réparer après explosion ;
7. visibilité des messages fermés dans l'historique d'équipe ;
8. format d'affichage des temps ;
9. appareils et navigateurs utilisés le jour du jeu ;
10. droits administrateur sur tableau de bord et portes.

Aucune de ces informations ne doit être inventée.

### 6.2 Authentification et sessions

- `login.php` est l'unique point d'authentification.
- Les pages fonctionnelles contrôlent la session à chaque requête.
- Le rôle et l'équipe proviennent exclusivement de la session.
- Les clés sont retirées des URLs après authentification et jamais inscrites dans les logs.
- Les URLs de retour sont internes et limitées aux trois pages fonctionnelles.
- Les cookies sont `HttpOnly`, `Secure` sous HTTPS et `SameSite` adapté.
- L'identifiant de session est renouvelé après authentification.
- Les actions de modification sont protégées par CSRF.

### 6.3 Données métier

- Les codes restent dans les JSON privés.
- Les années attendues restent côté serveur.
- Les phrases finales ne sont transmises qu'après validation du code exact.
- Le tableau de bord ne révèle aucune réponse.
- Une équipe ne lit et ne modifie que ses propres données.
- Les commandes de supervision sont réservées à l'administrateur.

### 6.4 JSON et concurrence

- Toutes les écritures passent par `StockageJson`.
- Les verrous couvrent la séquence lecture-modification-écriture.
- Les fichiers temporaires sont dans le même répertoire que la cible.
- Le remplacement est atomique lorsque le système de fichiers le permet.
- Une erreur ne doit pas laisser un fichier actif tronqué.
- Les chemins sont déterminés par une liste blanche.
- Une équipe ne peut pas écraser les données d'une autre.
- La fermeture des messages est indépendante par destinataire.

### 6.5 Chronométrage

- Le serveur est la source de vérité.
- JavaScript ne fait qu'afficher les valeurs.
- Les pauses sont exclues des temps écoulés et de résolution.
- Le compte à rebours peut devenir négatif sans fin automatique.
- La fin manuelle est définitive.
- La fin automatique survient lorsque les quatre années sont validées.
- Une partie terminée ne peut être reprise ; une réinitialisation est nécessaire.

### 6.6 Scène Stargate

- Ne pas refondre le moteur 3D.
- Ne pas modifier animations, sons ou proportions sans justification et accord.
- Ne supprimer aucune fonction avant d'en avoir compris les usages.
- Distinguer reset visuel, réparation et réinitialisation globale.
- Le résultat du code vient du serveur.
- Conserver Three.js tel qu'utilisé dans le fichier de référence tant qu'un changement n'est pas validé.

### 6.7 Hébergement mutualisé IONOS

- Aucun traitement de fond permanent.
- Aucun worker, Redis, Messenger ou service asynchrone.
- Les données doivent être inscriptibles par PHP mais inaccessibles par HTTP.
- Le document root doit pointer vers `public/`.
- La version PHP réelle doit être confirmée avant de choisir une API dépendante.
- Les permissions doivent rester minimales.

### 6.8 Règles de conduite

1. Le présent fichier à la racine est la source unique de vérité du plan.
2. Le cadrage V3 et les Instructions Globales restent applicables, sous réserve de la dérogation explicite sur les tests.
3. Une seule étape/sous-étape à la fois.
4. Avant de coder, présenter signature, rôle, logique et impacts ; attendre l'accord explicite.
5. Modifier uniquement le périmètre validé.
6. Ne jamais inventer une donnée métier manquante.
7. Fournir du code complet, sans omission ni TODO générique.
8. Documenter en français chaque classe, méthode, fonction et type.
9. Externaliser les textes applicatifs dans `TextesApplication`.
10. Après chaque sous-étape, expliquer les changements et effectuer une revue précise du diff.
11. Ne créer ni fichier ni dossier de tests, ni commande de test.
12. Proposer un commit Conventional Commits uniquement si le code est sain et sans erreur connue.

---

## 7. Conditions préalables au développement

- [ ] Le propriétaire valide explicitement le présent plan.
- [ ] Les informations métier bloquantes de la section 6.1 sont fournies ou déclarées hors périmètre pour les étapes concernées.
- [ ] La version PHP IONOS est connue.
- [ ] Le document root IONOS est confirmé.
- [ ] La protection de `.env` dans Git est approuvée.
- [ ] Les signatures et contrats de l'étape 0.4 sont validés.

**Aucun code applicatif ne doit être écrit avant validation explicite du présent plan.**

**Fin du plan.**

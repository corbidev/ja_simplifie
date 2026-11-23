# Cadrage fonctionnel et technique --- JEUX ASSEMBLÉES 2026

**Version :** 3.0 --- intégration de l'authentification centralisée\
**Date :** 23 novembre 2026\
**Statut :** cadrage consolidé à relire et à valider avant la rédaction
du plan d'implémentation\
**Nature :** spécifications fonctionnelles et contraintes techniques ;
ce document ne décrit pas du code déjà implémenté.

------------------------------------------------------------------------

## 1. Objet et périmètre

Le projet consiste à simplifier et adapter le dispositif Stargate
existant pour l'escape game historique **JEUX ASSEMBLÉES 2026**.

L'application comprend : - une page de connexion centralisée ; - une
page de supervision ; - un tableau de bord en lecture seule ; - une page
Stargate utilisée par les quatre équipes.

La simplification porte principalement sur la logique applicative,
l'authentification et le suivi numérique. Elle ne doit pas entraîner une
refonte visuelle inutile de la scène Stargate existante.

### 1.1 Éléments hors périmètre numérique

L'application ne gère pas et ne valide pas : - les énigmes physiques ; -
la découverte des symboles sur les supports papier ; - les pictogrammes
et indices de position ; - la progression des groupes dans les documents
physiques.

Ces éléments restent gérés par les joueurs et les organisateurs.

Le nom de domaine et sa configuration DNS sont gérés séparément par le
propriétaire du projet.

## 2. Organisation du jeu

-   Le jeu comporte **quatre équipes**, identifiées de 1 à 4.
-   Chaque équipe dispose de sa propre porte Stargate.
-   Le parcours physique comporte **quatre énigmes au total**, une par
    groupe.
-   Un cinquième symbole est communiqué directement, sans énigme.
-   Les quatre énigmes physiques permettent d'obtenir les quatre autres
    symboles.
-   Les énigmes physiques ne sont pas suivies par l'application.
-   L'entraide au sein d'une même équipe est autorisée ; l'entraide
    entre équipes concurrentes est interdite.
-   Chaque équipe doit reconstituer une séquence finale de cinq
    symboles.

Les documents remis aux équipes ont un contenu de base commun. Les
pictogrammes peuvent varier selon l'équipe et être placés à des
emplacements différents. Leur contenu précis reste défini par les
documents de jeu.

### 2.1 Références historiques

  ------------------------------------------------------------------------
  Équipe                          Année de référence Thème historique
  --------------------- ---------------------------- ---------------------
  Équipe 1                                      1879 Naissance de *La Tour
                                                     de Garde*

  Équipe 2                                      1914 Présentation du
                                                     *Photo-Drame de la
                                                     Création*

  Équipe 3                                      1922 Assemblée de Cedar
                                                     Point et proclamation
                                                     du Royaume

  Équipe 4                                      1931 Adoption du nom «
                                                     Témoins de Jéhovah »
  ------------------------------------------------------------------------

Les années attendues pour l'énigme finale sont configurées pour chaque
équipe. Elles correspondent à la phrase finale fournie par le robot. Les
correspondances exactes doivent être reprises des documents de jeu et ne
doivent pas être inventées.

## 3. Symboles et codes Stargate

### 3.1 Symboles disponibles

Les neuf symboles présents sur la porte et la console sont conservés :

`☉`, `△`, `◇`, `⌘`, `☽`, `✶`, `⊕`, `∿`, `⋈`

Cinq symboles parmi ces neuf composent le code final.

### 3.2 Règles des codes

-   Chaque code comporte exactement cinq symboles.
-   Les quatre équipes utilisent le même ensemble de cinq symboles, dans
    un ordre différent.
-   Chaque équipe possède sa propre séquence officielle.
-   Les anciennes séquences présentes dans le HTML ne doivent pas être
    reprises.
-   Les nouvelles séquences sont stockées dans les fichiers JSON de
    configuration des équipes.
-   L'application demande uniquement la séquence finale ; elle ne
    demande pas la saisie des pictogrammes.
-   L'ordre des symboles est déterminant.
-   Les codes sont vérifiés côté serveur et ne doivent pas être exposés
    dans le JavaScript ou le HTML transmis au navigateur.

Les séquences exactes des quatre équipes restent à fournir ou à valider
avant leur intégration.

### 3.3 Résultats possibles d'une tentative

  -----------------------------------------------------------------------
  Saisie                  Résultat fonctionnel    Comportement visuel
  ----------------------- ----------------------- -----------------------
  Les cinq symboles       Code correct            La porte s'ouvre et
  exacts, dans le bon                             affiche la phrase
  ordre                                           finale prédéfinie de
                                                  l'équipe.

  Les cinq bons symboles, Mauvais ordre           La porte s'ouvre, le
  dans un ordre incorrect                         robot sort sur la
                                                  passerelle puis
                                                  explose.

  Combinaison incorrecte  Code incorrect          La porte tente de
                                                  s'ouvrir mais reste
                                                  bloquée.
  -----------------------------------------------------------------------

Une tentative correspond à la validation effective d'une séquence
complète de cinq symboles. La sélection ou l'effacement de symboles
avant validation ne constitue pas une tentative.

Les séquences incomplètes et les symboles répétés sont rejetés. Le
détail de leur retour visuel doit rester cohérent avec le scénario de
combinaison incorrecte.

### 3.4 Indépendance des portes

Chaque équipe agit uniquement sur sa propre porte. Une tentative, une
ouverture, une explosion, un blocage ou une réparation ne doit pas
modifier l'état des portes des autres équipes.

## 4. Préservation du dispositif Stargate existant

Fichier HTML de référence :

`Stargate_V81_jambes_blanches_cables_genoux.html`

La migration doit préserver autant que possible à l'identique : - la
scène 3D, ses objets, le modèle et les matériaux ; - les neuf symboles
gravés et la console ; - les animations de rotation, d'activation et des
chevrons ; - l'ouverture de l'horizon ; - les effets visuels et sonores
; - l'arrivée du robot sur la passerelle ; - l'explosion du robot ; - le
scénario de porte bloquée ; - la séquence de réparation/réinitialisation
existante ; - le responsive sur mobile et écran large.

Les adaptations doivent porter prioritairement sur l'intégration PHP, le
chargement des paramètres, la validation côté serveur, le suivi et les
échanges avec la supervision.

Les animations ne doivent pas être réécrites simplement pour faciliter
l'intégration. Toute modification visuelle doit être justifiée par une
exigence du projet et validée au préalable.

### 4.1 Écart connu avec le HTML actuel

Le HTML actuel contient notamment les éléments de logique `TEAM_CODES`,
`TEAM_URL_CODE_MAP`, `getConnectedTeam()`, `isTeamCodeValid()` et
`identifyTeamFromCode()`.

Cette logique doit être adaptée de façon ciblée au modèle de quatre
portes indépendantes et aux trois résultats décrits au chapitre 3. Les
messages holographiques génériques du HTML ne sont pas les phrases
finales définitives des équipes.

## 5. Pages et point d'entrée de l'application

L'application comporte quatre pages PHP : `login.php` et les trois pages
fonctionnelles.

### 5.1 `login.php` --- point d'entrée unique de l'authentification

**Toute authentification est centralisée dans `login.php`.**

Les utilisateurs ne se connectent pas directement sur les pages
fonctionnelles. Lorsqu'une page fonctionnelle est demandée sans session
autorisée, elle redirige vers `login.php` en transmettant l'URL
d'origine et ses paramètres GET.

Les pages fonctionnelles concernées sont : - `porte-des-etoiles.php` ; -
`tableau-de-bord.php` ; - `supervision.php`.

L'URL d'origine doit être conservée afin que `login.php` sache quelle
ressource l'utilisateur voulait ouvrir. Après authentification, la
redirection doit revenir vers cette ressource avec ses paramètres
utiles, mais sans le paramètre `cle`.

#### 5.1.1 Accès avec une clé présente dans l'URL demandée

Si l'URL d'origine contient un paramètre `cle` :

1.  La page demandée redirige vers `login.php` avec l'URL d'origine et
    ses paramètres GET.
2.  `login.php` récupère la clé et les informations de destination.
3.  `login.php` vérifie la clé et vérifie également que cette clé est
    cohérente avec la page demandée et l'identifiant d'équipe fourni.
4.  Si la clé est valide et cohérente avec la destination, `login.php`
    crée la session correspondante.
5.  `login.php` redirige ensuite vers l'URL d'origine, en conservant les
    paramètres GET utiles et en supprimant `cle`.

La clé ne doit donc plus être présente dans l'URL après la création de
la session.

#### 5.1.2 Accès avec une clé absente

Si l'URL demandée ne contient pas de clé exploitable et qu'aucune
session valide n'existe : - l'utilisateur est redirigé vers `login.php`
; - la page de connexion propose la connexion administrateur ou la
saisie d'une clé ; - la destination initialement demandée est conservée
afin de permettre le retour après authentification.

#### 5.1.3 Accès avec une clé incorrecte ou incohérente

Si la clé est incorrecte, ou si elle correspond à une équipe mais n'est
pas autorisée pour la page demandée : - l'utilisateur est redirigé ou
maintenu sur `login.php` ; - un message indique que la connexion a
échoué ; - la page propose de réessayer en saisissant une clé ou de se
connecter en administrateur ; - la destination initialement demandée
reste connue afin de permettre une nouvelle tentative cohérente.

Une clé valide pour une équipe ne suffit pas à autoriser l'accès à
n'importe quelle page. La destination, le numéro d'équipe et le type de
clé doivent être compatibles.

#### 5.1.4 Connexion depuis `login.php` sans URL d'origine

La page de connexion doit également fonctionner lorsqu'elle est ouverte
directement, sans page d'origine.

Dans ce cas : - si l'utilisateur saisit l'identifiant `equipe1` et la
clé correspondante, `login.php` crée une session pour l'équipe 1 puis le
redirige vers `porte-des-etoiles.php` pour cette équipe ; - les
identifiants des autres équipes suivent le même principe ; - une
connexion administrateur réussie ouvre la supervision ; - une clé de
tableau de bord valide ouvre le tableau de bord avec l'identité d'accès
correspondante.

Le comportement exact des libellés de formulaire et du format de saisie
des identifiants sera précisé dans le plan d'implémentation, sans
modifier les règles de routage ci-dessus.

#### 5.1.5 Vérification de cohérence entre clé, équipe et page

`login.php` doit vérifier la cohérence complète entre : - la page
demandée ; - le paramètre `equipe` ; - la clé présentée ; - le type
d'accès correspondant.

Exemples obligatoires :

  ------------------------------------------------------------------------------------------
  URL demandée                               Clé présentée           Résultat
  ------------------------------------------ ----------------------- -----------------------
  `porte-des-etoiles.php?equipe=1&cle=...`   Clé de l'équipe 1       Acceptée si la clé est
                                                                     exacte.

  `porte-des-etoiles.php?equipe=2&cle=...`   Clé de l'équipe 1       Refusée.

  `tableau-de-bord.php?equipe=0&cle=...`     Clé dédiée au tableau   Acceptée si la clé est
                                             de bord                 exacte.

  `tableau-de-bord.php?equipe=1&cle=...`     Clé de l'équipe 1       Refusée, même si la clé
                                                                     est exacte pour
                                                                     l'équipe 1.

  `supervision.php`                          Clé d'une équipe        Refusée.

  `supervision.php`                          Identifiants            Acceptée.
                                             administrateur valides  
  ------------------------------------------------------------------------------------------

Le tableau de bord utilise donc une URL de la forme :

`tableau-de-bord.php?equipe=0&cle=xxxx`

La valeur `equipe=0` désigne l'accès au tableau de bord ; elle ne
représente pas une cinquième équipe.

### 5.2 Vérification de session par les pages fonctionnelles

Après authentification, les pages fonctionnelles ne doivent plus
utiliser la clé d'URL pour déterminer l'identité de l'utilisateur.

Chaque page vérifie la session et détermine à partir de celle-ci : - si
l'utilisateur est administrateur ; - ou à quelle équipe/groupe il est
rattaché ; - ou s'il possède l'identité d'accès au tableau de bord.

Les informations d'identité présentes dans la session constituent la
référence. Un paramètre GET modifié par l'utilisateur ne doit jamais
permettre de changer d'équipe ou de rôle.

La page de porte doit utiliser l'équipe enregistrée dans la session pour
charger les données et l'état de la bonne porte. Elle ne doit pas faire
confiance à un numéro d'équipe fourni uniquement par le navigateur.

La supervision est réservée à l'administrateur. Le tableau de bord est
accessible avec l'identité prévue pour `equipe=0` et selon les droits
administrateur définis par la session. Une session d'équipe ne donne pas
accès à la supervision.

### 5.3 `porte-des-etoiles.php`

**Rôle :** interface de jeu individuelle d'une équipe.

Fonctions attendues : - vérifier la session ; - déterminer l'équipe
depuis la session ; - afficher la scène Stargate existante ; - permettre
la sélection et l'effacement des symboles ; - soumettre une séquence de
cinq symboles ; - afficher le résultat et déclencher le scénario visuel
correspondant ; - afficher la phrase finale propre à l'équipe lorsque le
code est exact ; - afficher les messages de supervision destinés à cette
équipe ; - permettre à l'équipe de fermer ses notifications ; - afficher
un bandeau de pause lorsque la partie est en pause ; - bloquer les
actions de progression numérique lorsque la partie n'est pas en cours.

Si la session est absente ou non autorisée pour cette page,
l'utilisateur est redirigé vers `login.php` avec l'URL demandée.

### 5.4 `supervision.php`

**Rôle :** interface privée de pilotage de la partie.

Fonctions attendues : - vérifier que la session correspond à un
administrateur ; - démarrer la partie ; - mettre la partie en pause ; -
reprendre une partie en pause ; - arrêter la partie après confirmation
; - consulter le suivi des quatre équipes ; - saisir et valider l'année
annoncée oralement par une équipe ; - envoyer un message à une ou
plusieurs équipes ; - réinitialiser la partie après confirmation.

La supervision ne permet pas d'ouvrir manuellement une porte ni de
valider manuellement un code Stargate.

#### Validation de l'année annoncée

Lorsqu'une équipe a obtenu la phrase finale du robot, elle communique
oralement l'année correspondante au superviseur.

Dans la supervision, celui-ci : 1. sélectionne l'équipe ; 2. saisit
l'année annoncée ; 3. clique sur « Valider ».

Le serveur compare l'année saisie à l'année attendue configurée pour
cette équipe.

En cas d'année incorrecte : - le bloc de l'équipe scintille en rouge ; -
le superviseur peut corriger l'année ; - il peut relancer la validation
; - l'équipe n'est pas considérée comme ayant résolu son énigme finale.

En cas d'année correcte : - le bloc de l'équipe scintille en vert ; - il
passe ensuite en vert permanent ; - il affiche « Énigme résolue » ; -
l'équipe est considérée comme ayant trouvé sa réponse ; - cette
validation est définitive jusqu'à la réinitialisation.

Une validation correcte ne peut pas être annulée par le superviseur.

#### Mode consultation après la fin

Après la fin de partie, la supervision est en mode consultation. Le
superviseur peut consulter les résultats et l'historique des messages,
puis réinitialiser la partie. Aucune autre modification ou validation
n'est possible.

### 5.5 `tableau-de-bord.php`

**Rôle :** affichage de projection en lecture seule.

La page vérifie la session et l'identité d'accès au tableau de bord.
Elle présente une synthèse des quatre équipes : - état global de la
partie ; - état de chaque porte ; - nombre de tentatives d'ouverture ; -
porte ouverte ou non ; - énigme finale de l'année résolue ou non.

Le tableau de bord ne doit afficher ni les codes d'activation ni les
réponses attendues. Il ne contient aucune commande de contrôle.

Si la session est absente ou non autorisée, l'utilisateur est redirigé
vers `login.php`.

## 6. Cycle de vie de la partie et chronométrage

Les états fonctionnels sont : - **En attente** : nouvelle partie
initialisée, pas encore démarrée ; - **En cours** : le superviseur a
démarré la partie ; - **En pause** : progression numérique suspendue ; -
**Terminée** : partie définitivement arrêtée.

### 6.1 Démarrage

La partie est initialisée à l'état « En attente ».

Seul le superviseur peut la démarrer. Le clic sur « Démarrer » : - fait
passer l'état à « En cours » ; - enregistre l'instant de démarrage ; -
démarre le chronométrage au même moment.

Une réinitialisation ne démarre jamais automatiquement une nouvelle
partie.

### 6.2 Pause et reprise

Pendant la pause : - les pages des équipes restent consultables ; - les
portes et animations restent visibles ; - aucune soumission de code ni
action faisant progresser le jeu n'est acceptée ; - les messages restent
consultables et peuvent être fermés ; - le chronométrage est suspendu.

Le blocage de progression est appliqué côté serveur, pas uniquement par
désactivation des contrôles dans le navigateur.

À la reprise, le chronométrage continue sans remise à zéro et sans
comptabiliser la durée de pause.

### 6.3 Arrêt et fin de partie

**Tout arrêt du chronomètre termine définitivement la partie.**

Le chronomètre s'arrête dans l'un des deux cas : 1. le superviseur
demande l'arrêt et confirme ; 2. les quatre équipes ont toutes fait
valider correctement leur année.

Dans les deux cas : - le chronomètre s'arrête ; - l'état de la partie
passe à « Terminée » ; - toute nouvelle soumission de code est bloquée
; - toute nouvelle validation d'année est bloquée ; - les résultats
restent consultables ; - aucune reprise n'est possible ; une
réinitialisation est nécessaire.

Un arrêt manuel ne permet jamais de continuer à jouer.

### 6.4 Compte à rebours et temps écoulé

Deux valeurs sont affichées : - le compte à rebours à partir d'une durée
initiale ; - le temps écoulé depuis le démarrage.

La durée initiale du compte à rebours est définie dans le JSON de
paramétrage. Aucune durée fixe ne doit être codée en dur.

Lorsque le compte à rebours atteint zéro, il continue en négatif. Le
passage à zéro ne termine pas la partie et ne bloque pas les équipes.

La pause suspend les deux compteurs. La reprise les poursuit sans remise
à zéro.

### 6.5 Temps de résolution par équipe

Le temps de résolution d'une équipe est calculé depuis le démarrage de
la partie jusqu'à la validation correcte de son année par le
superviseur. Les périodes de pause ne sont pas comptabilisées.

Une équipe dont l'année n'a pas été validée avant la fin de la partie
n'a pas de temps de résolution final.

## 7. Fin de partie et consultation des résultats

Lorsque la partie se termine, la supervision affiche : - un message
visible « PARTIE TERMINÉE » ; - le temps final du chronomètre ; - un
récapitulatif des quatre équipes ; - le statut de résolution de chaque
équipe ; - le temps de résolution de chaque équipe ayant trouvé la bonne
année ; - le nombre de tentatives d'ouverture de chaque porte ; -
l'année validée pour chaque équipe résolue.

Les résultats et l'historique des messages restent consultables jusqu'à
la prochaine réinitialisation et sont archivés lors de celle-ci.

## 8. Messages et notifications

Le superviseur peut envoyer un message à une équipe ou à plusieurs
équipes sélectionnées.

Règles : - les notifications sont affichées sur `porte-des-etoiles.php`
; - une notification reste visible jusqu'à sa fermeture par l'équipe
destinataire ; - la fermeture par une équipe ne ferme pas la
notification pour les autres destinataires ; - l'état de fermeture est
indépendant pour chaque destinataire ; - les messages sont conservés
dans un historique ; - le contenu est affiché comme du texte brut,
jamais exécuté comme HTML ou JavaScript.

L'historique des messages est consultable par le superviseur après la
fin de la partie.

## 9. Actualisation des pages

Les trois pages fonctionnelles vérifient les données toutes les **5
secondes**, sans rechargement complet : - `supervision.php` ; -
`tableau-de-bord.php` ; - `porte-des-etoiles.php`.

Cette actualisation permet de synchroniser : - l'état de la partie ; -
le démarrage, la pause, la reprise et la fin ; - le chronométrage ; -
les messages et notifications ; - les compteurs et états des équipes ; -
la validation des années.

Les opérations d'écriture et les validations restent contrôlées côté
serveur. La page `login.php` n'a pas besoin de cette actualisation
périodique.

## 10. Architecture technique et stockage

### 10.1 Environnement

-   Hébergement : IONOS.
-   Langage serveur : PHP natif.
-   Stockage : fichiers JSON.
-   Aucune base de données relationnelle prévue.
-   Aucun framework PHP imposé.
-   La version PHP réellement disponible chez IONOS doit être vérifiée
    avant l'implémentation.
-   Le nom de domaine est hors périmètre.

Aucune fonctionnalité dépendant d'une version PHP non vérifiée ne doit
être choisie.

### 10.2 Arborescence cible

``` text
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

Les quatre pages PHP sont publiques au sens de leur emplacement dans le
dossier `public/`, mais les pages fonctionnelles restent protégées par
l'authentification centralisée. Les fichiers de données et les secrets
restent hors de la racine publique.

Le découpage interne de `src/` et des ressources CSS/JavaScript sera
précisé dans le plan d'implémentation après inspection des fichiers
existants.

### 10.3 Fichiers JSON actifs

Le stockage actif comprend six fichiers JSON :

  -----------------------------------------------------------------------
  Fichier                             Contenu
  ----------------------------------- -----------------------------------
  `equipe-1.json`                     Code final, phrase finale, année
                                      attendue et paramètres propres à
                                      l'équipe 1.

  `equipe-2.json`                     Configuration de l'équipe 2.

  `equipe-3.json`                     Configuration de l'équipe 3.

  `equipe-4.json`                     Configuration de l'équipe 4.

  `suivi-partie.json`                 État global, chronométrage,
                                      tentatives, états des portes et
                                      progression numérique.

  `messages.json`                     Messages envoyés et état de
                                      fermeture par destinataire.
  -----------------------------------------------------------------------

La durée initiale du compte à rebours est définie dans le JSON de
paramétrage. Son emplacement exact sera fixé dans le plan
d'implémentation.

Les secrets d'authentification restent dans `.env`, et non dans les JSON
publics.

### 10.4 Écritures et concurrence

Les écritures JSON doivent être protégées contre les accès simultanés
: - verrouillage pendant les mises à jour ; - écriture atomique lorsque
cela est adapté ; - prévention de l'écrasement des données d'une autre
équipe ; - gestion des erreurs de lecture, d'écriture et de décodage
JSON.

Une écriture interrompue ne doit pas laisser un fichier actif
partiellement écrit et illisible.

## 11. Authentification, rôles et sessions

### 11.1 Centralisation

`login.php` est le seul point de traitement des authentifications par
clé ou par identifiants administrateur.

Les pages `porte-des-etoiles.php`, `tableau-de-bord.php` et
`supervision.php` : - ne présentent pas leur propre formulaire de
connexion ; - ne traitent pas directement une clé de connexion ; -
vérifient la session à chaque accès ; - redirigent vers `login.php` si
la session est absente ou ne possède pas les droits requis.

### 11.2 Clés dans `.env`

Le fichier `.env`, placé hors de `public/`, contient : - l'identifiant
et le mot de passe administrateur ; - une clé dédiée au tableau de bord
; - quatre clés distinctes pour les équipes (`CLE_EQUIPE_1` à
`CLE_EQUIPE_4`).

Les clés sont créées et renseignées manuellement par le propriétaire du
projet.

Les clés des équipes et du tableau de bord peuvent être modifiées
manuellement dans `.env` entre deux parties. Une réinitialisation ne les
modifie pas.

### 11.3 Identité associée à une session

Après authentification, la session contient une identité fiable
déterminant le rôle et, le cas échéant, l'équipe : - administrateur ; -
équipe 1 ; - équipe 2 ; - équipe 3 ; - équipe 4 ; - accès tableau de
bord correspondant à `equipe=0`.

La valeur `equipe=0` n'est pas une équipe de jeu. Elle identifie l'accès
au tableau de bord.

Les pages fonctionnelles utilisent cette identité de session et ne
déduisent jamais le rôle ou l'équipe d'un paramètre GET modifiable.

### 11.4 Correspondance des accès

  -------------------------------------------------------------------------
  Ressource                 Identité attendue       Authentification
                                                    initiale
  ------------------------- ----------------------- -----------------------
  `porte-des-etoiles.php`   Une équipe de 1 à 4     Clé propre à cette
                                                    équipe et numéro
                                                    d'équipe correspondant.

  `tableau-de-bord.php`     Accès tableau de bord   Clé dédiée au tableau
                            (`equipe=0`)            de bord et paramètre
                                                    `equipe=0`.

  `supervision.php`         Administrateur          Identifiant et mot de
                                                    passe administrateur.
  -------------------------------------------------------------------------

Une clé d'équipe ne donne pas accès au tableau de bord ni à la
supervision. La clé du tableau de bord ne donne pas accès à une porte
d'équipe. Les identifiants administrateur ne doivent pas être confondus
avec une identité d'équipe.

### 11.5 Sessions et cookies

Après authentification, une session PHP est créée. La clé ne doit plus
être conservée dans l'URL ni réutilisée dans les liens internes.

Les cookies de session utilisent : - `HttpOnly` ; - `Secure` lorsque le
site est servi en HTTPS ; - `SameSite` adapté au fonctionnement de
l'application.

La session est une session navigateur, sans mécanisme de connexion
persistante demandé. La session doit être protégée contre la fixation de
session.

La supervision dispose d'une action de déconnexion. Aucun bouton de
déconnexion n'est prévu sur la page d'équipe.

### 11.6 Conservation et sécurité de l'URL de retour

L'URL d'origine est nécessaire au retour après authentification, mais
elle ne doit pas permettre une redirection vers un site externe.

La destination de retour doit être limitée aux pages fonctionnelles
autorisées de l'application. Le paramètre `cle` doit être supprimé de
l'URL finale après authentification réussie. Les autres paramètres GET
utiles sont conservés après validation de leur cohérence avec la page et
l'identité de session.

Une clé ne doit jamais être inscrite dans les journaux applicatifs ni
dans les messages d'erreur.

## 12. Réinitialisation et archivage

La réinitialisation est disponible depuis la supervision et nécessite
une confirmation explicite.

Le message de confirmation indique que la partie actuelle sera archivée
avant l'initialisation de la suivante.

### 12.1 Archivage

Un dossier d'archive est créé pour chaque partie, avec une date et une
heure dans son nom :

`archives/AAAA-MM-JJ_HH-MM/`

L'archive contient les six JSON actifs : - les quatre fichiers de
configuration des équipes ; - le fichier de suivi de partie ; - le
fichier des messages et leur historique.

### 12.2 Nouvelle partie

Après archivage : - les quatre JSON d'équipe actifs sont recréés avec
exactement les mêmes valeurs que précédemment ; - les codes, phrases
finales, années attendues et paramètres d'équipe ne sont pas modifiés
automatiquement ; - un nouveau fichier de suivi est créé avec les
valeurs initiales ; - un nouveau fichier de messages est créé sans les
messages de la partie précédente ; - l'état de la partie est « En
attente » ; - le chronomètre est arrêté et remis à sa valeur initiale
; - aucune équipe ne peut démarrer la partie depuis sa page ; - le
superviseur doit cliquer explicitement sur « Démarrer » pour lancer la
nouvelle partie et son chronomètre.

Les clés du fichier `.env` ne sont pas modifiées par la
réinitialisation.

## 13. Données suivies par l'application

Pour chaque équipe, l'application conserve au minimum : - l'identifiant
de l'équipe ; - le nombre de tentatives de validation du code de porte
; - l'état de la porte ; - l'indicateur porte ouverte ou non ; - l'état
de résolution de l'année finale ; - l'année saisie lors de la validation
par le superviseur ; - l'instant de validation correcte ; - le temps de
résolution calculé ; - les messages destinés à l'équipe et leur état de
fermeture.

Pour la partie, l'application conserve : - l'état global ; - les
horodatages de démarrage, pause, reprise et fin ; - la durée configurée
du compte à rebours ; - le temps écoulé et le temps restant, y compris
lorsqu'il est négatif.

L'application ne suit pas les énigmes physiques ni la découverte des
pictogrammes.

## 14. Sécurité et intégrité

Exigences minimales : - `.env` et les fichiers JSON ne sont pas
accessibles directement depuis le navigateur ; - les codes et années
attendues ne sont pas exposés au navigateur ; - les codes et années sont
comparés côté serveur ; - les identités de session sont établies
exclusivement par `login.php` ; - chaque page vérifie le rôle et
l'équipe à partir de la session ; - un paramètre GET ne permet jamais de
changer d'équipe ou de rôle ; - la cohérence entre page, paramètre
`equipe` et clé est contrôlée par `login.php` ; - les redirections de
retour sont limitées aux pages internes autorisées ; - les actions de
supervision sont protégées par authentification et contrôle de session
; - les actions modifiant l'état sont protégées contre les requêtes
forgées (CSRF) ; - les variables dynamiques affichées en HTML sont
échappées ; - les messages sont affichés comme du texte brut ; - les
entrées sont validées côté serveur ; - les chemins de fichiers sont
déterminés par l'application et ne sont pas construits à partir de
chemins utilisateurs non contrôlés ; - aucune donnée sensible n'est
écrite dans les journaux ; - les erreurs techniques ne révèlent ni
chemins serveur, ni secrets, ni codes ; - les écritures JSON sont
protégées contre la concurrence et les interruptions.

## 15. Vérifications et recette fonctionnelle

**Aucun test unitaire n'est demandé dans ce projet.**

La validation du fonctionnement repose sur : - des tests d'intégration
entre `login.php`, les sessions et les pages fonctionnelles ; - des
tests de sécurité sur les accès, les rôles, les clés, les redirections
et les données ; - une recette fonctionnelle manuelle sur les scénarios
réels du jeu ; - des vérifications visuelles et de compatibilité sur les
appareils et navigateurs utilisés le jour de l'événement.

### 15.1 Authentification et routage

Vérifier notamment : - l'accès à une page fonctionnelle sans session
redirige vers `login.php` ; - l'URL d'origine et ses paramètres GET sont
conservés pendant le parcours de connexion ; - une clé valide crée la
bonne session puis redirige vers l'URL d'origine sans `cle` ; - une clé
absente affiche les possibilités de connexion administrateur ou de
saisie de clé ; - une clé incorrecte affiche un échec et permet une
nouvelle tentative ; - une clé valide mais incompatible avec la page
demandée est refusée ; - `tableau-de-bord.php?equipe=1&cle=...` est
refusé même si la clé correspond à l'équipe 1 ; -
`tableau-de-bord.php?equipe=0&cle=...` accepte uniquement la clé du
tableau de bord ; - une connexion directe à `login.php` avec
l'identifiant `equipe1` et la clé de l'équipe 1 redirige vers la porte
de l'équipe 1 ; - les connexions directes des autres équipes suivent la
même règle ; - une connexion administrateur ouvre la supervision ; - les
pages refusent une session absente, expirée ou d'un rôle incompatible
; - modifier `equipe` dans l'URL après authentification ne permet pas de
changer d'équipe ; - aucune clé ne subsiste dans l'URL après
authentification réussie ; - une URL de retour externe ou altérée ne
provoque pas de redirection externe.

### 15.2 Portes et codes

Vérifier : - le code exact pour chacune des quatre équipes ; - les cinq
bons symboles dans un ordre incorrect ; - la présence d'un symbole
incorrect ; - les séquences incomplètes et les symboles répétés ; -
l'indépendance des quatre portes ; - l'incrémentation correcte du nombre
de tentatives ; - la persistance de l'état après rechargement ; - le
comportement de la porte pendant une pause et après la fin de partie ; -
la conservation des animations, sons, robot, explosion, blocage et
réparation du HTML de référence.

### 15.3 Année finale

Vérifier : - une année correcte saisie par le superviseur ; - une année
incorrecte ; - la correction d'une année incorrecte puis une nouvelle
validation ; - le scintillement rouge en cas d'erreur ; - le
scintillement vert puis l'état vert permanent en cas de succès ; -
l'impossibilité d'annuler une validation correcte ; - l'impossibilité de
valider après la fin de partie ; - l'arrêt automatique de la partie
lorsque les quatre équipes sont résolues.

### 15.4 Chronométrage et états

Vérifier : - la partie initialisée en attente ; - le démarrage manuel
depuis la supervision ; - le démarrage du chronomètre au même instant
; - la pause et la reprise ; - l'exclusion des pauses du temps écoulé et
des temps de résolution ; - le passage du compte à rebours sous zéro
sans fin automatique ; - l'arrêt manuel avec confirmation ; - l'arrêt
automatique lorsque les quatre années sont validées ; - le blocage de
toute progression après la fin ; - la réinitialisation vers l'état en
attente, sans démarrage automatique.

### 15.5 Messages, stockage et sécurité

Vérifier : - l'envoi d'un message à une seule équipe ; - l'envoi d'un
message à plusieurs équipes ; - la fermeture indépendante par
destinataire ; - la conservation de l'historique ; - l'isolation des
données d'équipe ; - les écritures JSON simultanées ; - l'archivage des
six JSON et la recréation correcte des fichiers actifs ; - la protection
CSRF et l'échappement des sorties.

## 16. Décisions confirmées

### Jeu et portes

-   [x] Quatre équipes et quatre portes indépendantes.
-   [x] Quatre énigmes physiques, une par groupe, et un symbole transmis
    directement.
-   [x] Énigmes physiques et pictogrammes hors suivi numérique.
-   [x] Neuf symboles disponibles ; code final de cinq symboles.
-   [x] Même ensemble de cinq symboles dans un ordre différent par
    équipe.
-   [x] Nouvelles séquences, sans reprise des anciennes.
-   [x] Trois résultats de code : exact, bons symboles dans le mauvais
    ordre, combinaison incorrecte.
-   [x] Préservation du HTML Stargate et de ses animations/effets.

### Pages et authentification

-   [x] Quatre pages PHP : `login.php`, `supervision.php`,
    `tableau-de-bord.php` et `porte-des-etoiles.php`.
-   [x] Toute authentification est centralisée dans `login.php`.
-   [x] Les trois pages fonctionnelles redirigent vers `login.php` avec
    l'URL d'origine et ses paramètres GET lorsqu'une session autorisée
    est absente.
-   [x] La clé valide crée une session puis l'utilisateur revient à
    l'URL d'origine sans le paramètre `cle`.
-   [x] Une clé absente ou incorrecte conduit à la page de connexion
    avec les possibilités de connexion administrateur ou de saisie d'une
    clé.
-   [x] La cohérence entre page demandée, paramètre `equipe` et clé est
    contrôlée.
-   [x] `equipe=0` désigne l'accès au tableau de bord.
-   [x] Une connexion directe à `login.php` avec `equipe1` et sa clé
    ouvre la porte de l'équipe 1.
-   [x] Les pages fonctionnelles déterminent le rôle et l'équipe
    exclusivement depuis la session.
-   [x] Une session d'équipe ne donne pas accès à la supervision.
-   [x] Les clés et secrets sont conservés dans `.env`, hors de la
    racine publique.

### Partie et suivi

-   [x] La supervision démarre, met en pause, reprend et arrête la
    partie.
-   [x] La supervision peut saisir et valider l'année annoncée oralement
    par une équipe.
-   [x] Mauvaise année : scintillement rouge et possibilité de corriger.
-   [x] Bonne année : scintillement vert puis bloc vert permanent «
    Énigme résolue ».
-   [x] Validation correcte définitive jusqu'à la réinitialisation.
-   [x] Chronomètre lancé uniquement par la supervision.
-   [x] Compte à rebours et temps écoulé affichés ensemble.
-   [x] Durée du compte à rebours définie dans le JSON.
-   [x] Le compte à rebours continue en négatif après zéro.
-   [x] Pause suspendant les deux compteurs ; reprise sans remise à
    zéro.
-   [x] Tout arrêt du chronomètre termine la partie.
-   [x] Temps de résolution du démarrage à la validation correcte, hors
    pauses.
-   [x] Réinitialisation avec confirmation et archivage des six JSON.
-   [x] Nouvelle partie en attente ; aucun démarrage automatique.
-   [x] Messages avec fermeture indépendante par destinataire.
-   [x] Actualisation des trois pages fonctionnelles toutes les cinq
    secondes.
-   [x] Tests unitaires exclus ; tests d'intégration, de sécurité et
    recette fonctionnelle conservés.

## 17. Points restant à préciser

Ces points ne doivent pas être résolus par supposition :

1.  **Codes finaux :** fournir ou valider les quatre séquences exactes
    de cinq symboles.
2.  **Phrases finales :** identifier précisément le fichier ou support
    contenant les quatre phrases à reprendre à l'identique.
3.  **Années attendues :** confirmer les années attendues pour chaque
    équipe et leur correspondance exacte avec les phrases du robot.
4.  **Durée du compte à rebours :** fournir la valeur à renseigner dans
    le JSON.
5.  **Version PHP :** relever la version effectivement disponible sur
    l'hébergement IONOS.
6.  **ESP32 :** confirmer si les deux ESP32 ont encore un rôle dans la
    version simplifiée. Aucune action ESP32 n'est définie tant que ce
    point n'est pas précisé.
7.  **Réparation après explosion :** vérifier le scénario exact existant
    dans le HTML, notamment l'action attendue des joueurs.
8.  **Historique des notifications :** confirmer si une notification
    fermée reste visible dans l'historique de l'équipe ou uniquement
    dans celui de la supervision.
9.  **Format des temps :** confirmer le format d'affichage du temps de
    résolution et du temps final du chronomètre.
10. **Navigateurs et appareils :** identifier les appareils et
    navigateurs utilisés le jour du jeu.
11. **Droits administrateur hors supervision :** confirmer si une
    session administrateur peut également consulter le tableau de bord
    et/ou une porte d'équipe. Une page de porte nécessite toujours une
    équipe déterminée ; aucun choix d'équipe par simple paramètre GET ne
    doit être considéré comme une authentification.

## 18. Étape suivante et règles de développement

La prochaine étape est la relecture et la validation explicite de ce
cadrage. Après validation seulement, un plan d'implémentation détaillé
et séquencé sera rédigé dans un fichier Markdown dédié à la racine du
projet.

Les Instructions Globales pour les Agents IA de Développement
s'appliquent intégralement : - s'appuyer sur les fichiers réels, les
spécifications validées et le plan ; - ne jamais inventer un
comportement ou combler une ambiguïté par supposition ; - traiter une
seule étape ou sous-étape à la fois ; - avant toute modification de
code, présenter la signature, le rôle, la logique et les impacts de la
fonctionnalité visée, puis attendre l'accord explicite ; - ne modifier
que le périmètre validé ; - fournir du code complet, sans omission ni
TODO générique ; - documenter en français chaque classe, méthode,
fonction ou type créé ou modifié ; - externaliser les textes applicatifs
dans des constantes ou un fichier dédié ; - après chaque étape,
expliquer les changements et fournir la méthode exacte de validation ; -
ne proposer un commit Conventional Commits que si le code est sans
erreur.

Pour ce projet, la stratégie de validation est limitée aux **tests
d'intégration, tests de sécurité et recette fonctionnelle**. Aucun test
unitaire ne doit être prévu dans le plan.

**Aucun code ne doit être écrit avant validation du cadrage et du plan
d'implémentation.**

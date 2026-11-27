# Couche d'accès aux données PHP — JEUX ASSEMBLÉES 2026

## Contenu

Cette archive fournit une couche PHP orientée objet pour accéder à MariaDB avec PDO.

- `src/Config/Configuration.php` : lecture du fichier `.env`.
- `src/Database/Connexion.php` : création de la connexion PDO.
- `src/Repository/AbstractRepository.php` : exécution centralisée des requêtes préparées et conversion JSON.
- `src/Repository/PartieRepository.php` : table `parties`.
- `src/Repository/EquipeRepository.php` : table `equipes`.
- `src/Repository/GroupeRepository.php` : table `groupes`.
- `src/Repository/PartieEquipeRepository.php` : table `partie_equipes`.
- `src/Repository/NotificationRepository.php` : table `notifications`.
- `src/Repository/JournalRepository.php` : table `journal`.
- `src/Support/Uuid.php` : génération des UUID.
- `bootstrap.php` : chargement automatique sans Composer et création de la connexion.

## Prérequis

- PHP **8.1 ou supérieur**.
- Extensions PHP `PDO` et `pdo_mysql`.
- MariaDB avec le schéma du projet déjà créé.
- Le compte SQL doit disposer des droits nécessaires sur la base `dbs16391701`.

La version PHP 8.1 est le minimum retenu par cette livraison en raison des propriétés typées en lecture seule et des fonctions utilisées.

## Installation

1. Décompresser le dossier hors du répertoire publiquement accessible.
2. Copier `.env.example` vers `.env`.
3. Renseigner les valeurs réelles `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` et `DB_PASSWORD`.
4. Vérifier que `.env` n'est pas accessible depuis le Web et qu'il est exclu du dépôt Git.
5. Inclure `bootstrap.php` depuis un script PHP situé dans `public/`.

Exemple :

```php
<?php

declare(strict_types=1);

use EscapeGame\Repository\PartieRepository;

$connexion = require dirname(__DIR__) . '/bootstrap.php';

$parties = new PartieRepository($connexion);
$liste = $parties->obtenirToutes();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($liste, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
```

Le chemin `dirname(__DIR__)` suppose que le script appelant se trouve directement dans `public/`. Adapter uniquement le chemin si l'arborescence réelle diffère.

## Exemple de création d'une partie et de ses équipes

```php
<?php

declare(strict_types=1);

use EscapeGame\Repository\PartieRepository;
use EscapeGame\Repository\EquipeRepository;
use EscapeGame\Repository\PartieEquipeRepository;

$connexion = require dirname(__DIR__) . '/bootstrap.php';

$parties = new PartieRepository($connexion);
$equipes = new EquipeRepository($connexion);
$suivis = new PartieEquipeRepository($connexion);

$connexion->beginTransaction();

try {
    $partieId = $parties->creer();

    for ($numero = 1; $numero <= 4; $numero++) {
        $equipeId = $equipes->creer(
            $partieId,
            $numero,
            [],
            null,
            null,
            null
        );

        $suivis->creer($partieId, $equipeId);
    }

    $connexion->commit();
} catch (Throwable $exception) {
    if ($connexion->inTransaction()) {
        $connexion->rollBack();
    }

    throw $exception;
}
```

## Principes de sécurité

- Toutes les valeurs SQL sont transmises par paramètres nommés à des requêtes préparées.
- Les noms de colonnes utilisés pour les mises à jour proviennent de listes blanches définies dans chaque repository.
- Les identifiants SQL ne sont jamais construits à partir d'une valeur utilisateur.
- Le mot de passe n'est jamais inclus dans un message d'erreur.
- Ne jamais journaliser les codes secrets `code_porte` et `code_final`.
- Échapper les données au moment de leur affichage HTML avec `htmlspecialchars`.
- Encoder les réponses JSON avec `json_encode`.
- Le contrôle d'accès HTTP et la protection CSRF ne sont pas fournis par cette couche de persistance : ils doivent être réalisés dans les pages ou contrôleurs.

## Remarques sur le modèle de données

- `code_porte` et `code_final` restent deux champs distincts.
- `phrase_cle` est portée par l'équipe.
- Le suivi de chaque équipe dans une partie est stocké dans `partie_equipes`.
- Les durées écoulée et restante ne sont pas persistées par ces repositories ; elles doivent être calculées à partir des dates et de la durée définies par le modèle validé.
- Le repository des notifications respecte la structure actuelle. Le champ `lu` est global à la notification ; la lecture individualisée par joueur n'est pas implémentée.
- Le repository journalise les types d'événements de l'ENUM validé dans les échanges du projet.
- Les opérations métier composites doivent être encadrées par une transaction PDO, comme dans l'exemple.

## Vérifications

Depuis la racine du dossier :

```bash
find src -name '*.php' -print0 | xargs -0 -n1 php -l
php -l bootstrap.php
```

Un test de connexion réel nécessite les paramètres IONOS et l'exécution sur un environnement disposant de `pdo_mysql`. Cette archive n'a pas pu être testée contre la base MariaDB distante.

## Tests autonomes

```bash
php tests/run.php
```

Ces tests vérifient le format UUID v4 et la lecture/validation de `.env`. Ils ne remplacent pas les tests d'intégration avec MariaDB.

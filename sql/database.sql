-- ============================================================
-- JEUX ASSEMBLÉES 2026
-- Structure complète de la base de données
-- Avec gestion des comptes et des clés d'accès
-- Compatible MariaDB avec contraintes CHECK appliquées
-- ============================================================

USE dbs16391701;

SET NAMES utf8mb4;

-- ============================================================
-- 1. TABLE : parties
-- Gestion des différentes parties du jeu
-- ============================================================

CREATE TABLE IF NOT EXISTS parties (
    id CHAR(36) NOT NULL,

    etat ENUM(
        'en_attente',
        'en_cours',
        'en_pause',
        'terminee'
    ) NOT NULL DEFAULT 'en_attente',

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    date_demarrage DATETIME NULL,
    date_pause DATETIME NULL,
    date_fin DATETIME NULL,
    date_cloture DATETIME NULL,

    duree_total_compte_a_rebours_secondes INT UNSIGNED NULL,
    temps_pause_cumule_secondes INT UNSIGNED NOT NULL DEFAULT 0,

    PRIMARY KEY (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. TABLE : equipes
-- Les quatre équipes d'une partie
-- ============================================================

CREATE TABLE IF NOT EXISTS equipes (
    id CHAR(36) NOT NULL,
    partie_id CHAR(36) NOT NULL,
    numero_equipe TINYINT UNSIGNED NOT NULL,

    phrase_cle TEXT NULL,
    code_porte VARCHAR(100) NULL,
    code_final VARCHAR(100) NULL,

    parametres JSON NOT NULL,

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uk_equipes_partie_numero (
        partie_id,
        numero_equipe
    ),

    UNIQUE KEY uk_equipes_partie_id (
        partie_id,
        id
    ),

    CONSTRAINT fk_equipes_parties
        FOREIGN KEY (partie_id)
        REFERENCES parties (id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. TABLE : groupes
-- Les quatre groupes de chaque équipe
-- ============================================================

CREATE TABLE IF NOT EXISTS groupes (
    id CHAR(36) NOT NULL,
    equipe_id CHAR(36) NOT NULL,
    numero_groupe TINYINT UNSIGNED NOT NULL,

    nom VARCHAR(100) NOT NULL,
    parametres JSON NOT NULL,

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uk_groupes_equipe_numero (
        equipe_id,
        numero_groupe
    ),

    UNIQUE KEY uk_groupes_equipe_id (
        equipe_id,
        id
    ),

    CONSTRAINT fk_groupes_equipes
        FOREIGN KEY (equipe_id)
        REFERENCES equipes (id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. TABLE : partie_equipes
-- Suivi de la progression de chaque équipe dans une partie
-- ============================================================

CREATE TABLE IF NOT EXISTS partie_equipes (
    partie_id CHAR(36) NOT NULL,
    equipe_id CHAR(36) NOT NULL,

    nombre_tentatives_porte INT UNSIGNED NOT NULL DEFAULT 0,

    etat_porte ENUM(
        'fermee',
        'ouverte',
        'explosee',
        'reinitialisee'
    ) NOT NULL DEFAULT 'fermee',

    porte_ouverte BOOLEAN NOT NULL DEFAULT FALSE,

    etat_resolution_annee ENUM(
        'non_resolue',
        'resolue'
    ) NOT NULL DEFAULT 'non_resolue',

    annee_saisie SMALLINT NULL,
    instant_validation_correcte DATETIME NULL,
    temps_resolution_secondes INT UNSIGNED NULL,

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (partie_id, equipe_id),

    CONSTRAINT fk_partie_equipes_equipes
        FOREIGN KEY (partie_id, equipe_id)
        REFERENCES equipes (partie_id, id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. TABLE : notifications
-- Notifications destinées à tous, une équipe ou un groupe
-- ============================================================

CREATE TABLE IF NOT EXISTS notifications (
    id CHAR(36) NOT NULL,
    partie_id CHAR(36) NOT NULL,
    equipe_id CHAR(36) NULL,
    groupe_id CHAR(36) NULL,

    type_destinataire ENUM(
        'tous',
        'equipe',
        'groupe'
    ) NOT NULL,

    type_notification VARCHAR(250) NOT NULL,
    contenu TEXT NOT NULL,

    lu BOOLEAN NOT NULL DEFAULT FALSE,
    date_lecture DATETIME NULL,

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_notifications_partie (partie_id),
    KEY idx_notifications_equipe (equipe_id),
    KEY idx_notifications_groupe (groupe_id),

    CONSTRAINT fk_notifications_partie_equipes
        FOREIGN KEY (partie_id, equipe_id)
        REFERENCES partie_equipes (partie_id, equipe_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notifications_groupes
        FOREIGN KEY (equipe_id, groupe_id)
        REFERENCES groupes (equipe_id, id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. TABLE : journal
-- Historique des événements du jeu et remontées techniques
-- Les événements peuvent être indépendants d'une partie
-- ============================================================

CREATE TABLE IF NOT EXISTS journal (
    id CHAR(36) NOT NULL,

    -- NULL autorisé pour les événements techniques indépendants
    -- d'une partie (exemple : test de connexion à la base).
    partie_id CHAR(36) NULL,

    equipe_id CHAR(36) NULL,
    groupe_id CHAR(36) NULL,

    type_evenement ENUM(
        'partie_creee',
        'partie_demarree',
        'partie_mise_en_pause',
        'partie_reprise',
        'partie_cloturee',

        'porte_tentative_code_incorrect',
        'porte_ouverte',
        'porte_explosee',
        'porte_reinitialisee',

        'annee_saisie_incorrecte',
        'annee_validee',

        'notification_creee',
        'notification_lue',

        'erreur_technique',
        'exception_php',
        'erreur_base_donnees',
        'erreur_requete_sql',
        'erreur_validation_donnees',
        'erreur_configuration',
        'erreur_acces',
        'acces_refuse',
        'ressource_introuvable',
        'operation_interrompue',

        'test'
    ) NOT NULL,

    niveau ENUM(
        'information',
        'avertissement',
        'erreur',
        'test'
    ) NOT NULL DEFAULT 'information',

    origine VARCHAR(100) NULL,
    acteur VARCHAR(100) NULL,

    libelle VARCHAR(255) NOT NULL,
    description TEXT NULL,
    donnees JSON NULL,

    date_evenement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_journal_partie (partie_id),
    KEY idx_journal_equipe (equipe_id),
    KEY idx_journal_groupe (groupe_id),
    KEY idx_journal_date_evenement (date_evenement),

    CONSTRAINT fk_journal_partie_equipes
        FOREIGN KEY (partie_id, equipe_id)
        REFERENCES partie_equipes (partie_id, equipe_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_journal_groupes
        FOREIGN KEY (equipe_id, groupe_id)
        REFERENCES groupes (equipe_id, id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. TABLE : utilisateurs
-- Comptes d'administration et comptes de connexion des parties
--
-- Règles :
-- - Un seul compte webmaster au maximum, sans partie associée.
-- - Plusieurs administrateurs, sans partie associée.
-- - Un superviseur au maximum par partie.
-- - Un compte user au maximum par partie.
-- - Les comptes webmaster/administrateur/superviseur utilisent
--   mot_de_passe_hash.
-- - Le compte user utilise code_secret_hash (code à six chiffres).
-- - Les secrets ne sont jamais stockés en clair.
-- ============================================================

CREATE TABLE IF NOT EXISTS utilisateurs (
    id CHAR(36) NOT NULL,

    partie_id CHAR(36) NULL,

    role ENUM(
        'webmaster',
        'administrateur',
        'superviseur',
        'user'
    ) NOT NULL,

    identifiant VARCHAR(100) NOT NULL,

    mot_de_passe_hash VARCHAR(255) NULL,
    code_secret_hash VARCHAR(255) NULL,

    -- Valeur calculée utilisée pour limiter à un seul webmaster.
    webmaster_unique TINYINT
        GENERATED ALWAYS AS (
            CASE WHEN role = 'webmaster' THEN 1 ELSE NULL END
        ) STORED,

    -- Valeur calculée utilisée pour limiter à un superviseur et
    -- un compte joueur par partie, indépendamment l'un de l'autre.
    role_partie_unique VARCHAR(20)
        GENERATED ALWAYS AS (
            CASE
                WHEN role IN ('superviseur', 'user') THEN role
                ELSE NULL
            END
        ) STORED,

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    derniere_connexion DATETIME NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,

    PRIMARY KEY (id),

    -- Identifiant de connexion unique dans toute l'application.
    UNIQUE KEY uk_utilisateurs_identifiant (identifiant),

    -- Un seul webmaster peut être enregistré.
    UNIQUE KEY uk_utilisateurs_webmaster (webmaster_unique),

    -- Un seul superviseur et un seul compte joueur par partie.
    UNIQUE KEY uk_utilisateurs_partie_role (partie_id, role_partie_unique),

    KEY idx_utilisateurs_partie (partie_id),
    KEY idx_utilisateurs_role (role),

    CONSTRAINT fk_utilisateurs_parties
        FOREIGN KEY (partie_id)
        REFERENCES parties (id)
        ON DELETE CASCADE,

    CONSTRAINT chk_utilisateurs_role_partie_secret
        CHECK (
            (
                role IN ('webmaster', 'administrateur')
                AND partie_id IS NULL
                AND mot_de_passe_hash IS NOT NULL
                AND code_secret_hash IS NULL
            )
            OR
            (
                role = 'superviseur'
                AND partie_id IS NOT NULL
                AND mot_de_passe_hash IS NOT NULL
                AND code_secret_hash IS NULL
            )
            OR
            (
                role = 'user'
                AND partie_id IS NOT NULL
                AND mot_de_passe_hash IS NULL
                AND code_secret_hash IS NOT NULL
            )
        )
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. TABLE : cles_acces
-- Clés individuelles des équipes et des groupes d'une partie
--
-- Règles :
-- - Une clé d'équipe cible une équipe et aucun groupe.
-- - Une clé de groupe cible une équipe et un groupe de cette équipe.
-- - Une seule clé par cible et par partie.
-- - La clé est stockée en clair, conformément au choix validé.
-- - La clé ne donne accès qu'après authentification du compte user
--   de la partie correspondante ; ce contrôle est applicatif.
-- ============================================================

CREATE TABLE IF NOT EXISTS cles_acces (
    id CHAR(36) NOT NULL,

    partie_id CHAR(36) NOT NULL,
    equipe_id CHAR(36) NOT NULL,
    groupe_id CHAR(36) NULL,

    type_cible ENUM('equipe', 'groupe') NOT NULL,

    -- Clé conservée en clair à la demande du propriétaire du projet.
    -- Générer une valeur aléatoire suffisamment longue côté serveur.
    cle_acces VARCHAR(128) NOT NULL,

    actif BOOLEAN NOT NULL DEFAULT TRUE,

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    derniere_utilisation DATETIME NULL,

    -- Identifiant de cible calculé pour permettre une contrainte
    -- d'unicité commune aux clés d'équipe et aux clés de groupe.
    cible_unique_id CHAR(36)
        GENERATED ALWAYS AS (
            CASE
                WHEN type_cible = 'equipe' THEN equipe_id
                ELSE groupe_id
            END
        ) STORED,

    PRIMARY KEY (id),

    UNIQUE KEY uk_cles_acces_valeur (cle_acces),
    UNIQUE KEY uk_cles_acces_cible (
        partie_id,
        type_cible,
        cible_unique_id
    ),

    KEY idx_cles_acces_partie (partie_id),
    KEY idx_cles_acces_equipe (equipe_id),
    KEY idx_cles_acces_groupe (groupe_id),

    CONSTRAINT fk_cles_acces_partie_equipe
        FOREIGN KEY (partie_id, equipe_id)
        REFERENCES equipes (partie_id, id)
        ON DELETE CASCADE,

    CONSTRAINT fk_cles_acces_groupe
        FOREIGN KEY (equipe_id, groupe_id)
        REFERENCES groupes (equipe_id, id)
        ON DELETE CASCADE,

    CONSTRAINT chk_cles_acces_cible
        CHECK (
            (type_cible = 'equipe' AND groupe_id IS NULL)
            OR
            (type_cible = 'groupe' AND groupe_id IS NOT NULL)
        )
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- NOTES DE DÉPLOIEMENT
--
-- 1. Ce script crée la structure, mais ne crée aucun compte, aucune
--    partie, aucune équipe, aucun groupe et aucune clé réelle.
--    Ces données doivent être provisionnées par une procédure
--    d'administration sécurisée.
--
-- 2. Pour chaque partie, l'application doit créer :
--    - 1 compte superviseur ;
--    - 1 compte user partagé ;
--    - 4 équipes et 4 groupes par équipe ;
--    - 4 clés de type 'equipe' ;
--    - 16 clés de type 'groupe'.
--
-- 3. Le compte user doit être authentifié (identifiant + code secret
--    à six chiffres vérifié avec password_verify) AVANT que la clé
--    d'équipe/groupe soit acceptée et qu'un espace soit ouvert.
--
-- 4. Les clés d'accès sont en clair dans cette version. L'accès à la
--    base doit donc être strictement limité et les clés ne doivent
--    jamais être écrites dans les journaux ou affichées après leur
--    création.
--
-- 5. L'application doit limiter les tentatives de connexion et de
--    validation des clés, avec temporisation ou blocage temporaire.
--    Ces protections ne sont pas assurées par le seul schéma SQL.
--
-- 6. Les contraintes CHECK nécessitent une version de MariaDB qui
--    les applique effectivement (MariaDB 10.2.1 ou supérieure).
--
-- 7. Les index uniques garantissent qu'il ne peut pas y avoir plus
--    d'un webmaster, d'un superviseur par partie, d'un compte user
--    par partie et d'une clé par cible. Ils ne garantissent pas à eux
--    seuls l'existence de ces comptes ou des 20 clés : le processus
--    de création d'une partie devra vérifier leur présence.
--
-- 8. CREATE TABLE IF NOT EXISTS ne modifie pas une table déjà
--    existante. Ce script est destiné à une base vierge ou à une
--    base dont les six premières tables correspondent exactement
--    à la structure ci-dessus. Pour une base déjà créée, effectuer
--    une sauvegarde et contrôler la structure avant exécution.
-- ============================================================

-- FIN DU SCRIPT

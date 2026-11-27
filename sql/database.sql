
-- ============================================================
-- JEUX ASSEMBLÉES 2026
-- Structure de la base de données
-- Référence de conception validée
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

    type_notification VARCHAR(50) NOT NULL,
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
-- ============================================================

CREATE TABLE IF NOT EXISTS journal (
    id CHAR(36) NOT NULL,
    partie_id CHAR(36) NOT NULL,
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
        'operation_interrompue'
    ) NOT NULL,

    niveau ENUM(
        'information',
        'avertissement',
        'erreur'
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
-- FIN DU SCRIPT
-- ============================================================


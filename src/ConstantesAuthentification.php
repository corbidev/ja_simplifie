<?php
declare(strict_types=1);

/**
 * Regroupe les textes affichés par les pages d'accueil et de connexion.
 *
 * Cette classe centralise les titres, libellés, aides et messages afin
 * d'éviter de disperser les textes applicatifs dans les pages.
 */
final class ConstantesAuthentification
{
    public const TITRE_PAGE = 'Connexion — Jeux Assemblées 2026';
    public const NOM_APPLICATION = 'Jeux Assemblées 2026';
    public const TITRE_ACCUEIL = 'Bienvenue aux Jeux Assemblées 2026';
    public const TEXTE_ACCUEIL = 'Retrouvez ici les accès aux espaces des Jeux Assemblées 2026.';
    public const LIBELLE_BOUTON_ACCEDER_ESPACES = 'Accéder aux espaces';

    public const TITRE_CONNEXION_EQUIPE = 'Connexion d’équipe';
    public const TITRE_ACCES_TABLEAU_DE_BORD = 'Accès au tableau de bord';
    public const TITRE_CONNEXION_ADMINISTRATEUR = 'Connexion administrateur';
    public const TEXTE_INTRODUCTION = 'Choisissez votre espace et saisissez vos informations de connexion.';

    public const LIBELLE_IDENTIFIANT_EQUIPE = 'Identifiant de l’équipe';
    public const LIBELLE_CLE_EQUIPE = 'Clé de l’équipe';
    public const LIBELLE_CLE_TABLEAU_DE_BORD = 'Clé du tableau de bord';
    public const LIBELLE_IDENTIFIANT_ADMINISTRATEUR = 'Identifiant administrateur';
    public const LIBELLE_MOT_DE_PASSE = 'Mot de passe';
    public const LIBELLE_BOUTON_CONNEXION = 'Se connecter';
    public const LIBELLE_THEME_SOMBRE = 'Mode sombre';
    public const LIBELLE_THEME_CLAIR = 'Mode clair';
    public const LIBELLE_ACCESSIBLE_BOUTON_THEME = 'Basculer entre le mode sombre et le mode clair';
    public const LIBELLE_ACCESSIBLE_ESPACES_CONNEXION = 'Espaces de connexion';

    public const MESSAGE_AUTHENTIFICATION_INCORRECTE = 'Les informations de connexion sont incorrectes.';
    public const MESSAGE_DESTINATION_INVALIDE = 'La destination demandée n’est pas valide.';
    public const MESSAGE_FORMULAIRE_INVALIDE = 'Le formulaire envoyé n’est pas valide.';
    public const MESSAGE_CSRF_INVALIDE = 'Le formulaire a expiré ou n’est plus valide. Veuillez le recharger et réessayer.';
    public const MESSAGE_CONFIGURATION_INDISPONIBLE = 'Le service de connexion est temporairement indisponible. Veuillez réessayer plus tard.';

    public const AIDE_CONNEXION_EQUIPE = 'Utilisez l’identifiant et la clé associés à votre équipe.';
    public const AIDE_ACCES_TABLEAU_DE_BORD = 'Saisissez la clé dédiée au tableau de bord.';
    public const AIDE_CONNEXION_ADMINISTRATEUR = 'Saisissez l’identifiant et le mot de passe administrateur.';

    public const LIBELLE_ACCESSIBLE_FORMULAIRE_EQUIPE = 'Formulaire de connexion d’équipe';
    public const LIBELLE_ACCESSIBLE_FORMULAIRE_TABLEAU_DE_BORD = 'Formulaire d’accès au tableau de bord';
    public const LIBELLE_ACCESSIBLE_FORMULAIRE_ADMINISTRATEUR = 'Formulaire de connexion administrateur';
}

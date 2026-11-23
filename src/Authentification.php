<?php
declare(strict_types=1);

/**
 * Regroupe les règles de configuration, d'authentification et de session.
 */
final class Authentification
{
    private const VARIABLES_CONFIGURATION_REQUISES = [
        'IDENTIFIANT_SUPERVISION',
        'MOT_DE_PASSE_SUPERVISION',
        'CLE_TABLEAU_DE_BORD',
        'CLE_EQUIPE_1',
        'CLE_EQUIPE_2',
        'CLE_EQUIPE_3',
        'CLE_EQUIPE_4',
    ];

    private const MESSAGE_ERREUR_CONFIGURATION = 'Impossible de charger la configuration d’authentification.';
    private const MESSAGE_ERREUR_SESSION = 'Impossible d’initialiser la session sécurisée.';
    private const MESSAGE_ERREUR_DESTINATION = 'Destination de retour invalide.';
    private const MESSAGE_ERREUR_IDENTITE = 'Identité d’authentification invalide.';

    private const PAGE_PORTE = 'porte-des-etoiles.php';
    private const PAGE_TABLEAU_DE_BORD = 'tableau-de-bord.php';
    private const PAGE_SUPERVISION = 'supervision.php';
    private const PARAMETRE_EQUIPE = 'equipe';
    private const PARAMETRE_CLE = 'cle';
    private const CLES_PAR_IDENTIFIANT_EQUIPE = [
        'equipe1' => ['numero' => 1, 'variable_cle' => 'CLE_EQUIPE_1'],
        'equipe2' => ['numero' => 2, 'variable_cle' => 'CLE_EQUIPE_2'],
        'equipe3' => ['numero' => 3, 'variable_cle' => 'CLE_EQUIPE_3'],
        'equipe4' => ['numero' => 4, 'variable_cle' => 'CLE_EQUIPE_4'],
    ];

    /**
     * Charge et valide les paramètres d'authentification depuis le fichier privé.
     *
     * @param string $cheminEnv Chemin absolu ou résolu côté serveur vers le fichier `.env`.
     * @return array<string, string> Paramètres requis, indexés par leur nom dans `.env`.
     * @throws RuntimeException Si le fichier est illisible, invalide ou incomplet.
     */
    public static function chargerConfiguration(string $cheminEnv): array
    {
        if (!is_file($cheminEnv) || !is_readable($cheminEnv)) {
            throw new RuntimeException(self::MESSAGE_ERREUR_CONFIGURATION);
        }

        // Le parseur peut émettre un avertissement contenant le chemin du fichier.
        $configuration = @parse_ini_file($cheminEnv, false, INI_SCANNER_RAW);

        if (!is_array($configuration)) {
            throw new RuntimeException(self::MESSAGE_ERREUR_CONFIGURATION);
        }

        $configurationRequise = [];

        foreach (self::VARIABLES_CONFIGURATION_REQUISES as $nomVariable) {
            if (
                !array_key_exists($nomVariable, $configuration)
                || !is_string($configuration[$nomVariable])
                || $configuration[$nomVariable] === ''
            ) {
                throw new RuntimeException(self::MESSAGE_ERREUR_CONFIGURATION);
            }

            $configurationRequise[$nomVariable] = $configuration[$nomVariable];
        }

        return $configurationRequise;
    }

    /**
     * Configure le cookie de session et démarre une session navigateur sécurisée.
     *
     * @return void
     * @throws RuntimeException Si la session ne peut pas être configurée ou démarrée avant l'envoi des en-têtes.
     */
    public static function initialiserSessionSecurisee(): void
    {
        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            throw new RuntimeException(self::MESSAGE_ERREUR_SESSION);
        }

        $httpsActif = isset($_SERVER['HTTPS'])
            && in_array(strtolower((string) $_SERVER['HTTPS']), ['on', '1'], true);

        $optionsCookie = [
            'lifetime' => 0,
            'path' => '/',
            'secure' => $httpsActif,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        if (!session_set_cookie_params($optionsCookie)) {
            throw new RuntimeException(self::MESSAGE_ERREUR_SESSION);
        }

        // L'avertissement de session peut révéler des chemins internes du serveur.
        if (!@session_start()) {
            throw new RuntimeException(self::MESSAGE_ERREUR_SESSION);
        }
    }

    /**
     * Valide une destination de retour et en extrait uniquement les paramètres autorisés.
     *
     * @param mixed $destinationBrute Valeur décodée du paramètre de retour, ou null en accès direct.
     * @return array{page: string, parametres: array{equipe?: int}}|null Destination normalisée ou null en accès direct.
     * @throws InvalidArgumentException Si la valeur n'est pas une destination interne autorisée.
     */
    public static function normaliserDestination(mixed $destinationBrute): ?array
    {
        if ($destinationBrute === null) {
            return null;
        }

        if (!is_string($destinationBrute) || $destinationBrute === '') {
            throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
        }

        try {
            $destination = parse_url($destinationBrute);
        } catch (ValueError) {
            throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
        }

        if (
            !is_array($destination)
            || !isset($destination['path'])
            || isset($destination['scheme'])
            || isset($destination['host'])
            || isset($destination['user'])
            || isset($destination['pass'])
            || isset($destination['port'])
            || array_key_exists('fragment', $destination)
        ) {
            throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
        }

        $parametres = [];
        $requete = $destination['query'] ?? '';

        if ($requete !== '') {
            if (preg_match('/%(?![A-Fa-f0-9]{2})/', $requete) === 1) {
                throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
            }

            foreach (explode('&', $requete) as $paire) {
                if ($paire === '') {
                    throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
                }

                $separateur = strpos($paire, '=');
                $cleBrute = $separateur === false ? $paire : substr($paire, 0, $separateur);
                $valeurBrute = $separateur === false ? '' : substr($paire, $separateur + 1);
                $nomParametre = urldecode($cleBrute);

                if (
                    !in_array($nomParametre, [self::PARAMETRE_EQUIPE, self::PARAMETRE_CLE], true)
                    || array_key_exists($nomParametre, $parametres)
                ) {
                    throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
                }

                $parametres[$nomParametre] = urldecode($valeurBrute);
            }
        }

        switch ($destination['path']) {
            case self::PAGE_PORTE:
                if (
                    !isset($parametres[self::PARAMETRE_EQUIPE])
                    || preg_match('/^[1-4]$/D', $parametres[self::PARAMETRE_EQUIPE]) !== 1
                ) {
                    throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
                }

                return [
                    'page' => self::PAGE_PORTE,
                    'parametres' => [
                        self::PARAMETRE_EQUIPE => (int) $parametres[self::PARAMETRE_EQUIPE],
                    ],
                ];

            case self::PAGE_TABLEAU_DE_BORD:
                if (($parametres[self::PARAMETRE_EQUIPE] ?? null) !== '0') {
                    throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
                }

                return [
                    'page' => self::PAGE_TABLEAU_DE_BORD,
                    'parametres' => [
                        self::PARAMETRE_EQUIPE => 0,
                    ],
                ];

            case self::PAGE_SUPERVISION:
                if ($parametres !== []) {
                    throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
                }

                return [
                    'page' => self::PAGE_SUPERVISION,
                    'parametres' => [],
                ];

            default:
                throw new InvalidArgumentException(self::MESSAGE_ERREUR_DESTINATION);
        }
    }

    /**
     * Vérifie les identifiants d'un formulaire et retourne l'identité authentifiée.
     *
     * @param array<string, mixed> $donnees Champs reçus depuis le formulaire POST.
     * @param array{
     *     IDENTIFIANT_SUPERVISION: string,
     *     MOT_DE_PASSE_SUPERVISION: string,
     *     CLE_TABLEAU_DE_BORD: string,
     *     CLE_EQUIPE_1: string,
     *     CLE_EQUIPE_2: string,
     *     CLE_EQUIPE_3: string,
     *     CLE_EQUIPE_4: string
     * } $configuration Configuration validée par `chargerConfiguration()`.
     * @return array{role: 'administrateur', equipe: null}|array{role: 'equipe', equipe: int}|array{role: 'tableau_de_bord', equipe: 0}|null Identité authentifiée ou null si l'accès échoue.
     */
    public static function determinerIdentiteDepuisFormulaire(array $donnees, array $configuration): ?array
    {
        $identifiantPresent = array_key_exists('identifiant', $donnees);
        $clePresente = array_key_exists('cle', $donnees);
        $motDePassePresent = array_key_exists('mot_de_passe', $donnees);

        if ($clePresente && !$identifiantPresent && !$motDePassePresent) {
            $cle = $donnees['cle'];

            if (!is_string($cle) || $cle === '') {
                return null;
            }

            if (!hash_equals($configuration['CLE_TABLEAU_DE_BORD'], $cle)) {
                return null;
            }

            return [
                'role' => 'tableau_de_bord',
                'equipe' => 0,
            ];
        }

        if ($identifiantPresent && $clePresente && !$motDePassePresent) {
            $identifiant = $donnees['identifiant'];
            $cle = $donnees['cle'];

            if (!is_string($identifiant) || $identifiant === '' || !is_string($cle) || $cle === '') {
                return null;
            }

            $accesEquipe = self::CLES_PAR_IDENTIFIANT_EQUIPE[$identifiant] ?? null;

            if ($accesEquipe === null || !hash_equals($configuration[$accesEquipe['variable_cle']], $cle)) {
                return null;
            }

            return [
                'role' => 'equipe',
                'equipe' => $accesEquipe['numero'],
            ];
        }

        if ($identifiantPresent && $motDePassePresent && !$clePresente) {
            $identifiant = $donnees['identifiant'];
            $motDePasse = $donnees['mot_de_passe'];

            if (
                !is_string($identifiant)
                || $identifiant === ''
                || !is_string($motDePasse)
                || $motDePasse === ''
            ) {
                return null;
            }

            $identifiantValide = hash_equals($configuration['IDENTIFIANT_SUPERVISION'], $identifiant);
            $motDePasseValide = hash_equals($configuration['MOT_DE_PASSE_SUPERVISION'], $motDePasse);

            if (!$identifiantValide || !$motDePasseValide) {
                return null;
            }

            return [
                'role' => 'administrateur',
                'equipe' => null,
            ];
        }

        return null;
    }

    /**
     * Vérifie une clé reçue dans l'URL selon la destination normalisée.
     *
     * @param array{page: string, parametres: array{equipe?: int}}|null $destination Destination validée, ou null en accès direct.
     * @param mixed $cle Clé reçue dans l'URL d'origine.
     * @param array{
     *     IDENTIFIANT_SUPERVISION: string,
     *     MOT_DE_PASSE_SUPERVISION: string,
     *     CLE_TABLEAU_DE_BORD: string,
     *     CLE_EQUIPE_1: string,
     *     CLE_EQUIPE_2: string,
     *     CLE_EQUIPE_3: string,
     *     CLE_EQUIPE_4: string
     * } $configuration Configuration validée par `chargerConfiguration()`.
     * @return array{role: 'administrateur', equipe: null}|array{role: 'equipe', equipe: int}|array{role: 'tableau_de_bord', equipe: 0}|null Identité authentifiée ou null si l'accès échoue.
     */
    public static function determinerIdentiteDepuisCleUrl(?array $destination, mixed $cle, array $configuration): ?array
    {
        if ($destination === null || !is_string($cle) || $cle === '') {
            return null;
        }

        if ($destination['page'] === self::PAGE_SUPERVISION) {
            return null;
        }

        if ($destination['page'] === self::PAGE_PORTE) {
            $identifiantEquipe = 'equipe' . $destination['parametres'][self::PARAMETRE_EQUIPE];
            $accesEquipe = self::CLES_PAR_IDENTIFIANT_EQUIPE[$identifiantEquipe] ?? null;

            if (
                $accesEquipe === null
                || !hash_equals($configuration[$accesEquipe['variable_cle']], $cle)
            ) {
                return null;
            }

            return [
                'role' => 'equipe',
                'equipe' => $accesEquipe['numero'],
            ];
        }

        if ($destination['page'] === self::PAGE_TABLEAU_DE_BORD) {
            if (!hash_equals($configuration['CLE_TABLEAU_DE_BORD'], $cle)) {
                return null;
            }

            return [
                'role' => 'tableau_de_bord',
                'equipe' => 0,
            ];
        }

        return null;
    }

    /**
     * Vérifie que l'identité authentifiée est autorisée pour la destination demandée.
     *
     * @param array{page: string, parametres: array{equipe?: int}}|null $destination Destination normalisée ou null en accès direct.
     * @param array{role: string, equipe: int|null} $identite Identité créée côté serveur.
     * @return bool Vrai uniquement si le rôle et l'équipe correspondent à la destination.
     */
    public static function destinationAutoriseePourIdentite(?array $destination, array $identite): bool
    {
        if ($destination === null) {
            return false;
        }

        $page = $destination['page'] ?? null;
        $equipeDestination = $destination['parametres']['equipe'] ?? null;
        $role = $identite['role'] ?? null;
        $equipeIdentite = $identite['equipe'] ?? null;

        return match ($role) {
            'equipe' => $page === self::PAGE_PORTE
                && is_int($equipeIdentite)
                && is_int($equipeDestination)
                && $equipeIdentite === $equipeDestination,
            'tableau_de_bord' => $page === self::PAGE_TABLEAU_DE_BORD
                && $equipeIdentite === 0
                && $equipeDestination === 0,
            'administrateur' => $page === self::PAGE_SUPERVISION
                && array_key_exists('equipe', $identite)
                && $identite['equipe'] === null,
            default => false,
        };
    }

    /**
     * Renouvelle l'identifiant de session et y enregistre une identité autorisée.
     *
     * @param array<string, mixed> $identite Identité construite par une méthode de validation.
     * @return void
     * @throws InvalidArgumentException Si la structure ou les valeurs de l'identité ne sont pas autorisées.
     * @throws RuntimeException Si la session n'est pas active, si les en-têtes sont envoyés ou si son identifiant ne peut pas être renouvelé.
     */
    public static function creerSession(array $identite): void
    {
        if (
            count($identite) !== 2
            || !array_key_exists('role', $identite)
            || !array_key_exists('equipe', $identite)
        ) {
            throw new InvalidArgumentException(self::MESSAGE_ERREUR_IDENTITE);
        }

        $identiteNormalisee = match ($identite['role']) {
            'administrateur' => $identite['equipe'] === null
                ? ['role' => 'administrateur', 'equipe' => null]
                : null,
            'equipe' => is_int($identite['equipe'])
                && in_array($identite['equipe'], [1, 2, 3, 4], true)
                    ? ['role' => 'equipe', 'equipe' => $identite['equipe']]
                    : null,
            'tableau_de_bord' => $identite['equipe'] === 0
                ? ['role' => 'tableau_de_bord', 'equipe' => 0]
                : null,
            default => null,
        };

        if ($identiteNormalisee === null) {
            throw new InvalidArgumentException(self::MESSAGE_ERREUR_IDENTITE);
        }

        if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
            throw new RuntimeException(self::MESSAGE_ERREUR_SESSION);
        }

        // L'avertissement de renouvellement peut exposer des détails du gestionnaire de session.
        if (!@session_regenerate_id(true)) {
            throw new RuntimeException(self::MESSAGE_ERREUR_SESSION);
        }

        $_SESSION['authentification'] = $identiteNormalisee;
    }
}

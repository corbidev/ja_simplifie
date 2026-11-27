<?php

declare(strict_types=1);

/**
 * Diagnostic de la configuration et de l'accès à MariaDB.
 *
 * Vérifie .env, PDO MySQL, la connexion créée par bootstrap.php, les tables,
 * le schéma du journal, les requêtes préparées et une écriture réelle si
 * journal autorise un événement test sans partie.
 *
 * Clé d'accès : CLE_TEST_BDD dans .env, ou CLE_TABLEAU_DE_BORD à défaut.
 */

/**
 * Lit une variable précise du fichier .env sans afficher les autres valeurs.
 *
 * @param string $chemin Chemin du fichier .env.
 * @param string $cle Nom de la variable.
 * @return string|null Valeur trouvée ou null.
 */
function jaTestLireEnv(string $chemin, string $cle): ?string
{
    if (!is_file($chemin) || !is_readable($chemin)) {
        return null;
    }

    $lignes = file($chemin, FILE_IGNORE_NEW_LINES);
    if ($lignes === false) {
        return null;
    }

    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);
        if ($ligne === '' || str_starts_with($ligne, '#') || !str_contains($ligne, '=')) {
            continue;
        }

        [$nom, $valeur] = explode('=', $ligne, 2);
        if (trim($nom) !== $cle) {
            continue;
        }

        $valeur = trim($valeur);
        if (strlen($valeur) >= 2) {
            $premier = $valeur[0];
            $dernier = $valeur[strlen($valeur) - 1];
            if (($premier === '"' && $dernier === '"') || ($premier === "'" && $dernier === "'")) {
                $valeur = substr($valeur, 1, -1);
            }
        }

        return $valeur;
    }

    return null;
}

/**
 * Échappe un texte destiné à la page HTML.
 *
 * @param string $texte Texte brut.
 * @return string Texte échappé.
 */
function jaTestEchapper(string $texte): string
{
    return htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Ajoute un contrôle au rapport.
 *
 * @param array<int, array{nom:string,etat:string,detail:string}> $rapport Rapport courant.
 * @param string $nom Nom du contrôle.
 * @param bool|null $ok Résultat ou null si non exécuté.
 * @param string $detail Explication sans secret.
 * @return void
 */
function jaTestAjouter(array &$rapport, string $nom, ?bool $ok, string $detail): void
{
    $rapport[] = [
        'nom' => $nom,
        'etat' => $ok === null ? 'non exécuté' : ($ok ? 'réussi' : 'échec'),
        'detail' => $detail,
    ];
}

$cheminEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
$cleAttendue = jaTestLireEnv($cheminEnv, 'CLE_TEST_BDD')
    ?? jaTestLireEnv($cheminEnv, 'CLE_TABLEAU_DE_BORD');
$cleRecue = $_POST['cle'] ?? '';
$autorise = is_string($cleAttendue)
    && $cleAttendue !== ''
    && is_string($cleRecue)
    && $cleRecue !== ''
    && hash_equals($cleAttendue, $cleRecue);

$rapport = [];

if ($autorise) {
    $envLisible = is_file($cheminEnv) && is_readable($cheminEnv);
    jaTestAjouter(
        $rapport,
        'Fichier .env',
        $envLisible,
        $envLisible ? 'Le fichier existe et est lisible.' : 'Le fichier .env est absent ou illisible.'
    );

    $configurationComplete = true;
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $nom) {
        $valeur = jaTestLireEnv($cheminEnv, $nom);
        if ($valeur === null || $valeur === '') {
            $configurationComplete = false;
        }
    }
    jaTestAjouter(
        $rapport,
        'Paramètres DB_*',
        $configurationComplete,
        $configurationComplete
            ? 'Les paramètres obligatoires sont présents ; leurs valeurs restent masquées.'
            : 'DB_HOST, DB_NAME, DB_USER et DB_PASSWORD doivent être présents et non vides.'
    );

    $pdoMysql = extension_loaded('pdo_mysql');
    jaTestAjouter(
        $rapport,
        'Extension pdo_mysql',
        $pdoMysql,
        $pdoMysql ? 'Le pilote PDO MySQL est chargé.' : 'Le pilote PDO MySQL n’est pas activé.'
    );

    if ($pdoMysql) {
        try {
            /** @var PDO $connexion */
            $connexion = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

            jaTestAjouter(
                $rapport,
                'Connexion MariaDB via bootstrap.php',
                $connexion instanceof PDO,
                'La connexion PDO a été créée.'
            );

            $pdoConfigure = $connexion->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION
                && $connexion->getAttribute(PDO::ATTR_EMULATE_PREPARES) === false
                && $connexion->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE) === PDO::FETCH_ASSOC;
            jaTestAjouter(
                $rapport,
                'Attributs PDO',
                $pdoConfigure,
                'Contrôle du mode exception, des requêtes préparées natives et du mode de récupération associatif.'
            );

            $requete = $connexion->prepare(
                'SELECT :valeur AS valeur_test WHERE :comparaison = :attendu'
            );
            $requete->bindValue(':valeur', 'connexion_ok', PDO::PARAM_STR);
            $requete->bindValue(':comparaison', 'ok', PDO::PARAM_STR);
            $requete->bindValue(':attendu', 'ok', PDO::PARAM_STR);
            $requete->execute();
            $ligneTest = $requete->fetch();
            $requeteOk = is_array($ligneTest) && ($ligneTest['valeur_test'] ?? null) === 'connexion_ok';
            jaTestAjouter(
                $rapport,
                'Requête préparée et paramètres typés',
                $requeteOk,
                'Une requête préparée a été exécutée avec des paramètres explicitement liés.'
            );

            $baseActive = $connexion->query('SELECT DATABASE()')->fetchColumn();
            jaTestAjouter(
                $rapport,
                'Base sélectionnée',
                is_string($baseActive) && $baseActive !== '',
                is_string($baseActive) && $baseActive !== ''
                    ? 'Une base de données est sélectionnée.'
                    : 'Aucune base de données active.'
            );

            $tablesAttendues = ['parties', 'equipes', 'groupes', 'partie_equipes', 'notifications', 'journal'];
            $requeteTables = $connexion->prepare(
                'SELECT TABLE_NAME
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = :type_table'
            );
            $requeteTables->bindValue(':type_table', 'BASE TABLE', PDO::PARAM_STR);
            $requeteTables->execute();
            $tablesExistantes = $requeteTables->fetchAll(PDO::FETCH_COLUMN);
            $tablesManquantes = array_values(array_diff($tablesAttendues, $tablesExistantes));
            jaTestAjouter(
                $rapport,
                'Présence des six tables',
                $tablesManquantes === [],
                $tablesManquantes === []
                    ? 'Les six tables attendues sont présentes.'
                    : 'Tables absentes : ' . implode(', ', $tablesManquantes) . '.'
            );

            $lectureTablesOk = true;
            foreach ($tablesAttendues as $table) {
                if (!in_array($table, $tablesExistantes, true)) {
                    $lectureTablesOk = false;
                    continue;
                }
                $connexion->query('SELECT 1 FROM ' . $table . ' LIMIT 0');
            }
            jaTestAjouter(
                $rapport,
                'Accès en lecture aux tables',
                $lectureTablesOk,
                $lectureTablesOk
                    ? 'Une requête de lecture a été exécutée sur chaque table.'
                    : 'Une ou plusieurs tables attendues sont absentes.'
            );

            $journalExiste = in_array('journal', $tablesExistantes, true);
            $partieNullable = false;
            $typeTestAutorise = false;

            if ($journalExiste) {
                $colonnes = $connexion->prepare(
                    'SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE
                     FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = :table_name
                       AND COLUMN_NAME IN (:col_partie, :col_type)'
                );
                $colonnes->bindValue(':table_name', 'journal', PDO::PARAM_STR);
                $colonnes->bindValue(':col_partie', 'partie_id', PDO::PARAM_STR);
                $colonnes->bindValue(':col_type', 'type_evenement', PDO::PARAM_STR);
                $colonnes->execute();

                foreach ($colonnes->fetchAll() as $colonne) {
                    if ($colonne['COLUMN_NAME'] === 'partie_id') {
                        $partieNullable = $colonne['IS_NULLABLE'] === 'YES';
                    }
                    if ($colonne['COLUMN_NAME'] === 'type_evenement') {
                        $typeTestAutorise = preg_match("/'test'/", (string) $colonne['COLUMN_TYPE']) === 1;
                    }
                }
            }

            jaTestAjouter(
                $rapport,
                'journal.partie_id nullable',
                $journalExiste && $partieNullable,
                !$journalExiste
                    ? 'La table journal est absente.'
                    : ($partieNullable
                        ? 'La colonne accepte NULL pour un événement autonome.'
                        : 'La colonne est NOT NULL ; un test sans partie est impossible.')
            );
            jaTestAjouter(
                $rapport,
                'ENUM journal.type_evenement',
                $journalExiste && $typeTestAutorise,
                !$journalExiste
                    ? 'La table journal est absente.'
                    : ($typeTestAutorise
                        ? 'La valeur test est autorisée.'
                        : 'La valeur test est absente de l’ENUM type_evenement.')
            );

            if ($journalExiste && $partieNullable && $typeTestAutorise) {
                $octets = random_bytes(16);
                $octets[6] = chr((ord($octets[6]) & 0x0f) | 0x40);
                $octets[8] = chr((ord($octets[8]) & 0x3f) | 0x80);
                $hex = bin2hex($octets);
                $id = sprintf(
                    '%s-%s-%s-%s-%s',
                    substr($hex, 0, 8),
                    substr($hex, 8, 4),
                    substr($hex, 12, 4),
                    substr($hex, 16, 4),
                    substr($hex, 20, 12)
                );

                $connexion->beginTransaction();
                try {
                    $insertion = $connexion->prepare(
                        'INSERT INTO journal
                            (id, partie_id, equipe_id, groupe_id, type_evenement, niveau, origine, acteur, libelle, description)
                         VALUES
                            (:id, :partie_id, :equipe_id, :groupe_id, :type_evenement, :niveau, :origine, :acteur, :libelle, :description)'
                    );
                    $insertion->bindValue(':id', $id, PDO::PARAM_STR);
                    $insertion->bindValue(':partie_id', null, PDO::PARAM_NULL);
                    $insertion->bindValue(':equipe_id', null, PDO::PARAM_NULL);
                    $insertion->bindValue(':groupe_id', null, PDO::PARAM_NULL);
                    $insertion->bindValue(':type_evenement', 'test', PDO::PARAM_STR);
                    $insertion->bindValue(':niveau', 'information', PDO::PARAM_STR);
                    $insertion->bindValue(':origine', 'public/test.php', PDO::PARAM_STR);
                    $insertion->bindValue(':acteur', 'diagnostic', PDO::PARAM_STR);
                    $insertion->bindValue(':libelle', 'Test de connexion à la base de données', PDO::PARAM_STR);
                    $insertion->bindValue(
                        ':description',
                        'Événement créé par le diagnostic technique. Aucun secret ni donnée personnelle.',
                        PDO::PARAM_STR
                    );
                    $insertion->execute();
                    $connexion->commit();

                    jaTestAjouter(
                        $rapport,
                        'Écriture réelle dans journal',
                        true,
                        'Événement test enregistré. Identifiant : ' . $id . '.'
                    );
                } catch (Throwable $exception) {
                    if ($connexion->inTransaction()) {
                        $connexion->rollBack();
                    }
                    jaTestAjouter(
                        $rapport,
                        'Écriture réelle dans journal',
                        false,
                        'Insertion impossible. Vérifier les droits INSERT et les contraintes de la table.'
                    );
                }
            } else {
                jaTestAjouter(
                    $rapport,
                    'Écriture réelle dans journal',
                    null,
                    'Non exécutée : le schéma ne permet pas encore un événement test sans partie.'
                );
            }
        } catch (Throwable $exception) {
            jaTestAjouter(
                $rapport,
                'Initialisation ou contrôle SQL',
                false,
                'La connexion ou un contrôle SQL a échoué. Vérifier les paramètres DB_*, les droits MariaDB et le schéma.'
            );
        }
    } else {
        jaTestAjouter(
            $rapport,
            'Contrôles MariaDB',
            null,
            'Non exécutés car le pilote pdo_mysql est indisponible.'
        );
    }
}

$echecs = count(array_filter($rapport, static fn (array $r): bool => $r['etat'] === 'échec'));
$reussites = count(array_filter($rapport, static fn (array $r): bool => $r['etat'] === 'réussi'));
$ignores = count(array_filter($rapport, static fn (array $r): bool => $r['etat'] === 'non exécuté'));

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnostic MariaDB — Jeux Assemblées 2026</title>
<style>
:root{color-scheme:dark;--fond:#080d18;--panneau:#111b2b;--texte:#edf3fb;--secondaire:#a9b8cc;--bordure:#26364c;--vert:#42d392;--rouge:#ff7777;--orange:#f5bd61}
*{box-sizing:border-box}
body{margin:0;padding:32px 16px;background:var(--fond);color:var(--texte);font:16px/1.5 system-ui,-apple-system,Segoe UI,sans-serif}
main{width:min(850px,100%);margin:0 auto}
h1{margin:0 0 8px;font-size:clamp(1.5rem,4vw,2.1rem)}
.intro,.aide{color:var(--secondaire)}
.panneau{margin-top:22px;padding:22px;background:var(--panneau);border:1px solid var(--bordure);border-radius:14px}
label{display:block;margin-bottom:8px;font-weight:650}
input[type=password]{width:100%;padding:13px;border:1px solid var(--bordure);border-radius:8px;background:#080f1c;color:var(--texte);font:inherit}
button{margin-top:14px;padding:12px 18px;border:0;border-radius:8px;background:#8bc5ff;color:#07111e;font:inherit;font-weight:700;cursor:pointer}
.ligne{display:grid;grid-template-columns:minmax(180px,.8fr) minmax(0,1.5fr);gap:16px;padding:14px 0;border-bottom:1px solid var(--bordure)}
.ligne:last-child{border-bottom:0}
.statut{font-weight:750;text-transform:uppercase;font-size:.78rem;letter-spacing:.06em}
.reussi{color:var(--vert)}.echec{color:var(--rouge)}.ignore{color:var(--orange)}
.detail{color:var(--secondaire);overflow-wrap:anywhere}
.resume{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
.puce{padding:7px 11px;border:1px solid var(--bordure);border-radius:99px;font-size:.9rem}
.erreur-auth{color:var(--rouge)}
@media(max-width:600px){.ligne{grid-template-columns:1fr;gap:4px}.panneau{padding:17px}}
</style>
</head>
<body>
<main>
<header>
<h1>Diagnostic de la base de données</h1>
<p class="intro">Jeux Assemblées 2026 — Vérification de la configuration PHP et de l'accès MariaDB.</p>
</header>

<?php if (!$autorise): ?>
<section class="panneau">
<h2>Accès protégé</h2>
<p class="aide">Saisis la clé d'accès configurée dans le fichier .env pour lancer le diagnostic.</p>
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
<p class="erreur-auth">Clé incorrecte ou clé d'accès non configurée.</p>
<?php endif; ?>
<form method="post" action="">
<label for="cle">Clé d'accès</label>
<input id="cle" name="cle" type="password" autocomplete="current-password" required>
<button type="submit">Lancer le diagnostic</button>
</form>
</section>
<?php else: ?>
<section class="panneau">
<h2>Résultats du diagnostic</h2>
<div class="resume">
<span class="puce">Réussis : <?= $reussites ?></span>
<span class="puce">Échecs : <?= $echecs ?></span>
<span class="puce">Non exécutés : <?= $ignores ?></span>
</div>
<?php foreach ($rapport as $resultat): ?>
<div class="ligne">
<div>
<div><strong><?= jaTestEchapper($resultat['nom']) ?></strong></div>
<div class="statut <?= $resultat['etat'] === 'réussi' ? 'reussi' : ($resultat['etat'] === 'échec' ? 'echec' : 'ignore') ?>">
<?= jaTestEchapper($resultat['etat']) ?>
</div>
</div>
<div class="detail"><?= jaTestEchapper($resultat['detail']) ?></div>
</div>
<?php endforeach; ?>
<p class="aide">Un événement de diagnostic est conservé dans journal lorsque l'écriture réussit. Les mots de passe et clés ne sont jamais affichés.</p>
</section>
<?php endif; ?>
</main>
</body>
</html>

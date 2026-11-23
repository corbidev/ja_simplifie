# JEUX ASSEMBLÉES 2026 — structure de préparation conforme au cadrage V2

Cette archive reprend l'arborescence cible de la section 10 du cadrage
fonctionnel et technique V2.

## Arborescence

```text
projet/
├── .env
├── public/
│   ├── supervision.php                 (à créer lors de l'implémentation)
│   ├── tableau-de-bord.php             (à créer lors de l'implémentation)
│   ├── porte-des-etoiles.php           (à créer lors de l'implémentation)
│   └── assets/
│       ├── css/
│       └── js/
├── src/
└── data/
    ├── equipe-1.json
    ├── equipe-2.json
    ├── equipe-3.json
    ├── equipe-4.json
    ├── suivi-partie.json
    ├── messages.json
    └── archives/
```

Les trois pages PHP ne sont pas générées ici : cette archive prépare les
données et les répertoires, elle ne constitue pas une implémentation.

## Fichiers JSON

- `equipe-1.json` à `equipe-4.json` : paramètres et données propres à
  chaque équipe, dont le code final, la phrase finale et l'année attendue.
- `suivi-partie.json` : état de la partie, chronométrage, tentatives,
  état des portes et validation des années.
- `messages.json` : messages et état de fermeture par destinataire.

Les codes finaux, les phrases finales, les années attendues définitives
et la durée initiale du compte à rebours restent non renseignés : le
cadrage V2 les identifie comme des points à confirmer. Les années et
thèmes figurant dans `reference_historique` reproduisent les références
communiquées dans la section 2 ; ils ne valent pas validation définitive
de `annee_attendue`.

Les structures JSON présentes sont des structures initiales de
préparation. Le découpage précis et les noms définitifs des propriétés
seront arrêtés dans le plan d'implémentation, sans inventer les données
manquantes.

## Fichier `.env`

Le `.env` contient les emplacements pour :
- l'identifiant et le mot de passe de supervision ;
- la clé du tableau de bord ;
- `CLE_EQUIPE_1` à `CLE_EQUIPE_4`.

Toutes les valeurs sont fictives (`A_REMPLACER`). Remplace-les
manuellement par tes propres secrets. Le fichier doit rester hors de
`public/` et ne doit jamais être accessible depuis le navigateur.

## Rappels du cadrage V2

- Quatre équipes, quatre portes indépendantes.
- Quatre énigmes physiques au total, une par groupe ; un cinquième symbole
  est transmis directement aux joueurs.
- Les symboles et codes sont validés côté serveur ; les codes ne sont pas
  exposés au navigateur.
- Les pages actualisent les données toutes les cinq secondes sans
  rechargement complet.
- Les données et secrets restent hors de la racine publique.
- La réinitialisation archive les six JSON actifs dans
  `data/archives/AAAA-MM-JJ_HH-MM/`, puis recrée les données actives.
- Le `.env` n'est pas modifié lors d'une réinitialisation.

Aucun code applicatif n'est inclus. Le cadrage V2 et sa validation
précèdent le plan d'implémentation et tout développement.

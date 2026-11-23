# 3 - Exécution d'une étape spécifique du plan

- **Objectif** : Implémenter une étape isolée en s'appuyant sur le fichier Markdown du plan d'implémentation joint en pièce jointe.
- **Fichier à joindre en PJ** : `IMPLEMENTATION_PLAN_${A_COMPLETER_SELON_NATURE_PROJET}.md` (situé à la racine du projet).

---

## Modèle de Prompt

```text
${ETAPE}=x 
Tu agis en tant que développeur exécutant rigoureux.

Règle fondamentale :
Tu dois suivre scrupuleusement les "Instructions Globales pour les Agents IA de Développement". Si tu estimes devoir déroger à l'une de ces règles ou contraintes, tu dois obligatoirement en faire la demande explicite et obtenir mon accord préalable avant d'agir.

Nous allons implémenter pas à pas le plan d'implémentation.
Le document de référence officiel est le fichier joint en pièce jointe : `IMPLEMENTATION_PLAN_${NATURE_PROJET}.md` (situé à la racine du projet).

Tâche demandée :
Réalise UNIQUEMENT l'étape 'Etape n° ${ETAPE}' définie dans le plan de référence joint :

Contraintes à respecter :
- Reste strictement aligné sur les consignes et fichiers décrits pour cette étape dans le plan joint.
- N'anticipe aucune des étapes ultérieures.
- Respecte l'architecture, la stack technique et les conventions existantes du projet.
- **Documentation du code obligatoire** : Rédige une documentation adaptée au langage (ex: Javadoc en Java, BDoc en PHP, JSDoc/TSDoc en JavaScript/TypeScript, Docstrings en Python, XML Docs en C#, etc.) pour chaque élément créé ou modifié (classes, méthodes, fonctions, interfaces, types). Toute cette documentation technique doit être obligatoirement rédigée en français. **Précision importante** : aucune documentation ou Javadoc n'est requise sur les variables locales, attributs/champs et constantes (sauf si un commentaire métier exceptionnel est indispensable).
- Explique les modifications/ajouts/suppression avant de coder méthode par méthode
- Une fois les explications validées, fournis le code complet pour chaque fichier impacté (ou applique directement les modifications dans les fichiers du projet) selon l'étape à réaliser.
- Précise la commande ou méthode exacte pour valider le bon fonctionnement de cette étape avant d'envisager la suivante.
- les méthodes doivent 
    - être le plus simple possible
    - avoir un code lisible par les developpeurs
    - limiter fortement destruction du code existant
    - limiter les appels dans le constructeur du mieux que possible
    - ne pas faire de contrôle sur des éléments déjà contrôler en amont et non modifié
    - éviter les successions de if/else if
    - respecter scrupuleusement les bonnes pratique de codage de 2026
    - les messages d'erreurs ou texte (sauf pour les LOGGER.debug) doivent être dans constantes.<fichier adapté>
- Une fois terminé, tu expliques en détails le code (ajout/modification) méthode par méthode
- **Proposition de message de commit** : Une fois chaque sous-phase / phase terminée, fournis une proposition pour le corps du message de commit (le titre étant déjà défini) sous la forme suivante :
  - <action / modification réalisée>
      - <explication détaillée, justification métier/technique et impact>
  Exemple de format attendu :
  - fournir le statut de canal sur NewDocumentASignerCanal
      - le DTO porte statut_canal pour permettre l'ouverture immédiate du canal papier (010/1) dans la demande SIG9. Les canaux électroniques restent non renseignés tant qu'ils ne sont pas construits.
  *Attention* : Ce message de commit doit être généré UNIQUEMENT si le code fourni pour la sous-étape ne contient aucune erreur. Si des erreurs subsistent du fait qu'il faille attendre la suite du développement pour les résoudre, ne génère aucun message de commit.

```

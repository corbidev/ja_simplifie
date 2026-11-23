# 2 - Plan d'implémentation séquencé (Racine du projet)

- **Objectif** : Structurer la feuille de route technique étape par étape et la sauvegarder dans un fichier Markdown à la **racine du projet** (le dossier contenant le dépôt `.git` / pom.xml / package.json / solution API). Ce fichier servira d'unique source de vérité pour les prompts suivants.
- **Emplacement cible standard** : `IMPLEMENTATION_PLAN_${A_COMPLETER_SELON_NATURE_PROJET}.md` à la racine du projet (`./`).
- **Modèle conseillé** : **GPT 6.1 Sol ☀️** *(Raisonnement : High)*.

---

## Modèle de Prompt

```text
Tu agis en tant que Lead Développeur et Architecte Logiciel senior.

Règle fondamentale :
Tu dois suivre scrupuleusement les "Instructions Globales pour les Agents IA de Développement". Si tu estimes devoir déroger à l'une de ces règles ou contraintes, tu dois obligatoirement en faire la demande explicite et obtenir mon accord préalable avant d'agir.

À partir des spécifications validées ci-dessus (depuis nos échanges précédents) :


Rédige un plan d'implémentation technique détaillé et séquencé (avec étapes, sous-étapes et cases à cocher).

Consignes d'écriture et de rédaction impératives :
- **Clarté et portabilité multi-IA (anti-hallucination)** : Le plan doit être rédigé de manière explicite, exhaustive et entièrement verbeuse pour être interprétable sans ambiguïté par n'importe quel LLM/IA. Il doit impérativement prémunir contre les hallucinations : se baser uniquement sur les éléments existants confirmés et les spécifications validées, nommer explicitement chaque variable, signature, contrat d'interface, chemin de fichier et classe sans jamais faire de supposition ni utiliser de formulation vague ("etc.", "à adapter selon le besoin", "faire le nécessaire").
- **Emplacement du fichier** : Ce plan doit être écrit / généré dans un fichier Markdown nommé `IMPLEMENTATION_PLAN_${A_COMPLETER_SELON_NATURE_PROJET}.md` situé impérativement à la racine du projet (le répertoire racine contenant le dossier `.git`, la ressource principale ou le fichier descripteur de build).
- Si tu es en mode Agent / outil d'édition, crée directement ce fichier à la racine du projet. Sinon, fournis l'intégralité du contenu prêt à être enregistré dans ce fichier.

Le document Markdown doit respecter la structure suivante :
# Plan d'implémentation - ${A_COMPLETER_TITRE_FONCTIONNALITE}

## 1. Contexte & Référence
- **Ticket / Référence** : ${A_COMPLETER_ID_DU_TICKET}
- **Résumé du besoin et des modifications prévues** : ${A_COMPLETER_RESUME_DES_CHANGEMENTS}

## 2. Synthèse de la solution technique
- Architecture retenue, patterns et bibliothèques utilisés.

## 3. Fichiers et modules impactés
- Liste exhaustive des fichiers à créer, modifier ou supprimer avec leurs chemins relatifs par rapport à la racine du projet.
- Pour chaque classe ou fichier impacté : lister explicitement les méthodes existantes impactées et les nouvelles méthodes à créer.

## 4. Découpage en étapes séquentielles (avec Étapes, sous-étapes et cases à cocher)
Le plan ne doit pas contenir le code final, mais doit être extrêmement verbeux, détaillé et explicite méthode par méthode, structuré en phases de travail cohérentes. Chaque consigne doit être factuelle et sans ambiguïté afin d'empêcher toute extrapolation ou hallucination lors de l'exécution par une IA.

Pour chaque étape (ex. `- [ ] Étape 1 : [Phase de travail cohérente]`) :
- **Objectif** : ce qui doit être réalisé globalement durant cette phase.
- **Fichiers concernés** : chemin et rôle.
- **Découpage en sous-étapes détaillées (méthode par méthode)** :
  Pour chaque sous-étape (ex. `- [ ] Sous-étape 1.1 : [Classe :: Nom de la méthode ou fonctionnalité]`) :
    - **Description et rôle** : rôle précis de la méthode dans la phase de travail.
    - **Actions à mener** : description explicite, verbeuse et détaillée des opérations, algorithmes, entrées/sorties attendues, flux de données et règles de gestion à appliquer au sein de cette méthode (sans rédiger le code final mais sans ambiguïté d'interprétation).
    - **Validation préalable** : proposition et explication de la démarche pour valider l'approche avant le passage à l'implémentation.
    - **Bilan post-réalisation attendu** : explication des impacts attendus (code ajouté, modifié et/ou supprimé).
- **Documentation du code requise** : spécifier l'ajout systématique de commentaires et documentations adaptés au langage (phpdoc, JSDoc/TSDoc, Docstrings Python, XML Documentation C#, etc.) rédigés obligatoirement en français pour toutes les classes, méthodes, fonctions et interfaces créées ou modifiées (aucune documentation n'est requise sur les variables et constantes).
- **Critères d'acceptation / Validation** : comment vérifier que l'étape et ses sous-étapes sont réussies (tests, logs, assertions, commandes de vérification).

## 5. Stratégie de tests
- pas de tests

## 6. Précautions et points de vigilance
- Risques de régression, sécurité, performance.
```

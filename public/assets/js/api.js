'use strict';

/**
 * Petit client HTTP commun aux pages Jeux Assemblées 2026.
 * Les données sont toujours validées et autorisées côté serveur.
 */
window.JAApi = (() => {
    const URL_API = 'api/supervision.php';

    /**
     * Effectue un appel à l'API et convertit sa réponse JSON.
     *
     * @param {string} methode Méthode HTTP.
     * @param {object|null} donnees Corps JSON éventuel.
     * @returns {Promise<object>} Réponse métier décodée.
     */
    async function appeler(methode, donnees = null, parametres = null) {
        const entetes = { Accept: 'application/json' };
        const options = { method: methode, credentials: 'same-origin', headers: entetes };

        if (donnees !== null) {
            const metaCsrf = document.querySelector('meta[name="csrf-token"]');
            const jeton = metaCsrf instanceof HTMLMetaElement ? metaCsrf.content : '';
            if (!jeton) {
                throw new Error('Le jeton de sécurité CSRF est absent de la page.');
            }
            entetes['Content-Type'] = 'application/json';
            entetes['X-CSRF-Token'] = jeton;
            options.body = JSON.stringify(donnees);
        }

        const url = parametres instanceof URLSearchParams ? `${URL_API}?${parametres.toString()}` : URL_API;
        const reponse = await fetch(url, options);
        let resultat;
        try {
            resultat = await reponse.json();
        } catch {
            throw new Error('Le serveur a renvoyé une réponse illisible.');
        }

        if (!reponse.ok || resultat.succes !== true) {
            throw new Error(typeof resultat.erreur === 'string' ? resultat.erreur : 'La requête a échoué.');
        }
        return resultat;
    }

    /**
     * Récupère l'état d'une partie pour l'identité actuellement connectée.
     *
     * @param {string} partieId Identifiant UUID de la partie.
     * @returns {Promise<object>} État de la partie.
     */
    async function obtenirEtat(partieId) {
        const parametres = new URLSearchParams({ action: 'etat', partie_id: partieId });
        const resultat = await appeler('GET', null, parametres);
        return resultat.donnees;
    }

    /**
     * Envoie une action de pilotage réservée à l'administrateur.
     *
     * @param {object} action Données d'action conformes au contrat de l'API.
     * @returns {Promise<object>} Réponse du serveur.
     */
    async function executer(action) {
        return appeler('POST', action);
    }

    return Object.freeze({ obtenirEtat, executer });
})();

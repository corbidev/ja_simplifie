'use strict';

const CLE_PREFERENCE_THEME = 'jeux-assemblees-theme';

/**
 * Applique le thème choisi et synchronise l'état accessible du bouton.
 *
 * @param {'dark'|'light'} theme Thème à appliquer à la page.
 * @param {HTMLButtonElement} bouton Bouton de bascule du thème.
 * @returns {void}
 */
function appliquerTheme(theme, bouton) {
    const themeValide = theme === 'light' ? 'light' : 'dark';
    const libelle = themeValide === 'dark'
        ? bouton.dataset.libelleSombre
        : bouton.dataset.libelleClair;
    const texteBouton = bouton.querySelector('[data-libelle-theme]');

    document.documentElement.dataset.theme = themeValide;
    bouton.setAttribute('aria-pressed', String(themeValide === 'dark'));

    if (texteBouton instanceof HTMLElement && typeof libelle === 'string') {
        texteBouton.textContent = libelle;
    }
}

/**
 * Lit le thème mémorisé et utilise le thème sombre si le stockage est indisponible.
 *
 * @returns {'dark'|'light'} Thème mémorisé ou thème sombre par défaut.
 */
function lireThemeMemorise() {
    try {
        return window.localStorage.getItem(CLE_PREFERENCE_THEME) === 'light'
            ? 'light'
            : 'dark';
    } catch (erreur) {
        if (!(erreur instanceof DOMException)) {
            throw erreur;
        }

        console.debug(erreur);
        return 'dark';
    }
}

/**
 * Mémorise le thème choisi sans empêcher la bascule si le stockage est bloqué.
 *
 * @param {'dark'|'light'} theme Thème à enregistrer.
 * @returns {void}
 */
function memoriserTheme(theme) {
    try {
        window.localStorage.setItem(CLE_PREFERENCE_THEME, theme);
    } catch (erreur) {
        if (!(erreur instanceof DOMException)) {
            throw erreur;
        }

        console.debug(erreur);
    }
}

/**
 * Initialise le bouton de bascule et restaure le thème mémorisé.
 *
 * @returns {void}
 */
function initialiserBasculeTheme() {
    const bouton = document.querySelector('[data-bascule-theme]');

    if (!(bouton instanceof HTMLButtonElement)) {
        return;
    }

    let themeActuel = lireThemeMemorise();
    appliquerTheme(themeActuel, bouton);

    /**
     * Bascule le thème, met à jour son état accessible et mémorise le choix.
     *
     * @returns {void}
     */
    function basculerTheme() {
        themeActuel = themeActuel === 'dark' ? 'light' : 'dark';
        appliquerTheme(themeActuel, bouton);
        memoriserTheme(themeActuel);
    }

    bouton.addEventListener('click', basculerTheme);
}

initialiserBasculeTheme();

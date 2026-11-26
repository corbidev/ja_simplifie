'use strict';

/**
 * Lit et valide les données publiques préparées par PHP.
 *
 * @returns {object|null} Données publiques du tableau de bord, ou null si elles sont invalides.
 */
function lireDonneesTableauDeBord() {
  const elementDonnees = document.getElementById('donnees-tableau-de-bord');
  if (!elementDonnees || window.TABLEAU_DE_BORD_ERREUR_CHARGEMENT === true) return null;

  try {
    const donnees = JSON.parse(elementDonnees.textContent);
    if (!donnees || !Array.isArray(donnees.equipes) || donnees.equipes.length !== 4
      || !donnees.partie || !donnees.partie.chronometrage) return null;

    const numeros = donnees.equipes.map((equipe) => equipe && equipe.equipe);
    if ([1, 2, 3, 4].some((numero) => !numeros.includes(numero))) return null;
    return donnees;
  } catch (erreur) {
    console.error('Les données publiques du tableau de bord sont invalides.', erreur);
    return null;
  }
}

/** Données publiques de la partie chargées depuis le bloc JSON produit par PHP. */
const DONNEES_TABLEAU_DE_BORD = lireDonneesTableauDeBord();

/** Icônes décoratives fixes, sans donnée historique confidentielle. */
const ICONES_EQUIPES = { 1: '◈', 2: '◎', 3: '⌘', 4: '✧' };

/** Regroupe les libellés affichés afin de faciliter leur évolution. */
const TEXTES = {
  equipe: 'ÉQUIPE',
  porteOuverte: 'PORTE OUVERTE',
  porteFermee: 'PORTE VERROUILLÉE',
  tentatives: 'TENTATIVES',
  resolution: 'RÉSOLUTION ANNÉE',
  nonResolue: 'NON RÉSOLUE',
  resolue: 'VALIDÉE',
  chronometreAttente: 'CHRONOMETRE EN ATTENTE',
  chronometreDepasse: 'TEMPS ECOULE',
  historiqueMasque: 'DONNÉES HISTORIQUES VERROUILLÉES',
  erreurDonnees: 'DONNÉES DE SUPERVISION INDISPONIBLES'
};

/** Alphabet 5x7 utilisé par l'afficheur matriciel, indépendant des polices installées. */
const GLYPHES_LED = {
  A:['01110','10001','10001','11111','10001','10001','10001'], B:['11110','10001','10001','11110','10001','10001','11110'],
  C:['01111','10000','10000','10000','10000','10000','01111'], D:['11110','10001','10001','10001','10001','10001','11110'],
  E:['11111','10000','10000','11110','10000','10000','11111'], F:['11111','10000','10000','11110','10000','10000','10000'],
  G:['01111','10000','10000','10111','10001','10001','01111'], H:['10001','10001','10001','11111','10001','10001','10001'],
  I:['11111','00100','00100','00100','00100','00100','11111'], J:['00111','00010','00010','00010','10010','10010','01100'],
  K:['10001','10010','10100','11000','10100','10010','10001'], L:['10000','10000','10000','10000','10000','10000','11111'],
  M:['10001','11011','10101','10101','10001','10001','10001'], N:['10001','11001','10101','10011','10001','10001','10001'],
  O:['01110','10001','10001','10001','10001','10001','01110'], P:['11110','10001','10001','11110','10000','10000','10000'],
  Q:['01110','10001','10001','10001','10101','10010','01101'], R:['11110','10001','10001','11110','10100','10010','10001'],
  S:['01111','10000','10000','01110','00001','00001','11110'], T:['11111','00100','00100','00100','00100','00100','00100'],
  U:['10001','10001','10001','10001','10001','10001','01110'], V:['10001','10001','10001','10001','10001','01010','00100'],
  W:['10001','10001','10001','10101','10101','10101','01010'], X:['10001','10001','01010','00100','01010','10001','10001'],
  Y:['10001','10001','01010','00100','00100','00100','00100'], Z:['11111','00001','00010','00100','01000','10000','11111'],
  '0':['01110','10001','10011','10101','11001','10001','01110'], '1':['00100','01100','00100','00100','00100','00100','01110'],
  '2':['01110','10001','00001','00010','00100','01000','11111'], '3':['11110','00001','00001','01110','00001','00001','11110'],
  '4':['00010','00110','01010','10010','11111','00010','00010'], '5':['11111','10000','10000','11110','00001','00001','11110'],
  '6':['01110','10000','10000','11110','10001','10001','01110'], '7':['11111','00001','00010','00100','01000','01000','01000'],
  '8':['01110','10001','10001','01110','10001','10001','01110'], '9':['01110','10001','10001','01111','00001','00001','01110'],
  ' ':['00000','00000','00000','00000','00000','00000','00000'], ':':['00000','00100','00100','00000','00100','00100','00000'], '-':['00000','00000','00000','11111','00000','00000','00000'],
  '/':['00001','00010','00010','00100','01000','01000','10000'], '.':['00000','00000','00000','00000','00000','00110','00110']
};

/**
 * Échappe une valeur dynamique avant son insertion dans un fragment HTML.
 *
 * @param {string|number} valeur Valeur à afficher.
 * @returns {string} Valeur encodée pour un contexte de texte HTML.
 */
function echapperHtml(valeur) {
  return String(valeur).replace(/[&<>"']/g, (caractere) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[caractere]);
}

/** Construit le module visuel d'une équipe à partir des données publiques fournies par PHP.
 * @param {object} equipe Données publiques d'une équipe.
 * @returns {string} Fragment HTML du module de supervision.
 */
function creerInterfaceEquipe(equipe) {
  const numeroEquipe = Number(equipe.equipe);
  const numero = String(numeroEquipe).padStart(2, '0');
  const porteOuverte = equipe.porte_ouverte === true;
  const porte = porteOuverte ? TEXTES.porteOuverte : TEXTES.porteFermee;
  const resolue = equipe.etat_resolution_annee === 'resolue';
  const resolution = resolue ? TEXTES.resolue : TEXTES.nonResolue;
  const porteIcone = porteOuverte ? '▣' : '▤';
  const tentatives = Math.max(0, Number(equipe.nombre_tentatives_porte) || 0);
  const phraseVisible = resolue && typeof equipe.phrase === 'string' && equipe.phrase.trim() !== '';
  const anneeVisible = resolue && equipe.annee !== null && equipe.annee !== undefined
    && Number.isInteger(Number(equipe.annee));
  const historique = phraseVisible
    ? `<div class="team-name">${echapperHtml(equipe.phrase)}</div>`
    : `<div class="team-name team-history-locked">${TEXTES.historiqueMasque}</div>`;
  const annee = anneeVisible
    ? `<div class="team-year">${echapperHtml(Number(equipe.annee))}</div>`
    : '';

  return `<article class="team-module" data-team="${numeroEquipe}" aria-label="${TEXTES.equipe} ${numero}">
    <div class="edge"></div><span class="corner tl"></span><span class="corner br"></span><div class="scanline"></div>
    <div class="module-inner">
      <div class="team-identity"><div class="team-kicker">${TEXTES.equipe} // UNITÉ ${numero}</div><div class="team-number" data-number="${numero}">${numero}</div><div class="team-history">${historique}${annee}</div></div>
      <div class="core-display">
        <svg viewBox="0 0 200 200" role="img" aria-label="Instrument de supervision ${numero}">
          <defs><radialGradient id="noyau${numeroEquipe}"><stop offset="0" stop-color="var(--accent)" stop-opacity=".42"/><stop offset=".55" stop-color="var(--accent2)" stop-opacity=".12"/><stop offset="1" stop-color="var(--accent)" stop-opacity="0"/></radialGradient></defs>
          <circle cx="100" cy="100" r="91" fill="url(#noyau${numeroEquipe})" class="pulse"/>
          <g class="ring-rotate" fill="none" stroke="var(--accent)" stroke-width="1.2"><circle cx="100" cy="100" r="79" stroke-dasharray="3 5"/><path d="M100 8V25 M100 175V192 M8 100H25 M175 100H192"/><path d="M35 35L47 47 M153 153L165 165 M165 35L153 47 M47 153L35 165" stroke-width="3"/></g>
          <g class="ring-reverse" fill="none" stroke="var(--accent2)" stroke-width="2"><circle cx="100" cy="100" r="62" stroke-dasharray="52 12 8 12"/><circle cx="100" cy="100" r="49" stroke-dasharray="2 7"/></g>
          <circle cx="100" cy="100" r="36" fill="#07172a" stroke="var(--accent)" stroke-width="1.5"/><circle cx="100" cy="100" r="29" fill="none" stroke="var(--accent2)" stroke-width="1" opacity=".8"/>
          <text x="100" y="110" text-anchor="middle" fill="var(--accent)" font-size="31" font-family="Arial">${ICONES_EQUIPES[numeroEquipe]}</text>
          <path d="M100 0V17 M100 183V200 M0 100H17 M183 100H200" stroke="var(--accent)" stroke-width="2" opacity=".8"/>
        </svg>
        <div class="core-caption">SCAN // SECTEUR ${numero}</div>
      </div>
      <div class="team-metrics">
        <div class="metric"><div class="metric-label">État du sas</div><div class="metric-value state status-line"><span class="status-dot"></span>${porteIcone} ${porte}</div></div>
        <div class="metric"><div class="metric-label">${TEXTES.tentatives}</div><div class="metric-value">${String(tentatives).padStart(2, '0')}</div><div class="signal-bars"><i></i><i></i><i></i><i></i><i></i><i></i></div></div>
        <div class="metric"><div class="metric-label">${TEXTES.resolution}</div><div class="metric-value state">${resolution}</div></div>
      </div>
    </div>
  </article>`;
}

/** Injecte les quatre modules d'équipe à partir des données PHP validées.
 * @returns {void}
 */
function initialiserTableauDeBord() {
  const conteneur = document.getElementById('equipes');
  if (!conteneur) return;

  if (!DONNEES_TABLEAU_DE_BORD) {
    conteneur.textContent = TEXTES.erreurDonnees;
    conteneur.classList.add('team-grid-error');
    actualiserHorloge();
    actualiserBandeauLed();
    dernierInstantHorloge = performance.now();
    window.requestAnimationFrame(boucleAffichage);
    return;
  }

  const equipesTriees = [...DONNEES_TABLEAU_DE_BORD.equipes]
    .sort((a, b) => Number(a.equipe) - Number(b.equipe));
  conteneur.innerHTML = equipesTriees.map(creerInterfaceEquipe).join('');
  actualiserHorloge();
  actualiserBandeauLed();
  window.addEventListener('resize', actualiserBandeauLed);
  dernierInstantHorloge = performance.now();
  window.requestAnimationFrame(boucleAffichage);
}

/** Actualise l'horloge circulaire à LED rouges.
 * @returns {void}
 */
function actualiserHorloge() {
  const elementHeure = document.getElementById('horloge');
  const anneau = document.getElementById('clockRing');
  if (!elementHeure || !anneau) return;

  const namespaceSvg = 'http://www.w3.org/2000/svg';
  if (anneau.childElementCount === 0) {
    for (let index = 0; index < 60; index += 1) {
      const angle = (index * 6 - 90) * Math.PI / 180;
      const point = document.createElementNS(namespaceSvg, 'circle');
      point.setAttribute('cx', String(50 + Math.cos(angle) * 43));
      point.setAttribute('cy', String(50 + Math.sin(angle) * 43));
      point.setAttribute('r', index % 5 === 0 ? '1.65' : '1.2');
      point.dataset.second = String(index);
      anneau.appendChild(point);
    }
  }

  // Chiffres LCD à sept segments, dessinés en SVG pour rester nets à toute échelle.
  const segmentsParChiffre = {
    '0': ['a', 'b', 'c', 'd', 'e', 'f'], '1': ['b', 'c'],
    '2': ['a', 'b', 'g', 'e', 'd'], '3': ['a', 'b', 'g', 'c', 'd'],
    '4': ['f', 'g', 'b', 'c'], '5': ['a', 'f', 'g', 'c', 'd'],
    '6': ['a', 'f', 'g', 'e', 'c', 'd'], '7': ['a', 'b', 'c'],
    '8': ['a', 'b', 'c', 'd', 'e', 'f', 'g'], '9': ['a', 'b', 'c', 'd', 'f', 'g']
  };
  const formesSegments = {
    a: '6,2 24,2 21,5 9,5', b: '25,4 27,6 27,15 24,18 23,7',
    c: '25,20 27,18 27,29 25,31 23,28 23,23', d: '6,32 24,32 21,29 9,29',
    e: '3,20 5,23 5,28 3,31 1,29 1,22', f: '3,4 5,6 5,15 3,18 1,15 1,6',
    g: '6,17 9,15 21,15 24,17 21,19 9,19'
  };
  const maintenant = new Date();
  const heure = new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit', hour12: false }).format(maintenant);
  if (elementHeure.dataset.value !== heure) {
    elementHeure.dataset.value = heure;
    const chiffres = heure.replace(':', '').split('');
    const svg = document.createElementNS(namespaceSvg, 'svg');
    svg.setAttribute('viewBox', '0 0 124 36');
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', `Il est ${heure}`);
    chiffres.forEach((chiffre, index) => {
      const groupe = document.createElementNS(namespaceSvg, 'g');
      groupe.setAttribute('transform', `translate(${index * 30 + (index > 1 ? 6 : 0)},1)`);
      Object.entries(formesSegments).forEach(([nom, points]) => {
        const polygone = document.createElementNS(namespaceSvg, 'polygon');
        polygone.setAttribute('points', points);
        polygone.setAttribute('class', `segment${segmentsParChiffre[chiffre].includes(nom) ? ' on' : ''}`);
        groupe.appendChild(polygone);
      });
      svg.appendChild(groupe);
    });
    const deuxPoints = document.createElementNS(namespaceSvg, 'g');
    deuxPoints.setAttribute('class', 'segment on');
    ['11', '24'].forEach(y => {
      const point = document.createElementNS(namespaceSvg, 'circle');
      point.setAttribute('cx', '61'); point.setAttribute('cy', y); point.setAttribute('r', '1.8');
      point.setAttribute('class', 'segment on'); deuxPoints.appendChild(point);
    });
    svg.appendChild(deuxPoints);
    elementHeure.replaceChildren(svg);
  }

  // Anneau immobile : les LED écoulées restent allumées, les suivantes sont éteintes.
  const secondeActive = maintenant.getSeconds();
  const points = anneau.children;
  for (let index = 0; index < points.length; index += 1) {
    points[index].classList.toggle('active', index < secondeActive);
  }
}

/** Dessine un message avec une véritable matrice de caractères LED 5x7.
 * Les glyphes sont définis localement pour garantir un rendu identique sur chaque navigateur.
 * @param {string} message Texte à afficher sur la matrice.
 * @returns {void}
 */
let MESSAGE_LED_EN_COURS = '';
let DEBUT_DEFILEMENT_LED = performance.now();
let dernierInstantHorloge = 0;

/**
 * Pilote l'actualisation de l'horloge à la seconde et le défilement LED en continu.
 * @param {number} instantAnimation Horodatage fourni par le navigateur.
 * @returns {void}
 */
function boucleAffichage(instantAnimation) {
  if (instantAnimation - dernierInstantHorloge >= 1000) {
    actualiserHorloge();
    dernierInstantHorloge = instantAnimation;
  }

  actualiserBandeauLed();
  window.requestAnimationFrame(boucleAffichage);
}

/**
 * Dessine un message avec une véritable matrice de caractères LED 5x7.
 * Chaque nouveau message reste immobile pendant deux secondes, puis défile vers la gauche.
 * Sur mobile, le défilement commence au premier caractère.
 * @param {string} message Texte à afficher sur la matrice.
 * @returns {void}
 */
function dessinerMatriceLed(message) {
  const canvas = document.getElementById('ledCanvas');
  if (!canvas) return;

  const rectangle = canvas.getBoundingClientRect();
  const largeurCss = rectangle.width;
  const hauteurCss = rectangle.height;
  if (largeurCss <= 0 || hauteurCss <= 0) return;

  const ratio = Math.min(window.devicePixelRatio || 1, 2);
  const largeur = Math.round(largeurCss * ratio);
  const hauteur = Math.round(hauteurCss * ratio);
  if (canvas.width !== largeur || canvas.height !== hauteur) {
    canvas.width = largeur;
    canvas.height = hauteur;
  }

  const contexte = canvas.getContext('2d');
  if (!contexte) return;
  contexte.setTransform(ratio, 0, 0, ratio, 0, 0);
  contexte.clearRect(0, 0, largeurCss, hauteurCss);
  contexte.fillStyle = '#020607';
  contexte.fillRect(0, 0, largeurCss, hauteurCss);

  const texte = String(message || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
  const caracteres = Array.from(texte).map((caractere) => GLYPHES_LED[caractere] || GLYPHES_LED['-']);
  const colonnes = caracteres.reduce((total, glyphe) => total + (glyphe ? 6 : 6), 0) - (caracteres.length ? 1 : 0);
  if (caracteres.length === 0) return;

  const pasHauteur = (hauteurCss - 12) / 7;
  const pasLargeur = (largeurCss - 20) / Math.max(1, colonnes);
  const pas = Math.max(2.2, Math.min(8, pasHauteur, pasLargeur));
  const largeurTexte = colonnes * pas;
  const debordement = Math.max(0, largeurTexte - (largeurCss - 12));
  const messageNormalise = String(message || '');
  if (messageNormalise !== MESSAGE_LED_EN_COURS) {
    MESSAGE_LED_EN_COURS = messageNormalise;
    DEBUT_DEFILEMENT_LED = performance.now();
  }

  const estMobile = window.matchMedia('(max-width: 760px)').matches;
  const distanceDefilement = debordement + largeurCss * .35;
  const tempsEcoule = Math.max(0, performance.now() - DEBUT_DEFILEMENT_LED);
  const pauseAvantDefilement = 2000;
  const tempsDefilement = Math.max(0, tempsEcoule - pauseAvantDefilement);
  const decalage = distanceDefilement > 0
    ? (tempsDefilement / 45) % distanceDefilement
    : 0;
  const positionInitiale = estMobile ? 6 : (largeurCss - largeurTexte) / 2;
  let origineX = positionInitiale - decalage;
  const origineY = (hauteurCss - 7 * pas) / 2;
  const rayon = Math.max(.85, pas * .31);

  // Trame de fond : points éteints, réguliers et peu lumineux.
  contexte.fillStyle = '#12312e';
  for (let y = pas / 2; y < hauteurCss; y += pas) {
    for (let x = pas / 2; x < largeurCss; x += pas) {
      contexte.beginPath();
      contexte.arc(x, y, Math.max(.55, rayon * .48), 0, Math.PI * 2);
      contexte.fill();
    }
  }

  caracteres.forEach((glyphe) => {
    glyphe.forEach((ligne, indexLigne) => {
      for (let indexColonne = 0; indexColonne < 5; indexColonne += 1) {
        if (ligne[indexColonne] !== '1') continue;
        const x = origineX + (indexColonne + .5) * pas;
        const y = origineY + (indexLigne + .5) * pas;
        if (x < -pas || x > largeurCss + pas) continue;
        contexte.beginPath();
        contexte.arc(x, y, rayon, 0, Math.PI * 2);
        contexte.fillStyle = '#42ffdc';
        contexte.shadowColor = '#00f5ce';
        contexte.shadowBlur = pas * 1.35;
        contexte.fill();
      }
    });
    origineX += 6 * pas;
  });
  contexte.shadowBlur = 0;
}

/** Affiche le chronométrage public transmis par PHP sur le bandeau LED.
 * @returns {void}
 */
function actualiserBandeauLed() {
  const chronometrage = DONNEES_TABLEAU_DE_BORD?.partie?.chronometrage;
  const restant = chronometrage?.temps_restant_secondes;

  if (restant === null || restant === undefined || !Number.isFinite(Number(restant))) {
    dessinerMatriceLed(TEXTES.chronometreAttente);
    return;
  }

  const secondesRestantes = Math.max(0, Math.floor(Number(restant)));
  const heures = String(Math.floor(secondesRestantes / 3600)).padStart(2, '0');
  const minutes = String(Math.floor((secondesRestantes % 3600) / 60)).padStart(2, '0');
  const secondes = String(secondesRestantes % 60).padStart(2, '0');
  dessinerMatriceLed(secondesRestantes === 0
    ? TEXTES.chronometreDepasse
    : `${heures}:${minutes}:${secondes}`);
}

/** Initialise un décor Three.js discret composé de particules et de lignes lumineuses.
 * @returns {object|null} Ressources de la scène ou null si Three.js est indisponible.
 */
function initialiserSceneThreeJs() {
  if (!window.THREE) return null;
  const canvas = document.getElementById('scene3d');
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, .1, 1000);
  camera.position.z = 65;
  const moteur = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
  moteur.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  moteur.setSize(window.innerWidth, window.innerHeight);
  const geometrie = new THREE.BufferGeometry();
  const positions = [];
  for (let index = 0; index < 260; index += 1) positions.push((Math.random() - .5) * 115, (Math.random() - .5) * 70, (Math.random() - .5) * 35);
  geometrie.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
  const particules = new THREE.Points(geometrie, new THREE.PointsMaterial({ color: 0x5bcfff, size: .22, transparent: true, opacity: .65 }));
  scene.add(particules);
  const groupe = new THREE.Group();
  for (let index = 0; index < 8; index += 1) {
    const geometrieAnneau = new THREE.TorusGeometry(5 + index * 2.3, .018, 4, 120);
    const materiau = new THREE.MeshBasicMaterial({ color: index % 2 ? 0x9b54ff : 0x22cfff, transparent: true, opacity: .12 });
    const anneau = new THREE.Mesh(geometrieAnneau, materiau);
    anneau.position.set((index - 3.5) * 13, index % 2 ? 22 : -22, -18);
    anneau.rotation.x = Math.PI / 2.7;
    groupe.add(anneau);
  }
  scene.add(groupe);
  const redimensionner = () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    moteur.setSize(window.innerWidth, window.innerHeight);
  };
  window.addEventListener('resize', redimensionner);
  return { scene, camera, moteur, particules, groupe };
}

/** Anime les particules et anneaux du décor 3D sans intervenir sur les données du jeu.
 * @param {{scene:object,camera:object,moteur:object,particules:object,groupe:object}|null} ressources Ressources Three.js.
 * @returns {void}
 */
function animerSceneThreeJs(ressources) {
  if (!ressources) return;
  const { moteur, scene, camera, particules, groupe } = ressources;
  const mouvementReduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const boucle = () => {
    if (!mouvementReduit) {
      particules.rotation.y += .00018;
      particules.rotation.x += .00007;
      groupe.rotation.z += .00012;
    }
    moteur.render(scene, camera);
    window.requestAnimationFrame(boucle);
  };
  boucle();
}

/** Lance les composants du tableau de bord après le chargement du document.
 * @returns {void}
 */
function demarrerApplication() {
  initialiserTableauDeBord();
  const scene = initialiserSceneThreeJs();
  animerSceneThreeJs(scene);
}

document.addEventListener('DOMContentLoaded', demarrerApplication);

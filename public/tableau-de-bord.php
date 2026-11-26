<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#050816">
<title>Tableau de supervision — JA 2026</title>
<link rel="stylesheet" href="assets/css/tableau-de-bord.css">
</head>
<body>
<canvas id="scene3d" aria-hidden="true"></canvas><div class="grain" aria-hidden="true"></div>
<main class="interface" aria-label="Tableau de supervision des quatre équipes">
  <header class="topline">
    <section class="clock-unit" aria-label="Horloge numérique à LED rouges">
      <div class="clock-face" aria-hidden="true"><svg id="clockRing" class="clock-ring" viewBox="0 0 100 100"></svg><div id="horloge" class="clock-digital" aria-label="Heure"></div></div>
    </section>
    <section class="led-shell" aria-label="Bandeau LED du chronomètre"><div class="led-screen"><canvas id="ledCanvas" aria-label="Chronomètre : durée non configurée"></canvas></div></section>
    
  </header>
  <section id="equipes" class="team-grid" aria-label="État des équipes"></section>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r160/three.min.js"></script>
<script src="assets/js/tableau-de-bord.js" defer></script>
</body>
</html>

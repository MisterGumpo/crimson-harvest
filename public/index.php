<?php

declare(strict_types=1);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CRIMSON HARVEST</title>
    <link rel="stylesheet" href="/assets/css/site.css">
    <link rel="stylesheet" href="/assets/css/podium.css">
</head>
<body>
    <main class="site-shell">
        <header class="masthead">
            <h1>CRIMSON HARVEST '26</h1>
            <p class="eyebrow">THE BLOOD MUST FLOW</p>
            <div id="live-tag" class="live-tag"><span></span> CHECKING FEED</div>
            <div class="countdown" aria-live="polite"><span id="event-label">CONNECTING TO THE BLOODSHED</span><strong id="countdown">--D --H --M --S</strong></div>
        </header>
        <div class="ticker" aria-live="polite"><b>LIVE:</b><div class="ticker-window"><div id="ticker-track" class="ticker-track"><span class="ticker-entry">Awaiting the next transmission...</span></div></div></div>
        <section class="boards" aria-label="Competition leaderboards">
            <article class="board board-hagilur"><header><p class="board-kicker">MOST KILLS</p><h2>HAGILUR</h2><p class="prize">1. NAGLFAR<br>2. GISTUM B-TYPE HARDENER<br>3. DREADNOUGHT SKILLBOOK</p><div id="hagilur-podium" class="podium"></div></header><ol id="hagilur" class="leaderboard"></ol></article>
            <article class="board board-regional"><header><p class="board-kicker">MOST KILLS</p><h2>METROPOLIS / HEIMATAR</h2><p class="prize">1. MOROS NAVY ISSUE<br>2. CENTUM B-TYPE MEMBRANE<br>3. DREADNOUGHT SKILLBOOK</p><div id="regional-podium" class="podium"></div></header><ol id="regional" class="leaderboard"></ol></article>
            <article class="board board-raffle"><header><p class="board-kicker">KILLER RAFFLE</p><h2>TICKET HOLDERS</h2><p class="prize">DRAW 1-2: LARGE SKILL INJECTOR<br>DRAW 3: 2X SMALL SKILL INJECTOR</p></header><ol id="raffle" class="leaderboard"></ol></article>
        </section>
        <section class="stats" aria-label="Event statistics"><div><b id="total-kills">0</b><span>KILLMAILS</span></div><div><b id="total-parts">0</b><span>PARTICIPATIONS</span></div><div><b id="unique-killers">0</b><span>UNIQUE KILLERS</span></div><div><b id="tickets">0</b><span>RAFFLE TICKETS</span></div><div><b id="latest-import">--</b><span>LAST IMPORT</span></div><p id="connection">CONNECTING...</p></section>
        <footer>BEST VIEWED IN NETSCAPE 4.0 <span>|</span> Y2K COMPLIANT <span>|</span> 33.6K MODEM RECOMMENDED</footer>
    </main>
    <script src="/assets/js/dashboard.js?v=20261002b" defer></script>
</body>
</html>

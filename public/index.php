<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
$siteUrl = rtrim($config->app['url'], '/');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crimson Harvest 26 - PVP Contest</title>
    <meta name="description" content="Crimson Harvest 26 EVE Online PVP contest. Destroy. Dominate. Decimate. Compete for prizes.">
    <link rel="icon" type="image/jpeg" href="/assets/img/favicon.jpg">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Crimson Harvest 26 - PVP Contest">
    <meta property="og:description" content="Crimson Harvest 26 EVE Online PVP contest. Destroy. Dominate. Decimate. Compete for prizes.">
    <meta property="og:url" content="<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>/">
    <meta property="og:image" content="<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>/assets/img/preview.jpg">
    <meta property="og:image:type" content="image/jpeg">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Crimson Harvest 26 - PVP Contest">
    <meta name="twitter:description" content="Crimson Harvest 26 EVE Online PVP contest. Destroy. Dominate. Decimate. Compete for prizes.">
    <meta name="twitter:image" content="<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>/assets/img/preview.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comic+Relief:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Creepster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/site.css">
    <link rel="stylesheet" href="/assets/css/podium.css">
    <link rel="stylesheet" href="/assets/css/retro.css?v=20261004r">
</head>

<body>
    <main class="site-shell">
        <header class="masthead">
            <div class="masthead-brand">
                <img class="masthead-logo" src="/assets/img/front-logo.png" alt="Front alliance logo">
                <div class="masthead-copy">
                    <p class="eyebrow">THE BLOOD MUST FLOW</p>
                    <h1>CRIMSON HARVEST <span>'26</span></h1>
                    <p class="masthead-subtitle">DESTROY. DOMINATE. DECIMATE. DEFECATE?</p>
                </div>
                <img class="masthead-logo" src="/assets/img/cryonic-logo.png" alt="Cryonic alliance logo">
            </div>
            <div class="event-status">
                <img class="countdown-skull" src="/assets/img/skull.gif" alt="">
                <div class="countdown" aria-label="Event countdown">
                    <span id="event-label">CONNECTING TO THE BLOODSHED</span>
                    <strong id="countdown">--D --H --M --S</strong>
                </div>
                <div class="event-controls">
                    <div id="live-tag" class="live-tag" role="status" aria-live="polite"><span></span> CHECKING FEED</div>
                    <button id="music-toggle" class="music-toggle" type="button" aria-pressed="false">MUSIC: OFF</button>
                </div>
                <img class="countdown-skull" src="/assets/img/skull.gif" alt="">
            </div>
        </header>
        <div class="ticker" aria-label="Latest kill reports">
            <b>LIVE:</b>
            <div class="ticker-window">
                <div id="ticker-track" class="ticker-track"><span class="ticker-entry">💀 Awaiting the next transmission... 💀</span></div>
            </div>
        </div>
        <section class="boards" aria-label="Competition leaderboards">
            <article class="board board-hagilur">
                <header class="board-header">
                    <h2>TOP KILLERS: HAGILUR</h2>
                    <p class="prize">1ST: NAGLFAR<br>2ND: GISTUM B-TYPE HARDENER<br>3RD: DREADNOUGHT SKILLBOOK</p>
                    <div id="hagilur-podium" class="podium" aria-label="Hagilur prize positions"></div>
                </header>
                <div class="leaderboard-scroll" role="region" aria-label="Hagilur standings" tabindex="0">
                    <ol id="hagilur" class="leaderboard"></ol>
                </div>
            </article>
            <article class="board board-regional">
                <header class="board-header">
                    <h2>TOP KILLERS: METRO / HEIMATAR</h2>
                    <p class="prize">1ST: MOROS NAVY ISSUE<br>2ND: CENTUM B-TYPE MEMBRANE<br>3RD: DREADNOUGHT SKILLBOOK</p>
                    <div id="regional-podium" class="podium" aria-label="Regional prize positions"></div>
                </header>
                <div class="leaderboard-scroll" role="region" aria-label="Regional standings" tabindex="0">
                    <ol id="regional" class="leaderboard"></ol>
                </div>
            </article>
            <article class="board board-raffle">
                <header class="board-header">
                    <h2>KILLER RAFFLE</h2>
                    <p class="prize">DRAW 1: LARGE SKILL INJECTOR<br>DRAW 2: LARGE SKILL INJECTOR<br>DRAW 3: 2X SMALL SKILL INJECTOR</p>
                </header>
                <div class="leaderboard-scroll" role="region" aria-label="Raffle ticket standings" tabindex="0">
                    <ol id="raffle" class="leaderboard"></ol>
                </div>
            </article>
        </section>
        <section class="dashboard-lower" aria-label="Event activity and statistics">
            <section class="stats" aria-label="Event statistics">
                <div class="stat"><img class="stat-gif" src="/assets/img/killmails.gif" alt=""><div class="stat-copy"><b id="total-kills">0</b><span>TOTAL KILLMAILS</span></div></div>
                <div class="stat"><img class="stat-gif" src="/assets/img/participations.gif" alt=""><div class="stat-copy"><b id="total-parts">0</b><span>PARTICIPATIONS</span></div></div>
                <div class="stat"><img class="stat-gif" src="/assets/img/unique-killers.gif" alt=""><div class="stat-copy"><b id="unique-killers">0</b><span>UNIQUE KILLERS</span></div></div>
                <div class="stat"><img class="stat-gif" src="/assets/img/raffle.gif" alt=""><div class="stat-copy"><b id="tickets">0</b><span>RAFFLE TICKETS</span></div></div>
                <div class="stat stat-import"><img class="stat-gif" src="/assets/img/import.gif" alt=""><div class="stat-copy"><b id="latest-import">--</b><span>LAST IMPORT</span></div></div>
            </section>
            <article class="latest-kill" aria-label="Contest rules">
                <div class="latest-kill-heading"><span class="crosshair" aria-hidden="true">&#8853;</span>
                    <div>
                        <p>Contest runs between 1 October @ 00:00 EVE and 1 November @ 00:00 EVE</p>
                    </div>
                </div>
                <p class="latest-kill-summary">A kill is any participation on a killmail. Winners of the Hagulur contest are excluded from the regional contest. Winners of the regional contest are excluded from the raffle. Each kill counts as one raffle ticket. Only characters in FRONT and COA count. Alts do not count and will be excluded at the end of the contest. <a href="https://discord.com/channels/871157131351588884/1464690096618999995/1555088839943528539" target="_blank">More information</a>.</p>
            </article>
        </section>
        <footer class="retro-footer">
            <div class="footer-badges" aria-label="Retro web badges">
                <img src="/assets/img/footer/ie4.gif" alt="Internet Explorer 4">
                <img src="/assets/img/footer/win98.gif" alt="Windows 98">
                <img src="/assets/img/footer/got_html.gif" alt="HTML">
                <img src="/assets/img/footer/css.gif" alt="CSS">
                <img src="/assets/img/footer/get_shockwave_player_20070913.gif" alt="Get Shockwave player">
                <img src="/assets/img/footer/get_player_19971010.gif" alt="Get the player">
                <img src="/assets/img/footer/amiga_friendly.gif" alt="Amiga friendly">
                <img src="/assets/img/footer/icqbutton.gif" alt="ICQ">
                <img src="/assets/img/footer/PIZZZA_boton.gif" alt="Pizzza">
            </div>
            <div class="footer-copy">
                <span>Brought to your world wide web internet browser by <a href="https://www.dudreda.news" target="_blank">Dudreda News</a>.</span>
            </div>
            <div class="footer-copy">
                <span>Y2K COMPLIANT!</span>
                <span>33.6K MODEM RECOMMENDED!</span>
            </div>
            <div class="footer-badges" aria-label="Retro web badges">
                <img src="/assets/img/footer/wwwbadge.gif" alt="World Wide Web" width="100" height="100">
            </div>
            <div class="footer-copy footer-copyright">
                <span>&copy; 1999 Dudreda News</span>
            </div>
        </footer>
    </main>
    <script src="/assets/js/dashboard.js?v=20261002d" defer></script>
</body>

</html>
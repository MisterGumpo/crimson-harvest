const previous = { hagilur: new Map(), regional: new Map(), raffle: new Map() };
let recentKillSignature = "";
let latestImportKey = null;
let latestImportValue = null;
let latestImportTimestamp = null;
let latestImportReceivedAt = null;
const labels = {
  before: "THE BLOODSHED BEGINS IN",
  active: "THE BLOODSHED ENDS IN",
  ended: "THE BLOODSHED HAS ENDED",
};
function escapeHtml(value) {
  const node = document.createElement("div");
  node.textContent = value ?? "";
  return node.innerHTML;
}
function setLatestImport(value, killCount, latestKillId, updateSignal) {
  const nextValue = value || null;
  const nextKey = `${nextValue || ""}|${killCount ?? ""}|${latestKillId ?? ""}|${updateSignal ?? ""}`;
  if (nextKey === latestImportKey) return;
  latestImportKey = nextKey;
  latestImportValue = nextValue;
  latestImportTimestamp = null;
  latestImportReceivedAt = nextValue ? Date.now() : null;
  if (nextValue) {
    const timestamp = Date.parse(
      nextValue.includes("T") ? nextValue : `${nextValue.replace(" ", "T")}Z`,
    );
    if (!Number.isNaN(timestamp)) latestImportTimestamp = timestamp;
  }
}
function updateLatestImport() {
  const element = document.getElementById("latest-import");
  if (!latestImportValue) {
    element.textContent = "--";
    return;
  }
  const seconds = Math.max(
    0,
    Math.floor((Date.now() - latestImportReceivedAt) / 1000),
  );
  element.textContent = `${seconds}s`;
}
function renderBoard(id, rows, type) {
  const list = document.getElementById(id);
  const old = previous[type];
  list.innerHTML = rows
    .map((pilot, index) => {
      const moved =
        old.has(pilot.character_id) &&
        old.get(pilot.character_id) !== pilot.score_position;
      const portrait = `https://images.evetech.net/characters/${pilot.character_id}/portrait?size=32`;
      const affiliationLogos = [
        ["alliances", pilot.alliance_id, "Alliance"],
        ["corporations", pilot.corporation_id, "Corporation"],
      ]
        .filter(([, entityId]) => Number(entityId) > 0)
        .map(
          ([entityType, entityId, label]) =>
            `<img class="affiliation-logo" src="https://images.evetech.net/${entityType}/${Number(entityId)}/logo?size=32" alt="${label} logo" loading="lazy">`,
        )
        .join("");
      const badge =
        type === "regional" && !pilot.prize_eligible
          ? "HAGILUR WINNER / INELIGIBLE"
          : pilot.prize_position
            ? `REGIONAL PRIZE ${pilot.prize_position}`
            : "";
      const score = type === "raffle" ? pilot.tickets : pilot.kills;
      return `<li class="row ${index < 3 ? "top" : ""} ${type === "regional" && !pilot.prize_eligible ? "muted" : ""} ${moved ? "changed" : ""}"><span class="rank">${pilot.score_position}</span><img class="portrait" src="${portrait}" alt=""><span class="affiliation-logos">${affiliationLogos}</span><span class="pilot"><span class="name">${escapeHtml(pilot.character_name)}</span><span class="badge">${badge}</span></span><span class="score">${score}</span></li>`;
    })
    .join("");
  rows.forEach((pilot) => old.set(pilot.character_id, pilot.score_position));
}
function renderPodium(id, rows) {
  const podium = document.getElementById(id);
  if (!podium) return;
  podium.innerHTML = rows
    .slice(0, 3)
    .map(
      (pilot) =>
        `<div class="podium-slot place-${pilot.prize_position || pilot.score_position}"><span>${pilot.prize_position || pilot.score_position}</span><img class="podium-portrait" src="https://images.evetech.net/characters/${pilot.character_id}/portrait?size=64" alt="" loading="lazy"><b>${escapeHtml(pilot.character_name)}</b><small>${pilot.kills} KILLS</small></div>`,
    )
    .join("");
}
function renderTicker(kills) {
  const uniqueKills = [];
  const seen = new Set();
  for (const kill of kills || []) {
    if (seen.has(kill.killmail_id)) continue;
    seen.add(kill.killmail_id);
    uniqueKills.push(kill);
    if (uniqueKills.length === 10) break;
  }
  const signature = uniqueKills.map((kill) => kill.killmail_id).join(",");
  if (signature === recentKillSignature) return;
  recentKillSignature = signature;
  const track = document.getElementById("ticker-track");
  if (uniqueKills.length === 0) {
    track.innerHTML =
      '<span class="ticker-entry">Awaiting the next transmission...</span>';
    return;
  }
  const entries = uniqueKills
    .map((kill) => {
      const ship =
        kill.victim_ship_name ||
        (kill.victim_ship_type_id
          ? `Ship type #${kill.victim_ship_type_id}`
          : "a ship");
      const url = kill.zkill_url || "#";
      return `<a class="ticker-entry" href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(kill.attacker_name || "A pilot")} destroyed ${escapeHtml(ship)} in ${escapeHtml(kill.solar_system_name || `System ${kill.solar_system_id}`)}!</a>`;
    })
    .join("");
  const copy = entries.replaceAll(
    'class="ticker-entry"',
    'class="ticker-entry" aria-hidden="true"',
  );
  track.innerHTML = entries + copy;
}
function updateCountdown(event) {
  const now = Date.now();
  const target =
    event.status === "before"
      ? Date.parse(event.startsAt)
      : Date.parse(event.endsAt);
  if (event.status === "ended") {
    document.getElementById("event-label").textContent = labels.ended;
    document.getElementById("countdown").textContent = "FINAL STANDINGS";
    return;
  }
  let seconds = Math.max(0, Math.floor((target - now) / 1000));
  const days = Math.floor(seconds / 86400);
  seconds %= 86400;
  const hours = Math.floor(seconds / 3600);
  seconds %= 3600;
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  document.getElementById("event-label").textContent = labels[event.status];
  document.getElementById("countdown").textContent =
    `${String(days).padStart(2, "0")}D ${String(hours).padStart(2, "0")}H ${String(mins).padStart(2, "0")}M ${String(secs).padStart(2, "0")}S`;
}
async function refresh() {
  try {
    const response = await fetch("/api/dashboard.php", { cache: "no-store" });
    if (!response.ok) throw new Error("unavailable");
    const data = await response.json();
    renderBoard("hagilur", data.hagilur, "hagilur");
    renderBoard("regional", data.regional, "regional");
    renderBoard("raffle", data.raffle, "raffle");
    renderPodium("hagilur-podium", data.hagilurPrizeWinners || data.hagilur);
    renderPodium("regional-podium", data.regionalPrizeWinners || []);
    updateCountdown(data.event);
    document.getElementById("total-kills").textContent =
      data.stats.total_killmails ?? 0;
    document.getElementById("total-parts").textContent =
      data.stats.total_participations ?? 0;
    document.getElementById("unique-killers").textContent =
      data.stats.unique_killers ?? 0;
    document.getElementById("tickets").textContent =
      data.stats.raffle_tickets ?? 0;
    setLatestImport(
      data.stats.latest_imported_at || null,
      data.stats.total_killmails,
      data.recentKills?.[0]?.killmail_id,
      data.ingestion?.lastHeartbeatAt,
    );
    updateLatestImport();
    renderTicker(data.recentKills || []);
    const workerOnline = Boolean(data.ingestion?.workerOnline);
    document.getElementById("live-tag").lastChild.textContent = workerOnline
      ? " LIVE FEED"
      : " WORKER OFFLINE";
    document.getElementById("connection").textContent = workerOnline
      ? "LIVE DATA CONNECTED"
      : "LIVE WORKER NOT RUNNING";
    document.getElementById("connection").style.color = workerOnline
      ? "#76d58e"
      : "#ed2433";
    window.latestEvent = data.event;
  } catch (error) {
    document.getElementById("connection").textContent = "LIVE DATA DELAYED";
    document.getElementById("connection").style.color = "#ed2433";
  }
}
refresh();
setInterval(refresh, 10000);
setInterval(() => {
  if (window.latestEvent) updateCountdown(window.latestEvent);
  updateLatestImport();
}, 1000);

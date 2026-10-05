# CRIMSON HARVEST

CRIMSON HARVEST is an EVE Online PvP competition dashboard and live killmail importer. The source repository is [MisterGumpo/crimson-harvest](https://github.com/MisterGumpo/crimson-harvest).

## Requirements

- PHP 8.2 or later, with PDO MySQL, OpenSSL, `allow_url_fopen` enabled and working CA certificates for outbound HTTPS.
- MariaDB 10.4 or later. The migration syntax targets MariaDB.
- Composer 2 and Git.
- For production: nginx, PHP-FPM, systemd and a Linux `flock` utility for the optional scheduled enrichment job.
- The PHP runtime and CLI must use compatible PHP versions and extensions. The importer needs outbound HTTPS access to EVE ESI and zKillboard's R2Z2 feed.

Only `public/` should be exposed by the web server. The application, `.env`, database files, `vendor/`, `bin/` and `storage/` belong outside the document root.

## Install

### Get the code

```sh
git clone https://github.com/MisterGumpo/crimson-harvest.git
cd crimson-harvest
composer install
```

Keep `composer.lock` in version control and use `composer install` for repeatable deployments. Do not run `composer update` as part of a routine release.

### Configure the database and environment

Create a database and application account in MariaDB. Replace the example password before running this SQL:

```sql
CREATE DATABASE crimson_harvest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crimson_harvest'@'localhost' IDENTIFIED BY 'replace-with-a-strong-password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
	ON crimson_harvest.* TO 'crimson_harvest'@'localhost';
```

The account needs schema privileges while running migrations. After setup, reduce it to the privileges required by your MariaDB version and operational policy; the application needs to read and write its tables. Do not use the MariaDB root account for the website or worker.

Copy `.env.example` to `.env`, then set these values:

| Setting | Description |
| --- | --- |
| `DBHOST` | MariaDB host, such as `127.0.0.1` |
| `DBNAME` | Application database name |
| `DBUSER` | Application database user |
| `DBPASS` | Application database password |
| `URL` | Public canonical site URL, including `https://` in production |

The application loads `.env` from its installation root. Keep it out of version control and readable only by the deployment account and the PHP/worker users that need it. Do not put secrets in `config/` or web-accessible files.

Create the tables and apply the bundled migration:

```sh
composer migrate
```

This command applies both `database/schema.sql` and `database/migrations/001_data_source.sql`; do not separately import the schema. Run it once for a new database and again for releases that include migrations. Back up production data before upgrades.

The event window is configured in UTC in `config/event.php`; locations are in `config/app.php`. The checked-in event is Crimson Harvest, from `2026-10-01 00:00:00 UTC` up to, but not including, `2026-11-01 00:00:00 UTC`. Review these settings before adapting the application for another event.

Demo rows are optional and excluded from public standings. Use `composer seed-demo` only in a development database, never as a production installation step.

## Production: nginx and PHP-FPM

Install PHP-FPM and its PDO MySQL/OpenSSL support, Composer, MariaDB, nginx and systemd using your distribution's packages. Confirm `allow_url_fopen` is enabled for the PHP CLI and FPM configurations and that the host has current CA certificates.

Deploy the full project to `/var/www/crimson-harvest`; configure PHP-FPM and the worker to read the application and `.env`. Keep code deployment-owned and read-only to runtime accounts. Grant write access only where required, such as `storage/logs/` for the optional cron log. Do not use world-writable permissions.

Adapt this nginx example for your domain, certificate paths and installed PHP-FPM socket (the example uses PHP 8.3):

```nginx
server {
	listen 80;
	server_name crimson.example.com;
	return 301 https://$host$request_uri;
}

server {
	listen 443 ssl;
	server_name crimson.example.com;
	root /var/www/crimson-harvest/public;
	index index.php;

	ssl_certificate /etc/letsencrypt/live/crimson.example.com/fullchain.pem;
	ssl_certificate_key /etc/letsencrypt/live/crimson.example.com/privkey.pem;

	location / {
		try_files $uri $uri/ =404;
	}

	location ~ \.php$ {
		try_files $uri =404;
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php8.3-fpm.sock;
	}

	location ~ /\.(?!well-known).* {
		deny all;
	}
}
```

Validate the configuration with `nginx -t` before reloading nginx. Ensure the FPM user can read the application and `.env`; the PHP-FPM socket and PHP CLI version should match the installed runtime. The dashboard API is at `/api/dashboard.php` and returns HTTP 503 if application data is unavailable.

## Run the live importer

The R2Z2 consumer is a separate, long-running CLI process. It is not started by a web request. Run exactly one instance: it advances a cursor stored in MariaDB's `feed_state` table, so restarts resume from the saved sequence. A fresh database starts at the current feed sequence; it does not automatically backfill older killmails. Do not schedule a second consumer with cron.

Create `/etc/systemd/system/crimson-harvest-consumer.service`, adjusting the PHP path, installation path, service user and database service name for your host:

```ini
[Unit]
Description=CRIMSON HARVEST R2Z2 consumer
Wants=network-online.target
After=network-online.target mariadb.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/crimson-harvest
ExecStart=/usr/bin/php /var/www/crimson-harvest/bin/consume-kills.php
Restart=always
RestartSec=10
NoNewPrivileges=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
```

Enable it and inspect its status and logs:

```sh
sudo systemctl daemon-reload
sudo systemctl enable --now crimson-harvest-consumer
sudo systemctl status crimson-harvest-consumer
sudo journalctl -u crimson-harvest-consumer -f
```

The worker waits six seconds when the next R2Z2 sequence is not available, retries errors after ten seconds and logs to the systemd journal. The dashboard's worker status is based on the database heartbeat. `CONSUMER_MAX_MESSAGES` is an optional process environment variable for diagnostics; it counts successfully processed feed messages, not elapsed time, and cannot bound a run that is waiting or retrying. For a staging smoke test that must stop on a deadline, use a shell timeout as well as `CONSUMER_MAX_MESSAGES`; do not run a second consumer against production just to test it.

## Optional character-name enrichment cron

Live ingestion resolves character names as killmails arrive. This one-shot command can also repair fallback names already stored in the database. It is optional; do not schedule migrations, demo seeding, or the continuous consumer in cron.

For a daily run as the same unprivileged account that can read `.env` and write `storage/logs/`, add a crontab entry like this (adjust paths and the PHP binary):

```cron
17 3 * * * cd /var/www/crimson-harvest && /usr/bin/flock -n storage/logs/enrich-characters.lock /usr/bin/php bin/enrich-characters.php >> storage/logs/enrich-characters.log 2>&1
```

`flock -n` prevents overlapping runs. Ensure the service account can create the lock and log files, and configure log rotation or another retention policy for `enrich-characters.log`. Systemd captures the consumer's output in the journal; configure journal retention according to your operations policy.

## Commands and recovery

Run commands from the project root:

| Command | Purpose |
| --- | --- |
| `composer migrate` | Apply the base schema and bundled migrations. |
| `composer test` | Run the PHPUnit test suite (development dependencies required). |
| `composer seed-demo` | Load repeatable demo fixtures; development only. |
| `php bin/consume-kills.php` | Run the persistent R2Z2 consumer under a supervisor. |
| `php bin/enrich-characters.php` | Resolve stored fallback character names through ESI. |
| `php bin/import-kill.php <killmail-id>` | Fetch one killmail and import it only if its time and location qualify for the configured event. |
| `php bin/import-history.php path/to/r2z2-records.json` | Import an array of R2Z2 records from a trusted recovery file. |
| `php -d memory_limit=1G bin/backfill-kills.php [YYYY-MM-DD [YYYY-MM-DD-exclusive-end]]` | Download published daily raw archives and import contest-eligible historical killmails. |

Historical imports are recovery tools, not scheduled jobs. The history importer trusts the supplied records and does not apply the single-kill command's event-window and location checks; validate the input before importing. Reimporting qualifying killmails can restore attacker data missing from older records, but avoid importing arbitrary or unverified datasets.

### Backfill published daily archives

For the configured event, import published historical killmails from October 1 at midnight UTC through the current UTC time:

```sh
php -d memory_limit=1G bin/backfill-kills.php 2026-10-01
```

An optional second date is an exclusive midnight-UTC boundary. For example, `2026-10-01 2026-10-05` imports October 1 through October 4. Dates must be inside the event window and not in the future. The command downloads one raw archive per UTC day from `https://r2z2.zkillboard.com/history/raw/YYYYMMDD.json`, applies the event, alliance and location rules, and resolves character names. Each daily JSON archive is decoded in memory; the example allows up to 1 GiB, so check available memory before running it alongside PHP-FPM.

Run only one backfill at a time. It does not read or update `feed_state`, so the existing live consumer can remain running. Existing killmail IDs are reused and attacker records are upserted, making interrupted ranges safe to rerun. Reported counts include previously imported records processed again, not just new rows. Existing live rows retain their source and metadata. Raw archives do not supply zKillboard valuation metadata, so newly imported archive rows can have no ISK value.

Archives can lag, especially for the current UTC day, and late submissions can update previous days. An unavailable archive causes a nonzero exit instead of claiming completion; earlier imports remain saved. Rerun the same range later to fill published gaps. The archives cover killmails known to zKillboard, not all kills in EVE. Do not rewind the live cursor or start another consumer to perform this backfill.

Hagilur is solar system `30002050`; Metropolis is region `10000042` and Heimatar is region `10000030`. Hagilur kills count in all contests for otherwise eligible characters. Attackers must belong to alliance ID `99013187` (The Obsidian Front - Reborn) or `99013786` (Cryonic Origin Alliance) to receive credit; NPCs and attackers without a valid character ID are excluded. Each character gets at most one credit per killmail, with no final-blow bonus. The current Hagilur top three are removed from the Metropolis / Heimatar leaderboard. The current regional prize winners are then removed from the raffle leaderboard. Lower-priority leaderboards are renumbered after exclusions, and eligibility is recalculated from the current standings on each dashboard update.

The importer uses R2Z2's ordered sequence feed and ESI for killmail/system and name resolution. It is not RedisQ-based. Monitor the systemd service, journal, database connection and dashboard heartbeat. If the dashboard returns 503, check PHP-FPM/nginx logs and database availability. If the worker appears offline, check its unit and database heartbeat; verify outbound HTTPS, DNS and CA certificates if feed or ESI requests fail. Empty standings can be expected when no eligible killmails have arrived in the configured event and regions.

## Windows/XAMPP development

Use PHP 8.2 or later with PDO MySQL, OpenSSL and `allow_url_fopen`, plus Composer. From PowerShell in the project directory:

```powershell
Copy-Item .env.example .env
composer install
```

Set the five database/site values in `.env`, create the MariaDB database and user as above, then run:

```powershell
composer migrate
composer test
```

Configure an Apache virtual host with its document root set to this project's `public/` directory, not the repository root. For local ingestion, open a separate terminal in the project root and run `php bin/consume-kills.php`; stop it with Ctrl+C. Windows does not use the Linux systemd unit or cron example. If scheduled enrichment is needed, configure Windows Task Scheduler to run `php bin/enrich-characters.php` from the project directory as an account with database and `.env` access, and avoid overlapping tasks.

## Updating and backups

Back up the MariaDB database, including `feed_state`, and keep a separate protected backup of `.env`. For an update, stop the consumer, deploy the intended revision, install locked production dependencies, run migrations, then restart and inspect the worker and dashboard:

```sh
sudo systemctl stop crimson-harvest-consumer
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
composer migrate
sudo systemctl start crimson-harvest-consumer
sudo systemctl status crimson-harvest-consumer
```

The commands assume deployment from the Git checkout and that migrations are safe for the release; back up first and follow your rollback procedure. Do not overwrite `.env`, discard local changes, or run demo seeding during production updates. Check `https://crimson.example.com/api/dashboard.php` and the service journal after deployment.

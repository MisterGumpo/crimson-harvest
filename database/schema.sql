CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    timezone VARCHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_events_window (starts_at, ends_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS killmails (
    killmail_id BIGINT UNSIGNED PRIMARY KEY,
    killmail_time DATETIME NOT NULL,
    solar_system_id INT UNSIGNED NOT NULL,
    region_id INT UNSIGNED NOT NULL,
    victim_character_id BIGINT UNSIGNED NULL,
    victim_character_name VARCHAR(255) NULL,
    victim_ship_type_id INT UNSIGNED NULL,
    victim_ship_name VARCHAR(255) NULL,
    zkill_url VARCHAR(255) NULL,
    total_value DECIMAL(20,2) NULL,
    data_source VARCHAR(16) NOT NULL DEFAULT 'live',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_killmails_time (killmail_time),
    INDEX idx_killmails_location (solar_system_id, region_id),
    INDEX idx_killmails_source (data_source)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS killmail_attackers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    killmail_id BIGINT UNSIGNED NOT NULL,
    character_id BIGINT UNSIGNED NOT NULL,
    character_name VARCHAR(255) NOT NULL,
    corporation_id BIGINT UNSIGNED NULL,
    corporation_name VARCHAR(255) NULL,
    alliance_id BIGINT UNSIGNED NULL,
    alliance_name VARCHAR(255) NULL,
    ship_type_id INT UNSIGNED NULL,
    ship_name VARCHAR(255) NULL,
    weapon_type_id INT UNSIGNED NULL,
    final_blow BOOLEAN NOT NULL DEFAULT FALSE,
    damage_done INT UNSIGNED NULL,
    UNIQUE KEY uq_killmail_character (killmail_id, character_id),
    INDEX idx_attackers_character (character_id),
    CONSTRAINT fk_attackers_killmail FOREIGN KEY (killmail_id) REFERENCES killmails(killmail_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feed_state (
    provider VARCHAR(64) PRIMARY KEY,
    last_sequence VARCHAR(255) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS excluded_characters (
    character_id BIGINT UNSIGNED PRIMARY KEY,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS excluded_killmails (
    killmail_id BIGINT UNSIGNED PRIMARY KEY,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solar_systems (
    solar_system_id INT UNSIGNED PRIMARY KEY,
    region_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_solar_systems_region (region_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS eve_characters (
    character_id BIGINT UNSIGNED PRIMARY KEY,
    character_name VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE killmails ADD COLUMN IF NOT EXISTS data_source VARCHAR(16) NOT NULL DEFAULT 'live';
ALTER TABLE killmails ADD INDEX IF NOT EXISTS idx_killmails_source (data_source);
UPDATE killmails SET data_source = 'demo' WHERE killmail_id BETWEEN 900000001 AND 900000070;

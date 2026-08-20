-- URW-208: opt-in stale-while-revalidate per cached endpoint. When an entry whose
-- config has async_refresh=1 is read after its TTL, the stale copy is served
-- immediately and a background refresh is kicked off (see api/api-cache.php and
-- api/internal/refresh-cache.php). Endpoints left at the default (0) keep the old
-- behaviour: a blocking synchronous refetch on expiry.
ALTER TABLE `outgoing_request_cache_config`
    ADD COLUMN `async_refresh` tinyint(1) NOT NULL DEFAULT 0;

-- Enable it for the Printful endpoints — merch pages read these on every view, so
-- serving stale-then-refresh removes the periodic slow (blocking) page load.
UPDATE `outgoing_request_cache_config` SET `async_refresh` = 1 WHERE `config_name` = 'printful';

# Reproducible development

Requirements for a developer machine: Docker with Compose, Python 3 and Node 22. The provisioned containers supply WordPress, PHP, MariaDB and WP-CLI. Host PHP/MySQL are not needed for the site. Never expose this disposable environment publicly. The documented passwords are disposable local fixtures, not production credentials.

```
./scripts/provision.sh             # start, install free plugins, seed
# Access http://localhost:8888; codeslot-admin / disposable-test-password
docker compose stop               # stop, retain database
docker compose down -v            # destroy ONLY this project's disposable database
./scripts/provision.sh             # recreate after reset

docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/integration.php
CS_WP_CLI='docker compose run --rm cli' python3 tests/isolation.py
npm ci
npx playwright install --with-deps chromium
npm run lint
npm run test:e2e
python3 scripts/build.py
```

The Compose CLI image is PHP 8.3. To run PHP tooling without host PHP:

```
docker compose run --rm --user root --entrypoint sh cli -c 'curl -fsSL https://getcomposer.org/composer-stable.phar -o /tmp/composer.phar && cd wp-content/plugins/codeslot && php /tmp/composer.phar install --no-interaction'
docker compose run --rm --entrypoint php cli wp-content/plugins/codeslot/vendor/bin/phpunit -c wp-content/plugins/codeslot/phpunit.xml
docker compose run --rm --entrypoint sh cli -c 'cd wp-content/plugins/codeslot && php vendor/bin/phpstan analyse --memory-limit=2G'
docker compose run --rm --entrypoint sh cli -c 'cd wp-content/plugins/codeslot && php vendor/bin/phpcs'
```

On hosts where container root creates root-owned artifacts, match the container UID to the host or run Composer in a host PHP 7.4+ installation. In managed cloud environments, mount the supplied public CA bundle into containers and configure PHP `curl.cainfo`, `openssl.cafile` and WordPress's test-only CA bundle. Never disable TLS verification. Dependency artifacts are ignored by Git and excluded from release ZIPs.

CI runs Composer tooling on GitHub-hosted PHP runners, WordPress/MariaDB integration, browser tests, Plugin Check, dependency audit and allowlisted packaging. Stable checks block packaging. Nightly WordPress/PHP checks are separate and non-blocking. Scheduled checks run weekly. Workflow files do not imply the jobs have passed; see actual Actions runs.

## Hooks and lifecycle

- `codeslot_manual` action with exact snippet name, after `wp` or `admin_init`.
- Output uses `wp_head`, `wp_body_open`, `wp_footer` at priority 20.
- PHP frontend/everywhere runs at `wp`; admin/everywhere at `admin_init`. It cannot register earlier hooks reliably. AJAX, REST and cron PHP execution is intentionally disabled.
- Content locations use `the_content` only for the main singular item inside the loop.
- WooCommerce hooks: `woocommerce_before_single_product`, `woocommerce_after_single_product`, `woocommerce_before_shop_loop`, `woocommerce_after_shop_loop`.
- `CodeSlot\Repository` is an internal API, not a stable external extension contract. Its writes enforce the same privileged capability gate.

An active snippet query is scoped to location and published status, cached within the request. Individual slots use only `get_queried_object_id()`, never whichever post happens to be in a secondary loop. Settings and metadata use WordPress caches. No frontend assets are enqueued.

## Server and responsive UI validation

`./scripts/test-servers.sh` uses the disposable provisioned site to test Apache/mod_php, Nginx/PHP-FPM and OpenLiteSpeed/LSAPI sequentially. It changes site URLs during testing, restores the default URL, removes its temporary identity probe and writes evidence to `work/server-results/`. Do not run it against production or concurrently with browser tests. Additional images are pinned in `compose.servers.yaml`. OpenLiteSpeed does not establish LiteSpeed Enterprise compatibility.

`npm run test:e2e` includes all six CodeSlot admin screens at 1440, 768, 390 and 320 pixels, legacy unnamed-snippet editing, filter state and narrow-screen conditions. Screenshots are written under test-results. The test-only product post type is used only if WooCommerce has not registered its real product type; results must identify which configuration ran.

#!/usr/bin/env bash
set -euo pipefail
docker compose up -d wordpress
for attempt in {1..60}; do
  if docker compose run --rm cli core version; then break; fi
  if [ "$attempt" -eq 60 ]; then exit 1; fi
  sleep 2
done
if ! docker compose run --rm cli core is-installed; then
  docker compose run --rm cli core install --url=http://localhost:8888 --title='CodeSlot disposable test site' --admin_user=codeslot-admin --admin_password=disposable-test-password --admin_email=test@example.org --skip-email
fi
docker compose run --rm cli plugin activate codeslot
docker compose run --rm cli plugin install woocommerce advanced-custom-fields elementor gutenberg classic-editor beaver-builder-lite-version contact-form-7 wordpress-seo polylang --activate
docker compose run --rm cli eval 'wp_mkdir_p( WPMU_PLUGIN_DIR ); copy( WP_PLUGIN_DIR . "/codeslot/tests/mu-fixtures.php", WPMU_PLUGIN_DIR . "/codeslot-test-fixtures.php" );'
docker compose run --rm cli option update classic-editor-replace block
docker compose run --rm cli option update classic-editor-allow-users allow
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/seed.php
docker compose run --rm cli rewrite structure '/%postname%/' --hard

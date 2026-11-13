#!/usr/bin/env bash
set -euo pipefail
docker compose up -d wordpress
until docker compose run --rm cli core version; do sleep 2; done
docker compose run --rm cli core install --url=http://localhost:8888 --title='CodeSlot disposable test site' --admin_user=codeslot-admin --admin_password=disposable-test-password --admin_email=test@example.org --skip-email
docker compose run --rm cli plugin activate codeslot
docker compose run --rm cli plugin install woocommerce advanced-custom-fields elementor classic-editor beaver-builder-lite-version contact-form-7 wordpress-seo polylang --activate
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/seed.php

#!/usr/bin/env bash
# Run only against this project's disposable Compose database.
set -euo pipefail
cd "$(dirname "$0")/.."
compose=(docker compose -f compose.yaml -f compose.servers.yaml)
"${compose[@]}" up -d wordpress nginx openlitespeed
wp() { "${compose[@]}" run --rm cli "$@"; }
cleanup() {
  wp eval 'if (file_exists(ABSPATH . "codeslot-server-probe.php")) { unlink(ABSPATH . "codeslot-server-probe.php"); }' || true
  wp option update home http://localhost:8888 || true
  wp option update siteurl http://localhost:8888 || true
}
trap cleanup EXIT
mkdir -p work/server-results
"${compose[@]}" exec -T wordpress apache2 -v > work/server-results/apache-version.txt
"${compose[@]}" exec -T nginx nginx -v > work/server-results/nginx-version.txt 2>&1
"${compose[@]}" exec -T openlitespeed /usr/local/lsws/bin/openlitespeed -v > work/server-results/openlitespeed-version.txt
"${compose[@]}" run --rm --entrypoint php cli -v > work/server-results/php-cli-version.txt
for spec in '8888 apache2handler Apache' '8889 fpm-fcgi nginx' '8890 litespeed LiteSpeed'; do
  read -r port sapi server <<< "$spec"
  url="http://localhost:$port"
  wp option update home "$url"
  wp option update siteurl "$url"
  wp eval-file wp-content/plugins/codeslot/tests/seed.php
  wp rewrite flush --hard
  wp eval-file wp-content/plugins/codeslot/tests/servers/fixtures.php
  for attempt in {1..30}; do
    if curl -fsS --max-time 5 "$url/codeslot-server-probe.php" > /dev/null; then break; fi
    if [ "$attempt" -eq 30 ]; then exit 1; fi
    sleep 1
  done
  python3 tests/isolation.py | tee "work/server-results/$port-isolation.txt"
  CS_SERVER_URL="$url" CS_EXPECT_SAPI="$sapi" CS_EXPECT_SERVER="$server" python3 tests/servers/server_http.py | tee "work/server-results/$port-http.txt"
done
"${compose[@]}" logs --no-color > work/server-results/compose.log

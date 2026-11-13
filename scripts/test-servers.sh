#!/usr/bin/env bash
# Run only against this project's disposable Compose database.
set -euo pipefail
cd "$(dirname "$0")/.."
compose=(docker compose -f compose.yaml -f compose.servers.yaml)
"${compose[@]}" up -d wordpress nginx openlitespeed
wp() { "${compose[@]}" run --rm cli "$@"; }
cleanup() {
  local cs_exit_code=$?
  if [ "$cs_exit_code" -ne 0 ]; then
    local cs_failure cs_php_errors
    cs_php_errors="$("${compose[@]}" exec -T openlitespeed sh -c 'tail -n 12 /tmp/codeslot-php-error.log 2>/dev/null' 2>/dev/null || true)"
    cs_failure="$cs_php_errors $(tail -n 8 work/server-results/*-isolation.txt work/server-results/*-http.txt work/server-results/*-probe.txt 2>/dev/null || true)"
    cs_failure="${cs_failure//%/%25}"
    cs_failure="${cs_failure//$'\r'/%0D}"
    cs_failure="${cs_failure//$'\n'/%0A}"
    echo "::error title=Server regression (${port:-startup})::$cs_failure"
  fi
  "${compose[@]}" logs --no-color > work/server-results/compose.log || true
  wp eval 'if (file_exists(ABSPATH . "codeslot-server-probe.php")) { unlink(ABSPATH . "codeslot-server-probe.php"); }' || true
  wp option update home http://localhost:8888 || true
  wp option update siteurl http://localhost:8888 || true
  return "$cs_exit_code"
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
    cs_http_code="$(curl -sS --max-time 5 -o "work/server-results/$port-probe.txt" -w '%{http_code}' "$url/codeslot-server-probe.php" || true)"
    if [ "$cs_http_code" = 200 ]; then break; fi
    if [ "$attempt" -eq 30 ]; then exit 1; fi
    sleep 1
  done
  python3 tests/isolation.py 2>&1 | tee "work/server-results/$port-isolation.txt"
  CS_SERVER_URL="$url" CS_EXPECT_SAPI="$sapi" CS_EXPECT_SERVER="$server" python3 tests/servers/server_http.py 2>&1 | tee "work/server-results/$port-http.txt"
done
"${compose[@]}" logs --no-color > work/server-results/compose.log

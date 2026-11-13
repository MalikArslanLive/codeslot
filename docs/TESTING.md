# Tests and quality gates

Use a disposable database only. Test fixtures deliberately create content and execute test PHP. See DEVELOPMENT.md for provisioning; No premium compatibility or full-matrix claim follows from one local run.

```
composer install --no-interaction
composer test                           # isolated unit tests
composer lint                           # WordPress coding standards
composer analyse                        # PHPStan, 2 GiB ceiling
composer audit
npm ci
npm run lint
npm audit --audit-level=high

docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/integration.php
CS_WP_CLI='docker compose run --rm cli' python3 tests/isolation.py
npm run test:e2e
# During this managed session the equivalent CLI command is:
# docker exec codeslot-wp wp ...

docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/benchmark.php
python3 scripts/build.py
```

Integration assertions run on actual WordPress APIs through WP-CLI rather than pretending a stubbed WordPress runtime establishes plugin compatibility. PHPUnit unit tests isolate condition logic and validation. Browser tests exercise actual login, editors and admin POST routes. HTTP source tests verify both positive and negative unique markers, not just rendered DOM text.

Mandatory release gates: lint, unit, standards, static analysis, dependency audit, core integration, privilege and nonce tests, multisite checks, classic/block theme isolation, Gutenberg/Classic/Woo product browser tests, clean ZIP installation and Plugin Check. Any blocked mandatory gate prevents a production-readiness claim.

Missing premium licenses produce **not tested** entries, never a synthetic pass. Failures during test setup (TLS trust, permissions, container disk/memory limits) are recorded and retried only after correcting the setup. Keep local test results with the change being reviewed.

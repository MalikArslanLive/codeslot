# Contributing

Use a branch from `develop`; open a PR with concrete behavior, risk and actual validation. Preserve unrelated content. Do not commit vendor, node_modules, database dumps, credentials, private snippets or premium plugins. Use GPL-compatible original code. Do not add telemetry or external execution services.

Run the checks in docs/DEVELOPMENT.md and docs/TESTING.md. Add permanent regression coverage for isolation, permission or recovery defects. Never present PHP evaluation as a sandbox. Mark skipped and blocked checks accurately. Keep CHANGELOG, feature and compatibility reports current. Main should require review and passing CI; see docs/REPOSITORY-SETTINGS.md for the current API access blocker.

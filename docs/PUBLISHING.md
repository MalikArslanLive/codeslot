# Packaging and publication

Version remains 1.0.0. The ZIP is prepared for WordPress.org review; approval/listing availability is a separate WordPress.org decision and is not claimed.

1. Run the quality gates described in [TESTING.md](TESTING.md).
2. Run `python3 scripts/build.py` from the repository root. The build allowlists only 12 production files and writes SHA256SUMS.
3. Inspect paths/CRC, install the ZIP on a disposable clean WordPress site and run Plugin Check and integration tests.
4. Copy the verified ZIP and checksum to packages/ after production changes. Keep main/develop consistent.
5. Inspect existing tags/releases. Never move or overwrite a published tag. Create the authorized annotated v1.0.0 tag only after gates pass.
6. Attach the ZIP and SHA256SUMS to a draft GitHub Release. Public GitHub Release publication, production deployment and WordPress.org submission require explicit owner approval.

The source repository intentionally retains tests, CI, lockfiles and provisioning scripts; the installable ZIP excludes them. They are required for reproducible development and should not be deleted as packaging clutter.

See [repository settings](REPOSITORY-SETTINGS.md) for administrative settings that cannot be applied while GitHub API access is blocked.

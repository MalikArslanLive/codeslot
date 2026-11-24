# GitHub distribution and packaging

CodeSlot is distributed as an installable ZIP through [GitHub Releases](https://github.com/MalikArslanLive/codeslot/releases/latest). It is not distributed through the WordPress.org Plugin Directory.

## Build

```sh
python3 scripts/build.py
python3 scripts/build-media.py
```

The production archive contains `codeslot-header-body-footer-code-snippet-manager/codeslot.php` and allowlisted runtime files, readme and licenses. Tests, development dependencies, demo data and branding stay outside the plugin ZIP. The installation directory and gettext domain use `codeslot-header-body-footer-code-snippet-manager`; the visible brand and admin route remain CodeSlot and `admin.php?page=codeslot`.

The media build validates eight screenshots, two icons and two banners, creates a separate `codeslot-media-1.0.0.zip`, and refreshes `packages/SHA256SUMS`. The plugin readme inside the ZIP, standalone release readme and repository readme.txt must match byte for byte. See [gallery and branding](../media/README.md).

## Verify and release

Check archive paths, CRC, source bytes, readme consistency and checksums. Test the changed behavior on a disposable WordPress installation; consult [the recorded verification scope](TESTING.md). Do not present earlier test results as newly run tests.

Publish the installable ZIP, separate media ZIP, readme.txt and SHA256SUMS. Keep source, release tag, download links and release notes synchronized. Future releases must increment the plugin header, stable tag, build filenames and documentation together.

The package includes readable PHP/JavaScript/CSS and JShrink's BSD license. WordPress editor libraries load from core. Optional tracking providers are disabled by default and disclosed in readme.txt. There is no developer telemetry or automatic GitHub updater.

## Installation and updates

Download the installable plugin ZIP, then use WordPress **Plugins → Add New → Upload Plugin**. For an update, upload the newer release ZIP and use WordPress's replacement confirmation. Back up the site and CodeSlot configuration first. GitHub's source-code archives are development snapshots; use the named installable ZIP asset.

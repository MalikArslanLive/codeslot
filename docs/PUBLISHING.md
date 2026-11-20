# Packaging

Version: 1.0.0. The production archive contains codeslot-header-body-footer-code-snippet-manager/codeslot.php and allowlisted runtime assets, templates, readme and GPL license. Development dependencies, tests, credentials and Git metadata stay outside the ZIP.

Build from the repository root:

```sh
python3 scripts/build.py
```

The build writes dist/codeslot-1.0.0.zip and dist/SHA256SUMS. Verify archive paths, CRC and checksum, install on a disposable WordPress site and run Plugin Check before distributing a changed package.

The current distribution is [packages/codeslot-1.0.0.zip](../packages/codeslot-1.0.0.zip), with [packages/SHA256SUMS](../packages/SHA256SUMS). After validating a changed build, copy both files from dist into packages together. The outer ZIP contains the codeslot-header-body-footer-code-snippet-manager directory; upload the ZIP itself through WordPress.

The published [1.0.0 release](https://github.com/MalikArslanLive/codeslot/releases/tag/v1.0.0), its annotated tag and final uploaded ZIP/checksum are synchronized with the current distribution. Update source, package and documentation together, verify the final archive, then synchronize the tag and release assets. WordPress.org submission is handled by the owner; preparation is not approval or submission.

Review the [WordPress.org detailed plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) and [final verification scope](TESTING.md). The package includes readable production PHP/JavaScript/CSS and the bundled JShrink BSD license. WordPress editor libraries are enqueued from core. Tracking providers are optional, disabled by default and disclosed with terms/privacy links in readme.txt. CodeSlot adds no public credit links, proprietary updater, remote recipe feed or developer telemetry. WordPress.org reviewers make the final acceptance decision. Once published, future releases must increment the plugin/header/stable-tag/package version together.

The 20 November 2026 finalization clean-installed the rebuilt ZIP, passed Plugin Check 2.2.0 and reverified source-byte/permission/CRC checks and head loading. The [test report](TESTING.md) distinguishes the complete suite from the final focused regression and records environment limits.

## WordPress.org media and final readme

The submission ZIP includes the final readme: explicit site-wide and individual-page/post/product scope, installation, quick start, FAQs, advanced guidance, external-service disclosures and eight screenshot captions. The standalone packages/readme.txt is byte-identical to that included readme.

Run `python3 scripts/build.py` followed by `python3 scripts/build-wordpress-media.py` to refresh both packages and the combined packages/SHA256SUMS. The production ZIP remains allowlisted runtime files; it contains no demo data or listing artwork. The separate media kit contains gallery, icons and banners folders, exact WordPress.org filenames/sizes, the same readme and an SVN upload guide. After approval, copy the image files into SVN's top-level assets directory, not the plugin's own runtime assets directory. See [the complete media guide](../wordpress-org/README.md).

The assigned WordPress.org slug is `codeslot-header-body-footer-code-snippet-manager`. It is used by the package root, plugin Text Domain header and all gettext calls. Internal codeslot storage keys, menu IDs, shortcodes and GitHub repository name remain unchanged. Run Plugin Check against an installation in the assigned folder, not only the short development folder.

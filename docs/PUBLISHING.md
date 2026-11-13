# Packaging

Version: 1.0.0. The production archive contains codeslot/codeslot.php and allowlisted runtime assets, templates, readme and GPL license. Development dependencies, tests, credentials and Git metadata stay outside the ZIP.

Build from the repository root:

```sh
python3 scripts/build.py
```

The build writes dist/codeslot-1.0.0.zip and dist/SHA256SUMS. Verify archive paths, CRC and checksum, install on a disposable WordPress site and run Plugin Check before distributing a changed package.

The published [1.0.0 release](https://github.com/MalikArslanLive/codeslot/releases/tag/v1.0.0), its tag and uploaded assets are retained unchanged. Do not overwrite a published version. WordPress.org submission is handled by the owner.

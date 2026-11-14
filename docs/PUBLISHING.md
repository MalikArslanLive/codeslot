# Packaging

Version: 1.0.0. The production archive contains codeslot/codeslot.php and allowlisted runtime assets, templates, readme and GPL license. Development dependencies, tests, credentials and Git metadata stay outside the ZIP.

Build from the repository root:

```sh
python3 scripts/build.py
```

The build writes dist/codeslot-1.0.0.zip and dist/SHA256SUMS. Verify archive paths, CRC and checksum, install on a disposable WordPress site and run Plugin Check before distributing a changed package.

The current distribution is [packages/codeslot-1.0.0.zip](../packages/codeslot-1.0.0.zip), with [packages/SHA256SUMS](../packages/SHA256SUMS). After validating a changed build, copy both files from dist into packages together. The outer ZIP contains the codeslot directory; upload the ZIP itself through WordPress.

The published [1.0.0 release](https://github.com/MalikArslanLive/codeslot/releases/tag/v1.0.0), its tag and uploaded assets retain their original source snapshot. Current main packages include subsequent revisions while the version stays 1.0.0 at the owner's request. Never replace the original published assets or move that tag. WordPress.org submission is handled by the owner; package preparation is not approval or submission.

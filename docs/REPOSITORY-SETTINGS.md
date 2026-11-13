# GitHub repository settings

These settings are separate from the plugin header and README. Committing a file cannot populate GitHub's About sidebar.

- Description: Free WordPress code manager with global and individual Header, Body & Footer slots, local PHP/JS/CSS/HTML snippets, conditions, saved versions and Safe Mode.
- About website: https://malikarslan.com/
- Topics: wordpress, wordpress-plugin, code-snippets, php, javascript, css, woocommerce, gutenberg, elementor.
- Plugin URI (WordPress plugin website link): https://github.com/MalikArslanLive/codeslot
- WordPress.org listing link: https://wordpress.org/plugins/codeslot/

## Verified API status — 2026-11-13

The published environment now permits api.github.com and uploads.github.com. Authenticated repository reads work. The existing v1.0.0 annotated tag was retained. A [draft release](https://github.com/MalikArslanLive/codeslot/releases) was created and its verified ZIP and SHA256SUMS uploaded. The ZIP asset's GitHub-reported SHA-256 matches the committed checksum. No public release was published.

These API writes still return **HTTP 403: Resource not accessible by integration** with the current credential:

| Operation | Required repository token permission |
|---|---|
| Description/homepage and delete-merged-branches setting | Administration: write |
| Topics | Administration: write |
| Main/develop branch protection | Administration: write |
| Private vulnerability reporting | Private vulnerability reporting: write |
| Dependabot vulnerability alerts / automated security fixes | Vulnerability alerts: write |
| Release environment | Environments: write |

Reading default Actions workflow permissions also returned 403 (Actions: read required). The repository response reports admin membership, but membership does not establish the token's endpoint permissions. Contents write works for draft release creation and asset uploads. Exact token permissions must be corrected or the owner can apply these settings through GitHub's interface; never paste credentials into issues, source or chat.

Proposed protection requires successful package and dependencies checks, PR changes and resolved conversations, blocks deletion/force pushes, and leaves owner administration override available. No protection or environment was created because the requests were rejected. The release environment proposal permits v* tags only and contains no production credentials.

The ten dependabot branches correspond to real open dependency-update PRs. They are not stale merged branches and must not be discarded merely to reduce the branch/PR count. Updates need review and validation. Shared Git history should only be rewritten after its scope is explicitly confirmed; a Git bundle backup was saved locally before cleanup.

## Authentication route diagnosis

Fresh controlled GET /user probes returned HTTP200 with the same account for (1) the configured HTTP-secret bearer, (2) no Authorization header, and (3) a deliberately invalid diagnostic bearer. Description/homepage PATCH and topics PUT still returned HTTP403 Resource not accessible by integration. This establishes that the managed route supplies authentication independently of the request header; it does not establish that the owner's fine-grained PAT lacks the permissions shown in settings. Do not request broader token access based on these responses. The managed GitHub authentication route must use the intended credential, or the owner can apply administrative settings directly through GitHub. About description/homepage/topics remain unset; release/content operations work through the current route.

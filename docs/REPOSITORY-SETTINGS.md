# GitHub repository settings

These settings are separate from the plugin header and README. Committing a file cannot populate GitHub's About sidebar.

- Description: Free WordPress code manager with global and individual Header, Body & Footer slots, local PHP/JS/CSS/HTML snippets, conditions, saved versions and Safe Mode.
- About website: https://malikarslan.com/
- Topics: wordpress, wordpress-plugin, code-snippets, php, javascript, css, woocommerce, gutenberg, elementor.
- Plugin URI (WordPress plugin website link): https://github.com/MalikArslanLive/codeslot
- WordPress.org listing link: https://wordpress.org/plugins/codeslot/

Current blocker: the managed cloud proxy denies api.github.com. The repository-edit request fails before authorization can be assessed. Required network additions are saved in environment settings. After access is applied, recheck the operation; do not assume the previous integration's permissions are unchanged.

Repository description/homepage/topics, branch protection, private vulnerability reporting and GitHub Environments may additionally require appropriate repository Administration/security/environment write permissions. Draft release creation requires Contents write. Native Git pushes work, which does not establish API Administration access. Never paste credentials into chat.

The ten dependabot branches correspond to real open dependency-update PRs. They are not stale merged branches and must not be discarded merely to reduce the branch/PR count. Updates need review and validation. Shared Git history should only be rewritten after its scope is explicitly confirmed; a Git bundle backup was saved locally before cleanup.

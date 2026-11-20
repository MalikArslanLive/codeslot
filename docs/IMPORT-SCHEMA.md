# CodeSlot JSON schema v1

The root must be an object with `schema: "codeslot/v1"` and `snippets: []`. Limits: 5 MiB input, 200 snippets, 1 MiB code per snippet, JSON depth 32, 20 condition groups per kind and 20 rules per group. Code is Unicode text. No files are executed on import. All entries validate before writes; database insertion errors cause best-effort rollback of newly created snippets.

```
{
  "schema": "codeslot/v1",
  "snippets": [{
    "name": "Example",
    "description": "Local header markup",
    "type": "html",
    "location": "header",
    "code": "<!-- Example -->",
    "priority": 10,
    "active": false,
    "locked": false,
    "categories": ["Example"],
    "tags": ["demo"],
    "conditions": {
      "include": [[{"key": "frontend", "op": "is", "value": ""}]],
      "exclude": [[{"key": "logged_in", "op": "not", "value": ""}]]
    }
  }]
}
```

Fields `type`, `location` and string `code` are required; name/description default empty, priority defaults 10 and is clamped to -1000…1000. Categories/tags accept string arrays or comma-delimited strings. Newly created copies force `active` and `locked` false. Existing exact duplicates are skipped without changing their state. Unknown extra snippet fields, including exported `id`, do not become code/configuration. Unknown condition keys or operators are rejected. Export includes local IDs for reference only. Global and individual slots are not part of snippet export.

Types: php, javascript, css, html, text, content, shortcode. See README for the permitted location matrix. Condition keys: entire_site, frontend, admin, front_page, home, singular, post_type, term, archive, search, 404, logged_in, role, url, mobile, shop, cart, checkout, account. Operators: is, not. Groups are OR; rules inside are AND; exclusion wins. Boolean context rules ignore value; scalar rules compare exact strings; role/term rules test membership.

Paragraph locations `before_paragraph` and `after_paragraph` accept `paragraph` (1–1000, default 1). Other saved snippets remain compatible when this field is absent. PHP location `once` always normalizes active to false and requires empty include/exclude conditions. Import never runs a task or imports its execution record; task execution requires a separate privileged confirmed POST.

Optional delivery fields: device is any (default), desktop or mobile; starts/ends are zero/empty for no bound, positive UTC Unix timestamps or editor-style YYYY-MM-DDTHH:MM strings interpreted in the site timezone. End must follow Start when both are set. load_file is a boolean supported only for JavaScript/CSS; compact is a boolean supported only for JavaScript. Both default false. selector is a CSS selector of 1–200 characters and is required for selector_before/after/prepend/append/replace locations. Non-PHP snippets additionally support admin_header/admin_footer. These fields round-trip in snippet/full backups; generated static files themselves are excluded.

Exports contain active/inactive saved snippets; Trash and private unsaved editor drafts are excluded. JavaScript/CSS imports require inner code without wrapper tags; complete embeds use HTML. Google Ads IDs must be in google_ads, not google.

The admin interface accepts multipart .json file uploads only, for snippets and full backups. HTTP upload origin, extension, status and a real-byte 5 MiB limit are checked before schema validation. The temporary upload is not copied into public uploads. Nonces and combined capabilities are checked before reading its content. Full backups retain the optional pixel require_consent boolean; new providers stay disabled; replaced existing providers use the selected disable/preserve-state policy.

Duplicate identity compares validated source/configuration (including name, description, delivery and conditions), ignoring local ID, active state and lock. Tags/categories are treated as sets. Code identity uses exact source bytes, including line endings, so a meaningful string-content change cannot be silently skipped; saved code is not rewritten. Default conflict policy skip never overwrites a same-name different version; explicit copy creates a new inactive/unlocked version. Backend Repository::import still returns only new IDs and exposes counts through its optional report argument. Explicit replace updates one unlocked unambiguous name match, retains its ID and records a revision. The same policy applies to full-backup snippet sections.

Individual slots must be arrays and paths must be strings. Empty string paths are skipped; null/malformed paths or slots fail validation before writes. Current exports give slug-less drafts an empty identity; malformed nonempty code/shape still fails validation before writes, with the entry number identified. Restoring settings preserves current Safe Mode rather than restoring a potentially disruptive flag; uninstall deletion is reset off only when settings are selected.

Restore option parameters: snippet_conflicts is skip/copy/replace; restore_state is disabled/preserve; full restore_existing maps global/individual/settings/files/pixels to skip/replace. Unknown values fail before writes. Replace uses an unambiguous unlocked current name match, never an uploaded ID. New entries are always inactive. HTTP snippet Replace requires its explicit confirmation; full restore requires the reviewed-section confirmation. The result report adds replaced/protected/ambiguous/enabled/disabled counts and selected policy fields while the return value remains newly created IDs only.

The editor resolves names through an authenticated, nonce-protected, read-only search endpoint. Stored conditions still contain stable numeric IDs, taxonomy:ID values, post-type slugs and role slugs. The removed product key is not accepted; use post_type with product. Boolean rule values normalize to empty strings. Specific items require positive numeric IDs; term requires taxonomy:positiveID; role/type require slugs; URL requires an exact slash-prefixed path without domain/query/fragment. See [Conditions](CONDITIONS.md).

Optional `requires` is a sorted, unique array of at most 50 WordPress plugin-file paths, each at most 200 bytes. Traversal/absolute/URL paths and malformed values are rejected. Missing plugins are valid saved requirements: imports retain them without enabling new code. The field participates in duplicate identity, revisions and full restore. Missing structured references anywhere in Include/Exclude pause the whole snippet rather than broadening its scope.

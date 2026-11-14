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

Fields `type`, `location` and string `code` are required; name/description default empty, priority defaults 10 and is clamped to -1000…1000. Categories/tags accept string arrays or comma-delimited strings. Imported `active` and `locked` values are always forced false. Unknown extra snippet fields, including exported `id`, do not become code/configuration. Unknown condition keys or operators are rejected. Export includes local IDs for reference only. Global and individual slots are not part of snippet export.

Types: php, javascript, css, html, text, content, shortcode. See README for the permitted location matrix. Condition keys: entire_site, frontend, admin, front_page, home, singular, post_type, term, archive, search, 404, logged_in, role, url, mobile, shop, product, cart, checkout, account. Operators: is, not. Groups are OR; rules inside are AND; exclusion wins. Boolean context rules ignore value; scalar rules compare exact strings; role/term rules test membership.

Paragraph locations `before_paragraph` and `after_paragraph` accept `paragraph` (1–1000, default 1). Other saved snippets remain compatible when this field is absent. PHP location `once` always normalizes active to false and requires empty include/exclude conditions. Import never runs a task or imports its execution record; task execution requires a separate privileged confirmed POST.

Optional delivery fields: device is any (default), desktop or mobile; starts/ends are zero/empty for no bound, positive UTC Unix timestamps or editor-style YYYY-MM-DDTHH:MM strings interpreted in the site timezone. End must follow Start when both are set. load_file is a boolean supported only for JavaScript/CSS; compact is a boolean supported only for JavaScript. Both default false. selector is a CSS selector of 1–200 characters and is required for selector_before/after/prepend/append/replace locations. Non-PHP snippets additionally support admin_header/admin_footer. These fields round-trip in snippet/full backups; generated static files themselves are excluded.

Exports contain active/inactive saved snippets; Trash and private unsaved editor drafts are excluded. Older CodeSlot wrapped JavaScript/CSS imports remain inactive and unlocked with source bytes preserved.

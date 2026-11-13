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

# Local library scope and omitted entries

CodeSlot bundles 142 original recipes and configuration guides. Executable cards open inactive; three clearly labeled wp-config.php guides display instructions only. Search, category filtering, code preview and pagination work without an external account or WordPress companion plugin. Recipe validity and inactive defaults are checked in actual WordPress integration. Individual behavior is environment-dependent; syntax/schema checks do not certify every combination of recipes, themes and WordPress patches.

The supplied library document was a feature reference, not source code. No external library code or branded descriptions are bundled. The catalog count is CodeSlot's own count, not a claim to reproduce another service's catalog exactly.

## Skipped because a companion plugin is required

- Mailer-provider constants and settings: SES, Brevo, Google, Mailgun/signing keys, Outlook, Postmark, SendGrid, SendLayer, SMTP.com, SparkPost, Zoho and Other SMTP integration-specific configuration.
- Companion email-log capabilities/constants, email-summary recipients, email-test menu changes, and enabling that companion's configuration-constant mode.
- Outlook OAuth scopes, redirect URI, single-tenant/GCC/DoD settings and provider-specific header-error workarounds.
- Postmark message streams and SES identity restrictions implemented through another mailer's provider API.
- BuddyPress mail rerouting and companion-specific form-field URL blocking.
- Enabling shortcode execution inside another snippet plugin's HTML snippets. CodeSlot already provides its own reusable-content/shortcode behavior.
- Action Scheduler administration/retention recipes and new WooCommerce-only library recipes. Existing WooCommerce native fields, insertion hooks and optional conversion-event integration remain supported; these are separate from the dependency-free library.

## Other boundaries

- SVG-upload enablement is omitted because this build does not bundle a vetted SVG sanitizer; simply allowing SVG MIME types would expose active content. No unsafe MIME-only recipe is offered.
- A library recipe that disables the file-editor capability would also revoke CodeSlot's own required capability. The manual wp-config.php guide explains that consequence and recovery instead of offering a misleading activation button.
- AI/LLM-specific output integrations remain outside the requested scope. No remote library, account connection or AI service was added.
- External mail transport, role delegation for another plugin and licensed companion capabilities are not reimplemented or advertised as native features.

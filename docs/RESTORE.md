# Import and restore guide

## Restore choices and result notices

Select at least one section for a full restore. If none is selected, CodeSlot says: “Select at least one section to restore, then click Restore selected sections. Nothing was restored.” It makes no changes; choose Snippets, Global, Individual, Settings, Files or Pixels as needed and try again.

Both snippet-only JSON import and full-backup restore show overwrite warnings and a live choice summary before submission. Download a current backup first if the existing content may be needed. Exact duplicates are always skipped unchanged.

| Scope | Existing-content choice | State after a write |
|---|---|---|
| Snippets | Skip, Keep both as a new copy, or Replace one matching unlocked name | New copies disabled/unlocked. Replacements default disabled; optional Preserve current state retains the target switch. |
| Global / individual fields | Keep existing nonempty fields, or Replace from backup | Missing/empty fields can receive new disabled data. Replacements use Disable/Preserve. Missing posts/terms are skipped. |
| Virtual files | Keep existing records, or Replace from backup | New records disabled. Replacements use Disable/Preserve; worker registration is off by default or retains its current enabled setting in preserve mode. Physical files remain untouched. |
| Pixels | Keep existing provider records/shared preferences, or Replace | New provider records disabled. Existing provider switches use Disable/Preserve; selected shared preferences are explicitly reported. |
| Settings | Keep current preferences, or Restore supported settings | Site-wide Safe Mode is always preserved. Restoring settings turns uninstall deletion off. |

Preserve is an explicit opt-in for existing entries being replaced and can execute changed enabled code immediately. No option automatically enables new entries. Skip and unselected sections preserve their exact current content and state. Copy never overwrites its original. A Replace match must be unique and unlocked; ambiguous or locked snippets remain untouched. Local IDs in an uploaded file cannot select a replacement target.

Full completion notices list all six sections: actual writes, enabled/disabled switches, skipped existing entries, identical unchanged entries, missing mappings and shared preference updates. Every unselected section explicitly says it was not replaced or updated. Short-import notices list new/replaced/skipped/conflicting snippets and explicitly state that global/individual/settings/files/pixels were not changed. Counts refer to saved switches; Safe Mode still overrides execution.

The snippet importer prevalidates all incoming source and choices, records history for replacements and attempts to restore replaced snapshots if a later insert fails. Full restore prevalidates section plans before mutations, but is not a whole-database transaction. Keep the downloaded backup for recovery.

Plugin requirements and structured condition identities are retained by snippet/full imports and revisions. The enabled/disabled counts describe saved switches: missing dependencies can still pause a saved enabled replacement. Import does not drop missing references or substitute another insertion location. Review [Dependencies](DEPENDENCIES.md) before enabling restored code, particularly on another site where content IDs or plugin paths may differ.

Each global/individual Header, Body or Footer field accepts up to 1 MiB of UTF-8 source. Invalid or oversized writes are rejected without replacing the existing field. The complete uploaded JSON file must still fit the 5 MiB import limit.

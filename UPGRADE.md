# Upgrade

## Upgrade to the 0.6 line

Upgrade PHP to `8.3` or later. Migrate Yii `2.0` applications to Yii `22.0` before using this adapter.

- Read the [migration guide](docs/migration.md) and [changelog](CHANGELOG.md) for the installed starting version.
- Update the adapter with its compatible `php-forge/inertia` core, retaining the application lockfile. The `0.6` line
  requires core `^0.5`; do not copy examples from a newer core API without checking that version.
- Replace removed adapter page/prop types and the old Vite integration as described in migration. Review custom
  root views and callbacks, validation error shapes, and access to top-level flash data.
- In custom root views for Inertia v3, set the JSON script's `data-page` to the root ID. Older clients reading JSON
  from the root element's `data-page` need the attribute-encoded format; see [root views](docs/configuration.md#root-view).
- Keep CSRF validation enabled and preserve the application's cookie validation key and request behavior.
- Verify initial HTML, Inertia JSON, partial/deferred reloads, write redirects, validation flashes, an outdated asset
  version, and any observer/dispatcher integration. Run the application's frontend checks separately.

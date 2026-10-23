# Upgrade Guide

## 0.6.0

- Upgrade PHP to 8.3 or later and migrate the application to Yii 22.0; Yii 2.0.x is not supported.
- Update this package together with `php-forge/inertia` `^0.5`.
- Replace the removed adapter page and prop types and `yii\inertia\Vite`, and review custom root views and callbacks,
  validation error shapes and access to top-level flash data.
- In custom root views for Inertia v3, set the JSON script's `data-page` to the root ID. Clients that read JSON from the
  root element's `data-page` need the attribute-encoded format; see [root views](docs/configuration.md#root-view).

See the [migration guide](docs/migration.md) for the changes since your installed version.

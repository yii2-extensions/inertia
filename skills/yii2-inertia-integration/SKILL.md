---
name: yii2-inertia-integration
description: Configure, troubleshoot, or migrate yii2-extensions/inertia in a Yii 22 application, including root views, props, validation flashes, signed CSRF cookies, redirects, and observers. Use for this PHP adapter; exclude Laravel adapters and frontend-only Inertia tasks.
license: BSD-3-Clause
metadata:
  source: https://github.com/yii2-extensions/inertia
---

# Integrate Inertia with Yii 22

Inspect the application's entry script, Composer requirements/lock, merged web configuration, request subclass,
frontend entry, and installed adapter/core versions. This adapter's 0.6 line requires PHP >=8.3, Yii `^22.0@dev`,
and `php-forge/inertia ^0.5`. Do not claim Yii 2.0.x compatibility or widen constraints as an installation workaround.
Installing this skill does not install the adapter, select a JavaScript framework, or build assets.

## Consult the relevant guide

Prefer supplied local Markdown or `docs/` in the matching package checkout. During development, inspect the current
local files, including uncommitted changes; do not attribute those changes to `HEAD`. A Composer archive may omit
guides; a copied skill need not have a sibling documentation tree. If local guides are unavailable, obtain the
installed adapter's version and `source.reference` from the configured vendor directory's `composer/installed.json`.
This installed metadata takes precedence over `composer.lock`, which describes the intended resolution and may
differ. Read `docs/` at that tag or source SHA in the
[package repository](https://github.com/yii2-extensions/inertia), resolving the revision from Composer each time
instead of defaulting to `main` or hard-coding a SHA in this skill. Read only the needed page:

- `installation.md`: dependencies, development-line resolution, bootstrap, and client prerequisites.
- `configuration.md`: manager defaults, root view encoding, flashes, CSRF, and observer/dispatcher contracts.
- `examples.md`: application root views and individual prop/redirect recipes.
- `migration.md` and root `UPGRADE.md`: upgrades from the original standalone implementation.
- `testing.md`: commands and the distinction between server, browser, and skill evidence.

Use the package's PHPDoc and installed core types for exact signatures. If a guide is not published at the installed
revision, state that gap and inspect the relevant source/changelog rather than substituting an unrelated adapter API.
The protocol and prop wrappers belong to `php-forge/inertia`; Yii response/session adaptation belongs here.

## Configure within the existing application

Preserve bootstrap entries, request settings, session handling, access rules, and application component IDs. Register
`yii\inertia\Bootstrap`; it registers `@inertia` and supplies a default `inertia` component when absent. Configure
`yii\inertia\Manager` under that ID. Return `Inertia::render()` or the adapter controller's protected `inertia()`
helper from actions, using exact client component names.

Start with the default root view or create the configured custom file before referencing it. Initial HTML must
include the matching root ID, HTML-safe `pageJson`, Yii's page lifecycle, and the application's own asset loading.
The bundled view targets Inertia v3 with a JSON script whose `data-page` value is the root ID. Older clients that
read JSON from the root element's `data-page` attribute need an attribute-encoded custom view; check the installed
client instead of assuming those formats are interchangeable.
Encode ordinary text/attributes; emit the supplied `pageJson` directly only inside its JSON script. Attribute-encode
it when storing JSON in the root element's `data-page` attribute. The adapter provides no Vite service, asset bundle,
SSR runtime, or JavaScript package installer.
Preserve the application's chosen build; verify that its resolver can load the named component.

## Resolve props and form feedback

Prop callbacks take zero arguments; capture application state explicitly. A version closure may take the current
Yii request, unlike prop closures. Use facade factories for always, optional, deferred, merge, once, and scroll props;
check the installed core for fluent modifiers. `scrollMetadata()` creates metadata, not a prop wrapper.

Choose explicit serializable fields for models and identities. Page props can replace a whole shared top-level value;
do not promise recursive merging. Do not use `exposeSharedProps = false` to hide values: it disables key metadata.
In a long-lived worker, verify visitor isolation in the surrounding application lifecycle before sharing state.

Send validation errors through the configured session flash key (`errors` by default): string field keys mapped to
strings or lists of strings. They appear in `props.errors`; other flashes appear in top-level `flash`. Keep
`X-Inertia-Error-Bag` behavior when using form bags. Invalid error shapes throw explicitly. Version conflicts,
prop failures, and observer failures preserve flashes; a later view failure can happen after they are consumed.

## Keep CSRF and response handling intact

Use `yii\inertia\web\Request` for signed cookie-to-header CSRF, reconciling any existing subclass. Preserve the
stable private cookie validation key, cookie validation, and CSRF protection. The names are `XSRF-TOKEN` and
`X-XSRF-TOKEN`. Verify initial cookie creation (the bundled root does not call `getCsrfToken()`) and the actual same-origin header/cookie exchange; do not disable
validation to fix a `400` or replace it with a raw-token recipe from another framework.

Return Yii redirects after writes and retain bootstrap's before-send normalization. Test PUT/PATCH/DELETE `302`
redirects becoming `303`. For external visits use `Inertia::location()` and check the `409`/`X-Inertia-Location`
response for Inertia requests. Fragment redirects use `X-Inertia-Redirect`, requiring a client that supports it
(Inertia v3). Preserve `Vary: X-Inertia`, version negotiation, and location headers through caches.

Configure a PSR-14 `eventDispatcher` before the manager creates its default protocol. A supplied `protocol` keeps
its own dispatcher. `pageObserver` receives resolved initial/JSON pages, skips version conflicts, and propagates
callback failures. Select/redact diagnostics before recording payloads. Debugger collector/panel setup belongs to
that debugger's installed version; the dispatcher option alone does not register them.

## Migrate and verify

For 0.1 consumers, replace removed `yii\inertia\Page` and prop classes with core types or facade factories, remove
`yii\inertia\Vite` usage, and review top-level flash access and callback contracts. Check the migration guide against
the actual starting version rather than treating every upgrade as a new API break.

Run the consuming application's existing checks. Verify HTML, JSON, a matching
partial reload, deferred resolution, a valid/invalid CSRF submission, write redirects, validation flashes, and an
outdated asset version. Native client mounting and skill activation require their own evidence. Report exact
versions, checks performed, and gaps; a syntax check or manual skill read does not prove those outcomes.

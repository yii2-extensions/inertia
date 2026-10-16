# Migration guide

## Choose the starting version

Use the application's lockfile and [changelog](../CHANGELOG.md). The current 0.6 line adapts `php-forge/inertia ^0.5`
and requires PHP 8.3 or later with Yii `^22.0@dev`. An application using Yii 2.0.x needs a separate framework migration;
changing this adapter's Composer constraint does not establish compatibility.

Follow only the changes relevant to the installed starting version.

## From the standalone 0.1 implementation

Version 0.2 extracted protocol behavior into `php-forge/inertia`. Keep Yii-facing calls through `yii\inertia\Inertia`,
`Manager`, `Bootstrap`, and the adapter's request/controller classes. Replace direct references to removed types:

| Removed adapter type       | Current core type or entry point                                              |
| -------------------------- | ----------------------------------------------------------------------------- |
| `yii\inertia\Page`         | `PHPForge\Inertia\Page`                                                       |
| `yii\inertia\AlwaysProp`   | `PHPForge\Inertia\Prop\AlwaysProp`; prefer `Inertia::always()`                |
| `yii\inertia\DeferredProp` | `PHPForge\Inertia\Prop\DeferredProp`; prefer `Inertia::defer()`               |
| `yii\inertia\OptionalProp` | `PHPForge\Inertia\Prop\OptionalProp`; prefer `Inertia::optional()`            |
| `yii\inertia\MergeProp`    | `PHPForge\Inertia\Prop\MergeProp`; prefer `Inertia::merge()` or `deepMerge()` |
| `yii\inertia\OnceProp`     | `PHPForge\Inertia\Prop\OnceProp`; prefer `Inertia::once()`                    |
| `yii\inertia\Vite`         | Removed; use the application's selected asset integration and build.          |

If application code directly imports core types, declare `php-forge/inertia` in that application's dependencies.
Factory return types and fluent modifiers belong to the core; inspect the installed core before adapting custom
wrappers. Do not reconstruct removed classes in the adapter namespace.

### Root views and props

Update a custom view's page type to `PHPForge\Inertia\Page` and use the supplied `pageJson`. The
[complete view example](examples.md#application-root-view) preserves Yii's page lifecycle and HTML-safe embedding.
Match the client root ID, component resolver, and asset loading. The default root has an inline JSON script
identified by `data-page="app"` (or the configured ID) for Inertia v3.
Clients that read JSON from the root element's `data-page` attribute need a custom view with attribute encoding;
see [root views](configuration.md#root-view).

Prop callbacks take zero arguments. Capture required values explicitly. The version callback may instead take the
current `yii\web\Request`. Avoid sending whole models or user identities when a small explicit array is sufficient.

### Errors and flash lifetime

Store validation messages as an array of string field keys with string or list-of-string values. They become
`props.errors`; other flashes are emitted in the top-level `flash` field. Update clients that previously read
`props.flash`. Invalid error structures now fail explicitly instead of being cast into an array.

Version conflicts, prop-resolution failures, and observer failures leave flashes available. After a page result and
a successful observer, the adapter consumes flashes before rendering the root view or serializing the response.
See [configuration](configuration.md#validation-errors-and-flash-messages) for the exact boundary.

### Redirects and CSRF

Keep bootstrap registration so response normalization runs before send. Preserve `Vary` and Inertia headers through
the application's middleware/proxy. Retain `yii\inertia\web\Request` for signed cookie-to-header CSRF handling;
verify a real valid form submission and rejection of an invalid token.

## From 0.2 through 0.5

- 0.3 requires core 0.2 and uses `PageInput::create()` with immutable modifiers. Review direct core construction in
  custom integrations; normal `Inertia::render()` calls remain adapter-facing.
- 0.4 requires core `^0.3`, uses its `Header` enum, and supports portable `ResolvedPageObserver` instances. Replace
  custom observer assumptions with the callback contract in [configuration](configuration.md#observe-pages-and-protocol-results).
- 0.5 requires core `^0.5`. Match direct core usage to that installed API.
- 0.6 adds the `scrollMetadata()` facade factory and the default protocol's `eventDispatcher` option. Configure the
  dispatcher before the protocol is created. A supplied `protocol` ignores the manager's dispatcher option.

## Verify the migrated application

Run the [upgrade checklist](../UPGRADE.md) and the [example tests](testing.md#application-checks). The
server-side tests use fixture pages and do not replace browser checks of the application's own client version,
CSRF cookie exchange, deferred UI, or navigation. Review the exact dependency changes before deploying.

---

[Examples](examples.md) · [AI assistance →](agents.md) · [Back to documentation](../README.md#documentation)

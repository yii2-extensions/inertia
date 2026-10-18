# Configuration reference

## Configure the manager

Merge this fragment into the existing web configuration:

```php
use yii\inertia\Bootstrap;
use yii\inertia\Manager;

return [
    'bootstrap' => [Bootstrap::class],
    'components' => [
        'inertia' => [
            'class' => Manager::class,
            'version' => static function (): string {
                $path = dirname(__DIR__) . '/public/build/manifest.json';

                return is_file($path) ? hash_file('sha256', $path) : '';
            },
            'shared' => [
                'app' => ['name' => static fn(): string => Yii::$app->name],
            ],
        ],
    ],
];
```

Adapt the manifest path to the application's actual build. This hashes an existing file; it does not load assets.
Use a deployment version string instead if that better matches the deployment process.

`yii\inertia\Inertia` delegates application operations to the component named `inertia`, which must be a `Manager`.
`yii\inertia\web\Controller` adds protected `inertia()` and `location()` helpers to Yii's controller.

## Manager options

| Property            | Default                  | Contract                                                                                                                                                     |
| ------------------- | ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `id`                | `app`                    | Root DOM ID; match it in the client setup.                                                                                                                   |
| `rootView`          | `@inertia/views/app.php` | View rendered for initial HTML visits.                                                                                                                       |
| `version`           | `null`                   | String, integer, or closure returning either or `null`. A closure takes zero arguments or the current `yii\web\Request`. `null` resolves to an empty string. |
| `shared`            | `[]`                     | Shared props. Use nested arrays in configuration; `share()` also accepts dotted paths.                                                                       |
| `errorFlashKey`     | `errors`                 | Session flash read as validation errors in `props.errors`.                                                                                                   |
| `encryptHistory`    | `false`                  | Forwarded to the core page options.                                                                                                                          |
| `clearHistory`      | `false`                  | Forwarded to the core page options.                                                                                                                          |
| `preserveFragment`  | `false`                  | Forwarded to the core page options.                                                                                                                          |
| `exposeSharedProps` | `true`                   | Include shared-prop key metadata; this does not remove shared values from props.                                                                             |
| `pageObserver`      | `null`                   | `PHPForge\Inertia\ResolvedPageObserver` notified after a page resolves.                                                                                      |
| `eventDispatcher`   | `null`                   | PSR-14 dispatcher used when the default protocol is first created.                                                                                           |
| `protocol`          | `null`                   | Optional `PHPForge\Inertia\Protocol`; an injected protocol keeps its own dispatcher.                                                                         |

The manager creates and retains its default protocol lazily. Configure the dispatcher before the first operation.
If injecting a protocol, configure its dispatcher on that protocol; `Manager::$eventDispatcher` is ignored.

Prop callbacks take **no arguments**. Capture application state explicitly or read it through `Yii::$app`. Only the
version callback receives the request when it declares a parameter. See [prop examples](examples.md#prop-recipes).
Page props replace shared values at matching keys; do not rely on recursive merging of an `auth` array. Register
only the user fields intended for the client, rather than serializing an entire identity or ActiveRecord object.

`share()`, `getShared()`, and `flushShared()` operate on the manager's current shared state. For long-lived workers,
ensure the application and component lifecycle isolate visitors; this adapter is not a worker lifecycle manager.

## Root view

The view receives `id`, a `PHPForge\Inertia\Page` instance as `page`, HTML-safe `pageJson`, and the values from the
third argument of `Inertia::render()`. Reserved values `id`, `page`, and `pageJson` take precedence over `viewData`.

The default view includes Yii's `beginPage()`, `head()`, `beginBody()`, `endBody()`, and `endPage()` lifecycle. It emits
an inline JSON script with `data-page` set to the root ID, as expected by Inertia v3. It loads no application
JavaScript. Create an application-owned root view from the [complete recipe](examples.md#application-root-view),
then set `rootView` to the actual file or alias:

```php
'rootView' => '@app/views/layouts/inertia.php',
```

Register the application's asset bundle in that view and preserve the Yii lifecycle calls. Encode ordinary text and
HTML attributes with `yii\helpers\Html::encode()`. Output the supplied `pageJson` directly only inside the JSON
script; it has already been HTML-safe encoded. Do not encode it again there or insert a fresh raw `json_encode()`.
For a client that reads JSON from the root element's `data-page` attribute (including
[Inertia v2.0](https://github.com/inertiajs/inertia/blob/v2.0.0/packages/react/src/createInertiaApp.ts#L73)), use a custom
view with `<div id="<?= Html::encode($id) ?>" data-page="<?= Html::encode($pageJson) ?>"></div>`. Check the installed
client's initial-page format; these two uses of `data-page` have different values.

## Validation errors and flash messages

`errorFlashKey` must contain an array with string field names and values that are strings or lists of strings, such
as `['email' => ['Enter an email address.']]`. Invalid structures throw `yii\base\InvalidConfigException`;
invalid field keys, including an empty string, throw `PHPForge\Inertia\Exception\InvalidPageInputException`.
The core emits these in `props.errors`, including on partial reloads, and applies `X-Inertia-Error-Bag` when present.
Other session flashes go to the **top-level `flash` page field**. Read them using the client API for that field.

Flashes are consumed after the core returns a page result and the observer succeeds, before root-view rendering or
response serialization. A version conflict, prop-resolution error, or observer error preserves them. A later view
or serialization failure can occur after consumption. An application without a session component supplies empty
errors and flash data.

## CSRF protection

Use the request configuration in [installation](installation.md#register-the-integration). The adapter uses
`XSRF-TOKEN` as the cookie/form name and `X-XSRF-TOKEN` as the header. Its cookie has `httpOnly: false` so the client
can read it. With cookie validation enabled, the header must contain Yii's signed cookie value; the adapter verifies
its signature and structure and masks the token for Yii's normal CSRF comparison.

The bundled default view does not explicitly initialize a CSRF token. In an application-owned root view, call
`Yii::$app->getRequest()->getCsrfToken()` before returning the initial HTML, as shown in the
[root-view recipe](examples.md#application-root-view). Yii's
page lifecycle alone does not create this cookie. Preserve HTTPS, same-site and
other application cookie settings as needed. When diagnosing a `400`, inspect the actual cookie/header exchange,
origin, cookie path/domain, and proxy configuration before changing request settings. Do not disable cookie or CSRF
validation to make a failing request pass.

## Redirects and response headers

Return Yii redirects after writes. For an Inertia request, the bootstrap passes `Location` or `X-Redirect` through
the core before send; a `302` after PUT/PATCH/DELETE becomes `303`. Use `Inertia::location()` for an external visit:
it returns a `409` with `X-Inertia-Location` for an Inertia request, and a normal redirect for a regular request.
Normalization also covers controller-created responses. Relative redirect destinations resolve against the current
request URL before core validation.
Malformed request data rejected by the core, such as an invalid method override, produces HTTP `400` through
`yii\web\BadRequestHttpException`.

Page responses merge `Vary: X-Inertia` across all existing header lines and preserve `Vary: *`. Protocol results
replace stale Inertia page/control headers on the current Yii response. An outdated version on an Inertia GET
returns a `409` location response before resolving props; validation errors and other flashes survive the following
full-page request. Preserve protocol headers through proxies and caches.

The core emits `409` with `X-Inertia-Redirect` for non-prefetch redirects containing a URL fragment. This requires a
client that supports that header, as documented by the [Inertia v3 protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol).
Do not assume full v2 compatibility from basic JSON responses alone.

## Observe pages and protocol results

`pageObserver` takes a portable observer whose callback receives the resolved payload and shared-prop keys:

```php
use PHPForge\Inertia\ResolvedPageObserver;

$manager->pageObserver = new ResolvedPageObserver(
    static function (array $payload, array $sharedKeys): void {
        // Inspect only the diagnostics needed by the application.
    },
);
```

It observes initial and Inertia pages, and skips version conflicts. Callback failures propagate; they are not hidden.
The payload may contain private user data, so select or redact diagnostics before recording them.

For protocol result events use `eventDispatcher` and `PHPForge\Inertia\Event\ProtocolResultCreated`. Debugger
provider registration is owned by the installed debugger version: setting this property alone does not register a
panel, collector, or access policy. See [examples](examples.md#observe-a-resolved-page) for the checked observer recipe.

---

[Installation](installation.md) · [Examples →](examples.md) · [Back to documentation](../README.md#documentation)

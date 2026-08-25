<?php

declare(strict_types=1);

namespace yii\inertia;

use Closure;
use PHPForge\Inertia\{Exception\InvalidRequestContextException, PageInput, Protocol, RequestContext};
use PHPForge\Inertia\Result\{InertiaPageResult, InitialPageResult, PageResult, ProtocolResult};
use ReflectionFunction;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper, Json, Url};
use yii\inertia\Exception\Message;
use yii\web\{Request, Response};

use function array_is_list;
use function in_array;
use function is_array;
use function is_int;
use function is_string;

/**
 * Server-side Yii adapter registered as the `inertia` application component.
 *
 * Adapts Yii requests, application state, views, sessions, and responses to the framework-agnostic protocol core.
 */
final class Manager extends Component
{
    /**
     * Whether rendered pages clear the client-side history.
     */
    public bool $clearHistory = false;
    /**
     * Whether rendered pages encrypt their client-side history state.
     */
    public bool $encryptHistory = false;
    /**
     * Session flash key exposed as `props.errors` in every page payload.
     */
    public string $errorFlashKey = 'errors';
    /**
     * Whether rendered pages expose shared-prop metadata.
     */
    public bool $exposeSharedProps = true;
    /**
     * Root element DOM `id` used by the default root view.
     */
    public string $id = 'app';
    /**
     * Whether rendered pages preserve the URL fragment.
     */
    public bool $preserveFragment = false;
    /**
     * Protocol service. Configure this property to inject a custom core clock for deterministic tests.
     */
    public Protocol|null $protocol = null;
    /**
     * Root view file rendered for the initial HTML response.
     */
    public string $rootView = '@inertia/views/app.php';
    /**
     * Shared props applied to every rendered page in the current request.
     *
     * @var array<string, mixed>
     */
    public array $shared = [];
    /**
     * Asset version used for client-side mismatch detection through the page `version` field.
     *
     * @var (Closure(): (int|string|null))|(Closure(Request): (int|string|null))|int|string|null
     */
    public Closure|int|string|null $version = null;

    /**
     * Removes all shared props.
     */
    public function flushShared(): void
    {
        $this->shared = [];
    }

    /**
     * Returns all shared props or a nested value using dot notation.
     *
     * @param string|null $key Dot-notated key to retrieve, or `null` to return every shared prop.
     * @param mixed $default Value returned when `$key` does not exist.
     *
     * @return mixed Shared value at `$key`, or `$default` when the key does not exist.
     */
    public function getShared(string|null $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->shared : ArrayHelper::getValue($this->shared, $key, $default);
    }

    /**
     * Resolves the configured asset version.
     *
     * @return int|string Resolved version, or an empty `string` when no version is configured.
     */
    public function getVersion(): int|string
    {
        $version = $this->version;

        if ($version instanceof Closure) {
            $version = $this->invokeVersion($version);
        }

        return is_int($version) || is_string($version) ? $version : '';
    }

    /**
     * Returns whether a request carries the Inertia request marker.
     *
     * @param Request|null $request Request to inspect, or `null` to use the current application request.
     *
     * @return bool `true` when the request is Inertia-driven; otherwise, `false`.
     */
    public function isInertiaRequest(Request|null $request = null): bool
    {
        $value = ($request ?? Yii::$app->getRequest())->getHeaders()->get(RequestContext::HEADER_INERTIA);

        return is_string($value) && in_array($value, ['true', '1'], true);
    }

    /**
     * Returns an Inertia location response or a standard redirect response.
     *
     * @param array<array-key, mixed>|string $url Destination URL or route array accepted by `Url::to()`.
     *
     * @return Response Yii response containing the protocol location or redirect result.
     */
    public function location(array|string $url): Response
    {
        $request = Yii::$app->getRequest();

        $result = $this->getProtocol()->location(
            $this->createRequestContext($request),
            Url::to($url, true),
        );

        return $this->createResponse($result);
    }

    /**
     * Normalizes a Yii response immediately before it is sent to an Inertia client.
     *
     * @param Response $response Outgoing Yii response to normalize.
     */
    public function normalizeResponse(Response $response): void
    {
        if (!$this->isInertiaRequest()) {
            return;
        }

        $this->mergeVaryHeader($response, 'X-Inertia');

        $headers = $response->getHeaders();
        $location = $headers->get('X-Redirect') ?? $headers->get('Location');

        if (!is_string($location) || !in_array($response->statusCode, [301, 302, 303, 307, 308], true)) {
            return;
        }

        $result = $this->getProtocol()->redirect(
            $this->createRequestContext(Yii::$app->getRequest()),
            $location,
            $response->statusCode,
        );

        $headers->remove('X-Redirect');
        $headers->remove('Location');

        $this->mapResultToResponse($result, $response);
    }

    /**
     * Renders an Inertia page through the framework-agnostic protocol core.
     *
     * Returns a JSON page payload for Inertia requests or the initial HTML document for standard browser requests.
     *
     * @param string $component Client-side component name.
     * @param array<string, mixed> $props Page-specific props.
     * @param array<string, mixed> $viewData Additional data available to the initial root view.
     *
     * @return Response Yii response containing the initial HTML document or Inertia JSON page.
     */
    public function render(string $component, array $props = [], array $viewData = []): Response
    {
        $request = Yii::$app->getRequest();

        [$errors, $flash] = $this->readFlashes();

        $input = PageInput::create($component, $props, $this->getVersion())
            ->withSharedProps($this->shared)
            ->withErrors($errors)
            ->withFlash($flash)
            ->withEncryptHistory($this->encryptHistory)
            ->withClearHistory($this->clearHistory)
            ->withPreserveFragment($this->preserveFragment)
            ->withSharedPropsExposure($this->exposeSharedProps);

        $result = $this->getProtocol()->page(
            $this->createRequestContext($request),
            $input,
        );

        if ($result instanceof PageResult) {
            $this->consumeFlashes();
        }

        return $this->createResponse($result, $viewData);
    }

    /**
     * Registers props shared with subsequent page responses in the current request.
     *
     * @param array<string, mixed>|string $key Shared values or a dot-notated prop key.
     * @param mixed $value Value registered when `$key` is a string.
     */
    public function share(array|string $key, mixed $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $name => $item) {
                $this->shared = $this->withSharedValue($this->shared, explode('.', $name), $item);
            }

            return;
        }

        $this->shared = $this->withSharedValue($this->shared, explode('.', $key), $value);
    }

    /**
     * Applies protocol headers to a Yii response.
     *
     * The `Vary` header is merged with existing values instead of replacing them.
     *
     * @param Response $response Yii response that receives the headers.
     * @param array<string, string> $headers Protocol headers indexed by name.
     */
    private function applyHeaders(Response $response, array $headers): void
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Vary') === 0) {
                $this->mergeVaryHeader($response, $value);
            } else {
                $response->getHeaders()->set($name, $value);
            }
        }
    }

    /**
     * Consumes every session flash after a page result is rendered.
     */
    private function consumeFlashes(): void
    {
        if (Yii::$app->has('session')) {
            Yii::$app->getSession()->getAllFlashes(true);
        }
    }

    /**
     * Creates a framework-neutral protocol context from a Yii request.
     *
     * @param Request $request Yii request to adapt.
     *
     * @throws InvalidRequestContextException If Yii supplies invalid request data.
     *
     * @return RequestContext Validated request data consumed by the protocol core.
     */
    private function createRequestContext(Request $request): RequestContext
    {
        $headers = [];

        foreach (
            [
                RequestContext::HEADER_ERROR_BAG,
                RequestContext::HEADER_EXCEPT_ONCE_PROPS,
                RequestContext::HEADER_INERTIA,
                RequestContext::HEADER_INFINITE_SCROLL_MERGE_INTENT,
                RequestContext::HEADER_PARTIAL_COMPONENT,
                RequestContext::HEADER_PARTIAL_DATA,
                RequestContext::HEADER_PARTIAL_EXCEPT,
                RequestContext::HEADER_PURPOSE,
                RequestContext::HEADER_RESET,
                RequestContext::HEADER_VERSION,
            ] as $name
        ) {
            $value = $request->getHeaders()->get($name);

            if (is_string($value)) {
                $headers[$name] = $value;
            }
        }

        return new RequestContext(
            method: $request->getMethod(),
            url: $request->getUrl(),
            absoluteUrl: $request->getAbsoluteUrl(),
            headers: $headers,
        );
    }

    /**
     * Creates a Yii response from a protocol result.
     *
     * @param ProtocolResult $result Protocol result to map.
     * @param array<string, mixed> $viewData Additional data available to the initial root view.
     *
     * @return Response Current application response populated from the protocol result.
     */
    private function createResponse(
        ProtocolResult $result,
        array $viewData = [],
    ): Response {
        return $this->mapResultToResponse($result, Yii::$app->getResponse(), $viewData);
    }

    /**
     * Returns the configured protocol service or creates the default service lazily.
     *
     * @return Protocol Protocol service used to resolve Inertia responses.
     */
    private function getProtocol(): Protocol
    {
        return $this->protocol ??= Protocol::create();
    }

    /**
     * Invokes the version resolver with zero arguments or the current request according to its declared arity.
     *
     * @param (Closure(): mixed)|(Closure(Request): mixed) $version Version resolver to invoke.
     *
     * @return mixed Value returned by the version resolver.
     */
    private function invokeVersion(Closure $version): mixed
    {
        return (new ReflectionFunction($version))->getNumberOfParameters() === 0
            ? $version()
            : $version(Yii::$app->getRequest());
    }

    /**
     * Applies a protocol result to the supplied Yii response.
     *
     * @param ProtocolResult $result Protocol result to map.
     * @param Response $response Yii response to populate.
     * @param array<string, mixed> $viewData Additional data available to the initial root view.
     *
     * @return Response Populated Yii response.
     */
    private function mapResultToResponse(
        ProtocolResult $result,
        Response $response,
        array $viewData = [],
    ): Response {
        $response->setStatusCode($result->statusCode());

        $this->applyHeaders($response, $result->headers());

        if ($result instanceof InertiaPageResult) {
            $response->format = Response::FORMAT_JSON;
            $response->content = null;
            $response->data = $result->page();

            return $response;
        }

        if ($result instanceof InitialPageResult) {
            $page = $result->page();

            $response->format = Response::FORMAT_HTML;
            $response->data = null;
            $response->content = Yii::$app->getView()->renderFile(
                $this->rootView,
                [
                    ...$viewData,
                    'id' => $this->id,
                    'page' => $page,
                    'pageJson' => Json::htmlEncode($page->toArray()),
                ],
            );

            return $response;
        }

        $response->format = Response::FORMAT_RAW;
        $response->content = '';
        $response->data = null;

        return $response;
    }

    /**
     * Merges comma-separated `Vary` values while treating token names case-insensitively.
     *
     * @param Response $response Yii response whose `Vary` header is updated.
     * @param string $value Header value containing the tokens to merge.
     */
    private function mergeVaryHeader(Response $response, string $value): void
    {
        $existing = $response->getHeaders()->get('Vary');
        $values = [];

        foreach ([$existing, $value] as $header) {
            if (!is_string($header)) {
                continue;
            }

            foreach (explode(',', $header) as $token) {
                $token = trim($token);

                if ($token !== '' && !isset($values[strtolower($token)])) {
                    $values[strtolower($token)] = $token;
                }
            }
        }

        if ($values !== []) {
            $response->getHeaders()->set('Vary', implode(', ', $values));
        }
    }

    /**
     * Reads session flashes without consuming them and separates validation errors from other flash data.
     *
     * @throws InvalidConfigException If flash keys or validation errors have an unsupported structure.
     *
     * @return array{array<string, list<string>|string>, array<string, mixed>} Validation errors and remaining flashes.
     */
    private function readFlashes(): array
    {
        if (!Yii::$app->has('session')) {
            return [[], []];
        }

        $flashes = [];

        foreach (Yii::$app->getSession()->getAllFlashes(false) as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidConfigException(
                    Message::SESSION_FLASH_KEY_INVALID->getMessage(),
                );
            }

            $flashes[$key] = $value;
        }

        $errors = [];

        if (array_key_exists($this->errorFlashKey, $flashes)) {
            $errors = $this->validateErrors($flashes[$this->errorFlashKey]);

            unset($flashes[$this->errorFlashKey]);
        }

        return [$errors, $flashes];
    }

    /**
     * Validates and normalizes the configured validation-error flash value.
     *
     * @param mixed $value Error flash value to validate.
     *
     * @throws InvalidConfigException If the error value does not contain supported validation messages.
     *
     * @return array<string, list<string>|string> Validation messages indexed by field name.
     */
    private function validateErrors(mixed $value): array
    {
        if (!is_array($value)) {
            throw new InvalidConfigException(
                Message::ERROR_FLASH_INVALID->getMessage(),
            );
        }

        $errors = [];

        foreach ($value as $key => $messages) {
            if (!is_string($key)) {
                throw new InvalidConfigException(
                    Message::ERROR_KEY_INVALID->getMessage(),
                );
            }

            if (is_string($messages)) {
                $errors[$key] = $messages;

                continue;
            }

            if (!is_array($messages) || !array_is_list($messages)) {
                throw new InvalidConfigException(
                    Message::ERROR_VALUE_INVALID->getMessage(),
                );
            }

            $normalized = [];

            foreach ($messages as $message) {
                if (!is_string($message)) {
                    throw new InvalidConfigException(
                        Message::ERROR_MESSAGE_LIST_INVALID->getMessage(),
                    );
                }

                $normalized[] = $message;
            }

            $errors[$key] = $normalized;
        }

        return $errors;
    }

    /**
     * Returns a nested value tree with a value assigned at the supplied path.
     *
     * @param array<array-key, mixed> $values Existing value tree.
     * @param non-empty-list<string> $path Nested path segments.
     * @param mixed $value Value to assign.
     *
     * @return array<array-key, mixed> Updated value tree.
     */
    private function withNestedValue(array $values, array $path, mixed $value): array
    {
        $key = array_shift($path);

        if ($path === []) {
            $values[$key] = $value;

            return $values;
        }

        $nested = $values[$key] ?? [];
        $values[$key] = $this->withNestedValue(is_array($nested) ? $nested : [], $path, $value);

        return $values;
    }

    /**
     * Returns shared props with a value assigned at the supplied path.
     *
     * @param array<string, mixed> $values Existing shared props.
     * @param non-empty-list<string> $path Nested path segments.
     * @param mixed $value Value to assign.
     *
     * @return array<string, mixed> Updated shared props.
     */
    private function withSharedValue(array $values, array $path, mixed $value): array
    {
        $key = array_shift($path);

        if ($path === []) {
            $values[$key] = $value;

            return $values;
        }

        $nested = $values[$key] ?? [];
        $values[$key] = $this->withNestedValue(is_array($nested) ? $nested : [], $path, $value);

        return $values;
    }
}

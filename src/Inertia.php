<?php

declare(strict_types=1);

namespace yii\inertia;

use Closure;
use PHPForge\Inertia\Prop\{
    AlwaysProp,
    DeferredProp,
    MergeProp,
    OnceProp,
    OptionalProp,
    Prop,
    ScrollMetadata,
    ScrollProp,
};
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * Provides Yii-oriented page operations and framework-neutral prop factories through the configured manager.
 */
final class Inertia
{
    private const string COMPONENT_ID = 'inertia';

    /**
     * Creates a prop that is always included in every response, bypassing partial-reload filtering.
     *
     * @param Closure|mixed $value Value or closure always included in responses.
     *
     * @return AlwaysProp Prop instance that is always included in responses.
     */
    public static function always(mixed $value): AlwaysProp
    {
        return Prop::always($value);
    }

    /**
     * Creates a prop that deep-merges with existing client-side data during partial reloads.
     *
     * @param Closure|mixed $value Value or closure to deep-merge.
     *
     * @return MergeProp Prop instance that deep-merges with existing client-side data during partial reloads.
     */
    public static function deepMerge(mixed $value): MergeProp
    {
        return Prop::merge($value)->deepMerge();
    }

    /**
     * Creates a deferred prop whose evaluation is postponed until the client requests it via a partial reload.
     *
     * @param (Closure(): mixed) $callback Closure resolved when the client requests this prop.
     * @param string $group Group name for batching deferred requests.
     * @param bool $rescue Whether callback failures should be rescued and reported as page metadata.
     *
     * @return DeferredProp Prop instance that is resolved when the client requests it via a partial reload.
     */
    public static function defer(
        Closure $callback,
        string $group = 'default',
        bool $rescue = false,
    ): DeferredProp {
        return Prop::defer($callback, $group, $rescue);
    }

    /**
     * Removes all shared props registered for the current request.
     */
    public static function flushShared(): void
    {
        self::manager()->flushShared();
    }

    /**
     * Returns the shared props or the nested value at `$key`.
     *
     * @param string|null $key Dot-notation key to retrieve, or `null` to return all shared props.
     * @param mixed $default Value returned when `$key` is not found.
     *
     * @return mixed Shared value at `$key`, or `$default` when the key does not exist.
     */
    public static function getShared(string|null $key = null, mixed $default = null): mixed
    {
        return self::manager()->getShared($key, $default);
    }

    /**
     * Returns the resolved asset version.
     *
     * @return int|string Resolved version, or an empty `string` when none is configured.
     */
    public static function getVersion(): int|string
    {
        return self::manager()->getVersion();
    }

    /**
     * Returns a `409` Inertia location response for Inertia requests, or a standard `302` redirect otherwise.
     *
     * @param array<array-key, mixed>|string $url Destination URL or route array accepted by `Url::to()`.
     *
     * @return Response Response instance with the appropriate status code and headers for the request type.
     */
    public static function location(array|string $url): Response
    {
        return self::manager()->location($url);
    }

    /**
     * Creates a prop that merges with existing client-side data during partial reloads instead of replacing it.
     *
     * @param Closure|mixed $value Value or closure to merge.
     *
     * @return MergeProp Prop instance that merges with existing client-side data during partial reloads instead of
     * replacing it.
     */
    public static function merge(mixed $value): MergeProp
    {
        return Prop::merge($value);
    }

    /**
     * Creates a prop that the client may retain and omit from subsequent requests.
     *
     * @param (Closure(): mixed) $callback Closure resolved when the prop is not already available to the client.
     *
     * @return OnceProp Prop instance containing the client-side cache metadata.
     */
    public static function once(Closure $callback): OnceProp
    {
        return Prop::once($callback);
    }

    /**
     * Creates a prop resolved only when a matching partial reload explicitly requests it.
     *
     * @param (Closure(): mixed) $callback Closure resolved when the client requests this prop.
     *
     * @return OptionalProp Prop instance excluded from standard page responses.
     */
    public static function optional(Closure $callback): OptionalProp
    {
        return Prop::optional($callback);
    }

    /**
     * Renders an Inertia page through the configured manager.
     *
     * @param string $component Client-side component name.
     * @param array<string, mixed> $props Page-specific props.
     * @param array<string, mixed> $viewData Additional data available to the initial root view.
     *
     * @return Response Yii response containing the initial HTML document or Inertia JSON page.
     */
    public static function render(string $component, array $props = [], array $viewData = []): Response
    {
        return self::manager()->render($component, $props, $viewData);
    }

    /**
     * Creates a prop containing infinite-scroll data and pagination metadata.
     *
     * @param mixed $value Paginated data exposed as the prop value.
     * @param (Closure(mixed): mixed)|ScrollMetadata $metadata Pagination metadata or a callback receiving the resolved
     * value.
     * @param string $wrapper Dot-notated merge path within the prop value.
     *
     * @return ScrollProp Prop instance containing infinite-scroll semantics.
     */
    public static function scroll(
        mixed $value,
        ScrollMetadata|Closure $metadata,
        string $wrapper = 'data',
    ): ScrollProp {
        return Prop::scroll($value, $metadata, $wrapper);
    }

    /**
     * Registers one or more props shared with subsequent page responses in the current request.
     *
     * @param array<string, mixed>|string $key Shared values or a dot-notated prop key.
     * @param mixed $value Value registered when `$key` is a string.
     */
    public static function share(array|string $key, mixed $value = null): void
    {
        self::manager()->share($key, $value);
    }

    /**
     * Returns the Inertia application component instance.
     *
     * @throws InvalidConfigException if the component is not properly configured or does not extend `Manager`.
     *
     * @return Manager Inertia application component instance.
     */
    private static function manager(): Manager
    {
        $manager = Yii::$app->get(self::COMPONENT_ID);

        if (!$manager instanceof Manager) {
            throw new InvalidConfigException(
                'The "inertia" application component must be an instance of ' . Manager::class . '.',
            );
        }

        return $manager;
    }
}

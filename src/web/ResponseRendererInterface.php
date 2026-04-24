<?php

declare(strict_types=1);

namespace yii\inertia\web;

use yii\web\Response;

/**
 * Defines the contract for rendering a response from a logical component identifier.
 *
 * Controllers delegate their final output through this interface so overlays (Inertia, JSON, API) can replace the
 * presentation strategy via DI without rewriting action methods.
 *
 * The `$component` argument is the Inertia component identifier (PascalCase, optionally namespaced, for example
 * `'Site/About'`). Implementations targeting the default PHP view layer are responsible for mapping this identifier
 * to the Yii view convention.
 *
 * Usage example:
 *
 * ```php
 * use yii\inertia\web\ResponseRendererInterface;
 *
 * final class SiteController extends \yii\web\Controller
 * {
 *     public function __construct(
 *         $id,
 *         $module,
 *         private readonly ResponseRendererInterface $renderer,
 *         $config = [],
 *     ) {
 *         parent::__construct($id, $module, $config);
 *     }
 *
 *     public function actionAbout(): \yii\web\Response|string
 *     {
 *         return $this->renderer->render('Site/About', ['team' => $team]);
 *     }
 * }
 * ```
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1.1
 */
interface ResponseRendererInterface
{
    /**
     * Renders a response for the given component identifier.
     *
     * @param string $component Component identifier (for example, `'Dashboard'`, `'Site/About'`).
     * @param array<string, mixed> $props Props serialized and forwarded to the frontend component.
     * @param array<string, mixed> $viewData Additional data available in the root view template only; not sent to the
     * frontend.
     *
     * @return Response|string Response instance or pre-rendered string, depending on the implementation.
     */
    public function render(string $component, array $props = [], array $viewData = []): Response|string;
}

<?php

declare(strict_types=1);

namespace yii\inertia\tests;

use Yii;
use yii\inertia\web\{InertiaRenderer, ResponseRendererInterface};
use yii\web\Response;

/**
 * Unit tests for {@see \yii\inertia\web\InertiaRenderer}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1.1
 */
final class InertiaRendererTest extends TestCase
{
    public function testRenderIsReachableThroughDiContainerAfterBootstrap(): void
    {
        $renderer = Yii::$container->get(ResponseRendererInterface::class);

        self::assertInstanceOf(
            InertiaRenderer::class,
            $renderer,
            'DI container should resolve ResponseRendererInterface to InertiaRenderer after bootstrap.',
        );
        self::assertSame(
            $renderer,
            Yii::$container->get(ResponseRendererInterface::class),
            'Binding should be a singleton returning the same instance across lookups.',
        );
    }

    public function testRenderReturnsHtmlShellForStandardBrowserRequest(): void
    {
        $this->setAbsoluteUrl('/site/index');

        $renderer = new InertiaRenderer();

        $response = $renderer->render('Dashboard', ['stats' => ['visits' => 42]]);

        self::assertSame(
            Response::FORMAT_HTML,
            $response->format,
            'Non-Inertia requests should use HTML response format.',
        );

        $page = $this->extractPage($response);

        self::assertSame(
            'Dashboard',
            $page['component'],
            'HTML shell should embed the page component identifier.',
        );
    }
    public function testRenderReturnsInertiaJsonResponseForInertiaRequest(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/site/index');

        $renderer = new InertiaRenderer();

        $response = $renderer->render(
            'Dashboard',
            [
                'stats' => ['visits' => 42],
            ],
        );

        self::assertSame(
            Response::FORMAT_JSON,
            $response->format,
            'Inertia requests should use JSON response format.',
        );
        self::assertSame(
            'true',
            $response->getHeaders()->get('X-Inertia'),
            "Inertia response should carry the 'X-Inertia: true' header.",
        );

        $page = $this->extractPage($response);

        self::assertSame('Dashboard', $page['component'], 'Page component should match the render argument.');
        self::assertArrayHasKey('stats', $page['props'], "Props should contain the 'stats' key.");
        self::assertSame(
            ['visits' => 42],
            $page['props']['stats'],
            'Props should be forwarded verbatim to the Inertia page.',
        );
    }
}

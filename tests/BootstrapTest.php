<?php

declare(strict_types=1);

namespace yii\inertia\tests;

use stdClass;
use Yii;
use yii\base\Event;
use yii\inertia\{Bootstrap, Manager};
use yii\web\Response;

/**
 * Unit tests for {@see \yii\inertia\Bootstrap}.
 */
final class BootstrapTest extends TestCase
{
    public function testBeforeSendConvertsFragmentRedirect(): void
    {
        $this->prepareBootstrappedInertiaRequest();
        $this->setAbsoluteUrl('/posts');

        $response = Yii::$app->getResponse()->redirect('/posts#comments');

        self::assertTrue(
            $response->hasEventHandlers(Response::EVENT_BEFORE_SEND),
            'An Inertia request should register the response normalizer.',
        );

        $response->trigger(Response::EVENT_BEFORE_SEND);

        self::assertSame(
            409,
            $response->statusCode,
            "An Inertia fragment redirect should return '409'.",
        );
        self::assertSame(
            'https://example.test/posts#comments',
            $response->getHeaders()->get('X-Inertia-Redirect'),
            'The fragment redirect should use the core protocol header.',
        );
        self::assertFalse(
            $response->getHeaders()->has('Location'),
            'A fragment redirect should remove Location.',
        );
        self::assertFalse(
            $response->getHeaders()->has('X-Redirect'),
            'A fragment redirect should remove Yii X-Redirect.',
        );
    }

    public function testBeforeSendDoesNotModifyNonInertiaRequests(): void
    {
        $this->setAbsoluteUrl('/posts');

        $response = Yii::$app->getResponse()->redirect('/target');

        self::assertFalse(
            Yii::$app->has('inertia', true),
            'The Inertia manager should initially remain lazy.',
        );
        self::assertFalse(
            $response->hasEventHandlers(Response::EVENT_BEFORE_SEND),
            'A standard request should not register the response normalizer.',
        );

        $response->trigger(Response::EVENT_BEFORE_SEND);

        self::assertSame(
            302,
            $response->statusCode,
            'A standard redirect should retain its status.',
        );
        self::assertNull(
            $response->getHeaders()->get('Vary'),
            'A standard response should not add Inertia headers.',
        );
        self::assertFalse(
            Yii::$app->has('inertia', true),
            'A standard request should not instantiate the Inertia manager.',
        );
    }

    public function testBeforeSendDoesNotNormalizeRedirectWithoutLocation(): void
    {
        $this->prepareBootstrappedInertiaRequest('PUT');
        $this->setAbsoluteUrl('/posts');

        $response = Yii::$app->getResponse();

        $response->setStatusCode(302);
        $response->trigger(Response::EVENT_BEFORE_SEND);

        self::assertSame(
            302,
            $response->statusCode,
            'A status without a location is not a redirect to normalize.',
        );
        self::assertSame(
            'X-Inertia',
            $response->getHeaders()->get('Vary'),
            'Inertia responses should still vary.',
        );
    }

    public function testBeforeSendIgnoresNonResponseSender(): void
    {
        $this->prepareBootstrappedInertiaRequest();

        $response = Yii::$app->getResponse();

        $event = new Event();
        $event->sender = new stdClass();

        $response->trigger(Response::EVENT_BEFORE_SEND, $event);

        self::assertNull(
            $response->getHeaders()->get('Vary'),
            'A non-response event sender should be ignored.',
        );
    }

    public function testBeforeSendKeepsPermanentRedirectStatus(): void
    {
        $this->prepareBootstrappedInertiaRequest('PUT');
        $this->setAbsoluteUrl('/posts');

        $response = Yii::$app->getResponse();

        $response->setStatusCode(301);
        $response->getHeaders()->set('Location', '/target');
        $response->trigger(Response::EVENT_BEFORE_SEND);

        self::assertSame(
            301,
            $response->statusCode,
            "The core only converts '302' mutation redirects to '303'",
        );
        self::assertSame(
            '/target',
            $response->getHeaders()->get('Location'),
            'The location should be retained.',
        );
    }

    public function testBeforeSendMergesVaryWithoutDuplicates(): void
    {
        $this->prepareBootstrappedInertiaRequest();
        $this->setAbsoluteUrl('/dashboard');

        $response = Yii::$app->getResponse();

        $response->getHeaders()->set('Vary', 'Accept-Encoding, X-INERTIA');
        $response->trigger(Response::EVENT_BEFORE_SEND);

        self::assertSame(
            'Accept-Encoding, X-INERTIA',
            $response->getHeaders()->get('Vary'),
            'Vary should preserve application values without duplicating X-Inertia.',
        );
    }

    public function testBeforeSendNormalizesMutationRedirect(): void
    {
        $this->prepareBootstrappedInertiaRequest('PUT');
        $this->setAbsoluteUrl('/posts');

        $response = Yii::$app->getResponse()->redirect('/target');

        $response->trigger(Response::EVENT_BEFORE_SEND);

        self::assertSame(
            303,
            $response->statusCode,
            "A '302' redirect after an Inertia PUT should become '303'.",
        );
        self::assertSame(
            'https://example.test/target',
            $response->getHeaders()->get('Location'),
            'The normalized redirect should retain Yii absolute URL generation.',
        );
        self::assertFalse(
            $response->getHeaders()->has('X-Redirect'),
            'Yii X-Redirect should be normalized to Location.',
        );
    }

    public function testBeforeSendPrefetchKeepsFragmentRedirect(): void
    {
        $this->prepareBootstrappedInertiaRequest();
        $this->setAbsoluteUrl('/posts');

        Yii::$app->getRequest()->getHeaders()->set('Purpose', 'prefetch');

        $response = Yii::$app->getResponse()->redirect('/posts#comments');

        $response->trigger(Response::EVENT_BEFORE_SEND);

        self::assertSame(
            302,
            $response->statusCode,
            'A prefetch fragment redirect should remain a standard redirect.',
        );
        self::assertSame(
            'https://example.test/posts#comments',
            $response->getHeaders()->get('Location'),
            'A prefetch fragment redirect should retain Location.',
        );
    }

    public function testBootstrapDoesNotOverrideExistingComponentDefinition(): void
    {
        $this->mockWebApplication(
            [
                'components' => [
                    'inertia' => [
                        'class' => Manager::class,
                        'id' => 'frontend-app',
                    ],
                ],
            ],
        );

        $manager = Yii::$app->get('inertia');

        self::assertInstanceOf(
            Manager::class,
            $manager,
            'The configured component should remain a Manager.',
        );
        self::assertSame(
            'frontend-app',
            $manager->id,
            'Bootstrap should preserve user component configuration.',
        );
    }

    public function testBootstrapRegistersAliasAndComponent(): void
    {
        self::assertSame(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src',
            Yii::getAlias('@inertia'),
            'Bootstrap should register the package alias.',
        );
        self::assertInstanceOf(
            Manager::class,
            Yii::$app->get('inertia'),
            'Bootstrap should register the adapter manager.',
        );
    }

    public function testNormalizeResponseAcceptsOnlyCoreRedirectStatuses(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/posts');

        $manager = Yii::$app->get('inertia');

        self::assertInstanceOf(
            Manager::class,
            $manager,
            'The application should expose the Inertia manager.',
        );

        foreach ([301, 302, 303, 307, 308] as $statusCode) {
            $response = new Response();

            $response->setStatusCode($statusCode);
            $response->getHeaders()->set('X-Redirect', '/target');
            $manager->normalizeResponse($response);

            self::assertSame(
                $statusCode,
                $response->statusCode,
                "Status {$statusCode} should be normalized.",
            );
            self::assertSame(
                '/target',
                $response->getHeaders()->get('Location'),
                "Status {$statusCode} should use Location.",
            );
            self::assertFalse(
                $response->getHeaders()->has('X-Redirect'),
                "Status {$statusCode} should remove X-Redirect.",
            );
        }

        foreach ([300, 304, 306, 309] as $statusCode) {
            $response = new Response();

            $response->setStatusCode($statusCode);
            $response->getHeaders()->set('X-Redirect', '/target');

            $manager->normalizeResponse($response);

            self::assertSame(
                $statusCode,
                $response->statusCode,
                "Status {$statusCode} should remain unchanged.",
            );
            self::assertSame(
                '/target',
                $response->getHeaders()->get('X-Redirect'),
                "Status {$statusCode} should remain unnormalized.",
            );
            self::assertFalse(
                $response->getHeaders()->has('Location'),
                "Status {$statusCode} should not add Location.",
            );
        }
    }

    public function testNormalizeResponsePrefersYiiAjaxRedirectHeader(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/posts');

        $response = Yii::$app->getResponse();

        $response->setStatusCode(302);
        $response->getHeaders()->set('X-Redirect', '/posts#yii-ajax-target');
        $response->getHeaders()->set('Location', '/standard-target');

        $manager = Yii::$app->get('inertia');

        self::assertInstanceOf(
            Manager::class,
            $manager,
            'The application should expose the Inertia manager.',
        );

        $manager->normalizeResponse($response);

        self::assertSame(
            'https://example.test/posts#yii-ajax-target',
            $response->getHeaders()->get('X-Inertia-Redirect'),
            'Yii X-Redirect should take precedence when both redirect headers are present.',
        );
        self::assertSame(
            409,
            $response->statusCode,
            'The preferred fragment redirect should use the core response.',
        );
        self::assertFalse(
            $response->getHeaders()->has('Location'),
            'Normalization should remove the stale Location.',
        );
        self::assertFalse(
            $response->getHeaders()->has('X-Redirect'),
            'Normalization should remove X-Redirect.',
        );
    }

    private function prepareBootstrappedInertiaRequest(string $method = 'GET'): void
    {
        $this->prepareInertiaRequest($method);

        (new Bootstrap())->bootstrap(Yii::$app);
    }
}

<?php

declare(strict_types=1);

namespace yii\inertia\tests;

use DateTimeImmutable;
use PHPForge\Inertia\Clock\Clock;
use PHPForge\Inertia\Exception\InvalidPageInputException;
use PHPForge\Inertia\{Page, Protocol};
use Yii;
use yii\base\InvalidConfigException;
use yii\inertia\{Inertia, Manager};
use yii\web\{Request, Response};

/**
 * Integration tests for {@see Manager} as the Yii2 adapter for the protocol core.
 */
final class ManagerTest extends TestCase
{
    public function testAdapterRejectsInvalidErrorFlashStructuresWithoutConsumingThem(): void
    {
        $invalidErrors = [
            'scalar' => ['Invalid', 'The Inertia error flash must be an array.'],
            'numeric key' => [[0 => 'Invalid'], 'Inertia error keys must be strings.'],
            'non-string value' => [
                ['email' => 42],
                'Inertia errors must contain strings or lists of strings.',
            ],
            'non-list messages' => [
                ['email' => ['first' => 'Invalid']],
                'Inertia errors must contain strings or lists of strings.',
            ],
            'non-string message' => [
                ['email' => ['Invalid', 42]],
                'Inertia error message lists must contain only strings.',
            ],
        ];

        foreach ($invalidErrors as $case => [$errors, $expectedMessage]) {
            Yii::$app->getSession()->setFlash('errors', $errors);

            try {
                Inertia::render('Dashboard');

                self::fail(
                    "The {$case} error flash should be rejected.",
                );
            } catch (InvalidConfigException $exception) {
                self::assertSame(
                    $expectedMessage,
                    $exception->getMessage(),
                    "The {$case} error flash exception should describe the invalid structure.",
                );
                self::assertSame(
                    ['errors' => $errors],
                    Yii::$app->getSession()->getAllFlashes(false),
                    "The {$case} error flash should remain available after rejection.",
                );
            }
        }
    }

    public function testAdapterRejectsNonStringFlashKeysWithoutConsumingThem(): void
    {
        $session = Yii::$app->getSession();

        $session->setFlash('42', 'Invalid flash key.');

        try {
            Inertia::render('Dashboard');

            self::fail(
                'A non-string flash key should be rejected.',
            );
        } catch (InvalidConfigException $exception) {
            self::assertSame(
                'Inertia session flash keys must be strings.',
                $exception->getMessage(),
                'The exception should identify the unsupported flash key.',
            );
            self::assertSame(
                [42 => 'Invalid flash key.'],
                $session->getAllFlashes(false),
                'A rejected flash should remain available for the next request.',
            );
        }
    }

    public function testFailedRenderDoesNotConsumeFlashes(): void
    {
        Yii::$app->getSession()->setFlash('errors', ['' => 'Invalid']);

        try {
            Inertia::render('Dashboard');

            self::fail(
                'Invalid error data should fail before a response is created.',
            );
        } catch (InvalidPageInputException) {
            self::assertSame(
                ['errors' => ['' => 'Invalid']],
                Yii::$app->getSession()->getAllFlashes(false),
                'A failed render should leave session flashes available for the next request.',
            );
        }
    }

    public function testFlushSharedRemovesAllSharedProps(): void
    {
        $manager = $this->manager();

        $manager->share('auth.user', ['id' => 7]);

        $manager->flushShared();

        self::assertSame(
            [],
            $manager->getShared(),
            'Flushing should remove every shared prop.',
        );
    }

    public function testGetVersionResolvesClosureWithoutArguments(): void
    {
        $manager = $this->manager();

        $manager->version = static fn(): string => 'asset-version';

        self::assertSame(
            'asset-version',
            $manager->getVersion(),
            'The Yii adapter should invoke zero-argument version callbacks without a request.',
        );
    }

    public function testGetVersionResolvesClosureWithYiiRequest(): void
    {
        $this->setAbsoluteUrl('/dashboard');

        $manager = $this->manager();

        $manager->version = static fn(Request $request): string => $request->getAbsoluteUrl();

        self::assertSame(
            'https://example.test/dashboard',
            $manager->getVersion(),
            'The Yii adapter should resolve version callbacks with the current Yii request.',
        );
    }

    public function testHistoryConfigurationIsForwardedToCorePage(): void
    {
        $manager = $this->manager();

        $manager->clearHistory = true;
        $manager->encryptHistory = true;
        $manager->preserveFragment = true;

        $this->setAbsoluteUrl('/dashboard');

        $page = $this->extractPage($manager->render('Dashboard'));

        self::assertArrayHasKey(
            'clearHistory',
            $page,
            'The page should contain clearHistory.',
        );
        self::assertArrayHasKey(
            'encryptHistory',
            $page,
            'The page should contain encryptHistory.',
        );
        self::assertArrayHasKey(
            'preserveFragment',
            $page,
            'The page should contain preserveFragment.',
        );
        self::assertTrue(
            $page['clearHistory'],
            'clearHistory should be forwarded to the core page.',
        );
        self::assertTrue(
            $page['encryptHistory'],
            'encryptHistory should be forwarded to the core page.',
        );
        self::assertTrue(
            $page['preserveFragment'],
            'preserveFragment should be forwarded to the core page.',
        );
    }

    public function testIsInertiaRequestAcceptsCanonicalAndNumericMarkers(): void
    {
        $manager = $this->manager();

        $request = new Request();

        $request->getHeaders()->set('X-Inertia', 'true');

        self::assertTrue(
            $manager->isInertiaRequest($request),
            'The canonical true marker should be accepted.',
        );

        $request->getHeaders()->set('X-Inertia', '1');

        self::assertTrue(
            $manager->isInertiaRequest($request),
            'The numeric marker should be accepted.',
        );

        $request->getHeaders()->set('X-Inertia', 'false');

        self::assertFalse(
            $manager->isInertiaRequest($request),
            'Other marker values should be rejected.',
        );
    }

    public function testLocationReturnsInertiaConflict(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/dashboard');

        $response = $this->manager()->location(['/login']);

        self::assertSame(
            409,
            $response->statusCode,
            "An Inertia location visit should return '409'.",
        );
        self::assertSame(
            Response::FORMAT_RAW,
            $response->format,
            'A location response should have an empty raw body.',
        );
        self::assertSame(
            '',
            $response->content,
            'A location response should have an empty body.',
        );
        self::assertSame(
            'https://example.test/index.php?r=login',
            $response->getHeaders()->get('X-Inertia-Location'),
            'The location header should contain the absolute Yii route URL.',
        );
        self::assertSame(
            'X-Inertia',
            $response->getHeaders()->get('Vary'),
            'The response should vary on X-Inertia.',
        );
    }

    public function testLocationReturnsStandardRedirect(): void
    {
        $this->setAbsoluteUrl('/dashboard');

        $response = $this->manager()->location('/login');

        self::assertSame(
            302,
            $response->statusCode,
            'A standard location visit should return 302.',
        );
        self::assertSame(
            'https://example.test/login',
            $response->getHeaders()->get('Location'),
            'The standard redirect should use an absolute location URL.',
        );
    }

    public function testManagerUsesConfiguredProtocolInstance(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/settings');

        $clock = new class implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('@1720000000');
            }
        };

        $manager = $this->manager();

        $manager->protocol = Protocol::create($clock);

        $response = $manager->render(
            'Settings',
            ['settings' => Inertia::once(static fn(): array => [])->until(60)],
        );

        self::assertInstanceOf(
            Page::class,
            $response->data,
            'An Inertia response should contain the core page.',
        );
        self::assertSame(
            ['settings' => ['prop' => 'settings', 'expiresAt' => 1_720_000_060_000]],
            $response->data->onceProps(),
            'The configured protocol clock should resolve once-prop expiration metadata.',
        );
    }

    public function testNormalizeResponseLeavesStandardResponseUntouched(): void
    {
        $response = new Response();

        $response->setStatusCode(302);
        $response->getHeaders()->set('Location', '/login');

        $this->manager()->normalizeResponse($response);

        self::assertSame(
            302,
            $response->statusCode,
            'A standard redirect should preserve its status code.',
        );
        self::assertSame(
            '/login',
            $response->getHeaders()->get('Location'),
            'A standard redirect should preserve its location header.',
        );
        self::assertNull(
            $response->getHeaders()->get('Vary'),
            'A standard response should not gain the Inertia Vary token.',
        );
    }

    public function testPartialReloadHeadersAreForwardedToCore(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/dashboard');

        $headers = Yii::$app->getRequest()->getHeaders();

        $headers->set('X-Inertia-Partial-Component', 'Dashboard');
        $headers->set('X-Inertia-Partial-Data', 'stats');

        $unrequestedResolved = false;

        $page = $this->extractPage(
            $this->manager()->render(
                'Dashboard',
                [
                    'stats' => static fn(): array => ['visits' => 42],
                    'unrequested' => static function () use (&$unrequestedResolved): array {
                        $unrequestedResolved = true;

                        return ['secret'];
                    },
                ],
            ),
        );

        $props = $page['props'];

        self::assertArrayHasKey(
            'stats',
            $props,
            'The requested prop should be present.',
        );
        self::assertSame(
            ['visits' => 42],
            $props['stats'],
            'The requested prop should be resolved.',
        );
        self::assertArrayNotHasKey(
            'unrequested',
            $props,
            'Unrequested props should be omitted.',
        );
        self::assertFalse(
            $unrequestedResolved,
            'Unrequested callbacks should not execute in the adapter.',
        );
    }

    public function testRenderConsumesAndMapsSessionErrorsAndFlash(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/profile');

        $session = Yii::$app->getSession();

        $session->setFlash('errors', ['email' => ['Invalid email.']]);
        $session->setFlash('success', 'Profile saved.');

        $page = $this->extractPage($this->manager()->render('Profile'));

        $props = $page['props'];

        self::assertArrayHasKey(
            'errors',
            $props,
            'The page should contain validation errors.',
        );
        self::assertArrayHasKey(
            'flash',
            $page,
            'The page should contain non-empty flash data.',
        );
        self::assertSame(
            ['email' => ['Invalid email.']],
            (array) $props['errors'],
            'Validation errors should be forwarded through the dedicated core input.',
        );
        self::assertSame(
            ['success' => 'Profile saved.'],
            (array) $page['flash'],
            'General session flashes should be emitted as top-level Inertia flash data.',
        );
        self::assertSame(
            [],
            $session->getAllFlashes(false),
            'A successful page response should consume flashes.',
        );
    }

    public function testRenderForwardsErrorBagHeader(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/login');

        Yii::$app->getRequest()->getHeaders()->set('X-Inertia-Error-Bag', 'login');
        Yii::$app->getSession()->setFlash('errors', ['email' => 'Invalid credentials.']);

        $page = $this->extractPage($this->manager()->render('Login'));

        $props = $page['props'];

        self::assertArrayHasKey(
            'errors',
            $props,
            'The page should contain the error bag.',
        );
        self::assertSame(
            ['login' => ['email' => 'Invalid credentials.']],
            (array) $props['errors'],
            'The adapter should forward the error bag header to the protocol core.',
        );
    }

    public function testRenderMapsInertiaPageResultToJsonResponse(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/dashboard');

        $response = $this->manager()->render('Dashboard', ['answer' => 42]);

        self::assertSame(
            200,
            $response->statusCode,
            "An Inertia page should return '200'.",
        );
        self::assertSame(
            Response::FORMAT_JSON,
            $response->format,
            'An Inertia page should use Yii JSON formatting.',
        );
        self::assertInstanceOf(
            Page::class,
            $response->data,
            'The response data should be the neutral core page.',
        );
        self::assertSame(
            'true',
            $response->getHeaders()->get('X-Inertia'),
            'The response should mark Inertia JSON.',
        );
        self::assertSame(
            'X-Inertia',
            $response->getHeaders()->get('Vary'),
            'The response should vary on X-Inertia.',
        );

        $props = $this->extractPage($response)['props'];

        self::assertArrayHasKey(
            'answer',
            $props,
            'The page should contain its answer prop.',
        );
        self::assertSame(
            42,
            $props['answer'],
            'Page props should be preserved.',
        );
    }

    public function testRenderMapsInitialPageResultToRootView(): void
    {
        $this->setAbsoluteUrl('/dashboard');

        $response = $this->manager()->render('Dashboard', ['answer' => 42]);
        $page = $this->extractPage($response);

        self::assertSame(
            200,
            $response->statusCode,
            "An initial page should return '200'.",
        );
        self::assertSame(
            Response::FORMAT_HTML,
            $response->format,
            'An initial page should render HTML.',
        );

        $props = $page['props'];

        self::assertArrayHasKey(
            'answer',
            $props,
            'The embedded page should contain its answer prop.',
        );
        self::assertArrayHasKey(
            'errors',
            $props,
            'The embedded page should contain its errors prop.',
        );
        self::assertSame(
            'Dashboard',
            $page['component'],
            'The root view should embed the core page.',
        );
        self::assertSame(
            42,
            $props['answer'],
            'The embedded page should contain its props.',
        );
        self::assertSame(
            [],
            $props['errors'],
            'Empty errors should serialize as an object and decode as an array.',
        );
    }

    public function testRenderMergesVaryHeader(): void
    {
        $this->setAbsoluteUrl('/dashboard');

        Yii::$app->getResponse()->getHeaders()->set('Vary', 'Accept-Encoding');

        $response = $this->manager()->render('Dashboard');

        self::assertSame(
            'Accept-Encoding, X-Inertia',
            $response->getHeaders()->get('Vary'),
            'Protocol Vary values should be merged with application values.',
        );
    }

    public function testRenderPassesAdditionalRootViewData(): void
    {
        $this->setAbsoluteUrl('/dashboard');

        $manager = $this->manager();

        $manager->rootView = '@tests/data/views/custom-app.php';

        $response = $manager->render('Dashboard', viewData: ['title' => 'Custom title']);

        self::assertStringContainsString(
            '<title>Custom title</title>',
            (string) $response->content,
            'Additional view data should remain available to the Yii root view.',
        );
    }

    public function testRenderPreservesAllValidErrorEntries(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/profile');

        Yii::$app->getSession()->setFlash(
            'errors',
            [
                'email' => 'Invalid email.',
                'name' => ['Name is required.', 'Name is too short.'],
            ],
        );

        $page = $this->extractPage($this->manager()->render('Profile'));

        self::assertArrayHasKey(
            'errors',
            $page['props'],
            'The page should contain all validation errors.',
        );
        self::assertSame(
            [
                'email' => 'Invalid email.',
                'name' => ['Name is required.', 'Name is too short.'],
            ],
            (array) $page['props']['errors'],
            'The adapter should retain string and list messages for every error key.',
        );
    }

    public function testRenderWithoutSessionUsesEmptyErrors(): void
    {
        $this->destroyApplication();
        $this->mockWebApplicationWithoutSession();
        $this->setAbsoluteUrl('/dashboard');

        $page = $this->extractPage($this->manager()->render('Dashboard'));

        $props = $page['props'];

        self::assertArrayHasKey(
            'errors',
            $props,
            'The page should contain empty errors.',
        );
        self::assertSame(
            [],
            $props['errors'],
            'Rendering without a session should use empty errors.',
        );
        self::assertArrayNotHasKey(
            'flash',
            $page,
            'Empty flash data should be omitted.',
        );
    }

    public function testSharedPropsUseDotNotationAndPagePropsWin(): void
    {
        $this->setAbsoluteUrl('/dashboard');

        $manager = $this->manager();

        $manager->share('auth.user.name', 'Shared name');

        $page = $this->extractPage(
            $manager->render('Dashboard', ['auth' => ['user' => ['name' => 'Page name']]]),
        );

        $props = $page['props'];

        self::assertArrayHasKey(
            'auth',
            $props,
            'The page should contain the auth prop.',
        );
        self::assertIsArray(
            $props['auth'],
            'The auth prop should be an array.',
        );
        self::assertArrayHasKey(
            'user',
            $props['auth'],
            'The auth prop should contain the user.',
        );
        self::assertIsArray(
            $props['auth']['user'],
            'The auth user prop should be an array.',
        );
        self::assertArrayHasKey(
            'name',
            $props['auth']['user'],
            'The auth user should contain its name.',
        );
        self::assertSame(
            'Page name',
            $props['auth']['user']['name'],
            'Page props should override shared props after dot-notation expansion.',
        );
        self::assertArrayHasKey(
            'sharedProps',
            $page,
            'The page should contain shared prop metadata.',
        );
    }

    public function testSharePreservesNestedAndTopLevelSiblings(): void
    {
        $manager = $this->manager();

        $manager->share(['auth.roles' => ['admin'], 'navigation.items' => ['home']]);
        $manager->share('auth.user.name', 'Jane');
        $manager->share('auth.user.email.primary', 'jane@example.test');
        $manager->share('auth.user.email.secondary', 'backup@example.test');

        self::assertSame(
            [
                'auth' => [
                    'roles' => ['admin'],
                    'user' => [
                        'name' => 'Jane',
                        'email' => [
                            'primary' => 'jane@example.test',
                            'secondary' => 'backup@example.test',
                        ],
                    ],
                ],
                'navigation' => ['items' => ['home']],
            ],
            $manager->getShared(),
            'Dot-notation sharing should preserve existing nested and top-level siblings.',
        );
    }

    public function testShareReplacesScalarIntermediateValue(): void
    {
        $manager = $this->manager();

        $manager->share('auth', 'unresolved');
        $manager->share('auth.user.name', 'Jane');

        self::assertSame(
            ['auth' => ['user' => ['name' => 'Jane']]],
            $manager->getShared(),
            'A nested shared prop should replace a scalar intermediate value.',
        );
    }

    public function testVersionConflictDoesNotResolvePropsOrConsumeFlashes(): void
    {
        $this->prepareInertiaRequest();
        $this->setAbsoluteUrl('/dashboard');

        Yii::$app->getRequest()->getHeaders()->set('X-Inertia-Version', 'old-version');
        Yii::$app->getSession()->setFlash('notice', 'Keep me.');

        $manager = $this->manager();

        $manager->version = 'new-version';
        $resolved = false;

        $response = $manager->render(
            'Dashboard',
            [
                'expensive' => static function () use (&$resolved): string {
                    $resolved = true;

                    return 'resolved';
                },
            ],
        );

        self::assertSame(
            409,
            $response->statusCode,
            "A mismatched Inertia GET version should return '409'.",
        );
        self::assertSame(
            Response::FORMAT_RAW,
            $response->format,
            'A conflict should return an empty raw response.',
        );
        self::assertSame(
            'https://example.test/dashboard',
            $response->getHeaders()->get('X-Inertia-Location'),
            'A conflict should target the current absolute URL.',
        );
        self::assertSame(
            'new-version',
            $response->getHeaders()->get('X-Inertia-Version'),
            'A conflict should expose the current version.',
        );
        self::assertFalse(
            $resolved,
            'Version conflicts should be decided before prop callbacks execute.',
        );
        self::assertSame(
            ['notice' => 'Keep me.'],
            Yii::$app->getSession()->getAllFlashes(false),
            'Version conflicts should preserve session flashes.',
        );
    }

    private function manager(): Manager
    {
        $manager = Yii::$app->get('inertia');

        self::assertInstanceOf(
            Manager::class,
            $manager,
            'The application should expose the Yii2 Inertia manager.',
        );

        return $manager;
    }
}

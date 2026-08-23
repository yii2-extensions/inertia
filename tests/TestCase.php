<?php

declare(strict_types=1);

namespace yii\inertia\tests;

use PHPForge\Inertia\Page;
use Yii;
use yii\helpers\Json;
use yii\inertia\tests\support\ApplicationFactory;
use yii\inertia\tests\support\stub\MockerFunctions;
use yii\web\Response;

use function preg_match;

/**
 * Base test case for inertia tests.
 *
 * @phpstan-type PagePayload array{
 *   component: string,
 *   props: array<string, mixed>,
 *   url: string,
 *   version: int|string,
 *   ...
 * }
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private const string PAGE_JSON_PATTERN = '/<script type="application\/json">(.*?)<\/script>/s';

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        Yii::getLogger()->flush();
    }

    /**
     * Destroys the current application.
     */
    protected function destroyApplication(): void
    {
        ApplicationFactory::destroy();
    }

    /**
     * Extracts a core page payload from an HTML or JSON response.
     *
     * @phpstan-return PagePayload
     */
    protected function extractPage(Response $response): array
    {
        if ($response->data instanceof Page) {
            return $this->validatePagePayload($response->data->toArray());
        }

        $content = (string) $response->content;

        self::assertMatchesRegularExpression(
            self::PAGE_JSON_PATTERN,
            $content,
            'HTML response should contain an inline JSON script with the page payload.',
        );

        $result = preg_match(self::PAGE_JSON_PATTERN, $content, $matches);

        self::assertSame(
            1,
            $result,
            'The root view should contain one page JSON script.',
        );

        $decoded = Json::decode($matches[1]);

        self::assertIsArray(
            $decoded,
            'The embedded page JSON should decode to an array.',
        );

        return $this->validatePagePayload($decoded);
    }

    /**
     * Populates `Yii::$app` with a new web application configured for Inertia tests.
     *
     * @param array $config Additional configuration to merge with the default application config.
     *
     * @phpstan-param array<string, mixed> $config
     */
    protected function mockWebApplication(array $config = []): void
    {
        ApplicationFactory::web($config);
    }

    /**
     * Populates `Yii::$app` with a web application that has no session component.
     *
     * @param array $config Additional configuration to merge with the default application config.
     *
     * @phpstan-param array<string, mixed> $config
     */
    protected function mockWebApplicationWithoutSession(array $config = []): void
    {
        ApplicationFactory::webWithoutSession($config);
    }

    /**
     * Marks the current request as an Inertia request.
     *
     * @param string $method HTTP method to set for the request (default: `GET`).
     */
    protected function prepareInertiaRequest(string $method = 'GET'): void
    {
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        Yii::$app->getRequest()->getHeaders()->set('X-Inertia', 'true');
        Yii::$app->getRequest()->getHeaders()->set('X-Requested-With', 'XMLHttpRequest');
    }

    /**
     * Sets the current absolute request URL for tests.
     *
     * @param string $url URL to set (for example, `/users/1`).
     */
    protected function setAbsoluteUrl(string $url): void
    {
        Yii::$app->getRequest()->setHostInfo('https://example.test');
        Yii::$app->getRequest()->setUrl($url);
    }

    protected function setUp(): void
    {
        parent::setUp();

        MockerFunctions::reset();

        $this->mockWebApplication();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->destroyApplication();
    }

    /**
     * @param array<array-key, mixed> $page
     *
     * @phpstan-assert PagePayload $page
     */
    private function assertPagePayload(array $page): void
    {
        self::assertArrayHasKey(
            'component',
            $page,
            'A page should contain its component.',
        );
        self::assertIsString(
            $page['component'],
            'The page component should be a string.',
        );
        self::assertArrayHasKey(
            'props',
            $page,
            'A page should contain props.',
        );
        self::assertIsArray(
            $page['props'],
            'Page props should be an array.',
        );

        foreach ($page['props'] as $key => $_) {
            self::assertIsString($key, 'Page prop keys should be strings.');
        }

        self::assertArrayHasKey(
            'url',
            $page,
            'A page should contain its relative URL.',
        );
        self::assertIsString(
            $page['url'],
            'The page URL should be a string.',
        );
        self::assertArrayHasKey(
            'version',
            $page,
            'A page should contain its asset version.',
        );
        self::assertTrue(
            is_int($page['version']) || is_string($page['version']),
            'The page version should be an integer or string.',
        );
    }

    /**
     * @param array<array-key, mixed> $page
     *
     * @phpstan-return PagePayload
     */
    private function validatePagePayload(array $page): array
    {
        $this->assertPagePayload($page);

        return $page;
    }
}

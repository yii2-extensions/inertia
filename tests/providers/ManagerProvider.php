<?php

declare(strict_types=1);

namespace yii\inertia\tests\providers;

/**
 * Data provider for {@see \yii\inertia\tests\ManagerTest}.
 */
final class ManagerProvider
{
    /**
     * @phpstan-return array<string, array{non-empty-string, non-empty-string, 303|409}>
     */
    public static function redirectReferences(): array
    {
        return [
            'relative path' => [
                'next',
                'https://example.test:8443/posts/next',
                303,
            ],
            'parent directory' => [
                '../next',
                'https://example.test:8443/posts/../next',
                303,
            ],
            'query only' => [
                '?page=2',
                'https://example.test:8443/posts/42?page=2',
                303,
            ],
            'fragment only' => [
                '#details',
                'https://example.test:8443/posts/42?tab=main#details',
                409,
            ],
            'relative path with query and fragment' => [
                'next?page=2#details',
                'https://example.test:8443/posts/next?page=2#details',
                409,
            ],
            'scheme relative' => [
                '//other.test/next',
                'https://other.test/next',
                303,
            ],
            'root relative' => [
                '/next',
                'https://example.test:8443/next',
                303,
            ],
            'absolute URL' => [
                'https://other.test/next',
                'https://other.test/next',
                303,
            ],
            'uppercase scheme' => [
                'HTTPS://other.test/next',
                'HTTPS://other.test/next',
                303,
            ],
        ];
    }
}

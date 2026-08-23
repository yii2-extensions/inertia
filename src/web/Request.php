<?php

declare(strict_types=1);

namespace yii\inertia\web;

use Override;
use Yii;

use function array_is_list;
use function count;
use function is_array;
use function is_string;

/**
 * Configures CSRF protection for Inertia applications using the cookie-to-header pattern.
 */
class Request extends \yii\web\Request
{
    /**
     * Cookie options. `httpOnly` is `false` so JavaScript can read the CSRF token.
     *
     * @var mixed[]
     */
    public $csrfCookie = ['httpOnly' => false];
    /**
     * Header checked for the CSRF token sent by Inertia's HTTP client.
     */
    public $csrfHeader = 'X-XSRF-TOKEN';
    /**
     * Cookie name and form parameter name for the CSRF token.
     */
    public $csrfParam = 'XSRF-TOKEN';

    /**
     * Returns the CSRF token sent via the `X-XSRF-TOKEN` header.
     *
     * @return string|null Masked CSRF token, or `null` when the header is absent or invalid.
     */
    #[Override]
    public function getCsrfTokenFromHeader(): string|null
    {
        $token = $this->headers->get($this->csrfHeader);

        if (!$this->enableCookieValidation) {
            return $token;
        }

        $data = Yii::$app->getSecurity()->validateData($token ?? '', $this->cookieValidationKey);

        if ($data === false) {
            return null;
        }

        $data = @unserialize($data, ['allowed_classes' => false]);

        if (!is_array($data) || !array_is_list($data) || count($data) !== 2) {
            return null;
        }

        [$parameter, $value] = $data;

        if ($parameter !== $this->csrfParam || !is_string($value)) {
            return null;
        }

        return Yii::$app->getSecurity()->maskToken($value);
    }
}

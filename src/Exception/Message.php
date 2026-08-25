<?php

declare(strict_types=1);

namespace yii\inertia\Exception;

use function sprintf;

/**
 * Defines exception message templates authored by the Yii2 Inertia adapter.
 *
 * Use {@see Message::getMessage()} to format a template with `sprintf()` arguments.
 */
enum Message: string
{
    /**
     * The configured Inertia application component is invalid.
     *
     * Format: "The \"inertia\" application component must be an instance of %s."
     */
    case APPLICATION_COMPONENT_INVALID = 'The "inertia" application component must be an instance of %s.';

    /**
     * The validation-error flash is not an array.
     *
     * Format: "The Inertia error flash must be an array."
     */
    case ERROR_FLASH_INVALID = 'The Inertia error flash must be an array.';

    /**
     * A validation-error key is not a string.
     *
     * Format: "Inertia error keys must be strings."
     */
    case ERROR_KEY_INVALID = 'Inertia error keys must be strings.';

    /**
     * A validation-error message list contains a non-string value.
     *
     * Format: "Inertia error message lists must contain only strings."
     */
    case ERROR_MESSAGE_LIST_INVALID = 'Inertia error message lists must contain only strings.';

    /**
     * A validation-error value has an unsupported structure.
     *
     * Format: "Inertia errors must contain strings or lists of strings."
     */
    case ERROR_VALUE_INVALID = 'Inertia errors must contain strings or lists of strings.';

    /**
     * A session flash key is not a string.
     *
     * Format: "Inertia session flash keys must be strings."
     */
    case SESSION_FLASH_KEY_INVALID = 'Inertia session flash keys must be strings.';

    /**
     * Formats the message template with the supplied arguments.
     *
     * @param int|string ...$argument Values inserted into the template.
     *
     * @return string Formatted exception message.
     */
    public function getMessage(int|string ...$argument): string
    {
        return sprintf($this->value, ...$argument);
    }
}

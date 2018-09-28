<?php

namespace App\Services;

use App\Model\Task\TypeInterface;

class TaskTypeValidator
{
    /**
     * @var string[]
     */
    private static $validTypes = [
        TypeInterface::TYPE_HTML_VALIDATION,
        TypeInterface::TYPE_CSS_VALIDATION,
        TypeInterface::TYPE_URL_DISCOVERY,
        TypeInterface::TYPE_LINK_INTEGRITY,
    ];

    public static function isValid(string $type): bool
    {
        return in_array(strtolower($type), self::$validTypes);
    }
}

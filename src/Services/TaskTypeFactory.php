<?php

namespace App\Services;

use App\Model\Task\Type;

class TaskTypeFactory
{
    public function create(string $name): ?Type
    {
        if (!TaskTypeValidator::isValid($name)) {
            return null;
        }

        return new Type(strtolower($name));
    }
}

<?php

namespace App\Tests\Services;

class ObjectPropertySetter
{
    /**
     * @param object $object
     * @param string $objectClass
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    public static function setProperty(
        $object,
        string $objectClass,
        string $propertyName,
        $propertyValue
    ) {
        try {
            $reflector = new \ReflectionClass($objectClass);
            $property = $reflector->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($object, $propertyValue);
        } catch (\ReflectionException $exception) {
        }
    }
}

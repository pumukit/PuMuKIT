<?php

declare(strict_types=1);

namespace Pumukit\Tests\PHPStan;

use Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractField;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

final class OdmMappedPropertiesExtension implements ReadWritePropertiesExtension
{
    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        return $this->isMapped($property, $propertyName);
    }

    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        return $this->isMapped($property, $propertyName);
    }

    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    private function isMapped(PropertyReflection $property, string $propertyName): bool
    {
        $class = $property->getDeclaringClass()->getNativeReflection();
        if (!$class->hasProperty($propertyName)) {
            return false;
        }

        return [] !== $class->getProperty($propertyName)->getAttributes(AbstractField::class, \ReflectionAttribute::IS_INSTANCEOF);
    }
}

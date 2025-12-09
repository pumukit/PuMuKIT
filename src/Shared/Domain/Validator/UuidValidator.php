<?php

declare(strict_types=1);

namespace App\Shared\Domain\Validator;

final class UuidValidator
{
    private const MONGODB_OBJECT_ID_PATTERN = '/^[a-f0-9]{24}$/i';

    public static function isValid(string $id): bool
    {
        return 1 === preg_match(self::MONGODB_OBJECT_ID_PATTERN, $id);
    }

    public static function validate(string $id, string $fieldName = 'ID'): void
    {
        if (empty($id)) {
            throw new \InvalidArgumentException(
                sprintf('%s cannot be empty', $fieldName)
            );
        }

        if (!self::isValid($id)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid %s format: %s. Expected 24 hexadecimal characters.', $fieldName, $id)
            );
        }
    }

    public static function validateArray(array $ids, string $fieldName = 'ID'): void
    {
        if (empty($ids)) {
            throw new \InvalidArgumentException(
                sprintf('%s array cannot be empty', $fieldName)
            );
        }

        foreach ($ids as $id) {
            if (!is_string($id) || empty($id)) {
                throw new \InvalidArgumentException(
                    sprintf('All %s must be non-empty strings', $fieldName)
                );
            }

            if (!self::isValid($id)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid %s format: %s. Expected 24 hexadecimal characters.', $fieldName, $id)
                );
            }
        }
    }
}

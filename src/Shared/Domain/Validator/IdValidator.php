<?php

declare(strict_types=1);

namespace App\Shared\Domain\Validator;

/**
 * Validates MongoDB ObjectId format
 * Centralizes ID validation logic to avoid repetition across validators.
 */
final class IdValidator
{
    private const MONGODB_OBJECTID_PATTERN = '/^[a-f0-9]{24}$/i';

    /**
     * Validates if a string is a valid MongoDB ObjectId format (24 hex characters).
     *
     * @param string $id The ID to validate
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $id): bool
    {
        return 1 === preg_match(self::MONGODB_OBJECTID_PATTERN, $id);
    }

    /**
     * Validates a single ID and throws exception if invalid.
     *
     * @param string $id        The ID to validate
     * @param string $fieldName The field name for error message (default: 'ID')
     *
     * @throws \InvalidArgumentException If ID is invalid
     */
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

    /**
     * Validates an array of IDs and throws exception if any is invalid.
     *
     * @param array  $ids       Array of IDs to validate
     * @param string $fieldName The field name for error message (default: 'ID')
     *
     * @throws \InvalidArgumentException If any ID is invalid
     */
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

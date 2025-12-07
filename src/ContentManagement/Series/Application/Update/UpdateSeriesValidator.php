<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Update;

use App\Shared\Domain\Validator\IdValidator;

final class UpdateSeriesValidator
{
    public static function validate(UpdateSeriesRequest $request): void
    {
        IdValidator::validate($request->id, 'Series ID');

        if (null !== $request->title) {
            if (!is_array($request->title)) {
                throw new \InvalidArgumentException('Title must be an array');
            }

            if (empty($request->title)) {
                throw new \InvalidArgumentException('Title array cannot be empty');
            }

            foreach ($request->title as $locale => $value) {
                if (!is_string($locale)) {
                    throw new \InvalidArgumentException('Title keys must be locale strings');
                }
                if (!is_string($value)) {
                    throw new \InvalidArgumentException('Title values must be strings');
                }
            }
        }

        if (null !== $request->subtitle) {
            if (!is_array($request->subtitle)) {
                throw new \InvalidArgumentException('Subtitle must be an array');
            }

            foreach ($request->subtitle as $locale => $value) {
                if (!is_string($locale)) {
                    throw new \InvalidArgumentException('Subtitle keys must be locale strings');
                }
                if (!is_string($value)) {
                    throw new \InvalidArgumentException('Subtitle values must be strings');
                }
            }
        }

        if (null !== $request->description) {
            if (!is_array($request->description)) {
                throw new \InvalidArgumentException('Description must be an array');
            }

            foreach ($request->description as $locale => $value) {
                if (!is_string($locale)) {
                    throw new \InvalidArgumentException('Description keys must be locale strings');
                }
                if (!is_string($value)) {
                    throw new \InvalidArgumentException('Description values must be strings');
                }
            }
        }

        if (null !== $request->header) {
            if (!is_array($request->header)) {
                throw new \InvalidArgumentException('Header must be an array');
            }

            foreach ($request->header as $locale => $value) {
                if (!is_string($locale)) {
                    throw new \InvalidArgumentException('Header keys must be locale strings');
                }
                if (!is_string($value)) {
                    throw new \InvalidArgumentException('Header values must be strings');
                }
            }
        }

        if (null !== $request->footer) {
            if (!is_array($request->footer)) {
                throw new \InvalidArgumentException('Footer must be an array');
            }

            foreach ($request->footer as $locale => $value) {
                if (!is_string($locale)) {
                    throw new \InvalidArgumentException('Footer keys must be locale strings');
                }
                if (!is_string($value)) {
                    throw new \InvalidArgumentException('Footer values must be strings');
                }
            }
        }

        if (null !== $request->keywords) {
            if (!is_array($request->keywords)) {
                throw new \InvalidArgumentException('Keywords must be an array');
            }

            foreach ($request->keywords as $locale => $value) {
                if (!is_string($locale)) {
                    throw new \InvalidArgumentException('Keywords keys must be locale strings');
                }
                if (!is_string($value)) {
                    throw new \InvalidArgumentException('Keywords values must be strings');
                }
            }
        }

        if (null !== $request->sorting) {
            $validSortings = [0, 1, 2, 3, 4, 5];
            if (!in_array($request->sorting, $validSortings, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid sorting value: %d. Must be between 0 and 5', $request->sorting)
                );
            }
        }

        if (null !== $request->seriesTypeId) {
            IdValidator::validate($request->seriesTypeId, 'Series Type ID');
        }

        if (null !== $request->seriesStyleId) {
            IdValidator::validate($request->seriesStyleId, 'Series Style ID');
        }

        if (null !== $request->properties) {
            if (!is_array($request->properties)) {
                throw new \InvalidArgumentException('Properties must be an array');
            }
        }
    }
}

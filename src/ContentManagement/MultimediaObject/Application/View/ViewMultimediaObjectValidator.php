<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Application\View;

use App\Shared\Domain\Validator\IdValidator;

final class ViewMultimediaObjectValidator
{
    public static function validate(ViewMultimediaObjectRequest $request): void
    {
        IdValidator::validate($request->id, 'MultimediaObject ID');

        $validTabs = ['general', 'media', 'metadata', 'owners', 'people', 'publication', 'tags'];

        if (!in_array($request->tab, $validTabs, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid tab "%s". Valid tabs are: %s', $request->tab, implode(', ', $validTabs))
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\MultimediaObject\Application\View;

final class ViewMultimediaObjectValidator
{
    public static function validate(ViewMultimediaObjectRequest $request): void
    {
        if (empty($request->id)) {
            throw new \InvalidArgumentException('MultimediaObject ID cannot be empty');
        }

        if (!preg_match('/^[a-f0-9]{24}$/i', $request->id)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid MultimediaObject ID format: %s', $request->id)
            );
        }

        $validTabs = ['general', 'media', 'metadata', 'owners', 'people', 'publication', 'tags'];
        if (!in_array($request->tab, $validTabs, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid tab "%s". Valid tabs are: %s', $request->tab, implode(', ', $validTabs))
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

final class i18nText
{
    private $text;

    private function __construct(array $text)
    {
        $this->text = $text;
    }

    public function toArray(): array
    {
        return $this->all();
    }

    public static function create(array $text): i18nText
    {
        return new self($text);
    }

    public function textFromLocale(string $locale = 'en'): string
    {
        if (isset($this->text[$locale])) {
            return $this->text[$locale];
        }

        return '';
    }

    public function all(): array
    {
        return $this->text;
    }
}

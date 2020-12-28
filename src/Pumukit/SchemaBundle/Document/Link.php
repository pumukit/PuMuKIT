<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
class Link extends Element
{
    /**
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    public function setName($name, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }
        $this->name[$locale] = $name;
    }

    public function getName($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        return $this->name[$locale] ?? '';
    }

    public function setI18nName(array $name): void
    {
        $this->name = $name;
    }

    public function getI18nName(): array
    {
        return $this->name;
    }
}

<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 */
class Material extends Element
{
    /**
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * @MongoDB\Field(type="string")
     */
    private $language;

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

    public function setLanguage($language): void
    {
        $this->language = $language;
    }

    public function getLanguage(): string
    {
        return $this->language ?? '';
    }
}

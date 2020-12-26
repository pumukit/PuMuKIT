<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\RoleRepository")
 */
class Role implements RoleInterface
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\UniqueIndex()
     */
    private $cod = '0';

    /**
     * @MongoDB\Field(type="int")
     * @MongoDB\Index
     * @Gedmo\SortablePosition
     */
    private $rank;

    /**
     * @MongoDB\Field(type="int", strategy="increment" )
     */
    private $number_people_in_multimedia_object = 0;

    /**
     * @MongoDB\Field(type="string")
     */
    private $xml;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $display = true;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $readOnly = false;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $text = ['en' => ''];

    /** @var string */
    private $locale = 'en';

    public function __toString(): string
    {
        return $this->getCod();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCod(string $code): void
    {
        $this->cod = $code;
    }

    public function getCod(): string
    {
        return $this->cod;
    }

    public function setRank(int $rank): void
    {
        $this->rank = $rank;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setXml(string $xml): void
    {
        $this->xml = $xml;
    }

    public function getXml(): ?string
    {
        return $this->xml;
    }

    public function setDisplay(bool $display): void
    {
        $this->display = $display;
    }

    public function getDisplay(): bool
    {
        return $this->display;
    }

    public function setReadOnly(bool $readOnly): void
    {
        $this->readOnly = $readOnly;
    }

    public function getReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function setName(string $name, ?string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    public function getName(?string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
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

    public function setText(string $text, ?string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->text[$locale] = $text;
    }

    public function getText(?string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->text[$locale] ?? '';
    }

    public function setI18nText(array $text): void
    {
        $this->text = $text;
    }

    public function getI18nText(): array
    {
        return $this->text;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function increaseNumberPeopleInMultimediaObject(): void
    {
        ++$this->number_people_in_multimedia_object;
    }

    public function decreaseNumberPeopleInMultimediaObject(): void
    {
        --$this->number_people_in_multimedia_object;
    }

    public function getNumberPeopleInMultimediaObject(): int
    {
        return $this->number_people_in_multimedia_object;
    }

    public function setNumberPeopleInMultimediaObject(int $number_people_in_multimedia_object): void
    {
        $this->number_people_in_multimedia_object = $number_people_in_multimedia_object;
    }

    public function cloneResource(): Role
    {
        $aux = clone $this;
        $aux->id = null;
        $aux->rank = null;

        return $aux;
    }
}

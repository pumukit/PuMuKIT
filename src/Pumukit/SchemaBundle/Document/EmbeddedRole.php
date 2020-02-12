<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument()
 */
class EmbeddedRole implements RoleInterface
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $cod = '0';

    /**
     * @MongoDB\Field(type="string")
     */
    private $xml;

    /**
     * @MongoDB\Field(type="boolean")
     */
    private $display = true;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $text = ['en' => ''];

    /**
     * @MongoDB\EmbedMany(targetDocument=EmbeddedPerson::class)
     */
    private $people;

    /** @var string */
    private $locale = 'en';

    public function __construct(RoleInterface $role)
    {
        if (null !== $role) {
            $this->id = $role->getId();
            $this->cod = $role->getCod();
            $this->xml = $role->getXml();
            $this->display = $role->getDisplay();
            $this->setI18nName($role->getI18nName());
            $this->setI18nText($role->getI18nText());
        }
        $this->people = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCod();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCod(string $cod): void
    {
        $this->cod = $cod;
    }

    public function getCod(): string
    {
        return $this->cod;
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

    public function setName(string $name, ?string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    public function getName(string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->name[$locale])) {
            return '';
        }

        return $this->name[$locale];
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
        if (!isset($this->text[$locale])) {
            return '';
        }

        return $this->text[$locale];
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

    public function getPeople()
    {
        return $this->people;
    }

    public function addPerson($person): void
    {
        if (!($this->containsPerson($person))) {
            $this->people[] = $this->createEmbeddedPerson($person);
        }
    }

    public function removePerson($person): bool
    {
        $embeddedPerson = $this->getEmbeddedPerson($person);

        $aux = $this->people->filter(static function ($i) use ($embeddedPerson) {
            return $i->getId() !== $embeddedPerson->getId();
        });

        $hasRemoved = (count($aux) !== count($this->people));

        $this->people = $aux;

        return $hasRemoved;
    }

    public function containsPerson(PersonInterface $person): bool
    {
        foreach ($this->people as $embeddedPerson) {
            if ($person->getId() === $embeddedPerson->getId()) {
                return true;
            }
        }

        return false;
    }

    public function containsAllPeople(array $people): bool
    {
        foreach ($people as $person) {
            if (!($this->containsPerson($person))) {
                return false;
            }
        }

        return true;
    }

    public function containsAnyPerson(array $people): bool
    {
        foreach ($people as $person) {
            if (!($this->containsPerson($person))) {
                return true;
            }
        }

        return false;
    }

    public function createEmbeddedPerson($person): EmbeddedPerson
    {
        if ($person instanceof EmbeddedPerson) {
            return $person;
        }

        return new EmbeddedPerson($person);
    }

    public function getEmbeddedPerson(PersonInterface $person)
    {
        foreach ($this->people as $embeddedPerson) {
            if ($person->getId() === $embeddedPerson->getId()) {
                return $embeddedPerson;
            }
        }

        return false;
    }
}

<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedRole.
 *
 * @MongoDB\EmbeddedDocument()
 */
class EmbeddedRole
{
    /**
     * @var string
     *
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $cod = '0';

    /**
     * See European Broadcasting Union Role Codes.
     *
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $xml;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean")
     */
    private $display = true;

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    private $text = ['en' => ''];

    /**
     * @var ArrayCollection
     *
     * @MongoDB\EmbedMany(targetDocument="EmbeddedPerson")
     */
    private $people;

    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cod.
     *
     * @param string $cod
     */
    public function setCod($cod)
    {
        $this->cod = $cod;
    }

    /**
     * Get cod.
     *
     * @return string
     */
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * Set xml.
     *
     * @param string $xml
     */
    public function setXml($xml)
    {
        $this->xml = $xml;
    }

    /**
     * Get xml.
     *
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Set display.
     *
     * @param bool $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

    /**
     * Get display.
     *
     * @return bool
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set name.
     *
     * @param string      $name
     * @param string|null $locale
     */
    public function setName($name, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    /**
     * Get name.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getName($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->name[$locale])) {
            return '';
        }

        return $this->name[$locale];
    }

    /**
     * Set I18n name.
     *
     * @param array $name
     */
    public function setI18nName(array $name)
    {
        $this->name = $name;
    }

    /**
     * Get i18n name.
     *
     * @return array
     */
    public function getI18nName()
    {
        return $this->name;
    }

    /**
     * Set text.
     *
     * @param string      $text
     * @param string|null $locale
     */
    public function setText($text, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->text[$locale] = $text;
    }

    /**
     * Get text.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getText($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->text[$locale])) {
            return '';
        }

        return $this->text[$locale];
    }

    /**
     * Set I18n text.
     *
     * @param array $text
     */
    public function setI18nText(array $text)
    {
        $this->text = $text;
    }

    /**
     * Get i18n text.
     *
     * @return array
     */
    public function getI18nText()
    {
        return $this->text;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Constructor.
     */
    public function __construct(Role $role)
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

    /**
     * Get people.
     */
    public function getPeople()
    {
        return $this->people;
    }

    /**
     * Add person.
     *
     * @param Person|EmbeddedPerson $person
     */
    public function addPerson($person)
    {
        if (!($this->containsPerson($person))) {
            $this->people[] = $this->createEmbeddedPerson($person);
        }
    }

    /**
     * Remove person.
     *
     * @param Person|EmbeddedPerson $person
     *
     * @return bool TRUE if this embedded person contained the specified person, FLASE otherwise
     */
    public function removePerson($person)
    {
        $embeddedPerson = $this->getEmbeddedPerson($person);

        $aux = $this->people->filter(function ($i) use ($embeddedPerson) {
            return $i->getId() !== $embeddedPerson->getId();
        });

        $hasRemoved = (count($aux) !== count($this->people));

        $this->people = $aux;

        return $hasRemoved;
    }

    /**
     * Contains person.
     *
     * @param Person|EmbeddedPerson $person
     *
     * @return EmbeddedPerson|bool EmbeddedPerson if found, FALSE otherwise
     */
    public function containsPerson($person)
    {
        foreach ($this->people as $embeddedPerson) {
            if ($person->getId() === $embeddedPerson->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contains all people.
     *
     * @param array $people
     *
     * @return bool TRUE if this embedded role contains all people, FLASE otherwise
     */
    public function containsAllPeople(array $people)
    {
        foreach ($people as $person) {
            if (!($this->containsPerson($person))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Contains any person.
     *
     * @param array $people
     *
     * @return bool TRUE if this embedded person contains any person of the list, FLASE otherwise
     */
    public function containsAnyPerson(array $people)
    {
        foreach ($people as $person) {
            if (!($this->containsPerson($person))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create embedded person.
     *
     * @param EmbeddedPerson|Person $person
     *
     * @return EmbeddedPerson
     */
    public function createEmbeddedPerson($person)
    {
        if ($person instanceof EmbeddedPerson) {
            return $person;
        } elseif ($person instanceof Person) {
            $embedPerson = new EmbeddedPerson($person);

            return $embedPerson;
        }

        throw new \InvalidArgumentException('Only Person or EmbeddedPerson are allowed.');
    }

    /**
     * Contained embed person.
     *
     * @param Person|EmbeddedPerson $person
     *
     * @return EmbeddedPerson|bool EmbeddedPerson if found, FALSE otherwise:
     */
    public function getEmbeddedPerson($person)
    {
        foreach ($this->people as $embeddedPerson) {
            if ($person->getId() === $embeddedPerson->getId()) {
                return $embeddedPerson;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getCod();
    }
}

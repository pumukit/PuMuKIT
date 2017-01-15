<?php

namespace Pumukit\TemplateBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Template.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\TemplateBundle\Repository\TemplateRepository")
 */
class Template
{
    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var bool
     *
     * @MongoDB\Boolean
     */
    private $hide = false;

    /**
     * @var date
     *
     * @MongoDB\Date
     */
    private $createdAt;

    /**
     * @var date
     *
     * @MongoDB\Date
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @MongoDB\String
     * @MongoDB\UniqueIndex(order="asc")
     */
    private $name = '';

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $text = array('en' => '');

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var locale
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->createdAt = new \Datetime('now');
        $this->updatedAt = new \Datetime('now');
    }

    /**
     * Get id.
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set hide.
     *
     * @param bool $hide
     */
    public function setHide($hide)
    {
        $this->hide = $hide;
    }

    /**
     * Get hide.
     *
     * @return bool
     */
    public function getHide()
    {
        return $this->hide;
    }

    /**
     * Get hide.
     *
     * @return bool
     */
    public function isHide()
    {
        return $this->hide;
    }

    /**
     * Set createdAt.
     *
     * @param date $createdAt
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param date $updatedAt
     *
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return date $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string $name
     */
    public function getName()
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
        if ($locale == null) {
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
        if ($locale == null) {
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
}

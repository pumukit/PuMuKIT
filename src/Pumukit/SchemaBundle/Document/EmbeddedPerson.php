<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedPerson.
 *
 * @MongoDB\EmbeddedDocument()
 */
class EmbeddedPerson
{
    use Traits\Properties;

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
    protected $name;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @Assert\Email
     * //@Assert\NotEmpty
     */
    protected $email;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * //@Assert\Url('http', 'https', 'ftp')
     */
    protected $web;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $phone;

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    protected $honorific = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    protected $firm = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    protected $post = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    protected $bio = ['en' => ''];

    /**
     * Locale.
     *
     * @var string
     */
    protected $locale = 'en';

    /**
     * Construct.
     */
    public function __construct(Person $person)
    {
        if (null !== $person) {
            $this->id = $person->getId();
            $this->name = $person->getName();
            $this->email = $person->getEmail();
            $this->web = $person->getWeb();
            $this->phone = $person->getPhone();
            $this->setI18nHonorific($person->getI18nHonorific());
            $this->setI18nFirm($person->getI18nFirm());
            $this->setI18nPost($person->getI18nPost());
            $this->setI18nBio($person->getI18nBio());
        }
    }

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
     * Set name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set email.
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set web.
     *
     * @param string $web
     */
    public function setWeb($web)
    {
        $this->web = $web;
    }

    /**
     * Get web.
     *
     * @return string
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set honorific.
     *
     * @param string      $honorific
     * @param string|null $locale
     */
    public function setHonorific($honorific, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->honorific[$locale] = $honorific;
    }

    /**
     * Get honorific.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getHonorific($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->honorific[$locale])) {
            return '';
        }

        return $this->honorific[$locale];
    }

    /**
     * Set i18n honorific.
     */
    public function setI18nHonorific(array $honorific)
    {
        $this->honorific = $honorific;
    }

    /**
     * Get i18n honorific.
     */
    public function getI18nHonorific()
    {
        return $this->honorific;
    }

    /**
     * Set firm.
     *
     * @param string      $firm
     * @param string|null $locale
     */
    public function setFirm($firm, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->firm[$locale] = $firm;
    }

    /**
     * Get firm.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getFirm($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->firm[$locale])) {
            return '';
        }

        return $this->firm[$locale];
    }

    /**
     * Set i18n firm.
     */
    public function setI18nFirm(array $firm)
    {
        $this->firm = $firm;
    }

    /**
     * Get i18n firm.
     */
    public function getI18nFirm()
    {
        return $this->firm;
    }

    /**
     * Set post.
     *
     * @param string      $post
     * @param string|null $locale
     */
    public function setPost($post, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->post[$locale] = $post;
    }

    /**
     * Get post.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getPost($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->post[$locale])) {
            return '';
        }

        return $this->post[$locale];
    }

    /**
     * Set i18n post.
     */
    public function setI18nPost(array $post)
    {
        $this->post = $post;
    }

    /**
     * Get i18n post.
     */
    public function getI18nPost()
    {
        return $this->post;
    }

    /**
     * Set bio.
     *
     * @param string      $bio
     * @param string|null $locale
     */
    public function setBio($bio, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->bio[$locale] = $bio;
    }

    /**
     * Get bio.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getBio($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->bio[$locale])) {
            return '';
        }

        return $this->bio[$locale];
    }

    /**
     * Set i18n bio.
     */
    public function setI18nBio(array $bio)
    {
        $this->bio = $bio;
    }

    /**
     * Get i18n bio.
     */
    public function getI18nBio()
    {
        return $this->bio;
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
     * Get honorific name.
     *
     * Returns person name with his/her honorific
     *
     * @return string
     */
    public function getHName()
    {
        return $this->getHonorific().' '.$this->getName();
    }

    /**
     * Get Other.
     *
     * Returns strings with person position
     *
     * @return string
     */
    public function getOther()
    {
        return $this->getPost().' '.$this->getFirm().' '.$this->getBio();
    }

    /**
     * Get info.
     *
     * Returns strings with person info:
     * Firm, Post and Bio separated by commas
     * or without Bio if param is false
     *
     * @param bool $withBio
     *
     * @return string
     */
    public function getInfo($withBio = true)
    {
        $aux = $withBio ?
             [$this->getPost(), $this->getFirm(), $this->getBio()] :
             [$this->getPost(), $this->getFirm()];
        $aux = array_filter($aux, function ($a) {
            return null !== $a && ('' != $a);
        });

        return implode(', ', $aux);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}

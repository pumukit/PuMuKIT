<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\PersonRepository")
 * @ApiResource(
 *           collectionOperations={"get"={"method"="GET", "access_control"="is_granted('ROLE_ACCESS_API')"}},
 *           itemOperations={"get"={"method"="GET", "access_control"="is_granted('ROLE_ACCESS_API')"}}
 *       )
 */
class Person implements PersonInterface
{
    use Traits\Properties;

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     *
     * @Assert\Email
     */
    protected $email;

    /**
     * @MongoDB\Field(type="string")
     * //@Assert\Url('http', 'https', 'ftp')
     */
    protected $web;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $phone;

    /**
     * @MongoDB\Field(type="raw")
     */
    protected $honorific = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    protected $firm = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    protected $post = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    protected $bio = ['en' => ''];

    /**
     * Used locale to override Translation listener`s locale this is not a mapped field of entity metadata, just a simple property.
     */
    protected $locale = 'en';

    /**
     * @MongoDB\ReferenceOne(targetDocument=User::class, inversedBy="person", storeAs="id", cascade={"persist"})
     */
    private $user;

    public function __toString(): string
    {
        if ($this->getName()) {
            return $this->getName() ?? '';
        }

        return '';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setWeb(?string $web): void
    {
        $this->web = $web;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setHonorific(?string $honorific, string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->honorific[$locale] = $honorific;
    }

    public function getHonorific(string $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->honorific[$locale] ?? '';
    }

    public function setI18nHonorific(array $honorific): void
    {
        $this->honorific = $honorific;
    }

    public function getI18nHonorific(): array
    {
        return $this->honorific;
    }

    public function setFirm(?string $firm, string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->firm[$locale] = $firm;
    }

    public function getFirm(string $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->firm[$locale] ?? '';
    }

    public function setI18nFirm(array $firm): void
    {
        $this->firm = $firm;
    }

    public function getI18nFirm(): array
    {
        return $this->firm;
    }

    public function setPost(?string $post, string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->post[$locale] = $post;
    }

    public function getPost(string $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->post[$locale] ?? '';
    }

    public function setI18nPost(array $post): void
    {
        $this->post = $post;
    }

    public function getI18nPost(): array
    {
        return $this->post;
    }

    public function setBio(?string $bio, string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->bio[$locale] = $bio;
    }

    public function getBio(string $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->bio[$locale] ?? '';
    }

    public function setI18nBio(array $bio): void
    {
        $this->bio = $bio;
    }

    public function getI18nBio(): array
    {
        return $this->bio;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getHName(): string
    {
        return $this->getHonorific().' '.$this->getName();
    }

    public function getOther(): string
    {
        return $this->getPost().' '.$this->getFirm().' '.$this->getBio();
    }

    public function getInfo(bool $withBio = true): string
    {
        $aux = $withBio ?
             [$this->getPost(), $this->getFirm(), $this->getBio()] :
             [$this->getPost(), $this->getFirm()];
        $aux = array_filter($aux, static function ($a) {
            return null !== $a && ('' !== $a);
        });

        return implode(', ', $aux);
    }

    public function cloneResource(): PersonInterface
    {
        $aux = clone $this;
        $aux->id = null;

        return $aux;
    }
}

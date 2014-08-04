<?php

namespace Pumukit\SchemaBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\SchemaBundle\Entity\Person
 *
 * @ORM\Table(name="person")
 * @ORM\Entity(repositoryClass="Pumukit\SchemaBundle\Entity\PersonRepository")
 */
class Person
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $login
     *
     * @ORM\Column(name="login", type="string", length=100, nullable=true)
     */
    private $login;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=100, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3")
     *
     */
    private $password;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=30, nullable=true)
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string $web
     *
     * @ORM\Column(name="web", type="string", length=150, nullable=true)
     * @Assert\Url()
     */
    private $web;

    /**
     * @var string $phone
     *
     * @ORM\Column(name="phone", type="string", length=30, nullable=true)
     */
    private $phone;

    /**
     * @var string $honorific
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="honorific", type="string", length=20, nullable=true)
     */
    private $honorific;

    /**
     * @var string $firm
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="firm", type="string", length=255, nullable=true)
     */
    private $firm;

    /**
     * @var string $post
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="post", type="string", length=255, nullable=true)
     */
    private $post;

    /**
     * @var string $bio
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="bio", type="string", length=255, nullable=true)
     */
    private $bio;

    /**
     * @var ArrayCollection $person_in_multimedia_object
     *
     * @ORM\OneToMany(targetEntity="PersonInMultimediaObject", mappedBy="person", cascade={"remove"})
     */
    private $people_in_multimedia_object;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set login
     *
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set web
     *
     * @param string $web
     */
    public function setWeb($web)
    {
        $this->web = $web;
    }

    /**
     * Get web
     *
     * @return string
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * Set phone
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set honorific
     *
     * @param string $honorific
     */
    public function setHonorific($honorific)
    {
        $this->honorific = $honorific;
    }

    /**
     * Get honorific
     *
     * @return string
     */
    public function getHonorific()
    {
        return $this->honorific;
    }

    /**
     * Set firm
     *
     * @param string $firm
     */
    public function setFirm($firm)
    {
        $this->firm = $firm;
    }

    /**
     * Get firm
     *
     * @return string
     */
    public function getFirm()
    {
        return $this->firm;
    }

    /**
     * Set post
     *
     * @param string $post
     */
    public function setPost($post)
    {
        $this->post = $post;
    }

    /**
     * Get post
     *
     * @return string
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set bio
     *
     * @param string $bio
     */
    public function setBio($bio)
    {
        $this->bio = $bio;
    }

    /**
     * Get bio
     *
     * @return string
     */
    public function getBio()
    {
        return $this->bio;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->people_in_multimedia_object = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add people_in_multimedia_object
     *
     * @param  \Pumukit\SchemaBundle\Entity\PersonInMultimediaObject $peopleInMultimediaObject
     * @return Person
     */
    public function addPeopleInMultimediaObject(\Pumukit\SchemaBundle\Entity\PersonInMultimediaObject $peopleInMultimediaObject)
    {
        $this->people_in_multimedia_object[] = $peopleInMultimediaObject;

        return $this;
    }

    /**
     * Remove people_in_multimedia_object
     *
     * @param \Pumukit\SchemaBundle\Entity\PersonInMultimediaObject $peopleInMultimediaObject
     */
    public function removePeopleInMultimediaObject(\Pumukit\SchemaBundle\Entity\PersonInMultimediaObject $peopleInMultimediaObject)
    {
        $this->people_in_multimedia_object->removeElement($peopleInMultimediaObject);
    }

    /**
     * Get people_in_multimedia_object
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPeopleInMultimediaObject()
    {
        return $this->people_in_multimedia_object;
    }
}

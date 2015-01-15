<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedPerson
 *
 * @MongoDB\EmbeddedDocument()
 */
class EmbeddedPerson
{
    /**
     * @var string $id
     *
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @var string $name
     *
     * @MongoDB\String
     */
    protected $name;
    
    /**
     * @var string $email
     *
     * @MongoDB\String
     * @Assert\Email
     * //@Assert\NotEmpty
     */
    protected $email;
    
    /**
     * @var string $web
     *
     * @MongoDB\String
     * //@Assert\Url('http', 'https', 'ftp')
     */
    protected $web;
    
    /**
     * @var string $phone
     *
     * @MongoDB\String
     */
    protected $phone;
    
    /**
     * @var string $honorific
     *
     * @MongoDB\Raw
     */
    protected $honorific = array('en' => '');
    
    /**
     * @var string $firm
     *
     * @MongoDB\Raw
     */
    protected $firm = array('en' => '');
    
    /**
     * @var string $post
     *
     * @MongoDB\Raw
     */
    protected $post = array('en' => '');
    
    /**
     * @var string $bio
     *
     * @MongoDB\Raw
     */
    protected $bio = array('en' => '');
    
    /**
     * @var ArrayCollection $roles
     *
     * @MongoDB\EmbedMany(targetDocument="EmbeddedRole")
     */
    protected $roles;
    
    /**
     * Locale
     * @var locale $locale
     */
    protected $locale = 'en';
    
    /**
     * Construct
     */
    public function __construct(Person $person)
    {
        if (null !== $person){
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
        $this->roles = new ArrayCollection();
    }
    
    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
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
    public function setHonorific($honorific, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->honorific[$locale] = $honorific;
    }
    
    /**
     * Get honorific
     *
     * @return string
     */
    public function getHonorific($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->honorific[$locale])) {
            return;
        }
        
        return $this->honorific[$locale];
    }
    
    /**
     * Set i18n honorific
     */
    public function setI18nHonorific(array $honorific)
    {
        $this->honorific = $honorific;
    }
    
    /**
     * Get i18n honorific
     */
    public function getI18nHonorific()
    {
        return $this->honorific;
    }
    
    /**
     * Set firm
     *
     * @param string $firm
     */
    public function setFirm($firm, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->firm[$locale] = $firm;
    }
    
    /**
     * Get firm
     *
     * @return string
     */
    public function getFirm($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->firm[$locale])) {
            return;
        }
        
        return $this->firm[$locale];
    }
    
    /**
     * Set i18n firm
     */
    public function setI18nFirm(array $firm)
    {
        $this->firm = $firm;
    }
    
    /**
     * Get i18n firm
     */
    public function getI18nFirm()
    {
        return $this->firm;
    }
    
    /**
     * Set post
     *
     * @param string $post
     */
    public function setPost($post, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->post[$locale] = $post;
    }
    
    /**
     * Get post
     *
     * @return string
     */
    public function getPost($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->post[$locale])) {
            return;
        }
        
        return $this->post[$locale];
    }
    
    /**
     * Set i18n post
     */
    public function setI18nPost(array $post)
    {
        $this->post = $post;
    }
    
    /**
     * Get i18n post
     */
    public function getI18nPost()
    {
        return $this->post;
    }
    
    /**
     * Set bio
     *
     * @param string $bio
     */
    public function setBio($bio, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->bio[$locale] = $bio;
    }
    
    /**
     * Get bio
     *
     * @return string
     */
    public function getBio($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->bio[$locale])) {
            return;
        }
        
        return $this->bio[$locale];
    }
    
    /**
     * Set i18n bio
     */
    public function setI18nBio(array $bio)
    {
        $this->bio = $bio;
    }
    
    /**
     * Get i18n bio
     */
    public function getI18nBio()
    {
        return $this->bio;
    }
    
    /**
     * Get roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get embedded role
     *
     * @param Role|EmbeddedRole $role
     * @return EmbeddedRole|boolean EmbeddedRole if found, FALSE otherwise
     */
    public function getEmbeddedRole($role)
    {
        return EmbeddedRole::getEmbeddedRole($this->roles, $role);
    }
    
    /**
     * Set role
     * @param Role|EmbeddedRole $role
     */
    public function addRole($role)
    {      
        if (!($this->containsRole($role))) {
            $this->roles[] = EmbeddedRole::createEmbeddedRole($this->roles, $role);
        }
    }

    /**
     * Remove role
     *
     * @param Role|EmbeddedRole $role
     * @return boolean TRUE if this embedded person contained the specified role, FLASE otherwise.
     */
    public function removeRole($role)
    {
        $embeddedRole = $this->getEmbeddedRole($role);

        $aux = $this->roles->filter(function ($i) use ($embeddedRole) {
              return $i->getId() !== $embeddedRole->getId();
          });

        $hasRemoved = (count($aux) !== count($this->roles));

        $this->roles = $aux;

        return $hasRemoved;
    }

    /**
     * Contains role
     *
     * @param Role|EmbeddedRole $role
     * @return EmbeddedRole|boolean EmbeddedRole if found, FALSE otherwise.
     */
    public function containsRole($role)
    {
        if ($this->getEmbeddedRole($role)){
            return true;
        }

        return false;
    }

    /**
     * Contains all roles
     *
     * @param array $roles
     * @return boolean TRUE if this embedded person contains all roles, FLASE otherwise.
     */
    public function containsAllRoles(array $roles)
    {
        foreach ($roles as $role) {
            if (!($this->containsRole($role))) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Contains any role
     *
     * @param array $roles
     * @return boolean TRUE if this embedded person contains any role of the list, FLASE otherwise.
     */
    public function containsAnyRole(array $roles)
    {
        foreach ($roles as $role) {
            if (!($this->containsRole($role))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Contains any visible role
     *
     * Checks if any of the roles in the embeddedPerson has display with true
     *
     * @return boolean TRUE if any of the roles has display=true, FALSE otherwise
     */
    public function containsAnyVisibleRole()
    {
        foreach ($this->roles as $role){
            if ($role->getDisplay()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
    
    /**
     * Create embedded person
     *
     * @param ArrayCollection $embedPeople
     * @param EmbeddedPerson|Person $person
     *
     * @return EmbeddedPerson
     */
    public static function createEmbeddedPerson($embedPeople, $person)
    {
        if ($person instanceof self){
            return $person;
        }elseif ($containedEmbedPerson = self::getEmbeddedPerson($embedPeople, $person)) {
           return $containedEmbedPerson;
        }elseif ($person instanceof Person){
            $embedPerson = new self($person);
            
            return $embedPerson;
        }
        
        throw new \InvalidArgumentException('Only Person or EmbeddedPerson are allowed.');
    }

    /**
     * Contained embed person
     *
     * @param ArrayCollection $embedPeople
     * @param Person|EmbeddedPerson $person
     * @return EmbeddedPerson|boolean EmbeddedPerson if found, FALSE otherwise:
     */
    public static function getEmbeddedPerson($embedPeople, $person)
    {
        foreach ($embedPeople as $embedPerson) {
            if (0 === strcmp($person->getId(), $embedPerson->getId())) {
                return $embedPerson;
            }
        }
        
        return false;
    }    
}

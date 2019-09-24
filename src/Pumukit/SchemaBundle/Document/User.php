<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use FOS\UserBundle\Model\GroupInterface;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @MongoDB\Document
 */
class User extends BaseUser
{
    use Traits\Properties;

    public const ORIGIN_LOCAL = 'local';
    public const MAX_LOGIN_ATTEMPTS = 1;

    /**
     * @MongoDB\Id(strategy="auto")
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $fullname;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $loginAttempt = 0;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $lastLoginAttempt;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $origin = self::ORIGIN_LOCAL;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Group", storeAs="id", sort={"key":1}, strategy="setArray")
     */
    protected $groups;

    /**
     * @MongoDB\ReferenceOne(targetDocument="PermissionProfile", storeAs="id", cascade={"persist"})
     */
    private $permissionProfile;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Person", inversedBy="user", storeAs="id", cascade={"persist"})
     */
    private $person;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        parent::__construct();
    }

    public function setPermissionProfile(PermissionProfile $permissionProfile): void
    {
        $this->permissionProfile = $permissionProfile;
    }

    public function getPermissionProfile(): ?PermissionProfile
    {
        return $this->permissionProfile;
    }

    public function setPerson(Person $person = null): void
    {
        $this->person = $person;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setFullname(string $fullname): void
    {
        $this->fullname = $fullname;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function getLoginAttempt(): int
    {
        return $this->loginAttempt;
    }

    public function addLoginAttempt(): void
    {
        if ($this->loginAttempt > self::MAX_LOGIN_ATTEMPTS) {
            $this->loginAttempt = self::MAX_LOGIN_ATTEMPTS;
        }

        if (self::MAX_LOGIN_ATTEMPTS === $this->loginAttempt) {
            $this->setEnabled(false);
        }

        if ($this->loginAttempt < self::MAX_LOGIN_ATTEMPTS) {
            ++$this->loginAttempt;
            $this->setLastLoginAttempt(new \DateTime());
        }
    }

    public function canLogin(): bool
    {
        return $this->loginAttempt < self::MAX_LOGIN_ATTEMPTS;
    }

    public function getLastLoginAttempt(): \DateTime
    {
        return $this->lastLoginAttempt;
    }

    public function setLastLoginAttempt(\DateTime $lastLoginAttempt): void
    {
        $this->lastLoginAttempt = $lastLoginAttempt;
    }

    public function resetLoginChecks(): void
    {
        $this->loginAttempt = 0;
        $this->setLastLoginAttempt(new \DateTime());
        $this->setEnabled(true);
    }

    public function setOrigin(string $origin): void
    {
        $this->origin = $origin;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function isLocal(): bool
    {
        return self::ORIGIN_LOCAL === $this->origin;
    }

    public function containsGroup(GroupInterface $group): bool
    {
        return $this->groups->contains($group);
    }

    public function addGroup(GroupInterface $group): void
    {
        $this->groups->add($group);
    }

    public function removeGroup(GroupInterface $group): void
    {
        $this->groups->removeElement($group);
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function getGroupsIds(): array
    {
        // Performance boost (Don't repeat it, only if it's exceptionally necesary)
        if ($this->groups instanceof \Doctrine\ODM\MongoDB\PersistentCollection && !$this->groups->isDirty()) {
            //See PersistentCollection class (coll + mongoData)
            return array_merge(
                array_map(
                    static function ($g) {
                        return new \MongoId($g->getId());
                    },
                    $this->groups->unwrap()->toArray()
                ),
                $this->groups->getMongoData()
            );
        }

        return array_map(
            static function ($g) {
                return new \MongoId($g->getId());
            },
            $this->groups->toArray()
        );
    }

    /**
     * NOTE: Override BaseUser::getRole to avoid call User::getGroups and avoid a query.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = static::ROLE_DEFAULT;

        return array_unique($roles);
    }
}

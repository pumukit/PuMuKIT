<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\UserRepository")
 */
class User implements UserInterface
{
    use Traits\Properties;

    public const ROLE_DEFAULT = 'ROLE_USER';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const ORIGIN_LOCAL = 'local';
    public const MAX_LOGIN_ATTEMPTS = 3;
    public const RESET_LOGIN_ATTEMPTS_INTERVAL = '5 minutes';

    /**
     * @MongoDB\Id(strategy="auto")
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $username;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $email;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $enabled;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $fullName;

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
     * @MongoDB\Field(type="string")
     */
    protected $salt;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $password;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $plainPassword;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $lastLogin;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $confirmationToken;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $passwordRequestedAt;

    /**
     * @MongoDB\Field(type="raw")
     */
    protected $roles;

    /**
     * @MongoDB\ReferenceMany(targetDocument=Group::class, storeAs="id", sort={"key":1}, strategy="setArray")
     */
    protected $groups;

    /**
     * @MongoDB\ReferenceOne(targetDocument=PermissionProfile::class, storeAs="id", cascade={"persist"})
     */
    private $permissionProfile;

    /**
     * @MongoDB\ReferenceOne(targetDocument=Person::class, inversedBy="user", storeAs="id", cascade={"persist"})
     */
    private $person;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->enabled = false;
        $this->roles = [];
        $this->lastLogin = new \DateTime();
        $this->lastLoginAttempt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setPermissionProfile(PermissionProfile $permissionProfile): void
    {
        $this->permissionProfile = $permissionProfile;
    }

    public function getPermissionProfile(): ?PermissionProfile
    {
        return $this->permissionProfile;
    }

    public function setPerson(PersonInterface $person = null): void
    {
        $this->person = $person;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getLoginAttempt(): int
    {
        return $this->loginAttempt;
    }

    public function setLoginAttempt(int $loginAttempt): void
    {
        $this->loginAttempt = $loginAttempt;
    }

    public function addLoginAttempt(): void
    {
        ++$this->loginAttempt;

        if ($this->loginAttempt < self::MAX_LOGIN_ATTEMPTS) {
            $this->setLastLoginAttempt(new \DateTime());

            return;
        }

        $this->loginAttempt = self::MAX_LOGIN_ATTEMPTS;

        $this->setEnabled(false);
    }

    public function isResetLoginAttemptsAllowed(): bool
    {
        $lastLoginAttempt = clone $this->getLastLoginAttempt();
        $lastLoginAttempt->add(\DateInterval::createFromDateString(self::RESET_LOGIN_ATTEMPTS_INTERVAL));
        $now = new \DateTime();

        return $lastLoginAttempt < $now;
    }

    public function resetLoginAttempts(): void
    {
        $this->loginAttempt = 0;
        $this->setLastLoginAttempt(new \DateTime());
        $this->setEnabled(true);
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

    public function addGroup(GroupInterface $group): bool
    {
        return $this->groups->add($group);
    }

    public function removeGroup(GroupInterface $group): User
    {
        $this->groups->removeElement($group);

        return $this;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function getGroupsIds(): array
    {
        // Performance boost (Don't repeat it, only if it's exceptionally necessary)
        if ($this->groups instanceof \Doctrine\ODM\MongoDB\PersistentCollection && !$this->groups->isDirty()) {
            //See PersistentCollection class (coll + mongoData)
            return array_merge(
                array_map(
                    static function ($g) {
                        return new ObjectId($g->getId());
                    },
                    $this->groups->unwrap()->toArray()
                ),
                $this->groups->getMongoData()
            );
        }

        return array_map(
            static function ($g) {
                return new ObjectId($g->getId());
            },
            $this->groups->toArray()
        );
    }

    public function addRole($role): User
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function removeRole($role): User
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    /**
     * NOTE: Override BaseUser::getRole to avoid call User::getGroups and avoid a query.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_DEFAULT;

        return array_unique($roles);
    }

    public function hasRole($role): bool
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function setUsername(string $username): void
    {
        $this->username = strtolower($username);
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = strtolower($email);
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getSalt(): ?string
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return $this->salt;
    }

    public function setSalt($salt): void
    {
        $this->salt = $salt;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword($password): void
    {
        $this->password = $password;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public function setLastLogin(\DateTimeInterface $date): void
    {
        $this->lastLogin = $date;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken($confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getId()
    {
        return $this->id;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }
}

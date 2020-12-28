<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\EmbeddedDocument
 */
class EmbeddedBroadcast
{
    public const TYPE_PUBLIC = 'public';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_LOGIN = 'login';
    public const TYPE_GROUPS = 'groups';

    public const NAME_PUBLIC = 'Public';
    public const NAME_PASSWORD = 'Password protected';
    public const NAME_LOGIN = 'Only logged in Users';
    public const NAME_GROUPS = 'Only Users in Groups';

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $name = self::NAME_PUBLIC;

    /**
     * @MongoDB\Field(type="string")
     */
    private $type = self::TYPE_PUBLIC;

    /**
     * @MongoDB\Field(type="string")
     */
    private $password;

    /**
     * @MongoDB\ReferenceMany(targetDocument=Group::class, storeAs="id", sort={"key":1}, strategy="setArray")
     */
    private $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function __toString(): string
    {
        $groups = $this->getGroups();
        $groupsDescription = '';
        if (($groups) && (self::TYPE_GROUPS === $this->getType())) {
            $groupsDescription = ': ';
            foreach ($groups as $group) {
                $groupsDescription .= $group->getName();
                if ($group !== $groups->last()) {
                    $groupsDescription .= ', ';
                }
            }
        }

        return $this->getName().$groupsDescription;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function containsGroup(Group $group): bool
    {
        return $this->groups->contains($group);
    }

    public function addGroup(Group $group): bool
    {
        return $this->groups->add($group);
    }

    public function removeGroup(Group $group): void
    {
        $this->groups->removeElement($group);
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @Assert\IsTrue(message = "Password required if not public")
     */
    public function isPasswordValid(): bool
    {
        $isPublic = self::TYPE_PUBLIC === $this->getType();
        $isPrivate = (self::TYPE_PASSWORD === $this->getType()) && ('' !== $this->getPassword());

        return $isPublic || $isPrivate;
    }
}

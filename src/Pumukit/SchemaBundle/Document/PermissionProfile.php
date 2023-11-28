<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\PermissionProfileRepository")
 *
 * @ApiResource(
 *       collectionOperations={"get"={"method"="GET", "access_control"="is_granted('ROLE_ACCESS_API')"}},
 *       itemOperations={"get"={"method"="GET", "access_control"="is_granted('ROLE_ACCESS_API')"}}
 *   )
 */
class PermissionProfile
{
    public const SCOPE_GLOBAL = 'ROLE_SCOPE_GLOBAL';
    public const SCOPE_PERSONAL = 'ROLE_SCOPE_PERSONAL';
    public const SCOPE_NONE = 'ROLE_SCOPE_NONE';

    public static $scopeDescription = [
        self::SCOPE_GLOBAL => 'Global Scope',
        self::SCOPE_PERSONAL => 'Personal Scope',
        self::SCOPE_NONE => 'No Scope',
    ];

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     *
     * @MongoDB\UniqueIndex(order="asc")
     */
    private $name = '';

    /**
     * @MongoDB\Field(type="collection")
     */
    private $permissions = [];

    /**
     * @MongoDB\Field(type="bool")
     */
    private $system = false;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $default = false;

    /**
     * @MongoDB\Field(type="string")
     */
    private $scope = self::SCOPE_PERSONAL;

    /**
     * @MongoDB\Field(type="int")
     *
     * @Gedmo\SortablePosition
     */
    private $rank;

    public function __toString(): string
    {
        return $this->getName();
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

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function addPermission(string $permission): array
    {
        $this->permissions[] = $permission;

        return $this->permissions = array_unique($this->permissions);
    }

    public function removePermission(string $permission): bool
    {
        $permissionKey = array_search($permission, $this->permissions, true);
        if (is_int($permissionKey)) {
            unset($this->permissions[$permissionKey]);

            return true;
        }

        return false;
    }

    public function containsPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function containsAllPermissions(array $permissions): bool
    {
        return count(array_intersect($permissions, $this->permissions)) === count($permissions);
    }

    public function containsAnyPermission(array $permissions): bool
    {
        return 0 !== count(array_intersect($permissions, $this->permissions));
    }

    public function setSystem(bool $system): void
    {
        $this->system = $system;
    }

    public function getSystem(): bool
    {
        return $this->system;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }

    public function getDefault(): bool
    {
        return $this->default;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setRank(int $rank): void
    {
        $this->rank = $rank;
    }

    public function getRank()
    {
        return $this->rank;
    }

    public function isGlobal(): bool
    {
        return self::SCOPE_GLOBAL === $this->getScope();
    }

    public function isPersonal(): bool
    {
        return self::SCOPE_PERSONAL === $this->getScope();
    }

    public function isNone(): bool
    {
        return self::SCOPE_NONE === $this->getScope();
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }
}

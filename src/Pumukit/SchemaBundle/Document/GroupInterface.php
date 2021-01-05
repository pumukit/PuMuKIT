<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

interface GroupInterface
{
    public function addRole(string $role);

    public function getId();

    public function getName(): string;

    public function hasRole(string $role): bool;

    public function getRoles();

    public function removeRole(string $role);

    public function setName(string $name);

    public function setRoles(array $roles);
}

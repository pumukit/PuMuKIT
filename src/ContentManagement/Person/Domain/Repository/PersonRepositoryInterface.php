<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Domain\Repository;

use Pumukit\SchemaBundle\Document\Person;

interface PersonRepositoryInterface
{
    public function find(string $id): ?Person;

    public function findByEmail(string $email): ?Person;

    public function findAll(): array;

    public function save(Person $person): void;

    public function delete(Person $person): void;
}

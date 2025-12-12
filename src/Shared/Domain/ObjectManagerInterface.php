<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface ObjectManagerInterface
{
    public function persist(object $object): void;

    public function remove(object $object): void;

    public function flush(): void;

    public function clear(?string $objectName = null): void;

    public function refresh(object $object): void;

    public function find(string $className, mixed $id): ?object;

    public function getRepository(string $className): object;
}

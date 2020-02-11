<?php

namespace Pumukit\SchemaBundle\Document;

interface RoleInterface
{
    public function getCod(): string;

    public function setCod(string $code): void;
}

<?php

declare(strict_types=1);

namespace App\Shared\Domain\Criteria;

final class Filter
{
    public function __construct(
        private string $field,
        private string $operator,
        private mixed $value
    ) {}

    public static function fromValues(array $values): self
    {
        return new self($values['field'], $values['operator'], $values['value']);
    }

    public function field(): string { return $this->field; }
    public function operator(): string { return $this->operator; }
    public function value(): mixed { return $this->value; }
}

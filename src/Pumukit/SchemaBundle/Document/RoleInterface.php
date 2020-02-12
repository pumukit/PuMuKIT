<?php

namespace Pumukit\SchemaBundle\Document;

interface RoleInterface
{
    public function __toString(): string;

    public function getId();

    public function getCod(): string;

    public function setCod(string $code): void;

    // See European Broadcasting Union Role Codes: https://www.ebu.ch/metadata/cs/web/ebu_RoleCodeCS_p.xml.htm.
    public function setXml(string $xml): void;

    public function getXml(): ?string;

    public function setDisplay(bool $display): void;

    public function getDisplay(): bool;

    public function setName(string $name, ?string $locale = null): void;

    public function getName(?string $locale = null): string;

    public function setI18nName(array $name): void;

    public function getI18nName(): array;

    public function getText(?String $locale = null): string;

    public function setI18nText(array $text): void;

    public function getI18nText(): array;
}

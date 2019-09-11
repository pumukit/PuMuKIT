<?php

namespace Pumukit\SchemaBundle\Document;

interface PersonInterface
{
    public function __toString(): string;

    public function getName(): string;

    public function setName(string $name): void;

    public function getEmail(): string;

    public function setEmail(string $email): void;

    public function setWeb(string $web): void;

    public function getWeb(): string;

    public function setPhone(string $phone): void;

    public function getPhone(): string;

    public function setHonorific(string $honorific, string $locale = null): void;

    public function getHonorific(string $locale = null): string;

    public function setI18nHonorific(array $honorific): void;

    public function getI18nHonorific(): array;

    public function setFirm(string $firm, string $locale = null): void;

    public function getFirm(string $locale = null): string;

    public function setI18nFirm(array $firm): void;

    public function getI18nFirm(): array;

    public function setPost(string $post, string $locale = null): void;

    public function getPost(string $locale = null): string;

    public function setI18nPost(array $post): void;

    public function getI18nPost(): array;

    public function setBio(string $bio, string $locale = null): void;

    public function getBio(string $locale = null): string;

    public function setI18nBio(array $bio): void;

    public function getI18nBio(): array;

    /** Returns person name with his/her honorific. */
    public function getHName(): string;

    /** Returns strings with person position. */
    public function getOther(): string;

    /** Returns strings with person info: Firm, Post and Bio separated by commas or without Bio if param is false. */
    public function getInfo(bool $withBio = true): string;
}

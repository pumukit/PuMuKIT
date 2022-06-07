<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ObjectValue\Immutable;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ImmutableService
{
    private $documentManager;
    private $authorizationChecker;

    public function __construct(DocumentManager $documentManager, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->documentManager = $documentManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function setImmutableValues(bool $immutableValue, MultimediaObject $multimediaObject, ?UserInterface $user): MultimediaObject
    {
        if (!$this->canBeEdited()) {
            return $multimediaObject;
        }

        $shouldChangeValue = $this->validateChange($multimediaObject, $immutableValue);
        if ($shouldChangeValue) {
            $this->updateImmutableData($immutableValue, $multimediaObject, $user);
            $this->documentManager->persist($multimediaObject);
        }

        return $multimediaObject;
    }

    public function canBeEdited(): bool
    {
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_IMMUTABLE)) {
            return true;
        }

        return false;
    }

    private function validateChange(MultimediaObject $multimediaObject, bool $immutableValue): bool
    {
        $actualImmutableObject = $multimediaObject->getImmutable();

        if ($actualImmutableObject && $actualImmutableObject->value() !== $immutableValue) {
            return true;
        }

        if ($actualImmutableObject && $actualImmutableObject->value() === $immutableValue) {
            return false;
        }

        if (!$actualImmutableObject && $immutableValue) {
            return true;
        }

        return false;
    }

    private function updateImmutableData(bool $immutableValue, MultimediaObject $multimediaObject, ?UserInterface $user): void
    {
        $immutable = $this->createImmutable($immutableValue, $user);
        $multimediaObject->setImmutable($immutable);
    }

    private function createImmutable(bool $immutableValue, ?UserInterface $user): Immutable
    {
        return Immutable::create($immutableValue, $user);
    }
}

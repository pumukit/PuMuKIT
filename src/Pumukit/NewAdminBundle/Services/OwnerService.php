<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Services\PersonWithRoleEventDispatcherService;

class OwnerService
{
    public const MULTIMEDIA_OBJECT_CO_OWNERS_PROPERTY = 'rejected_co_owners';
    public const MULTIMEDIA_OBJECT_OWNERS_PROPERTY = 'owners';

    private $documentManager;
    private $multimediaObjectService;
    private $personWithRoleEventDispatcherService;
    private $personalScopeRoleCode;

    public function __construct(
        DocumentManager $documentManager,
        MultimediaObjectService $multimediaObjectService,
        PersonWithRoleEventDispatcherService $personWithRoleEventDispatcherService,
        string $personalScopeRoleCode
    ) {
        $this->documentManager = $documentManager;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->personWithRoleEventDispatcherService = $personWithRoleEventDispatcherService;
        $this->personalScopeRoleCode = $personalScopeRoleCode;
    }

    public function rejectCoOwnerFromMultimediaObject(MultimediaObject $multimediaObject, User $user, User $coOwner): MultimediaObject
    {
        if (!$this->multimediaObjectService->isUserOwner($user, $multimediaObject)) {
            return $multimediaObject;
        }

        $role = $this->documentManager->getRepository(Role::class)->findOneBy(['cod' => $this->personalScopeRoleCode]);
        if (!$role instanceof Role) {
            throw new \Exception('Role not found');
        }

        $owners = $multimediaObject->getProperty(self::MULTIMEDIA_OBJECT_OWNERS_PROPERTY);
        $key = array_search($user->getId(), $owners);
        if (false !== $key) {
            unset($owners[$key]);
            $multimediaObject->setProperty(self::MULTIMEDIA_OBJECT_OWNERS_PROPERTY, array_values($owners));
            $multimediaObject->removePersonWithRole($user->getPerson(), $role);
        }

        $rejectedCoOwners = $multimediaObject->getProperty(self::MULTIMEDIA_OBJECT_CO_OWNERS_PROPERTY);
        $rejectedCoOwners[] = $coOwner->getId();
        $multimediaObject->setProperty(self::MULTIMEDIA_OBJECT_CO_OWNERS_PROPERTY, $rejectedCoOwners);

        $this->documentManager->flush();

        $this->dispatchPersonWithRoleEvent($multimediaObject, $coOwner, $role);

        return $multimediaObject;
    }

    private function dispatchPersonWithRoleEvent(MultimediaObject $multimediaObject, User $coOwner, Role $role): void
    {
        $this->personWithRoleEventDispatcherService->dispatchDelete($multimediaObject, $coOwner->getPerson(), $role);
    }
}

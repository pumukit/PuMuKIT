<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\CloneService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\TranslatorInterface;

class MultimediaObjectArchiveService
{
    protected $documentManager;
    protected $cloneService;
    protected $immutableService;
    protected $personService;
    protected $tokenStorage;
    protected $userService;
    protected $translator;
    protected $multimediaObjectArchivedUserAssign;

    public function __construct(
        DocumentManager $documentManager,
        CloneService $cloneService,
        ImmutableService $immutableService,
        PersonService $personService,
        TokenStorage $tokenStorage,
        UserService $userService,
        TranslatorInterface $translator,
        ?string $multimediaObjectArchivedUserAssign
    ) {
        $this->documentManager = $documentManager;
        $this->cloneService = $cloneService;
        $this->immutableService = $immutableService;
        $this->personService = $personService;
        $this->tokenStorage = $tokenStorage;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->multimediaObjectArchivedUserAssign = $multimediaObjectArchivedUserAssign;
    }

    public function archiveMultimediaObject(MultimediaObject $multimediaObject): MultimediaObject
    {
        $clonedMultimediaObject = $this->cloneService->cloneMultimediaObject($multimediaObject);

        $addToTitle = $this->translator->trans('ARCHIVED').' '.date('Y');
        $this->cloneService->cloneTitle($multimediaObject, $clonedMultimediaObject, $addToTitle);

        try {
            $token = $this->tokenStorage->getToken();
            if ($token) {
                $user = $token->getUser();
            } else {
                $user = null;
            }
        } catch (\Exception $exception) {
            $user = null;
        }
        $this->immutableService->setImmutableValues(true, $multimediaObject, $user);

        $formerOwnersIds = $this->personService->removeOwnersFromMultimediaObject($multimediaObject);
        $this->addFormerOwner($multimediaObject, $formerOwnersIds);
        $this->assignUserOnArchivedMultimediaObject($multimediaObject);

        $this->documentManager->persist($multimediaObject);
        $this->documentManager->flush();

        return $multimediaObject;
    }

    private function addFormerOwner(MultimediaObject $multimediaObject, array $formerOwnersIds): void
    {
        foreach ($formerOwnersIds as $personId) {
            $person = $this->documentManager->getRepository(Person::class)->findOneBy(['_id' => new ObjectId($personId)]);
            if ($person instanceof Person) {
                $this->personService->createRelationPerson($person, $this->getFormerOwnerRole(), $multimediaObject, false, false);
            }
        }
    }

    private function assignUserOnArchivedMultimediaObject(MultimediaObject $multimediaObject): void
    {
        if ($this->multimediaObjectArchivedUserAssign) {
            $user = $this->documentManager->getRepository(User::class)->findOneBy([
                'email' => $this->multimediaObjectArchivedUserAssign,
            ]);

            if ($user instanceof User) {
                $this->personService->createRelationPerson($user->getPerson(), $this->getOwnerRole(), $multimediaObject, false, false);
                $this->userService->removeOwnerUserFromMultimediaObject($multimediaObject, $user);
            }
        }
    }

    private function getOwnerRole(): Role
    {
        return $this->documentManager->getRepository(Role::class)->findOneBy(['cod' => 'owner']);
    }

    private function getFormerOwnerRole(): Role
    {
        return $this->documentManager->getRepository(Role::class)->findOneBy(['cod' => 'former_owner']);
    }
}

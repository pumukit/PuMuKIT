<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\PersonInterface;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FilterService
{
    public const MAGIC_STATUS = [
        MultimediaObject::STATUS_PUBLISHED,
        MultimediaObject::STATUS_HIDDEN,
    ];

    public const PUBLIC_STATUS = [
        MultimediaObject::STATUS_PUBLISHED,
    ];

    public const ALL_STATUS = [
        MultimediaObject::STATUS_PUBLISHED,
        MultimediaObject::STATUS_HIDDEN,
        MultimediaObject::STATUS_BLOCKED,
    ];

    /** @var PersonService; */
    private $personService;

    /** @var TokenStorageInterface */
    private $securityContext;
    private $addUserAsPerson;

    public function __construct(PersonService $personService, TokenStorageInterface $securityContext, $addUserAsPerson = true)
    {
        $this->personService = $personService;
        $this->securityContext = $securityContext;
        $this->addUserAsPerson = $addUserAsPerson;
    }

    public function checkFilterActivation(ControllerEvent $event): bool
    {
        [$controller, $routeParams] = $this->getEventData($event);

        if (!is_array($controller)) {
            return false;
        }

        $isFilterActivated = (!isset($routeParams['filter']) || $routeParams['filter']);

        return !(!$isFilterActivated || !$event->isMasterRequest());
    }

    public function getEventData(ControllerEvent $event): array
    {
        $request = $event->getRequest();
        $controller = $event->getController();
        $routeParams = $request->attributes->get('_route_params');

        return [
            $controller,
            $routeParams,
        ];
    }

    public function setGenericFilterParameters(BsonFilter $filter, array $routeParams): void
    {
        if (isset($routeParams['show_hide']) && $routeParams['show_hide']) {
            $filter->setParameter('status', [
                '$in' => self::MAGIC_STATUS,
            ]);
        } elseif (isset($routeParams['show_block']) && $routeParams['show_block']) {
            $filter->setParameter('status', [
                '$in' => self::ALL_STATUS,
            ]);
        } else {
            $filter->setParameter('status', [
                '$in' => self::PUBLIC_STATUS,
            ]);
        }

        if (!isset($routeParams['track']) || $routeParams['track']) {
            $filter->setParameter('display_track_tag', 'display');
        }
    }

    public function setFrontendFilterParameters(BsonFilter $filter, array $routeParams): void
    {
        if (!isset($routeParams['no_channels']) || !$routeParams['no_channels']) {
            $filter->setParameter('pub_channel_tag', PumukitWebTVBundle::WEB_TV_TAG);
        }

        $filter->setParameter('type', ['$ne' => MultimediaObject::TYPE_LIVE]);
    }

    public function setAdminParameters(BsonFilter $filter, ?User $user = null): void
    {
        // NOTE: Returns empty results, since the user is anonymous or does not have SCOPE_PERSONAL
        if (!$user || !$user->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            $filter->setParameter('people', []);
            $filter->setParameter('groups', []);

            return;
        }

        $person = $this->personService->getPersonFromLoggedInUser($user);

        if (null !== $people = $this->getPeopleMongoQuery($person)) {
            $filter->setParameter('people', $people);
        }

        if (null !== $person) {
            $filter->setParameter('person_id', $person->getId());
        }

        if (null !== $roleCode = $this->personService->getPersonalScopeRoleCode()) {
            $filter->setParameter('role_code', $roleCode);
        }

        if (null !== $groups = $this->getGroupsMongoQuery($user)) {
            $filter->setParameter('groups', $groups);
        }
        $filter->setParameter('series_groups', $user->getGroupsIds());
    }

    public function setPersonalFilterParameters(BsonFilter $filter, User $user): void
    {
        $groups = [
            '$in' => $user->getGroupsIds(),
        ];
        $filter->setParameter('groups', $groups);

        $person = $this->personService->getPersonFromLoggedInUser($user);
        if ((null !== $person) && (null !== ($roleCode = $this->personService->getPersonalScopeRoleCode()))) {
            $people = [
                '$elemMatch' => [
                    'people._id' => new ObjectId($person->getId()),
                    'cod' => $roleCode,
                ],
            ];
            $filter->setParameter('people', $people);
        }
    }

    public function checkUserActivateFilter(): ?User
    {
        if (!$this->addUserAsPerson) {
            return null;
        }

        $loggedInUser = $this->getLoggedInUser();
        if ($loggedInUser && ($loggedInUser->hasRole(PermissionProfile::SCOPE_GLOBAL) || $loggedInUser->hasRole('ROLE_SUPER_ADMIN'))) {
            return null;
        }

        return $loggedInUser;
    }

    public function getPeopleMongoQuery(?PersonInterface $person = null): array
    {
        $people = [];
        if ((null !== $person) && (null !== ($roleCode = $this->personService->getPersonalScopeRoleCode()))) {
            $people['$elemMatch'] = [];
            $people['$elemMatch']['people._id'] = new ObjectId($person->getId());
            $people['$elemMatch']['cod'] = $roleCode;
        }

        return $people;
    }

    public function getGroupsMongoQuery(User $user): array
    {
        $groups = [];
        $groups['$in'] = $user->getGroupsIds();

        return $groups;
    }

    private function getLoggedInUser(): ?User
    {
        if (null !== $token = $this->securityContext->getToken()) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }
}

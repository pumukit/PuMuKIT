<?php

namespace Pumukit\CoreBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PersonService;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class FilterService.
 */
class FilterService
{
    const MAGIC_STATUS = [
        MultimediaObject::STATUS_PUBLISHED,
        MultimediaObject::STATUS_HIDDEN,
    ];

    const PUBLIC_STATUS = [
        MultimediaObject::STATUS_PUBLISHED,
    ];

    const ALL_STATUS = [
        MultimediaObject::STATUS_PUBLISHED,
        MultimediaObject::STATUS_HIDDEN,
        MultimediaObject::STATUS_BLOCKED,
    ];

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var PersonService;
     */
    private $personService;

    /**
     * @var TokenStorage
     */
    private $securityContext;

    private $addUserAsPerson;

    /**
     * FilterService constructor.
     *
     * @param DocumentManager $dm
     * @param PersonService   $personService
     * @param TokenStorage    $securityContext
     * @param bool            $addUserAsPerson
     */
    public function __construct(DocumentManager $dm, PersonService $personService, TokenStorage $securityContext, $addUserAsPerson = true)
    {
        $this->dm = $dm;
        $this->personService = $personService;
        $this->securityContext = $securityContext;
        $this->addUserAsPerson = $addUserAsPerson;
    }

    /**
     * @param FilterControllerEvent $event
     *
     * @return bool
     */
    public function checkFilterActivation(FilterControllerEvent $event)
    {
        [$controller, $routeParams] = $this->getEventData($event);

        if (!is_array($controller)) {
            return false;
        }

        $isFilterActivated = (!isset($routeParams['filter']) || $routeParams['filter']);

        if (!$event->isMasterRequest() || !$isFilterActivated) {
            return false;
        }

        return true;
    }

    /**
     * @param FilterControllerEvent $event
     *
     * @return array
     */
    public function getEventData(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $controller = $event->getController();
        $routeParams = $request->attributes->get('_route_params');

        return [
            $controller,
            $routeParams,
        ];
    }

    /**
     * @param BsonFilter $filter
     * @param array      $routeParams
     */
    public function setGenericFilterParameters(BsonFilter $filter, array $routeParams)
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

    /**
     * @param BsonFilter $filter
     * @param array      $routeParams
     */
    public function setFrontendFilterParameters(BsonFilter $filter, array $routeParams)
    {
        if (!isset($routeParams['no_channels']) || !$routeParams['no_channels']) {
            $filter->setParameter('pub_channel_tag', 'PUCHWEBTV');
        }

        $filter->setParameter('type', ['$ne' => MultimediaObject::TYPE_LIVE]);
    }

    /**
     * @param BsonFilter $filter
     * @param User|null  $user
     *
     * @throws \MongoException
     */
    public function setAdminParameters(BsonFilter $filter, User $user = null)
    {
        // NOTE: Returns empty results, since the user is anonimous or does not have SCOPE_PERSONAL
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

    /**
     * @param BsonFilter $filter
     * @param User       $user
     *
     * @throws \MongoException
     */
    public function setPersonalFilterParameters(BsonFilter $filter, User $user)
    {
        $groups = [
            '$in' => [
                $user->getGroupsIds(),
            ],
        ];
        $filter->setParameter('groups', $groups);

        $person = $this->personService->getPersonFromLoggedInUser($user);
        if ((null !== $person) && (null !== ($roleCode = $this->personService->getPersonalScopeRoleCode()))) {
            $people = [
                '$elemMatch' => [
                    'people._id' => new \MongoId($person->getId()),
                    'cod' => $roleCode,
                ],
            ];
            $filter->setParameter('people', $people);
        }
    }

    /**
     * @return User|null
     */
    public function checkUserActivateFilter()
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

    /**
     * Get logged in user.
     *
     * @return User|null
     */
    private function getLoggedInUser()
    {
        if (null !== $token = $this->securityContext->getToken()) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Get people mongo query.
     *
     * Match the MultimediaObjects
     * with given Person and Role code
     *
     * Query in MongoDB:
     * {"people":{"$elemMatch":{"people._id":{"$id":"___MongoID_of_Person___"},"cod":"___Role_cod___"}}}
     *
     * @param Person|null $person
     *
     * @return array
     *
     * @throws \MongoException
     */
    public function getPeopleMongoQuery(Person $person = null)
    {
        $people = [];
        if ((null !== $person) && (null !== ($roleCode = $this->personService->getPersonalScopeRoleCode()))) {
            $people['$elemMatch'] = [];
            $people['$elemMatch']['people._id'] = new \MongoId($person->getId());
            $people['$elemMatch']['cod'] = $roleCode;
        }

        return $people;
    }

    /**
     * Get groups mongo query.
     *
     * Match the MultimediaObjects
     * with some of the admin groups
     * of the given user
     *
     * Query in MongoDB:
     * {"groups":{"$in":["___MongoID_of_Group_1___", "___MongoID_of_Group_2___"...]}}
     *
     * @param User $user
     *
     * @return array $groups
     */
    public function getGroupsMongoQuery(User $user)
    {
        $groups = [];
        $groups['$in'] = $user->getGroupsIds();

        return $groups;
    }
}

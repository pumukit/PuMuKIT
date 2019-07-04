<?php

namespace Pumukit\NewAdminBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\NewAdminBundle\Controller\NewAdminControllerInterface;

class FilterListener
{
    private $dm;
    private $personService;
    private $securityContext;
    private $addUserAsPerson;

    public function __construct(DocumentManager $documentManager, PersonService $personService, TokenStorage $securityContext, $addUserAsPerson = true)
    {
        $this->dm = $documentManager;
        $this->personService = $personService;
        $this->securityContext = $securityContext;
        $this->addUserAsPerson = $addUserAsPerson;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get('_route_params');
        $isFilterActivated = (!isset($routeParams['filter']) || $routeParams['filter']);

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         * From Symfony Docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
         */
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        //@deprecated: PuMuKIT 2.2: This logic will be removed eventually. Please implement the interface WebTVBundleController to use the filter.
        $deprecatedCheck = (false !== strpos($req->attributes->get('_controller'), 'pumukitnewadmin'));

        if (($controller[0] instanceof NewAdminControllerInterface /*deprecated*/ || $deprecatedCheck)
            && $event->isMasterRequest()
            && $isFilterActivated) {
            if ($this->addUserAsPerson) {
                $loggedInUser = $this->getLoggedInUser();
                if ($loggedInUser->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
                    $filter = $this->dm->getFilterCollection()->enable('backoffice');

                    $person = $this->personService->getPersonFromLoggedInUser($loggedInUser);

                    if (null !== $people = $this->getPeopleMongoQuery($person)) {
                        $filter->setParameter('people', $people);
                    }

                    if (null !== $person) {
                        $filter->setParameter('person_id', $person->getId());
                    }

                    if (null !== $roleCode = $this->personService->getPersonalScopeRoleCode()) {
                        $filter->setParameter('role_code', $roleCode);
                    }

                    if (null !== $groups = $this->getGroupsMongoQuery($loggedInUser)) {
                        $filter->setParameter('groups', $groups);
                    }
                    $filter->setParameter('series_groups', $loggedInUser->getGroupsIds());
                }
            }
        }
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
     * @return array $people
     */
    private function getPeopleMongoQuery(Person $person = null)
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
     * Get logged in user.
     */
    private function getLoggedInUser()
    {
        if (null !== $token = $this->securityContext->getToken()) {
            return $token->getUser();
        }

        return null;
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
    private function getGroupsMongoQuery(User $user)
    {
        $groups = [];
        $groups['$in'] = $user->getGroupsIds();

        return $groups;
    }
}

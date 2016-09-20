<?php

namespace Pumukit\CoreBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\SecurityContext;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\CoreBundle\Controller\AdminController;
use Pumukit\CoreBundle\Controller\WebTVController;
use Pumukit\CoreBundle\Controller\PersonalController;

class FilterListener
{
    private $dm;
    private $personService;
    private $securityContext;
    private $addUserAsPerson;

    public function __construct(DocumentManager $documentManager, PersonService $personService, SecurityContext $securityContext, $addUserAsPerson = true)
    {
        $this->dm = $documentManager;
        $this->personService = $personService;
        $this->securityContext = $securityContext;
        $this->addUserAsPerson = $addUserAsPerson;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get("_route_params");
        $isFilterActivated = (!isset($routeParams["filter"]) || $routeParams["filter"]);

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         * From Symfony Docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
         */
        $controller = $event->getController();
        if(!is_array($controller)) {
            return;
        }

        if (!$event->isMasterRequest()
            || !$isFilterActivated) {
            return;
        }
        if ($controller[0] instanceof NewAdminController) {
            $this->enableAdminFilter($routeParams);
        } elseif ($controller[0] instanceof WebTVController) {
            $this->enableWebTVFilter($routeParams);
        } elseif ($controller[0] instanceof PersonalController) {
            $this->enablePersonalFilter($routeParams);
        }
    }

    /**
     * Get people mongo query
     *
     * Match the MultimediaObjects
     * with given Person and Role code
     *
     * Query in MongoDB:
     * {"people":{"$elemMatch":{"people._id":{"$id":"___MongoID_of_Person___"},"cod":"___Role_cod___"}}}
     *
     * @param  Person|null $person
     * @return array       $people
     */
    private function getPeopleMongoQuery(Person $person = null)
    {
        $people = array();
        if ((null != $person) && (null != ($roleCode = $this->personService->getPersonalScopeRoleCode()))) {
            $people['$elemMatch'] = array();
            $people['$elemMatch']['people._id'] = new \MongoId($person->getId());
            $people['$elemMatch']['cod'] = $roleCode;
        }

        return $people;
    }

    /**
     * Get logged in user
     */
    private function getLoggedInUser()
    {
        if (null != $token = $this->securityContext->getToken()) {
            $user = $token->getUser();
            if($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Get groups mongo query
     *
     * Match the MultimediaObjects
     * with some of the admin groups
     * of the given user
     *
     * Query in MongoDB:
     * {"groups":{"$in":["___MongoID_of_Group_1___", "___MongoID_of_Group_2___"...]}}
     *
     * @param  User  $user
     * @return array $groups
     */
    private function getGroupsMongoQuery(User $user)
    {
        $groups = array();
        $groups['$in'] = $user->getGroupsIds();

        return $groups;
    }

    /**
     * Enables the 'admin' filter.
     *
     * This filter is used mainly in the "back-office" part of the application.
     */
    private function enableAdminFilter($routeParams)
    {
        if (!$this->addUserAsPerson) {
            return;
        }
        //Users with SCOPE_GLOBAL can view everything.
        $loggedInUser = $this->getLoggedInUser();
        if (!$loggedInUser || !$loggedInUser->hasRole(PermissionProfile::SCOPE_PERSONAL)) {
            return;
        }

        $filter = $this->dm->getFilterCollection()->enable("admin");
        $person = $this->personService->getPersonFromLoggedInUser($loggedInUser);

        if (null != $people = $this->getPeopleMongoQuery($person)) {
            $filter->setParameter("people", $people);
        }

        if (null != $person) {
            $filter->setParameter("person_id", $person->getId());
        }

        if (null != $roleCode = $this->personService->getPersonalScopeRoleCode()) {
            $filter->setParameter("role_code", $roleCode);
        }

        if (null != $groups = $this->getGroupsMongoQuery($loggedInUser)) {
            $filter->setParameter("groups", $groups);
        }
        $filter->setParameter("series_groups", $loggedInUser->getGroupsIds());
    }

    /**
     * Enable the "webtv" filter
     *
     */
    private function enableWebTVFilter($routeParams)
    {
        $filter = $this->dm->getFilterCollection()->enable("webtv");
        if(isset($routeParams["show_hide"]) && $routeParams["show_hide"]) {
            $filter->setParameter("status", array('$in' => array(MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDE)));
        } else {
            $filter->setParameter("status", MultimediaObject::STATUS_PUBLISHED);
        }
        if(!isset($routeParams["track"]) || $routeParams["track"]) {
            $filter->setParameter("display_track_tag", "display");
        }
        if(!isset($routeParams["no_channels"]) || !$routeParams["no_channels"]) {
            $filter->setParameter("pub_channel_tag", "PUCHWEBTV");
        }
    }

    private function enablePersonalFilter($routeParams)
    {
        $loggedInUser = $this->getLoggedInUser();

        if($loggedInUser && ($loggedInUser->hasRole(PermissionProfile::SCOPE_GLOBAL) || $loggedInUser->hasRole('ROLE_SUPER_ADMIN'))) {
            return;
        }
        $filter = $this->dm->getFilterCollection()->enable("personal");

        $groups = array();
        if(null != $loggedInUser) {
            $groups['$in'] = $loggedInUser->getGroupsIds();
            $filter->setParameter('groups', $groups);
        }

        $person = $this->personService->getPersonFromLoggedInUser($loggedInUser);
        if ((null != $person) && (null != ($roleCode = $this->personService->getPersonalScopeRoleCode()))) {
            $people = [
                '$elemMatch' => [
                    'people._id' => new \MongoId($person->getId()),
                    'cod' => $roleCode,
                ]
            ];
            $filter->setParameter('people', $people);
        }
        $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
        $filter->setParameter("display_track_tag", "display");
    }
}

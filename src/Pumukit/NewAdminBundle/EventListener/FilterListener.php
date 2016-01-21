<?php

namespace Pumukit\NewAdminBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;

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

    public function onKernelRequest(GetResponseEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get("_route_params");

        //TODO: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST 
            && (false !== strpos($req->attributes->get("_controller"), 'pumukitnewadmin'))
            && (!isset($routeParams["filter"]) || $routeParams["filter"])) {

            if ($this->addUserAsPerson) {
                $loggedInUser = $this->getLoggedInUser();
                if ($loggedInUser->hasRole(PermissionProfile::SCOPE_PERSONAL) ||
                    $loggedInUser->hasRole(PermissionProfile::SCOPE_NONE)) {
                    $filter = $this->dm->getFilterCollection()->enable("backoffice");

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
                }
            }
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
            return $token->getUser();
        }

        return null;
    }
}
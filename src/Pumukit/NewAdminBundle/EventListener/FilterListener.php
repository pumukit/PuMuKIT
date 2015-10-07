<?php

namespace Pumukit\NewAdminBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\PersonService;

class FilterListener
{
    private $dm;
    private $personService;

    public function __construct(DocumentManager $documentManager, PersonService $personService)
    {
        $this->dm = $documentManager;
        $this->personService = $personService;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get("_route_params");

        //TODO: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST 
            && (false !== strpos($req->attributes->get("_controller"), 'pumukitnewadmin'))
            && (!isset($routeParams["filter"]) || $routeParams["filter"])) {

            $loggedInUser = $this->personService->getLoggedInUser();
            if ($loggedInUser->hasRole('ROLE_AUTO_PUBLISHER') && !$loggedInUser->hasRole('ROLE_ADMIN')) {
                $filter = $this->dm->getFilterCollection()->enable("backend");

                if (null != $people = $this->getPeopleMongoQuery()) {
                    $filter->setParameter("people", $people);
                }

                $filter->setParameter("series_ids", $this->getSeriesMongoQuery());
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
     */
    private function getPeopleMongoQuery()
    {
        $people = array();
        if ((null != ($person = $this->personService->getPersonFromLoggedInUser()))
            && (null != ($roleCode = $this->personService->getAutoPublisherRoleCode()))) {
            $people['$elemMatch'] = array();
            $people['$elemMatch']['people._id'] = new \MongoId($person->getId());
            $people['$elemMatch']['cod'] = $roleCode;
        }

        return $people;
    }

    /**
     * Get series mongo query
     * Match the Series
     * with given ids
     *
     * Query in MongoDB:
     * db.Series.find({ "_id": { "$in": [ ObjectId("__id_1__"), ObjectId("__id_2__")... ] } });
     */
    private function getSeriesMongoQuery()
    {
        $seriesIds = array();
        if ((null != ($person = $this->personService->getPersonFromLoggedInUser()))
            && (null != ($roleCode = $this->personService->getAutoPublisherRoleCode()))) {
            $repoMmobj = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
            $referencedSeries = $repoMmobj->findSeriesFieldByPersonIdAndRoleCod($person->getId(), $roleCode);
            $seriesIds['$in'] = $referencedSeries->toArray();
        }

        return $seriesIds;
    }
}
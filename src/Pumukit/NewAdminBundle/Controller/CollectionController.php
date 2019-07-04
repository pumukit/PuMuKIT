<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class CollectionController extends Controller implements NewAdminControllerInterface
{
    /**
     * Returns true if the user has enough permissions to delete the $resource passed.
     *
     * This function will always return true if the user the MODIFY_ONWER permission. Otherwise,
     * it checks if it is the owner of the object (and there are no other owners) and returns false if not.
     * Since this is a series, that means it will check every object for ownerships.
     */
    protected function isUserAllowedToDelete(Series $series)
    {
        if (!$this->isGranted(Permission::MODIFY_OWNER)) {
            $loggedInUser = $this->getUser();
            $personService = $this->get('pumukitschema.person');
            $person = $personService->getPersonFromLoggedInUser($loggedInUser);
            $role = $personService->getPersonalScopeRole();
            if (!$person) {
                return false;
            }
            $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')
                ->getRepository(MultimediaObject::class)
            ;
            $allMmobjs = $mmobjRepo->createStandardQueryBuilder()->field('series')->equals($series->getId())->getQuery()->execute();
            foreach ($allMmobjs as $resource) {
                if (!$resource->containsPersonWithRole($person, $role) ||
                    count($resource->getPeopleByRole($role, true)) > 1) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * batch delete.
     *
     * Executes the delete process for each collection id passed as argument in $ids.
     */
    protected function batchDeleteCollection(array $ids)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(Series::class)
        ;
        foreach ($ids as $id) {
            $collection = $seriesRepo->find($id);
            if (!$collection || !$this->isUserAllowedToDelete($collection)) {
                //Once isUserAllowedToDelete is passed to a service, this function can also be passed.
                continue;
            }

            try {
                $factoryService->deleteSeries($collection);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
    }

    protected function createPager($queryBuilder, $request, $session_namespace = 'admin/collection')
    {
        $session = $this->get('session');
        if ($request->get('page', null)) {
            $session->set($session_namespace.'/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
        }
        $page = $session->get($session_namespace.'/page', 1);
        $limit = $session->get($session_namespace.'/paginate', 10);
        $adapter = new DoctrineODMMongoDBAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setNormalizeOutOfRangePages(true);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}

<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\PersonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CollectionController extends AbstractController implements NewAdminControllerInterface
{
    /** @var DocumentManager */
    protected $documentManager;

    /** @var FactoryService */
    protected $factoryService;

    /** @var PaginationService */
    protected $paginationService;

    /** @var PersonService */
    protected $personService;

    /** @var SessionInterface */
    private $session;

    public function __construct(
        DocumentManager $documentManager,
        FactoryService $factoryService,
        PaginationService $paginationService,
        PersonService $personService,
        SessionInterface $session
    ) {
        $this->documentManager = $documentManager;
        $this->factoryService = $factoryService;
        $this->paginationService = $paginationService;
        $this->personService = $personService;
        $this->session = $session;
    }

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
            $person = $this->personService->getPersonFromLoggedInUser($loggedInUser);
            $role = $this->personService->getPersonalScopeRole();
            if (!$person) {
                return false;
            }
            $mmobjRepo = $this->documentManager
                ->getRepository(MultimediaObject::class)
            ;
            $allMmobjs = $mmobjRepo->createStandardQueryBuilder()->field('series')->equals($series->getId())->getQuery()->execute();
            foreach ($allMmobjs as $resource) {
                if (!$resource->containsPersonWithRole($person, $role)
                    || (is_countable($resource->getPeopleByRole($role, true)) ? count($resource->getPeopleByRole($role, true)) : 0) > 1) {
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
        $seriesRepo = $this->documentManager
            ->getRepository(Series::class)
        ;
        foreach ($ids as $id) {
            $collection = $seriesRepo->find($id);
            if (!$collection || !$this->isUserAllowedToDelete($collection)) {
                // Once isUserAllowedToDelete is passed to a service, this function can also be passed.
                continue;
            }

            try {
                $this->factoryService->deleteSeries($collection);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
    }

    protected function createPager($queryBuilder, $request, $session_namespace = 'admin/collection')
    {
        $session = $this->session;
        if ($request->get('page', null)) {
            $session->set($session_namespace.'/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
        }
        $page = $session->get($session_namespace.'/page', 1);
        $limit = $session->get($session_namespace.'/paginate', 10);

        return $this->paginationService->createDoctrineODMMongoDBAdapter($queryBuilder, (int) $page, $limit);
    }
}

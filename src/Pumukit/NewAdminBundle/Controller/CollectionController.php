<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class CollectionController extends Controller implements NewAdminController
{
    /**
     * @Template()
     */
    public function showAction(Series $collection, Request $request)
    {
        $this->get('session')->set('admin/collection/id', $collection->getId());
        return array('collection' => $collection);
    }

    /**
     * Returns true if the user has enough permissions to delete the $resource passed
     *
     * This function will always return true if the user the MODIFY_ONWER permission. Otherwise,
     * it checks if it is the owner of the object (and there are no other owners) and returns false if not.
     * Since this is a series, that means it will check every object for ownerships.
     */
    protected function isUserAllowedToDelete(Series $series)
    {
        if(!$this->isGranted(Permission::MODIFY_OWNER)) {
            $loggedInUser = $this->getUser();
            $personService = $this->get('pumukitschema.person');
            $person = $personService->getPersonFromLoggedInUser($loggedInUser);
            $role = $personService->getPersonalScopeRole();
            if(!$person)
                return false;
            $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')
                              ->getRepository('PumukitSchemaBundle:MultimediaObject');
            $allMmobjs = $mmobjRepo->createStandardQueryBuilder()->field('series')->equals($series->getId())->getQuery()->execute();
            foreach($allMmobjs as $resource) {
                if(!$resource->containsPersonWithRole($person, $role) ||
                   count($resource->getPeopleByRole($role, true)) > 1) {
                    return false;
                }
            }
        }
        return true;
    }
}
